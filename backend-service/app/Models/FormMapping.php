<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain',
        'field_mappings',
        'form_selector',
        'form_config',
        'usage_count',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'field_mappings' => 'array',
        'form_config' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get active mappings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get mappings for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get mappings for a specific domain
     */
    public function scopeForDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Increment usage count and update last used
     */
    public function recordUsage()
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }
}
