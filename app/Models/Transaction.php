<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'sum',
        'note',
        'category_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses()
    {
        return Transaction::whereHas('category', function ($query) {
            $query->where('is_income', false);
        });
    }

    public function incomes()
    {
        return Transaction::whereHas('category', function ($query) {
            $query->where('is_income', true);
        });
    }
}
