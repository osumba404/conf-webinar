<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecordingController extends Controller
{
    public function start(Request $request, Meeting $meeting)
    {
        $recordingId = uniqid('rec_');
        
        return response()->json([
            'recording_id' => $recordingId,
            'status' => 'started'
        ]);
    }

    public function upload(Request $request, Meeting $meeting)
    {
        $request->validate([
            'chunk' => 'required',
            'recording_id' => 'required|string'
        ]);

        $recordingId = $request->recording_id;
        $chunk = base64_decode($request->chunk);
        
        Storage::append("recordings/{$meeting->slug}/{$recordingId}.webm", $chunk);

        return response()->json(['status' => 'uploaded']);
    }

    public function stop(Request $request, Meeting $meeting)
    {
        $recordingId = $request->recording_id;
        
        return response()->json([
            'status' => 'stopped',
            'url' => Storage::url("recordings/{$meeting->slug}/{$recordingId}.webm")
        ]);
    }
}
