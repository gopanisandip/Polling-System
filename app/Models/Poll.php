<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'slug',
        'is_active',
        'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'end_at' => 'datetime',
        'total_votes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Poll $poll) {
            if (empty($poll->slug)) {
                $poll->slug = Str::slug($poll->title) . '-' . Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function isOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasVotedByIp(string $ip): bool
    {
        return $this->votes()->where('ip_address', $ip)->exists();
    }

    public function hasVotedByUser(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function getShareUrl(): string
    {
        return route('polls.show', $this->slug);
    }
}
