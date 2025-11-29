<?php

namespace App\Http\Controllers;

use App\Events\MeetingSignal;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class SignalingController extends Controller
{
    public function signal(Request $request, Meeting $meeting)
    {
        $message = $request->all();
        $sessionId = session()->getId();
        $userSession = session('meeting_' . $meeting->slug);
        
        if (!$userSession) {
            return response()->json(['error' => 'Not authorized for this meeting'], 403);
        }
        
        // Store participant in cache with Google user info
        $participants = Cache::get("meeting.{$meeting->slug}.participants", []);
        $participants[$sessionId] = [
            'id' => $sessionId,
            'user_id' => $userSession['user_id'],
            'user_name' => $userSession['user_name'],
            'user_email' => $userSession['user_email'],
            'joined_at' => $userSession['joined_at'],
            'last_seen' => now(),
            'is_muted' => $userSession['is_muted'] ?? false,
            'is_video_off' => $userSession['is_video_off'] ?? false
        ];
        Cache::put("meeting.{$meeting->slug}.participants", $participants, 3600);
        
        // Handle different message types
        switch ($message['type']) {
            case 'join':
                return $this->handleJoin($meeting, $sessionId);
            case 'offer':
            case 'answer':
            case 'ice-candidate':
                return $this->relaySignal($meeting, $message, $sessionId);
            case 'chat':
            case 'hand-raised':
            case 'poll':
            case 'notes':
                return $this->broadcastMessage($meeting, $message, $sessionId);
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    private function handleJoin(Meeting $meeting, string $sessionId)
    {
        $participants = Cache::get("meeting.{$meeting->slug}.participants", []);
        
        // Store signaling data for this session
        Cache::put("meeting.{$meeting->slug}.signals.{$sessionId}", [], 3600);
        
        return response()->json([
            'type' => 'joined',
            'participantCount' => count($participants),
            'sessionId' => $sessionId
        ]);
    }
    
    private function relaySignal(Meeting $meeting, array $message, string $fromSession)
    {
        $message['from'] = $fromSession;
        $targetSession = $message['target'] ?? null;
        
        // Broadcast via WebSocket
        broadcast(new MeetingSignal($meeting->slug, $message, $targetSession));
        
        return response()->json(['status' => 'relayed']);
    }
    
    private function broadcastMessage(Meeting $meeting, array $message, string $fromSession)
    {
        $message['from'] = $fromSession;
        
        // Broadcast via WebSocket
        broadcast(new MeetingSignal($meeting->slug, $message));
        
        return response()->json(['status' => 'broadcasted']);
    }
    
    public function poll(Request $request, Meeting $meeting)
    {
        $sessionId = session()->getId();
        $signals = Cache::get("meeting.{$meeting->slug}.signals.{$sessionId}", []);
        
        // Clear signals after retrieving
        Cache::put("meeting.{$meeting->slug}.signals.{$sessionId}", [], 3600);
        
        // Update last seen
        $participants = Cache::get("meeting.{$meeting->slug}.participants", []);
        if (isset($participants[$sessionId])) {
            $participants[$sessionId]['last_seen'] = now();
            Cache::put("meeting.{$meeting->slug}.participants", $participants, 3600);
        }
        
        return response()->json([
            'signals' => $signals,
            'participantCount' => count($participants)
        ]);
    }
    
    public function leave(Request $request, Meeting $meeting)
    {
        $sessionId = session()->getId();
        
        // Remove from participants
        $participants = Cache::get("meeting.{$meeting->slug}.participants", []);
        unset($participants[$sessionId]);
        Cache::put("meeting.{$meeting->slug}.participants", $participants, 3600);
        
        // Clean up signals
        Cache::forget("meeting.{$meeting->slug}.signals.{$sessionId}");
        
        // Notify others
        foreach ($participants as $otherSessionId => $participant) {
            $signals = Cache::get("meeting.{$meeting->slug}.signals.{$otherSessionId}", []);
            $signals[] = ['type' => 'user-left', 'from' => $sessionId];
            Cache::put("meeting.{$meeting->slug}.signals.{$otherSessionId}", $signals, 3600);
        }
        
        return response()->json(['status' => 'left']);
    }
}