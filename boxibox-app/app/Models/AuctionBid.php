<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBid extends Model
{
    protected $fillable = [
        'auction_id',
        'bidder_id',
        'bidder_name',
        'bidder_email',
        'bidder_phone',
        'amount',
        'is_winning',
        'is_auto_bid',
        'max_auto_bid',
        'ip_address',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'max_auto_bid' => 'decimal:2',
        'is_winning' => 'boolean',
        'is_auto_bid' => 'boolean',
    ];

    // Relationships
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function bidder(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'bidder_id');
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', ' ') . ' €';
    }

    // Scopes
    public function scopeWinning($query)
    {
        return $query->where('is_winning', true);
    }

    public function scopeByAuction($query, int $auctionId)
    {
        return $query->where('auction_id', $auctionId);
    }
}
