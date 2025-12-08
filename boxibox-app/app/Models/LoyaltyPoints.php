<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyPoints extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'program_id',
        'points_balance',
        'total_points_earned',
        'total_points_redeemed',
        'tier',
    ];

    protected $casts = [
        'points_balance' => 'integer',
        'total_points_earned' => 'integer',
        'total_points_redeemed' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'program_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'loyalty_points_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(LoyaltyRedemption::class, 'loyalty_points_id');
    }

    public function earnPoints(int $points, string $description, ?int $invoiceId = null, ?int $contractId = null): void
    {
        $this->points_balance += $points;
        $this->total_points_earned += $points;
        $this->save();

        $this->transactions()->create([
            'type' => 'earn',
            'points' => $points,
            'description' => $description,
            'invoice_id' => $invoiceId,
            'contract_id' => $contractId,
        ]);

        $this->updateTier();
    }

    public function redeemPoints(int $points, string $description): bool
    {
        if ($this->points_balance < $points) {
            return false;
        }

        $this->points_balance -= $points;
        $this->total_points_redeemed += $points;
        $this->save();

        $this->transactions()->create([
            'type' => 'redeem',
            'points' => -$points,
            'description' => $description,
        ]);

        return true;
    }

    protected function updateTier(): void
    {
        if ($this->program) {
            $newTier = $this->program->getTierForPoints($this->total_points_earned);
            if ($newTier !== $this->tier) {
                $this->tier = $newTier;
                $this->save();
            }
        }
    }
}
