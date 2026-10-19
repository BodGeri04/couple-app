<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySnap extends Model
{
    /** Everything else (users, file paths) is set explicitly in the controller. */
    protected $fillable = ['caption', 'mood'];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function toPayload(User $viewer): array
    {
        return [
            'id' => $this->id,
            'mine' => $this->sender_id === $viewer->id,
            'sender' => $this->sender?->name,
            'caption' => $this->caption,
            'mood' => $this->mood,
            'created_at' => $this->created_at->toIso8601String(),
            'viewed_at' => $this->viewed_at?->toIso8601String(),
            'reply_to_id' => $this->reply_to_id,
            'image_url' => route('snaps.image', ['snap' => $this->id], false),
            'thumb_url' => route('snaps.image', ['snap' => $this->id, 'size' => 'thumb'], false),
        ];
    }
}