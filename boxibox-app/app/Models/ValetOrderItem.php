<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'valet_order_id',
        'valet_item_id',
        'item_description',
        'category',
        'size',
        'quantity',
        'is_new_item',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_new_item' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(ValetOrder::class, 'valet_order_id');
    }

    public function item()
    {
        return $this->belongsTo(ValetItem::class, 'valet_item_id');
    }

    public function getDisplayName(): string
    {
        if ($this->item) {
            return $this->item->name;
        }
        return $this->item_description ?? 'Article sans nom';
    }
}
