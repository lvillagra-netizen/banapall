<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogger
{
    public static function record(
        string $action,
        ?Model $model = null,
        array $old = [],
        array $new = [],
        ?string $description = null,
    ): void {
        $request = request();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'auditable_type' => $model?->getMorphClass(),
            'auditable_id' => $model?->getKey(),
            'old_values' => self::sanitize($old),
            'new_values' => self::sanitize($new),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
        ]);
    }

    private static function sanitize(array $values): array
    {
        return Arr::except($values, ['password', 'password_confirmation', 'remember_token', 'xml_payload']);
    }
}
