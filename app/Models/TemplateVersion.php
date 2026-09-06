<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateVersion extends Model
{
    protected $fillable = [
        'message_template_id', 'version', 'action', 'status', 'components',
        'request_payload', 'response_payload', 'review_note', 'submitted_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'request_payload' => 'array',
            'response_payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
