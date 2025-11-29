# Start SFU Server for 150+ Attendees

## Quick Start

### Terminal 1: Laravel
```bash
php artisan serve
```

### Terminal 2: Reverb WebSocket
```bash
php artisan reverb:start
```

### Terminal 3: Mediasoup SFU (NEW - REQUIRED)
```bash
node mediasoup-server.js
```

### Terminal 4: Vite (Optional)
```bash
npm run dev
```

## What Changed?

**Before (Mesh):**
- Each user connects to every other user
- Max 3-5 participants
- Bandwidth: User sends N streams

**Now (SFU):**
- Each user sends 1 stream to server
- Server forwards to all others
- Supports 150+ participants
- Bandwidth: User sends 1 stream, receives N streams

## Architecture

```
┌─────────────┐
│  Browser 1  │──┐
└─────────────┘  │
                 │
┌─────────────┐  │    ┌──────────────────┐
│  Browser 2  │──┼───▶│  Mediasoup SFU   │
└─────────────┘  │    │  (Port 3000)     │
                 │    └──────────────────┘
┌─────────────┐  │              │
│  Browser 3  │──┘              │
└─────────────┘                 │
                                │
      ┌─────────────────────────┘
      │
┌─────▼──────┐
│  Laravel   │
│  + Reverb  │
└────────────┘
```

## Port Configuration

- **8000** - Laravel (HTTP)
- **8080** - Reverb (WebSocket)
- **3000** - Mediasoup SFU
- **10000-10100** - Mediasoup RTC (UDP)

## Firewall Rules (Production)

```bash
# Allow SFU ports
sudo ufw allow 3000/tcp
sudo ufw allow 10000:10100/udp
```

## Production Deployment

### Update mediasoup-server.js

Change:
```javascript
announcedIp: '127.0.0.1'
```

To:
```javascript
announcedIp: 'YOUR_SERVER_PUBLIC_IP'
```

### Update sfu-client.js

Change:
```javascript
new SFUClient('http://localhost:3000')
```

To:
```javascript
new SFUClient('https://your-domain.com:3000')
```

## Performance Tuning

### For 150 attendees:

**Server Requirements:**
- CPU: 8+ cores
- RAM: 16GB+
- Bandwidth: 500 Mbps+

**mediasoup-server.js settings:**
```javascript
// Increase worker count
const numWorkers = os.cpus().length;
const workers = [];

for (let i = 0; i < numWorkers; i++) {
    workers.push(await createWorker());
}

// Load balance across workers
let workerIdx = 0;
function getNextWorker() {
    const worker = workers[workerIdx];
    workerIdx = (workerIdx + 1) % workers.length;
    return worker;
}
```

## Monitoring

Check SFU status:
```bash
curl http://localhost:3000/health
```

Monitor connections:
```javascript
// Add to mediasoup-server.js
app.get('/stats', (req, res) => {
    res.json({
        transports: transports.size,
        producers: producers.size,
        consumers: consumers.size
    });
});
```

## Troubleshooting

**SFU not connecting?**
- Check port 3000 is open
- Verify mediasoup-server.js is running
- Check browser console for errors

**Poor quality with many users?**
- Enable simulcast (see below)
- Increase server resources
- Use CDN for static assets

## Enable Simulcast (Recommended)

Update sfu-client.js:
```javascript
async produce(track) {
    const encodings = track.kind === 'video' ? [
        { maxBitrate: 100000, scaleResolutionDownBy: 4 },
        { maxBitrate: 300000, scaleResolutionDownBy: 2 },
        { maxBitrate: 900000, scaleResolutionDownBy: 1 }
    ] : undefined;

    const producer = await this.producerTransport.produce({ 
        track,
        encodings
    });
    
    return producer;
}
```

## Cost Estimate (AWS)

**For 150 concurrent users:**
- EC2 c5.2xlarge: $0.34/hour
- Bandwidth: ~150 Mbps = $13/TB
- Monthly: ~$250-500

## Alternative: Managed Services

If self-hosting is complex:
- **Daily.co**: $0.002/min/participant = $18/hour for 150 users
- **Agora**: $0.99/1000 minutes
- **Twilio**: $0.004/min/participant = $36/hour for 150 users
