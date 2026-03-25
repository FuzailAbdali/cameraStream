<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'external_ip',
        'port',
        'rtsp_path',
        'username',
        'password',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    public function getStreamHostAttribute(): string
    {
        return $this->external_ip ?: $this->ip_address;
    }

    public function getRtspPathAttribute(?string $value): string
    {
        return self::sanitizeRtspPath($value);
    }

    public function getRtspUrlAttribute(): string
    {
        $username = rawurlencode($this->username);
        $password = rawurlencode($this->password);
        $path = self::sanitizeRtspPath($this->rtsp_path);

        return sprintf('rtsp://%s:%s@%s:%d/%s', $username, $password, $this->stream_host, $this->port, $path);
    }

    public function setRtspPathAttribute(?string $value): void
    {
        $this->attributes['rtsp_path'] = self::sanitizeRtspPath($value);
    }

    public static function sanitizeRtspPath(?string $path): string
    {
        $normalized = trim((string) $path);
        $normalized = ltrim($normalized, '/');
        $normalized = preg_replace('/[^A-Za-z0-9_\-\.\/\?=&]/', '', $normalized) ?? '';

        return $normalized !== '' ? $normalized : 'stream';
    }
}
