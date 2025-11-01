<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingsAuditLog extends Model
{
    use HasFactory;

    protected $table = 'settings_audit_log';

    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'category',
    ];

    protected $casts = [
        'old_value' => 'string',
        'new_value' => 'string',
    ];

    /**
     * Get the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Log a settings change.
     */
    public static function log(string $key, ?string $oldValue, ?string $newValue, ?string $category = null): void
    {
        if ($oldValue === $newValue) {
            return; // No change, don't log
        }

        static::create([
            'setting_key' => $key,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => auth()->id(),
            'category' => $category,
        ]);
    }
}