import * as mediasoupClient from 'mediasoup-client';
import io from 'socket.io-client';

class SFUClient {
    constructor(serverUrl = 'http://localhost:3000') {
        this.socket = io(serverUrl);
        this.device = null;
        this.producerTransport = null;
        this.consumerTransport = null;
        this.producers = new Map();
        this.consumers = new Map();
    }

    async init() {
        const routerRtpCapabilities = await new Promise((resolve) => {
            this.socket.emit('getRouterRtpCapabilities', resolve);
        });

        this.device = new mediasoupClient.Device();
        await this.device.load({ routerRtpCapabilities });

        await this.createTransports();
        this.setupSocketListeners();
    }

    async createTransports() {
        const producerTransportInfo = await new Promise((resolve) => {
            this.socket.emit('createProducerTransport', resolve);
        });

        this.producerTransport = this.device.createSendTransport(producerTransportInfo);

        this.producerTransport.on('connect', async ({ dtlsParameters }, callback) => {
            this.socket.emit('connectProducerTransport', {
                transportId: this.producerTransport.id,
                dtlsParameters
            });
            callback();
        });

        this.producerTransport.on('produce', async ({ kind, rtpParameters }, callback) => {
            const { id } = await new Promise((resolve) => {
                this.socket.emit('produce', {
                    transportId: this.producerTransport.id,
                    kind,
                    rtpParameters
                }, resolve);
            });
            callback({ id });
        });

        const consumerTransportInfo = await new Promise((resolve) => {
            this.socket.emit('createConsumerTransport', resolve);
        });

        this.consumerTransport = this.device.createRecvTransport(consumerTransportInfo);

        this.consumerTransport.on('connect', async ({ dtlsParameters }, callback) => {
            this.socket.emit('connectConsumerTransport', {
                transportId: this.consumerTransport.id,
                dtlsParameters
            });
            callback();
        });
    }

    setupSocketListeners() {
        this.socket.on('newProducer', async ({ producerId, socketId }) => {
            await this.consume(producerId, socketId);
        });
    }

    async produce(track) {
        const producer = await this.producerTransport.produce({ track });
        this.producers.set(producer.id, producer);
        return producer;
    }

    async consume(producerId, socketId) {
        const consumerInfo = await new Promise((resolve) => {
            this.socket.emit('consume', {
                transportId: this.consumerTransport.id,
                producerId,
                rtpCapabilities: this.device.rtpCapabilities
            }, resolve);
        });

        if (consumerInfo.error) return;

        const consumer = await this.consumerTransport.consume(consumerInfo);
        this.consumers.set(consumer.id, { consumer, socketId });

        this.socket.emit('resumeConsumer', { consumerId: consumer.id });

        return consumer;
    }

    async produceMedia(stream) {
        const audioTrack = stream.getAudioTracks()[0];
        const videoTrack = stream.getVideoTracks()[0];

        if (audioTrack) {
            await this.produce(audioTrack);
        }

        if (videoTrack) {
            await this.produce(videoTrack);
        }
    }
}

export default SFUClient;
