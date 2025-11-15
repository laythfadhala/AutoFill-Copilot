<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUsage extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'tokens_used',
        'tokens_this_month',
        'count_this_month',
        'current_month',
    ];

    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the user that owns the token usage.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
