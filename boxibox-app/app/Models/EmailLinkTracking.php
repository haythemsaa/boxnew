<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailLinkTracking extends Model
{
    protected $table = 'email_link_tracking';

    protected $fillable = [
        'email_tracking_id',
        'link_id',
        'original_url',
        'link_name',
        'click_count',
        'first_clicked_at',
        'last_clicked_at',
        'click_details',
    ];

    protected $casts = [
        'first_clicked_at' => 'datetime',
        'last_clicked_at' => 'datetime',
        'click_details' => 'array',
        'click_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->link_id)) {
                $model->link_id = Str::uuid()->toString();
            }
        });
    }

    public function emailTracking(): BelongsTo
    {
        return $this->belongsTo(EmailTracking::class);
    }

    public function recordClick(array $details = []): void
    {
        $this->increment('click_count');

        $clickDetails = $this->click_details ?? [];
        $clickDetails[] = array_merge([
            'clicked_at' => now()->toISOString(),
        ], $details);

        $update = [
            'click_details' => $clickDetails,
            'last_clicked_at' => now(),
        ];

        if (!$this->first_clicked_at) {
            $update['first_clicked_at'] = now();
        }

        $this->update($update);

        // Also update parent email tracking
        $this->emailTracking->markAsClicked($this->original_url);
    }
}
