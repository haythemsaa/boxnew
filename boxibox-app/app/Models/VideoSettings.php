<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'video_enabled',
        'recording_enabled',
        'chat_enabled',
        'screen_sharing_enabled',
        'waiting_room_enabled',
        'max_call_duration_minutes',
        'max_concurrent_calls',
        'welcome_message',
        'waiting_room_message',
        'notification_emails',
        'working_hours',
        'ice_servers',
    ];

    protected $casts = [
        'video_enabled' => 'boolean',
        'recording_enabled' => 'boolean',
        'chat_enabled' => 'boolean',
        'screen_sharing_enabled' => 'boolean',
        'waiting_room_enabled' => 'boolean',
        'max_call_duration_minutes' => 'integer',
        'max_concurrent_calls' => 'integer',
        'notification_emails' => 'array',
        'working_hours' => 'array',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Default working hours
    public static function getDefaultWorkingHours(): array
    {
        return [
            ['day' => 1, 'start' => '09:00', 'end' => '18:00', 'enabled' => true], // Lundi
            ['day' => 2, 'start' => '09:00', 'end' => '18:00', 'enabled' => true], // Mardi
            ['day' => 3, 'start' => '09:00', 'end' => '18:00', 'enabled' => true], // Mercredi
            ['day' => 4, 'start' => '09:00', 'end' => '18:00', 'enabled' => true], // Jeudi
            ['day' => 5, 'start' => '09:00', 'end' => '18:00', 'enabled' => true], // Vendredi
            ['day' => 6, 'start' => '09:00', 'end' => '12:00', 'enabled' => false], // Samedi
            ['day' => 0, 'start' => '09:00', 'end' => '12:00', 'enabled' => false], // Dimanche
        ];
    }

    public function isWithinWorkingHours(): bool
    {
        $workingHours = $this->working_hours ?? self::getDefaultWorkingHours();
        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i');

        foreach ($workingHours as $schedule) {
            if ($schedule['day'] === $dayOfWeek && ($schedule['enabled'] ?? true)) {
                if ($currentTime >= $schedule['start'] && $currentTime <= $schedule['end']) {
                    return true;
                }
            }
        }

        return false;
    }
}
