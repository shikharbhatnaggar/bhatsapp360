<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageStatusEvent extends Model
{
    protected $fillable = ['message_id', 'status', 'source', 'raw', 'occurred_at'];

    protected function casts(): array
    {
        return ['raw' => 'array', 'occurred_at' => 'datetime'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
