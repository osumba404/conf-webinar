const mediasoup = require('mediasoup');
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = socketIO(server, {
    cors: { origin: '*' }
});

let worker, router;
const transports = new Map();
const producers = new Map();
const consumers = new Map();

async function createWorker() {
    worker = await mediasoup.createWorker({
        logLevel: 'warn',
        rtcMinPort: 10000,
        rtcMaxPort: 10100,
    });
    return worker;
}

async function createRouter() {
    router = await worker.createRouter({
        mediaCodecs: [
            { kind: 'audio', mimeType: 'audio/opus', clockRate: 48000, channels: 2 },
            { kind: 'video', mimeType: 'video/VP8', clockRate: 90000 }
        ]
    });
    return router;
}

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    socket.on('getRouterRtpCapabilities', (callback) => {
        callback(router.rtpCapabilities);
    });

    socket.on('createProducerTransport', async (callback) => {
        const transport = await router.createWebRtcTransport({
            listenIps: [{ ip: '0.0.0.0', announcedIp: '127.0.0.1' }],
            enableUdp: true,
            enableTcp: true,
            preferUdp: true,
        });

        transports.set(transport.id, transport);

        callback({
            id: transport.id,
            iceParameters: transport.iceParameters,
            iceCandidates: transport.iceCandidates,
            dtlsParameters: transport.dtlsParameters,
        });
    });

    socket.on('createConsumerTransport', async (callback) => {
        const transport = await router.createWebRtcTransport({
            listenIps: [{ ip: '0.0.0.0', announcedIp: '127.0.0.1' }],
            enableUdp: true,
            enableTcp: true,
            preferUdp: true,
        });

        transports.set(transport.id, transport);

        callback({
            id: transport.id,
            iceParameters: transport.iceParameters,
            iceCandidates: transport.iceCandidates,
            dtlsParameters: transport.dtlsParameters,
        });
    });

    socket.on('connectProducerTransport', async ({ transportId, dtlsParameters }) => {
        const transport = transports.get(transportId);
        await transport.connect({ dtlsParameters });
    });

    socket.on('connectConsumerTransport', async ({ transportId, dtlsParameters }) => {
        const transport = transports.get(transportId);
        await transport.connect({ dtlsParameters });
    });

    socket.on('produce', async ({ transportId, kind, rtpParameters }, callback) => {
        const transport = transports.get(transportId);
        const producer = await transport.produce({ kind, rtpParameters });
        
        producers.set(producer.id, producer);
        
        socket.broadcast.emit('newProducer', { producerId: producer.id, socketId: socket.id });
        
        callback({ id: producer.id });
    });

    socket.on('consume', async ({ transportId, producerId, rtpCapabilities }, callback) => {
        const transport = transports.get(transportId);
        
        if (!router.canConsume({ producerId, rtpCapabilities })) {
            return callback({ error: 'Cannot consume' });
        }

        const consumer = await transport.consume({
            producerId,
            rtpCapabilities,
            paused: true,
        });

        consumers.set(consumer.id, consumer);

        callback({
            id: consumer.id,
            producerId,
            kind: consumer.kind,
            rtpParameters: consumer.rtpParameters,
        });
    });

    socket.on('resumeConsumer', async ({ consumerId }) => {
        const consumer = consumers.get(consumerId);
        await consumer.resume();
    });

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });
});

(async () => {
    await createWorker();
    await createRouter();
    server.listen(3000, () => {
        console.log('Mediasoup SFU running on port 3000');
    });
})();
