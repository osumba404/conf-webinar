# Quick Setup Guide

## Features Implemented ✅

1. **WebSockets (Laravel Reverb)** - Real-time signaling
2. **Redis** - Fast caching and message queuing
3. **Recording** - Client-side meeting recording
4. **Screen Sharing** - Share your screen with participants
5. **Chat** - Real-time messaging
6. **Bandwidth Adaptation** - Auto-adjust quality based on network
7. **Reconnection Handling** - Auto-reconnect on connection loss

## Start the Application

### 1. Start Laravel Server
```bash
cd meet
php artisan serve
```
Access at: http://127.0.0.1:8000

### 2. Start Reverb WebSocket Server (New Terminal)
```bash
php artisan reverb:start
```
WebSocket running on: ws://127.0.0.1:8080

### 3. Start Vite Dev Server (Optional - for development)
```bash
npm run dev
```

## Test the Features

### Recording
1. Join a meeting
2. Click the record button (🔴)
3. Recording starts automatically
4. Click again to stop
5. File saved to `storage/app/recordings/{meeting-slug}/`

### Screen Sharing
1. Click the screen share button (🖥️)
2. Select window/screen to share
3. All participants see your screen
4. Click again to stop sharing

### Chat
1. Click the chat icon to open sidebar
2. Type message and press Enter
3. Messages broadcast to all participants in real-time

### Bandwidth Adaptation
- Automatic - monitors network every 10 seconds
- Adjusts video quality based on latency
- No user action needed

### Reconnection
- Automatic on connection loss
- Up to 5 retry attempts
- Exponential backoff

## Architecture

```
┌─────────────────┐
│   Browser 1     │
│   (WebRTC)      │
└────────┬────────┘
         │
         ├──WebSocket──┐
         │             │
┌────────▼────────┐   │   ┌─────────────────┐
│   Laravel       │   │   │   Browser 2     │
│   + Reverb      │◄──┴───│   (WebRTC)      │
│   (Signaling)   │       └─────────────────┘
└────────┬────────┘
         │
    ┌────▼────┐
    │  Redis  │
    │ (Cache) │
    └─────────┘
```

## Next Steps for Production

### 1. Add TURN Server (Required for NAT traversal)
```bash
# Install Coturn
sudo apt-get install coturn

# Configure in .env
TURN_SERVER=turn:your-server.com:3478
TURN_USERNAME=username
TURN_PASSWORD=password
```

### 2. Add SFU for Scalability (3+ participants)
**Option A: Mediasoup (Recommended)**
```bash
npm install mediasoup mediasoup-client
```

**Option B: Jitsi Videobridge**
```bash
docker run -d -p 8080:8080 jitsi/jvb
```

### 3. Use HTTPS (Required for production)
- getUserMedia requires secure context
- Configure SSL certificate
- Update REVERB_SCHEME=https

### 4. Cloud Storage for Recordings
```env
FILESYSTEM_DISK=s3
AWS_BUCKET=your-bucket
```

## Troubleshooting

### WebSocket not connecting?
- Check Reverb is running: `php artisan reverb:start`
- Verify port 8080 is not blocked
- Check browser console for errors

### Recording not working?
- Ensure HTTPS in production
- Check browser permissions
- Verify storage directory is writable

### Poor video quality?
- Bandwidth adaptation will auto-adjust
- Check network latency
- Consider using SFU for multiple participants

## File Structure

```
app/
├── Events/
│   └── MeetingSignal.php          # WebSocket event
├── Http/Controllers/
│   ├── SignalingController.php    # WebRTC signaling
│   └── RecordingController.php    # Recording management
resources/
├── js/
│   ├── bootstrap.js               # Echo initialization
│   └── meeting.js                 # Recording, bandwidth, reconnection
└── views/meetings/
    └── room.blade.php             # Meeting room UI
```

## Support

For issues or questions:
1. Check FEATURES.md for detailed documentation
2. Review browser console for errors
3. Check Laravel logs: `storage/logs/laravel.log`
4. Check Reverb logs in terminal
