<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preference_type',
        'key',
        'value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function get(string $userId, string $preferenceType, string $key, $default = null)
    {
        $preference = static::where('user_id', $userId)
            ->where('preference_type', $preferenceType)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : $default;
    }

    public static function set(string $userId, string $preferenceType, string $key, $value): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId,
                'preference_type' => $preferenceType,
                'key' => $key,
            ],
            ['value' => $value]
        );
    }

    public static function getNotificationPreferences(string $userId): array
    {
        $preferences = static::where('user_id', $userId)
            ->where('preference_type', 'notification')
            ->pluck('value', 'key')
            ->toArray();

        return array_merge([
            'email_campaign_updates' => true,
            'email_payment_notifications' => true,
            'email_savings_updates' => true,
            'sms_important_updates' => false,
            'push_notifications' => true,
        ], $preferences);
    }

    public static function setNotificationPreferences(string $userId, array $preferences): void
    {
        foreach ($preferences as $key => $value) {
            static::set($userId, 'notification', $key, $value);
        }
    }
}
