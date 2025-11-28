@extends('layouts.app')

@section('title', 'Join ' . $meeting->title . ' - Digital Leap Africa')
@section('meta_description', 'Test your camera and microphone before joining the meeting')

@push('styles')
<style>
        .lobby { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .lobby-header { text-align: center; margin-bottom: 2rem; }
        .lobby-header h1 { color: var(--diamond-white); margin-bottom: 0.5rem; }
        .lobby-header p { color: var(--cool-gray); }
        .preview { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; margin-bottom: 2rem; }
        .video-section { }
        .video-preview { background: #000; border-radius: var(--radius); position: relative; aspect-ratio: 16/9; border: 1px solid rgba(255,255,255,0.1); }
        .video-preview video { width: 100%; height: 100%; border-radius: var(--radius); }
        .video-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; text-align: center; }
        .controls { display: flex; gap: 1rem; margin-top: 1rem; justify-content: center; }
        .settings-panel { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: var(--radius); padding: 1.5rem; }
        .settings-section { margin-bottom: 1.5rem; }
        .settings-section:last-child { margin-bottom: 0; }
        .settings-title { color: var(--cyan-accent); font-weight: 600; margin-bottom: 1rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .network-info { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid; }
        .network-good { background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.3); color: #10b981; }
        .network-poor { background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); color: #ef4444; }
        .network-details { font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.8; }
        .setting-item { margin-bottom: 1rem; }
        .toggle { display: flex; align-items: center; gap: 0.75rem; }
        .switch { position: relative; width: 44px; height: 24px; background: rgba(255,255,255,0.2); border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .switch.active { background: var(--cyan-accent); }
        .switch::after { content: ''; position: absolute; width: 20px; height: 20px; background: white; border-radius: 50%; top: 2px; left: 2px; transition: 0.3s; }
        .switch.active::after { left: 22px; }
        .setting-label { color: var(--diamond-white); font-weight: 500; }
        .setting-description { font-size: 0.8rem; color: var(--cool-gray); margin-top: 0.25rem; }
        .device-status { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; font-size: 0.85rem; }
        .status-indicator { width: 8px; height: 8px; border-radius: 50%; }
        .status-good { background: #10b981; }
        .status-error { background: #ef4444; }
</style>
@endpush

@section('content')
<div class="lobby">
        <div class="lobby-header">
            <h1>Ready to join "{{ $meeting->title }}"?</h1>
            <p>Test your camera and microphone before joining</p>
        </div>
        
        <div class="preview">
            <div class="video-section">
                <div class="video-preview">
                    <video id="localVideo" autoplay muted style="display: none;"></video>
                    <div class="video-overlay" id="videoOverlay">
                        <i class="fas fa-video-slash" style="font-size: 3rem; opacity: 0.5; margin-bottom: 1rem;"></i>
                        <p>Camera is off</p>
                    </div>
                </div>
                <div class="controls">
                    <button class="btn btn-outline" id="toggleVideo">
                        <i class="fas fa-video"></i> Camera
                    </button>
                    <button class="btn btn-outline" id="toggleAudio">
                        <i class="fas fa-microphone"></i> Microphone
                    </button>
                </div>
                <div class="device-status">
                    <div class="status-indicator status-error" id="cameraStatus"></div>
                    <span id="cameraStatusText">Camera: Not detected</span>
                </div>
                <div class="device-status">
                    <div class="status-indicator status-error" id="micStatus"></div>
                    <span id="micStatusText">Microphone: Not detected</span>
                </div>
            </div>
            
            <div class="settings-panel">
                <div class="settings-section">
                    <div class="settings-title">Network Information</div>
                    <div id="networkInfo" class="network-info network-good">
                        <div><i class="fas fa-circle" style="color: #10b981;"></i> Network: Good (Loading...)</div>
                        <div class="network-details">
                            <div id="networkSpeed">Speed: Checking...</div>
                            <div id="connectionType">Connection: Checking...</div>
                            <div id="ispInfo">ISP: Detecting...</div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <div class="settings-title">Video Settings</div>
                    <div class="setting-item">
                        <div class="toggle">
                            <div class="switch" id="dataSaverToggle"></div>
                            <div>
                                <div class="setting-label">Data Saver Mode</div>
                                <div class="setting-description">Reduces video quality to save bandwidth</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="toggle">
                            <div class="switch" id="audioOnlyToggle"></div>
                            <div>
                                <div class="setting-label">Audio Only Mode</div>
                                <div class="setting-description">Join without video</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <button class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;" onclick="joinMeeting()">
            <i class="fas fa-sign-in-alt"></i> Join Meeting
        </button>
    </div>

@push('scripts')
<script>
        let localStream = null;
        let videoEnabled = true;
        let audioEnabled = true;
        let dataSaverMode = false;
        let audioOnlyMode = false;
        let networkQuality = 'good';

        async function init() {
            // Request permissions first
            try {
                await navigator.mediaDevices.getUserMedia({ audio: true, video: true });
            } catch (e) {
                console.log('Initial permission request:', e.message);
            }
            
            await checkNetworkQuality();
            await setupMedia();
            setupToggles();
        }

        async function checkNetworkQuality() {
            const start = Date.now();
            try {
                await fetch('/ping?' + Date.now());
                const ping = Date.now() - start;
                const networkInfo = document.getElementById('networkInfo');
                
                if (ping > 300) {
                    networkQuality = 'poor';
                    networkInfo.className = 'network-info network-poor';
                    networkInfo.querySelector('div').innerHTML = '<i class="fas fa-circle" style="color: #ef4444;"></i> Network: Poor (' + ping + 'ms)';
                    document.getElementById('audioOnlyToggle').click();
                } else {
                    networkInfo.querySelector('div').innerHTML = '<i class="fas fa-circle" style="color: #10b981;"></i> Network: Good (' + ping + 'ms)';
                }
                
                // Get additional network info
                await getNetworkDetails();
            } catch (e) {
                console.warn('Network check failed');
            }
        }
        
        async function getNetworkDetails() {
            try {
                // Get connection info
                const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
                if (connection) {
                    const speed = connection.downlink ? Math.round(connection.downlink * 10) / 10 + ' Mbps' : 'Unknown';
                    const type = connection.effectiveType || connection.type || 'Unknown';
                    document.getElementById('networkSpeed').textContent = 'Speed: ' + speed;
                    document.getElementById('connectionType').textContent = 'Type: ' + type.toUpperCase();
                } else {
                    // Fallback speed test
                    const speedTest = await performSpeedTest();
                    document.getElementById('networkSpeed').textContent = 'Speed: ~' + speedTest + ' Mbps';
                    document.getElementById('connectionType').textContent = 'Type: Unknown';
                }
                
                // Get ISP info (using a free API)
                try {
                    const response = await fetch('https://ipapi.co/json/');
                    const data = await response.json();
                    document.getElementById('ispInfo').textContent = 'ISP: ' + (data.org || data.isp || 'Unknown Provider');
                } catch (e) {
                    // Fallback to another API
                    try {
                        const response2 = await fetch('https://api.ipify.org?format=json');
                        const data2 = await response2.json();
                        document.getElementById('ispInfo').textContent = 'IP: ' + data2.ip;
                    } catch (e2) {
                        document.getElementById('ispInfo').textContent = 'ISP: Unable to detect';
                    }
                }
            } catch (e) {
                console.warn('Network details failed:', e);
            }
        }
        
        async function performSpeedTest() {
            try {
                const startTime = Date.now();
                const response = await fetch('/ping?' + Date.now() + '&size=1000'); // Larger payload
                const endTime = Date.now();
                const duration = endTime - startTime;
                
                // Rough speed calculation (1KB in duration ms)
                const speedMbps = (8 / duration) * 1000; // Convert to Mbps
                return Math.max(0.1, Math.round(speedMbps * 10) / 10);
            } catch (e) {
                return 'Unknown';
            }
        }

        async function setupMedia() {
            try {
                // Check device availability first
                const devices = await navigator.mediaDevices.enumerateDevices();
                const hasCamera = devices.some(device => device.kind === 'videoinput');
                const hasMicrophone = devices.some(device => device.kind === 'audioinput');
                
                if (!hasCamera && !hasMicrophone) {
                    updateDeviceStatus(new Error('No camera or microphone found'));
                    return;
                }
                
                const constraints = getMediaConstraints();
                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                const video = document.getElementById('localVideo');
                const overlay = document.getElementById('videoOverlay');
                
                video.srcObject = localStream;
                
                // Update device status
                updateDeviceStatus();
                
                // Show/hide video based on constraints
                if (constraints.video && !audioOnlyMode) {
                    video.style.display = 'block';
                    overlay.style.display = 'none';
                } else {
                    video.style.display = 'none';
                    overlay.style.display = 'flex';
                }
            } catch (e) {
                console.error('Media access failed:', e);
                updateDeviceStatus(e);
                // Show permission prompt if needed
                if (e.name === 'NotAllowedError') {
                    alert('Please allow camera and microphone access to join the meeting. Click the camera icon in your browser address bar.');
                }
            }
        }
        
        function updateDeviceStatus(error = null) {
            const cameraStatus = document.getElementById('cameraStatus');
            const cameraText = document.getElementById('cameraStatusText');
            const micStatus = document.getElementById('micStatus');
            const micText = document.getElementById('micStatusText');
            
            if (error) {
                cameraStatus.className = 'status-indicator status-error';
                cameraText.textContent = 'Camera: Access denied or not found';
                micStatus.className = 'status-indicator status-error';
                micText.textContent = 'Microphone: Access denied or not found';
                return;
            }
            
            if (localStream) {
                const videoTracks = localStream.getVideoTracks();
                const audioTracks = localStream.getAudioTracks();
                
                if (videoTracks.length > 0) {
                    cameraStatus.className = 'status-indicator status-good';
                    cameraText.textContent = 'Camera: ' + videoTracks[0].label || 'Camera: Ready';
                } else {
                    cameraStatus.className = 'status-indicator status-error';
                    cameraText.textContent = 'Camera: Not available';
                }
                
                if (audioTracks.length > 0) {
                    micStatus.className = 'status-indicator status-good';
                    micText.textContent = 'Microphone: ' + audioTracks[0].label || 'Microphone: Ready';
                } else {
                    micStatus.className = 'status-indicator status-error';
                    micText.textContent = 'Microphone: Not available';
                }
            }
        }

        function getMediaConstraints() {
            if (audioOnlyMode) {
                return { audio: true, video: false };
            }
            
            const videoConstraints = dataSaverMode ? 
                { width: 320, height: 240, frameRate: 15 } : 
                { width: 1280, height: 720, frameRate: 30 };
                
            return { audio: true, video: videoConstraints };
        }

        function setupToggles() {
            document.getElementById('dataSaverToggle').onclick = toggleDataSaver;
            document.getElementById('audioOnlyToggle').onclick = toggleAudioOnly;
            document.getElementById('toggleVideo').onclick = toggleVideo;
            document.getElementById('toggleAudio').onclick = toggleAudio;
        }

        function toggleDataSaver() {
            dataSaverMode = !dataSaverMode;
            document.getElementById('dataSaverToggle').classList.toggle('active');
            if (localStream && !audioOnlyMode) {
                setupMedia();
            }
        }

        function toggleAudioOnly() {
            audioOnlyMode = !audioOnlyMode;
            document.getElementById('audioOnlyToggle').classList.toggle('active');
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            setupMedia();
        }

        function toggleVideo() {
            console.log('Toggle video clicked');
            if (localStream) {
                const videoTrack = localStream.getVideoTracks()[0];
                const video = document.getElementById('localVideo');
                const overlay = document.getElementById('videoOverlay');
                const btn = document.getElementById('toggleVideo');
                
                if (videoTrack) {
                    videoEnabled = !videoEnabled;
                    videoTrack.enabled = videoEnabled;
                    
                    if (videoEnabled && !audioOnlyMode) {
                        video.style.display = 'block';
                        overlay.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-video"></i> Camera';
                    } else {
                        video.style.display = 'none';
                        overlay.style.display = 'flex';
                        btn.innerHTML = '<i class="fas fa-video-slash"></i> Camera Off';
                    }
                } else {
                    console.log('No video track available');
                }
            } else {
                console.log('No local stream available');
            }
        }

        function toggleAudio() {
            console.log('Toggle audio clicked');
            if (localStream) {
                const audioTrack = localStream.getAudioTracks()[0];
                const btn = document.getElementById('toggleAudio');
                
                if (audioTrack) {
                    audioEnabled = !audioEnabled;
                    audioTrack.enabled = audioEnabled;
                    
                    if (audioEnabled) {
                        btn.innerHTML = '<i class="fas fa-microphone"></i> Microphone';
                    } else {
                        btn.innerHTML = '<i class="fas fa-microphone-slash"></i> Muted';
                    }
                } else {
                    console.log('No audio track available');
                }
            } else {
                console.log('No local stream available');
            }
        }

        function joinMeeting() {
            const settings = {
                dataSaverMode,
                audioOnlyMode,
                videoEnabled,
                audioEnabled,
                networkQuality
            };
            
            localStorage.setItem('meetingSettings', JSON.stringify(settings));
            window.location.href = '{{ route("meetings.join", $meeting) }}';
        }

        init();
</script>
@endpush
@endsection