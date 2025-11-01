<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'created_by',
        'title',
        'content',
        'type',
        'is_public',
        'send_notification',
        'published_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'send_notification' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at <= now();
    }

    public function scopePublished($query)
    {
        return $query->where('is_public', true)
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }
}

