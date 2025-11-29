<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Video Conference Platform - 150+ Attendees

A scalable video conferencing platform built with Laravel, WebRTC, and Mediasoup SFU. Supports 150+ concurrent participants with real-time features.

### Features

- ✅ **150+ Concurrent Users** - Mediasoup SFU architecture
- ✅ **WebRTC** - Peer-to-peer video/audio streaming
- ✅ **WebSockets** - Real-time signaling (Laravel Reverb)
- ✅ **Recording** - Client-side meeting recordings
- ✅ **Screen Sharing** - Share screens with all participants
- ✅ **Chat** - Real-time messaging
- ✅ **Bandwidth Adaptation** - Auto quality adjustment
- ✅ **Reconnection** - Auto-reconnect on disconnect
- ✅ **Google OAuth** - Secure authentication
- ✅ **Hand Raising** - Queue system
- ✅ **Polls** - Quick voting

## Quick Start

### One-Click Start (Windows)
```bash
start-all.bat
```

### Manual Start

**Terminal 1: Laravel**
```bash
php artisan serve
```

**Terminal 2: Reverb WebSocket**
```bash
php artisan reverb:start
```

**Terminal 3: Mediasoup SFU**
```bash
node mediasoup-server.js
```

Access: **http://127.0.0.1:8000**

## Architecture

```
Users (150+) → Mediasoup SFU (Port 3000) → Laravel + Reverb (Port 8000/8080)
```

**How it works:**
1. Each user sends 1 stream to SFU
2. SFU forwards to all other users
3. Linear bandwidth growth (not exponential)

## Tech Stack

- **Laravel 12** - Backend framework
- **Mediasoup** - SFU for 150+ users
- **Laravel Reverb** - WebSocket server
- **WebRTC** - Real-time communication
- **Redis** - Caching & queuing
- **MySQL** - Database
- **Tailwind CSS** - Styling
- **Vite** - Asset bundling

## Installation

```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build
```

## Configuration

### Google OAuth
Update `.env`:
```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

### Database
```env
DB_DATABASE=meet
DB_USERNAME=root
DB_PASSWORD=
```

## Documentation

- **README_150_USERS.md** - Complete guide for 150+ users
- **START_SFU.md** - Detailed SFU setup
- **FEATURES.md** - All features documentation
- **SFU_INTEGRATION.md** - Advanced SFU topics

## Production Deployment

### Server Requirements
- CPU: 8+ cores
- RAM: 16GB+
- Bandwidth: 500 Mbps+
- OS: Ubuntu 22.04 LTS

### Firewall Rules
```bash
sudo ufw allow 8000/tcp   # Laravel
sudo ufw allow 8080/tcp   # Reverb
sudo ufw allow 3000/tcp   # Mediasoup
sudo ufw allow 10000:10100/udp  # RTC
```

### Process Manager
```bash
npm install -g pm2
pm2 start mediasoup-server.js --name sfu
pm2 start "php artisan reverb:start" --name reverb
pm2 startup && pm2 save
```

## Capacity

| Setup | Max Users | Bandwidth/User | Cost/Month |
|-------|-----------|----------------|------------|
| Mesh | 3-5 | High | Low |
| **SFU** | **150+** | **Medium** | **$350** |
| MCU | 500+ | Low | High |

## Cost Estimate (Self-Hosted)

- Server: c5.2xlarge ($250/month)
- Bandwidth: 150 users ($100/month)
- **Total: ~$350/month**

## License

MIT License
