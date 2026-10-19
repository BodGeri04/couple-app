<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wish extends Model
{
    protected $fillable = ['name', 'url'];

    protected function casts(): array
    {
        return [
            'reacted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function toPayload(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'url' => $this->url,
            'reaction' => $this->reaction, // null | 'consider' | 'decline'
            'reacted_at' => $this->reacted_at?->toIso8601String(),
        ];
    }
}