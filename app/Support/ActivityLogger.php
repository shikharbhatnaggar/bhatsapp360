<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Single write point for the audit trail. Everything the product does to a
 * customer, template, campaign or message lands here.
 */
class ActivityLogger
{
    public static function log(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?int $tenantId = null,
    ): ActivityLog {
        return ActivityLog::withoutGlobalScope('tenant')->create([
            'tenant_id' => $tenantId ?? Auth::user()?->tenant_id ?? ($subject->tenant_id ?? null),
            'user_id' => Auth::id(),
            'event' => $event,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
