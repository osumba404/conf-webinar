@extends('layouts.app')

@section('title', $meeting->title . ' - Digital Leap Africa')
@section('meta_description', 'Video conference meeting room')

@push('styles')
<style>
        .meeting-container { display: grid; grid-template-columns: 1fr 0px; height: 100vh; background: var(--navy-bg); transition: grid-template-columns 0.3s ease; }
        .meeting-container.sidebar-open { grid-template-columns: 1fr 320px; }
        .main-area { display: flex; flex-direction: column; }
        .video-grid { flex: 1; display: flex; align-items: center; justify-content: center; gap: 20px; padding: 2rem; background: linear-gradient(135deg, var(--navy-bg) 0%, #0a0f1a 100%); flex-wrap: wrap; }
        .video-grid.has-video { display: grid; gap: 12px; }
        .video-grid.grid-1 { grid-template-columns: 1fr; }
        .video-grid.grid-2 { grid-template-columns: 1fr 1fr; }
        .video-grid.grid-3 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .video-grid.grid-4 { grid-template-columns: 1fr 1fr; grid-template-rows: 1fr 1fr; }
        .video-grid.grid-many { grid-template-columns: repeat(3, 1fr); }
        
        .video-tile { background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius); position: relative; overflow: hidden; transition: all 0.3s ease; }
        .participant-avatar { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 120px; height: 120px; background: linear-gradient(135deg, var(--cyan-accent), #0ea5e9); border-radius: 50%; color: var(--navy-bg); font-size: 2.5rem; font-weight: bold; margin: 10px; }
        .participant-name { margin-top: 8px; color: var(--diamond-white); font-size: 0.9rem; text-align: center; }
        .video-tile:hover { border-color: var(--cyan-accent); box-shadow: 0 0 20px rgba(0, 201, 255, 0.2); }
        .video-tile video { width: 100%; height: 100%; object-fit: cover; border-radius: var(--radius); }
        .video-tile.speaking { border: 2px solid var(--cyan-accent); box-shadow: 0 0 25px rgba(0, 201, 255, 0.4); }
        .video-tile .user-info { position: absolute; bottom: 12px; left: 12px; background: rgba(0,0,0,0.8); padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; color: var(--diamond-white); backdrop-filter: blur(10px); }
        .video-tile .muted { position: absolute; top: 12px; right: 12px; background: rgba(239, 68, 68, 0.9); padding: 6px; border-radius: 50%; color: white; font-size: 0.8rem; }
        
        .controls-bar { display: flex; justify-content: center; gap: 1rem; padding: 1.5rem; background: linear-gradient(135deg, var(--charcoal) 0%, var(--navy-bg) 100%); border-top: 1px solid rgba(0, 201, 255, 0.2); }
        .control-btn { width: 56px; height: 56px; border: 2px solid rgba(255,255,255,0.2); border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: all 0.3s ease; background: rgba(255,255,255,0.05); }
        .control-btn:hover { transform: scale(1.1); border-color: var(--cyan-accent); }
        .control-btn.active { background: #ef4444; color: white; border-color: #ef4444; }
        .control-btn.inactive { background: rgba(255,255,255,0.05); color: var(--cool-gray); }
        .control-btn.primary { background: var(--cyan-accent); color: var(--navy-bg); border-color: var(--cyan-accent); }
        
        .sidebar { background: linear-gradient(180deg, var(--charcoal) 0%, var(--navy-bg) 100%); display: flex; flex-direction: column; border-left: 1px solid rgba(0, 201, 255, 0.2); transform: translateX(100%); transition: transform 0.3s ease; }
        .sidebar.open { transform: translateX(0); }
        .sidebar-toggle { position: fixed; top: 50%; right: 20px; z-index: 1000; background: var(--cyan-accent); color: var(--navy-bg); border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; transition: all 0.3s ease; }
        .sidebar-toggle:hover { background: #0ea5e9; }
        .sidebar-tabs { display: flex; border-bottom: 1px solid rgba(0, 201, 255, 0.2); background: rgba(255,255,255,0.02); }
        .tab { flex: 1; padding: 1rem 0.5rem; text-align: center; cursor: pointer; background: none; border: none; color: var(--cool-gray); font-weight: 500; transition: all 0.3s ease; }
        .tab.active { color: var(--cyan-accent); background: rgba(0, 201, 255, 0.1); }
        .tab:hover { color: var(--diamond-white); }
        .sidebar-content { flex: 1; overflow-y: auto; }
        
        .chat-area { padding: 1.25rem; display: flex; flex-direction: column; height: 100%; }
        .chat-messages { flex: 1; overflow-y: auto; margin-bottom: 1rem; padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); }
        .chat-input-container { display: flex; gap: 8px; }
        .chat-input { flex: 1; padding: 0.75rem; border: 1px solid rgba(0, 201, 255, 0.3); border-radius: 8px; background: rgba(255,255,255,0.05); color: var(--diamond-white); font-family: inherit; }
        .chat-send-btn { padding: 0.75rem; background: var(--cyan-accent); color: var(--navy-bg); border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; }
        .chat-send-btn:hover { background: #0ea5e9; }
        .chat-input:focus { outline: none; border-color: var(--cyan-accent); box-shadow: 0 0 0 2px rgba(0, 201, 255, 0.2); }
        
        .notes-area { padding: 1.25rem; }
        .notes-editor { width: 100%; height: 300px; padding: 0.75rem; border: 1px solid rgba(0, 201, 255, 0.3); border-radius: 8px; background: rgba(255,255,255,0.05); color: var(--diamond-white); resize: none; font-family: inherit; }
        .notes-editor:focus { outline: none; border-color: var(--cyan-accent); box-shadow: 0 0 0 2px rgba(0, 201, 255, 0.2); }
        
        .participants-list { padding: 1.25rem; }
        .participants-list h4 { color: var(--diamond-white); margin-bottom: 1rem; font-size: 1rem; }
        .participant { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); transition: all 0.3s ease; }
        .participant:hover { background: rgba(0, 201, 255, 0.05); border-color: rgba(0, 201, 255, 0.2); }
        .participant.hand-raised { background: rgba(251, 191, 36, 0.1); border-color: rgba(251, 191, 36, 0.3); color: #fbbf24; }
        
        .hand-queue { margin-top: 1.5rem; }
        .hand-queue h4 { margin-bottom: 0.75rem; color: #fbbf24; font-size: 0.9rem; }
        
        .poll-modal { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: none; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(5px); }
        .poll-content { background: linear-gradient(135deg, var(--charcoal) 0%, var(--navy-bg) 100%); padding: 2rem; border-radius: var(--radius); max-width: 500px; width: 90%; border: 1px solid rgba(0, 201, 255, 0.2); }
        .poll-content h3 { color: var(--diamond-white); margin-bottom: 1rem; }
        .poll-option { margin: 0.75rem 0; padding: 0.75rem; background: rgba(255,255,255,0.05); border-radius: 8px; cursor: pointer; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.1); }
        .poll-option:hover { background: rgba(0, 201, 255, 0.1); border-color: var(--cyan-accent); }
</style>
@endpush

@section('content')
@endsection

@push('styles')
<style>
    body { overflow: hidden !important; }
    .container { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
    main { padding: 0 !important; }
    nav { display: none !important; }
    header { display: none !important; }
</style>
@endpush

<div class="meeting-container" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; margin: 0; width: 100vw; height: 100vh; z-index: 10;">
    <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
        <i class="fas fa-comments"></i>
    </button>
        <div class="main-area">
            <div id="videoGrid" class="video-grid">
                <div class="participant-avatar" id="localAvatar">
                    <i class="fas fa-user"></i>
                    <div class="participant-name">You</div>
                </div>
                <div class="video-tile" id="localTile" style="display: none;">
                    <video id="localVideo" autoplay muted></video>
                    <div class="user-info">You</div>
                </div>
            </div>
            
            <div class="controls-bar">
                <button class="control-btn inactive" id="muteBtn" title="Mute/Unmute">
                    <i class="fas fa-microphone"></i>
                </button>
                <button class="control-btn inactive" id="videoBtn" title="Camera On/Off">
                    <i class="fas fa-video"></i>
                </button>
                <button class="control-btn inactive" id="screenBtn" title="Share Screen">
                    <i class="fas fa-desktop"></i>
                </button>
                <button class="control-btn inactive" id="handBtn" title="Raise Hand">
                    <i class="fas fa-hand-paper"></i>
                </button>
                <button class="control-btn inactive" id="pollBtn" title="Create Poll">
                    <i class="fas fa-poll"></i>
                </button>
                <button class="control-btn active" id="leaveBtn" title="Leave Meeting">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
        </div>
        
        <div class="sidebar">
            <div class="sidebar-tabs">
                <button class="tab active" onclick="switchTab('chat')">Chat</button>
                {{-- <button class="tab" onclick="switchTab('notes')">Notes</button> --}}
                <button class="tab" onclick="switchTab('people')">People</button>
            </div>
            
            <div class="sidebar-content">
                <div id="chatTab" class="chat-area">
                    <div id="chatMessages" class="chat-messages"></div>
                    <div class="chat-input-container">
                        <input type="text" id="chatInput" class="chat-input" placeholder="Type a message...">
                        <button class="chat-send-btn" onclick="sendChatMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
                
                {{-- <div id="notesTab" class="notes-area" style="display: none;">
                    <h4>Shared Meeting Notes</h4>
                    <textarea id="notesEditor" class="notes-editor" placeholder="Collaborative notes..."></textarea>
                </div> --}}
                
                <div id="peopleTab" class="participants-list" style="display: none;">
                    <h4>Participants (<span id="participantCount">1</span>)</h4>
                    <div id="participantsList">
                        <!-- Participants will be loaded dynamically -->
                    </div>
                    
                    <div class="hand-queue">
                        <h4><i class="fas fa-hand-paper"></i> Hand Raised Queue</h4>
                        <div id="handQueue"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="pollModal" class="poll-modal">
        <div class="poll-content">
            <h3>Create Quick Poll</h3>
            <input type="text" id="pollQuestion" placeholder="Enter your question..." style="width: 100%; margin: 15px 0; padding: 10px; border-radius: 6px; border: 1px solid #4b5563; background: #1f2937; color: white;">
            <div id="pollOptions">
                <input type="text" placeholder="Option 1" class="poll-option-input" style="width: 100%; margin: 8px 0; padding: 10px; border-radius: 6px; border: 1px solid #4b5563; background: #1f2937; color: white;">
                <input type="text" placeholder="Option 2" class="poll-option-input" style="width: 100%; margin: 8px 0; padding: 10px; border-radius: 6px; border: 1px solid #4b5563; background: #1f2937; color: white;">
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <button onclick="closePoll()" style="margin-right: 10px; padding: 8px 16px; background: #6b7280; color: white; border: none; border-radius: 6px;">Cancel</button>
                <button onclick="createPoll()" style="padding: 8px 16px; background: #10b981; color: white; border: none; border-radius: 6px;">Create Poll</button>
            </div>
        </div>
    </div>

@push('scripts')
<script>
        // WebRTC and Meeting State
        let localStream = null;
        let peers = new Map();
        let meetingSettings = JSON.parse(localStorage.getItem('meetingSettings') || '{}');
        let isAudioMuted = !meetingSettings.audioEnabled;
        let isVideoMuted = !meetingSettings.videoEnabled;
        let isHandRaised = false;
        let handQueue = [];
        let activeSpeaker = null;
        let sessionId = null;
        
        const iceServers = [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
        ];

        // Initialize meeting
        async function initMeeting() {
            await setupWebSocket();
            await setupLocalMedia();
            setupEventListeners();
            updateControlButtons();
        }
        
        function toggleSidebar() {
            const container = document.querySelector('.meeting-container');
            const sidebar = document.querySelector('.sidebar');
            const toggle = document.getElementById('sidebarToggle');
            
            container.classList.toggle('sidebar-open');
            sidebar.classList.toggle('open');
            
            const icon = toggle.querySelector('i');
            if (container.classList.contains('sidebar-open')) {
                icon.className = 'fas fa-times';
            } else {
                icon.className = 'fas fa-comments';
            }
        }

        async function setupWebSocket() {
            // Join meeting via HTTP
            await fetch('/meetings/{{ $meeting->slug }}/signal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ type: 'join' })
            });
            
            // Start polling for messages
            startPolling();
        }
        
        function startPolling() {
            setInterval(async () => {
                try {
                    const response = await fetch('/meetings/{{ $meeting->slug }}/poll');
                    const data = await response.json();
                    
                    data.signals.forEach(handleSignalingMessage);
                    
                    // Load participants every poll
                    loadParticipants();
                } catch (error) {
                    console.error('Polling error:', error);
                }
            }, 1000); // Poll every second
        }

        async function setupLocalMedia() {
            try {
                const constraints = getMediaConstraints();
                localStream = await navigator.mediaDevices.getUserMedia(constraints);
                document.getElementById('localVideo').srcObject = localStream;
                
                // Apply initial mute states
                localStream.getAudioTracks().forEach(track => track.enabled = !isAudioMuted);
                localStream.getVideoTracks().forEach(track => track.enabled = !isVideoMuted);
                
                // Show avatar by default, video tile when video is enabled
                updateLocalView();
                
            } catch (error) {
                console.error('Failed to get local media:', error);
            }
        }
        
        function updateLocalView() {
            const avatar = document.getElementById('localAvatar');
            const tile = document.getElementById('localTile');
            const grid = document.getElementById('videoGrid');
            
            if (localStream && localStream.getVideoTracks().length > 0 && localStream.getVideoTracks()[0].enabled && !isVideoMuted) {
                avatar.style.display = 'none';
                tile.style.display = 'block';
                grid.classList.add('has-video');
            } else {
                avatar.style.display = 'flex';
                tile.style.display = 'none';
                if (grid.children.length <= 2) grid.classList.remove('has-video');
            }
        }

        function getMediaConstraints() {
            if (meetingSettings.audioOnlyMode) {
                return { audio: true, video: false };
            }
            
            const videoConstraints = meetingSettings.dataSaverMode ? 
                { width: 320, height: 240, frameRate: 15 } : 
                { width: 1280, height: 720, frameRate: 30 };
                
            return { audio: true, video: videoConstraints };
        }

        function setupEventListeners() {
            document.getElementById('muteBtn').onclick = toggleAudio;
            document.getElementById('videoBtn').onclick = toggleVideo;
            document.getElementById('screenBtn').onclick = toggleScreenShare;
            document.getElementById('handBtn').onclick = toggleHand;
            document.getElementById('pollBtn').onclick = showPollModal;
            document.getElementById('leaveBtn').onclick = leaveMeeting;
            
            document.getElementById('chatInput').onkeypress = (e) => {
                if (e.key === 'Enter') sendChatMessage();
            };
            
            document.getElementById('notesEditor').oninput = (e) => {
                broadcastMessage({ type: 'notes', content: e.target.value });
            };
        }

        function handleSignalingMessage(message) {
            
            switch (message.type) {
                case 'user-joined':
                    handleUserJoined(message);
                    break;
                case 'user-left':
                    handleUserLeft(message);
                    break;
                case 'offer':
                    handleOffer(message);
                    break;
                case 'answer':
                    handleAnswer(message);
                    break;
                case 'ice-candidate':
                    handleIceCandidate(message);
                    break;
                case 'chat':
                    displayChatMessage(message);
                    break;
                case 'hand-raised':
                    updateHandQueue(message);
                    break;
                case 'poll':
                    displayPoll(message);
                    break;
                case 'screen-share':
                    handleScreenShare(message);
                    break;
            }
        }
        
        async function handleUserJoined(message) {
            const peerId = message.from;
            if (peerId && !peers.has(peerId)) {
                await createPeerConnection(peerId, true);
            }
        }
        
        function handleUserLeft(message) {
            const peerId = message.from;
            if (peers.has(peerId)) {
                peers.get(peerId).close();
                peers.delete(peerId);
                removeVideoTile(peerId);
                updateVideoGrid();
            }
        }
        
        async function createPeerConnection(peerId, isInitiator) {
            const pc = new RTCPeerConnection({ iceServers });
            peers.set(peerId, pc);
            
            localStream.getTracks().forEach(track => {
                pc.addTrack(track, localStream);
            });
            
            pc.ontrack = (event) => {
                addVideoTile(peerId, event.streams[0]);
                updateVideoGrid();
            };
            
            pc.onicecandidate = (event) => {
                if (event.candidate) {
                    broadcastMessage({
                        type: 'ice-candidate',
                        candidate: event.candidate,
                        target: peerId
                    });
                }
            };
            
            if (isInitiator) {
                const offer = await pc.createOffer();
                await pc.setLocalDescription(offer);
                broadcastMessage({
                    type: 'offer',
                    offer: offer,
                    target: peerId
                });
            }
        }
        
        async function handleOffer(message) {
            const peerId = message.from;
            if (!peers.has(peerId)) {
                await createPeerConnection(peerId, false);
            }
            
            const pc = peers.get(peerId);
            await pc.setRemoteDescription(message.offer);
            
            const answer = await pc.createAnswer();
            await pc.setLocalDescription(answer);
            
            broadcastMessage({
                type: 'answer',
                answer: answer,
                target: peerId
            });
        }
        
        async function handleAnswer(message) {
            const peerId = message.from;
            const pc = peers.get(peerId);
            if (pc) {
                await pc.setRemoteDescription(message.answer);
            }
        }
        
        async function handleIceCandidate(message) {
            const peerId = message.from;
            const pc = peers.get(peerId);
            if (pc) {
                await pc.addIceCandidate(message.candidate);
            }
        }
        
        function addVideoTile(peerId, stream) {
            const videoGrid = document.getElementById('videoGrid');
            
            // Create avatar
            const avatar = document.createElement('div');
            avatar.className = 'participant-avatar';
            avatar.id = `avatar-${peerId}`;
            avatar.innerHTML = `
                <i class="fas fa-user"></i>
                <div class="participant-name">User ${peerId.substr(0, 8)}</div>
            `;
            
            // Create video tile
            const tile = document.createElement('div');
            tile.className = 'video-tile';
            tile.id = `tile-${peerId}`;
            tile.style.display = 'none';
            
            const video = document.createElement('video');
            video.autoplay = true;
            video.srcObject = stream;
            
            const userInfo = document.createElement('div');
            userInfo.className = 'user-info';
            userInfo.textContent = `User ${peerId.substr(0, 8)}`;
            
            tile.appendChild(video);
            tile.appendChild(userInfo);
            
            videoGrid.appendChild(avatar);
            videoGrid.appendChild(tile);
            
            // Check if video track is enabled
            const videoTrack = stream.getVideoTracks()[0];
            if (videoTrack && videoTrack.enabled) {
                avatar.style.display = 'none';
                tile.style.display = 'block';
                videoGrid.classList.add('has-video');
            }
        }
        
        function removeVideoTile(peerId) {
            const tile = document.getElementById(`tile-${peerId}`);
            const avatar = document.getElementById(`avatar-${peerId}`);
            if (tile) tile.remove();
            if (avatar) avatar.remove();
        }
        
        function updateVideoGrid() {
            const grid = document.getElementById('videoGrid');
            const tileCount = grid.children.length;
            
            grid.className = 'video-grid ';
            if (tileCount === 1) grid.className += 'grid-1';
            else if (tileCount === 2) grid.className += 'grid-2';
            else if (tileCount <= 4) grid.className += 'grid-4';
            else grid.className += 'grid-many';
        }
        
        function updateHandQueue(message) {
            const queueDiv = document.getElementById('handQueue');
            if (message.raised) {
                if (!handQueue.includes(message.from)) {
                    handQueue.push(message.from);
                }
            } else {
                handQueue = handQueue.filter(id => id !== message.from);
            }
            
            queueDiv.innerHTML = handQueue.map(id => 
                `<div class="participant hand-raised"><i class="fas fa-hand-paper"></i> User ${id.substr(0, 8)}</div>`
            ).join('');
        }
        
        function displayPoll(message) {
            const chatMessages = document.getElementById('chatMessages');
            const pollDiv = document.createElement('div');
            pollDiv.innerHTML = `
                <div style="background: #4b5563; padding: 15px; border-radius: 8px; margin: 10px 0;">
                    <h4><i class="fas fa-poll"></i> ${message.question}</h4>
                    ${message.options.map((option, i) => 
                        `<div class="poll-option" onclick="votePoll(${i})">${option}</div>`
                    ).join('')}
                </div>
            `;
            chatMessages.appendChild(pollDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        function handleScreenShare(message) {
            if (message.sharing) {
                enterScreenShareMode(`tile-${message.from}`);
            } else {
                exitScreenShareMode();
            }
        }
        
        function enterScreenShareMode(tileId) {
            const videoGrid = document.getElementById('videoGrid');
            const mainArea = videoGrid.parentElement;
            
            // Hide video grid and show screen share
            videoGrid.style.display = 'none';
            
            // Create screen share container
            const screenContainer = document.createElement('div');
            screenContainer.id = 'screenShareContainer';
            screenContainer.style.cssText = `
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #000;
                position: relative;
            `;
            
            // Clone the sharing tile
            const sharingTile = document.getElementById(tileId);
            if (sharingTile) {
                const screenVideo = sharingTile.querySelector('video').cloneNode();
                screenVideo.srcObject = sharingTile.querySelector('video').srcObject;
                screenVideo.style.cssText = `
                    width: 100%;
                    height: 100%;
                    object-fit: contain;
                `;
                
                const screenInfo = document.createElement('div');
                screenInfo.style.cssText = `
                    position: absolute;
                    top: 20px;
                    left: 20px;
                    background: rgba(0,0,0,0.8);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 20px;
                    font-size: 0.9rem;
                `;
                screenInfo.innerHTML = `<i class="fas fa-desktop"></i> ${sharingTile.querySelector('.user-info').textContent} is sharing`;
                
                screenContainer.appendChild(screenVideo);
                screenContainer.appendChild(screenInfo);
            }
            
            mainArea.insertBefore(screenContainer, videoGrid);
            
            // Show small video grid in corner
            createThumbnailGrid();
        }
        
        function exitScreenShareMode() {
            const screenContainer = document.getElementById('screenShareContainer');
            const thumbnailGrid = document.getElementById('thumbnailGrid');
            const videoGrid = document.getElementById('videoGrid');
            
            if (screenContainer) screenContainer.remove();
            if (thumbnailGrid) thumbnailGrid.remove();
            
            videoGrid.style.display = 'grid';
        }
        
        function createThumbnailGrid() {
            const mainArea = document.getElementById('videoGrid').parentElement;
            const thumbnailGrid = document.createElement('div');
            thumbnailGrid.id = 'thumbnailGrid';
            thumbnailGrid.style.cssText = `
                position: absolute;
                bottom: 20px;
                right: 20px;
                display: flex;
                gap: 8px;
                z-index: 100;
            `;
            
            // Add all video tiles as thumbnails
            const videoGrid = document.getElementById('videoGrid');
            Array.from(videoGrid.children).forEach(tile => {
                const thumbnail = tile.cloneNode(true);
                thumbnail.style.cssText = `
                    width: 120px;
                    height: 80px;
                    border-radius: 8px;
                    overflow: hidden;
                    border: 2px solid rgba(255,255,255,0.3);
                `;
                
                const video = thumbnail.querySelector('video');
                if (video) {
                    video.srcObject = tile.querySelector('video').srcObject;
                    video.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                }
                
                thumbnailGrid.appendChild(thumbnail);
            });
            
            mainArea.appendChild(thumbnailGrid);
        }

        function toggleAudio() {
            isAudioMuted = !isAudioMuted;
            localStream.getAudioTracks().forEach(track => track.enabled = !isAudioMuted);
            updateControlButtons();
            updateParticipantStatus({ is_muted: isAudioMuted });
            broadcastMessage({ type: 'audio-toggle', muted: isAudioMuted });
        }

        function toggleVideo() {
            isVideoMuted = !isVideoMuted;
            localStream.getVideoTracks().forEach(track => track.enabled = !isVideoMuted);
            updateControlButtons();
            updateLocalView();
            updateParticipantStatus({ is_video_off: isVideoMuted });
            broadcastMessage({ type: 'video-toggle', muted: isVideoMuted });
        }

        async function toggleScreenShare() {
            try {
                if (localStream.getVideoTracks()[0].label.includes('screen')) {
                    // Stop screen sharing, return to camera
                    const constraints = getMediaConstraints();
                    localStream = await navigator.mediaDevices.getUserMedia(constraints);
                    document.getElementById('localVideo').srcObject = localStream;
                    
                    // Update all peer connections
                    peers.forEach(async (pc) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            await sender.replaceTrack(localStream.getVideoTracks()[0]);
                        }
                    });
                    
                    // Broadcast screen share stopped
                    broadcastMessage({ type: 'screen-share', sharing: false });
                    exitScreenShareMode();
                    updateControlButtons();
                } else {
                    // Start screen sharing
                    const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                    const videoTrack = screenStream.getVideoTracks()[0];
                    
                    // Replace video track in local stream
                    localStream.getVideoTracks().forEach(track => track.stop());
                    localStream.removeTrack(localStream.getVideoTracks()[0]);
                    localStream.addTrack(videoTrack);
                    
                    document.getElementById('localVideo').srcObject = localStream;
                    
                    // Update all peer connections
                    peers.forEach(async (pc) => {
                        const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
                        if (sender) {
                            await sender.replaceTrack(videoTrack);
                        }
                    });
                    
                    // Broadcast screen share started
                    broadcastMessage({ type: 'screen-share', sharing: true });
                    enterScreenShareMode('localTile');
                    updateControlButtons();
                    
                    // Handle screen share end
                    videoTrack.onended = () => {
                        toggleScreenShare(); // Return to camera
                    };
                }
            } catch (error) {
                console.error('Screen share error:', error);
            }
        }
        
        async function updateParticipantStatus(status) {
            try {
                await fetch('/meetings/{{ $meeting->slug }}/participants/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(status)
                });
            } catch (error) {
                console.error('Status update error:', error);
            }
        }
        
        async function loadParticipants() {
            try {
                const response = await fetch('/meetings/{{ $meeting->slug }}/participants');
                const data = await response.json();
                
                const participantsList = document.getElementById('participantsList');
                const participantCount = document.getElementById('participantCount');
                const videoGrid = document.getElementById('videoGrid');
                
                participantCount.textContent = data.participants.length;
                
                participantsList.innerHTML = data.participants.map(participant => `
                    <div class="participant">
                        <span><i class="fas fa-user"></i></span>
                        <span>${participant.user_name}</span>
                        ${participant.is_muted ? '<i class="fas fa-microphone-slash" style="color: #ef4444;"></i>' : ''}
                        ${participant.is_video_off ? '<i class="fas fa-video-slash" style="color: #ef4444;"></i>' : ''}
                    </div>
                `).join('');
                
                // Create avatars for participants not yet connected via WebRTC
                data.participants.forEach(participant => {
                    const participantId = `participant-${participant.user_name.replace(/\s+/g, '-').toLowerCase()}`;
                    
                    // Skip if this is the current user or if avatar already exists
                    if (participant.user_name === 'You' || document.getElementById(`avatar-${participantId}`)) {
                        return;
                    }
                    
                    // Skip if WebRTC connection exists
                    if (peers.has(participantId)) {
                        return;
                    }
                    
                    // Create avatar for participant
                    const avatar = document.createElement('div');
                    avatar.className = 'participant-avatar';
                    avatar.id = `avatar-${participantId}`;
                    avatar.innerHTML = `
                        <i class="fas fa-user"></i>
                        <div class="participant-name">${participant.user_name}</div>
                    `;
                    
                    videoGrid.appendChild(avatar);
                });
                
            } catch (error) {
                console.error('Load participants error:', error);
            }
        }

        function toggleHand() {
            isHandRaised = !isHandRaised;
            updateControlButtons();
            broadcastMessage({ type: 'hand-raised', raised: isHandRaised });
        }

        function updateControlButtons() {
            document.getElementById('muteBtn').className = `control-btn ${isAudioMuted ? 'active' : 'inactive'}`;
            document.getElementById('videoBtn').className = `control-btn ${isVideoMuted ? 'active' : 'inactive'}`;
            document.getElementById('handBtn').className = `control-btn ${isHandRaised ? 'primary' : 'inactive'}`;
            
            const isScreenSharing = localStream && localStream.getVideoTracks()[0] && localStream.getVideoTracks()[0].label.includes('screen');
            document.getElementById('screenBtn').className = `control-btn ${isScreenSharing ? 'primary' : 'inactive'}`;
        }

        function sendChatMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();
            if (message) {
                broadcastMessage({ type: 'chat', message, sender: 'You' });
                displayChatMessage({ message, sender: 'You' });
                input.value = '';
            }
        }

        function displayChatMessage(data) {
            const messages = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.innerHTML = `<strong>${data.sender}:</strong> ${data.message}`;
            div.style.marginBottom = '8px';
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.sidebar-content > div').forEach(d => d.style.display = 'none');
            
            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').style.display = 'block';
        }

        function showPollModal() {
            document.getElementById('pollModal').style.display = 'flex';
        }

        function closePoll() {
            document.getElementById('pollModal').style.display = 'none';
        }

        function createPoll() {
            const question = document.getElementById('pollQuestion').value;
            const options = Array.from(document.querySelectorAll('.poll-option-input')).map(input => input.value).filter(v => v);
            
            if (question && options.length >= 2) {
                broadcastMessage({ type: 'poll', question, options });
                closePoll();
            }
        }

        async function broadcastMessage(message) {
            try {
                await fetch('/meetings/{{ $meeting->slug }}/signal', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(message)
                });
            } catch (error) {
                console.error('Broadcast error:', error);
            }
        }

        async function leaveMeeting() {
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            
            await fetch('/meetings/{{ $meeting->slug }}/leave', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
            
            window.location.href = '/';
        }

        function votePoll(optionIndex) {
            broadcastMessage({
                type: 'poll-vote',
                option: optionIndex
            });
        }
        
        // Initialize when page loads
        initMeeting();
        
        // Load participants initially
        setTimeout(loadParticipants, 1000);
</script>
@endpush
