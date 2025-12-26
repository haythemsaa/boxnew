<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Auction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'site_id',
        'box_id',
        'contract_id',
        'customer_id',
        'auction_number',
        'total_debt',
        'storage_fees',
        'late_fees',
        'legal_fees',
        'days_overdue',
        'contents_description',
        'contents_photos',
        'starting_bid',
        'reserve_price',
        'current_bid',
        'winning_bid',
        'winning_bidder_id',
        'status',
        'first_notice_date',
        'second_notice_date',
        'final_notice_date',
        'auction_start_date',
        'auction_end_date',
        'sold_at',
        'legal_jurisdiction',
        'legal_documents',
        'legal_requirements_met',
        'legal_notes',
        'external_platform',
        'external_listing_id',
        'external_listing_url',
    ];

    protected $casts = [
        'total_debt' => 'decimal:2',
        'storage_fees' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'legal_fees' => 'decimal:2',
        'starting_bid' => 'decimal:2',
        'reserve_price' => 'decimal:2',
        'current_bid' => 'decimal:2',
        'winning_bid' => 'decimal:2',
        'days_overdue' => 'integer',
        'contents_photos' => 'array',
        'legal_documents' => 'array',
        'legal_requirements_met' => 'boolean',
        'first_notice_date' => 'date',
        'second_notice_date' => 'date',
        'final_notice_date' => 'date',
        'auction_start_date' => 'datetime',
        'auction_end_date' => 'datetime',
        'sold_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auction) {
            if (empty($auction->uuid)) {
                $auction->uuid = (string) Str::uuid();
            }
            if (empty($auction->auction_number)) {
                $auction->auction_number = static::generateAuctionNumber($auction->tenant_id);
            }
        });
    }

    public static function generateAuctionNumber(int $tenantId): string
    {
        $prefix = 'AUC';
        $year = date('Y');
        $count = static::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s%s%05d', $prefix, $year, $count);
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class)->orderBy('amount', 'desc');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(AuctionNotice::class);
    }

    public function winningBidder(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'winning_bidder_id');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'notice_sent' => 'Avis envoyé',
            'scheduled' => 'Programmée',
            'active' => 'En cours',
            'ended' => 'Terminée',
            'sold' => 'Vendu',
            'unsold' => 'Non vendu',
            'redeemed' => 'Dette remboursée',
            'cancelled' => 'Annulée',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => '#9E9E9E',
            'notice_sent' => '#FF9800',
            'scheduled' => '#2196F3',
            'active' => '#4CAF50',
            'ended' => '#607D8B',
            'sold' => '#4CAF50',
            'unsold' => '#f44336',
            'redeemed' => '#9C27B0',
            'cancelled' => '#f44336',
            default => '#9E9E9E',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               $this->auction_start_date <= now() &&
               $this->auction_end_date > now();
    }

    public function getTimeRemainingAttribute(): ?string
    {
        if (!$this->is_active) {
            return null;
        }
        return $this->auction_end_date->diffForHumans();
    }

    public function getBidCountAttribute(): int
    {
        return $this->bids()->count();
    }

    // Scopes
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeBySite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('auction_start_date', '<=', now())
            ->where('auction_end_date', '>', now());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeEnding($query, int $hours = 24)
    {
        return $query->active()
            ->where('auction_end_date', '<=', now()->addHours($hours));
    }

    // Actions
    public function sendFirstNotice(): void
    {
        $this->update([
            'status' => 'notice_sent',
            'first_notice_date' => now(),
        ]);

        AuctionNotice::create([
            'auction_id' => $this->id,
            'notice_type' => 'first_warning',
            'channel' => 'email',
            'status' => 'pending',
        ]);
    }

    public function sendSecondNotice(): void
    {
        $this->update(['second_notice_date' => now()]);

        AuctionNotice::create([
            'auction_id' => $this->id,
            'notice_type' => 'second_warning',
            'channel' => 'registered_mail',
            'status' => 'pending',
        ]);
    }

    public function sendFinalNotice(): void
    {
        $this->update(['final_notice_date' => now()]);

        AuctionNotice::create([
            'auction_id' => $this->id,
            'notice_type' => 'final_notice',
            'channel' => 'registered_mail',
            'status' => 'pending',
        ]);
    }

    public function schedule(\DateTime $startDate, \DateTime $endDate): void
    {
        $this->update([
            'status' => 'scheduled',
            'auction_start_date' => $startDate,
            'auction_end_date' => $endDate,
            'legal_requirements_met' => true,
        ]);
    }

    public function start(): void
    {
        $this->update(['status' => 'active']);
    }

    public function end(): void
    {
        $winningBid = $this->bids()->where('is_winning', true)->first();

        $this->update([
            'status' => $winningBid ? 'sold' : 'unsold',
            'winning_bid' => $winningBid?->amount,
            'winning_bidder_id' => $winningBid?->bidder_id,
            'sold_at' => $winningBid ? now() : null,
        ]);
    }

    public function placeBid(array $data): AuctionBid
    {
        // Invalidate previous winning bid
        $this->bids()->where('is_winning', true)->update(['is_winning' => false]);

        // Create new bid
        $bid = $this->bids()->create([
            ...$data,
            'is_winning' => true,
        ]);

        // Update current bid
        $this->update(['current_bid' => $bid->amount]);

        return $bid;
    }

    public function redeem(): void
    {
        $this->update(['status' => 'redeemed']);

        // Release box back to customer
        if ($this->contract) {
            // Mark debt as paid in contract
        }
    }

    public function cancel(string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'legal_notes' => $reason,
        ]);
    }
}
