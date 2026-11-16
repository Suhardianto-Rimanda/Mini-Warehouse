<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class StockMutation extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }
    
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
