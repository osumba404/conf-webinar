import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// WebSocket Setup
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Recording functionality
class MeetingRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.recordedChunks = [];
        this.recordingId = null;
        this.isRecording = false;
    }

    async start(stream, meetingSlug) {
        this.recordedChunks = [];
        this.isRecording = true;
        
        const response = await fetch(`/meetings/${meetingSlug}/recording/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        this.recordingId = data.recording_id;
        
        this.mediaRecorder = new MediaRecorder(stream, {
            mimeType: 'video/webm;codecs=vp9'
        });
        
        this.mediaRecorder.ondataavailable = async (event) => {
            if (event.data.size > 0) {
                this.recordedChunks.push(event.data);
                
                // Upload chunk
                const reader = new FileReader();
                reader.onloadend = async () => {
                    const base64 = reader.result.split(',')[1];
                    await fetch(`/meetings/${meetingSlug}/recording/upload`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            recording_id: this.recordingId,
                            chunk: base64
                        })
                    });
                };
                reader.readAsDataURL(event.data);
            }
        };
        
        this.mediaRecorder.start(5000); // Record in 5s chunks
    }

    async stop(meetingSlug) {
        if (this.mediaRecorder && this.isRecording) {
            this.mediaRecorder.stop();
            this.isRecording = false;
            
            const response = await fetch(`/meetings/${meetingSlug}/recording/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ recording_id: this.recordingId })
            });
            
            return await response.json();
        }
    }
}

// Bandwidth adaptation
class BandwidthManager {
    constructor() {
        this.currentQuality = 'high';
    }

    async measureBandwidth() {
        const startTime = Date.now();
        try {
            await fetch('/ping');
            const latency = Date.now() - startTime;
            
            if (latency > 300) return 'low';
            if (latency > 150) return 'medium';
            return 'high';
        } catch {
            return 'low';
        }
    }

    getConstraints(quality) {
        const constraints = {
            high: { width: 1280, height: 720, frameRate: 30 },
            medium: { width: 640, height: 480, frameRate: 24 },
            low: { width: 320, height: 240, frameRate: 15 }
        };
        return constraints[quality];
    }

    async adaptQuality(peerConnection, localStream) {
        const quality = await this.measureBandwidth();
        
        if (quality !== this.currentQuality) {
            this.currentQuality = quality;
            const constraints = this.getConstraints(quality);
            
            const videoTrack = localStream.getVideoTracks()[0];
            if (videoTrack) {
                await videoTrack.applyConstraints(constraints);
            }
        }
    }
}

// Reconnection handler
class ReconnectionManager {
    constructor(onReconnect) {
        this.onReconnect = onReconnect;
        this.reconnectAttempts = 0;
        this.maxAttempts = 5;
    }

    async attemptReconnect() {
        if (this.reconnectAttempts < this.maxAttempts) {
            this.reconnectAttempts++;
            console.log(`Reconnection attempt ${this.reconnectAttempts}/${this.maxAttempts}`);
            
            await new Promise(resolve => setTimeout(resolve, 2000 * this.reconnectAttempts));
            
            try {
                await this.onReconnect();
                this.reconnectAttempts = 0;
                return true;
            } catch (error) {
                return this.attemptReconnect();
            }
        }
        return false;
    }

    reset() {
        this.reconnectAttempts = 0;
    }
}

export { MeetingRecorder, BandwidthManager, ReconnectionManager };
