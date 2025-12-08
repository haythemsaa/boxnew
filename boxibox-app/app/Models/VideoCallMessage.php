<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoCallMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'video_call_id',
        'user_id',
        'sender_type',
        'sender_name',
        'message',
        'type',
        'file_url',
    ];

    // Relationships
    public function videoCall(): BelongsTo
    {
        return $this->belongsTo(VideoCall::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
