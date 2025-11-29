# SFU Integration Guide

## Why SFU?

**Current Setup (Mesh):**
- Each participant connects to every other participant
- Works well for 2-3 participants
- Bandwidth usage: O(n²) - exponential growth

**With SFU:**
- Each participant sends once to server
- Server forwards to all others
- Bandwidth usage: O(n) - linear growth
- Supports 100+ participants

## Option 1: Mediasoup (Recommended)

### Installation

```bash
npm install mediasoup mediasoup-client
```

### Create Mediasoup Server

**File: `mediasoup-server.js`**
```javascript
const mediasoup = require('mediasoup');
const express = require('express');
const http = require('http');
const socketIO = require('socket.io');

const app = express();
const server = http.createServer(app);
const io = socketIO(server);

let worker, router;

async function createWorker() {
    worker = await mediasoup.createWorker({
        logLevel: 'warn',
        rtcMinPort: 10000,
        rtcMaxPort: 10100,
    });

    worker.on('died', () => {
        console.error('mediasoup worker died, exiting...');
        process.exit(1);
    });

    return worker;
}

async function createRouter() {
    const mediaCodecs = [
        {
            kind: 'audio',
            mimeType: 'audio/opus',
            clockRate: 48000,
            channels: 2,
        },
        {
            kind: 'video',
            mimeType: 'video/VP8',
            clockRate: 90000,
        },
    ];

    router = await worker.createRouter({ mediaCodecs });
    return router;
}

io.on('connection', async (socket) => {
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

        callback({
            id: transport.id,
            iceParameters: transport.iceParameters,
            iceCandidates: transport.iceCandidates,
            dtlsParameters: transport.dtlsParameters,
        });
    });

    // Add more handlers for produce, consume, etc.
});

(async () => {
    await createWorker();
    await createRouter();
    server.listen(3000, () => {
        console.log('Mediasoup server running on port 3000');
    });
})();
```

### Update Laravel to Use Mediasoup

**File: `resources/js/mediasoup-client.js`**
```javascript
import * as mediasoupClient from 'mediasoup-client';

class MediasoupManager {
    constructor() {
        this.device = null;
        this.producerTransport = null;
        this.consumerTransport = null;
        this.producers = new Map();
        this.consumers = new Map();
    }

    async init(socket) {
        this.socket = socket;
        
        // Get router capabilities
        const routerRtpCapabilities = await new Promise((resolve) => {
            socket.emit('getRouterRtpCapabilities', resolve);
        });

        // Create device
        this.device = new mediasoupClient.Device();
        await this.device.load({ routerRtpCapabilities });
    }

    async createProducerTransport() {
        const transportInfo = await new Promise((resolve) => {
            this.socket.emit('createProducerTransport', resolve);
        });

        this.producerTransport = this.device.createSendTransport(transportInfo);

        this.producerTransport.on('connect', async ({ dtlsParameters }, callback) => {
            this.socket.emit('connectProducerTransport', { dtlsParameters });
            callback();
        });

        this.producerTransport.on('produce', async ({ kind, rtpParameters }, callback) => {
            const { id } = await new Promise((resolve) => {
                this.socket.emit('produce', { kind, rtpParameters }, resolve);
            });
            callback({ id });
        });
    }

    async produce(track) {
        const producer = await this.producerTransport.produce({ track });
        this.producers.set(producer.id, producer);
        return producer;
    }
}

export default MediasoupManager;
```

### Run Mediasoup Server

```bash
node mediasoup-server.js
```

## Option 2: Jitsi Videobridge

### Docker Setup

```bash
docker run -d \
  --name jitsi-jvb \
  -p 10000:10000/udp \
  -p 8080:8080 \
  -e JVB_AUTH_USER=focus \
  -e JVB_AUTH_PASSWORD=yourpassword \
  jitsi/jvb
```

### Integration

```javascript
// Use Jitsi Meet API
const domain = 'meet.jit.si';
const options = {
    roomName: 'YourMeetingRoom',
    width: '100%',
    height: '100%',
    parentNode: document.querySelector('#meet')
};

const api = new JitsiMeetExternalAPI(domain, options);
```

## Option 3: Janus Gateway

### Installation

```bash
# Ubuntu/Debian
sudo apt-get install libmicrohttpd-dev libjansson-dev \
    libssl-dev libsrtp-dev libsofia-sip-ua-dev libglib2.0-dev \
    libopus-dev libogg-dev libcurl4-openssl-dev liblua5.3-dev \
    libconfig-dev pkg-config gengetopt libtool automake

git clone https://github.com/meetecho/janus-gateway.git
cd janus-gateway
sh autogen.sh
./configure --prefix=/opt/janus
make
sudo make install
```

### Run Janus

```bash
/opt/janus/bin/janus
```

## Comparison

| Feature | Mediasoup | Jitsi | Janus |
|---------|-----------|-------|-------|
| Language | Node.js | Java | C |
| Performance | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| Ease of Setup | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐ |
| Scalability | 100+ | 100+ | 200+ |
| Recording | Custom | Built-in | Custom |
| Documentation | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |

## Recommendation

**For your Laravel project:**
1. **Start with Mediasoup** - Best balance of performance and ease
2. **Use Jitsi** if you need quick setup with built-in features
3. **Use Janus** only if you need maximum performance and have C expertise

## Integration Steps

1. **Install SFU** (choose one above)
2. **Update SignalingController** to route through SFU
3. **Modify room.blade.php** to use SFU client library
4. **Test with 3+ participants**
5. **Monitor performance** and adjust

## Performance Tips

- Use VP8 codec (better CPU usage than VP9)
- Enable simulcast for adaptive streaming
- Set bandwidth limits per participant
- Use TURN server for NAT traversal
- Monitor CPU and bandwidth usage

## Cost Considerations

**Self-hosted:**
- Server: $20-100/month (depending on participants)
- Bandwidth: ~1-2 Mbps per participant
- TURN server: $10-20/month

**Managed Services:**
- Twilio Video: $0.004/min/participant
- Agora: $0.99/1000 minutes
- Daily.co: $0.002/min/participant
