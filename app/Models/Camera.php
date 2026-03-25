<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'rtsp_scheme_id',
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

    public function rtspScheme(): BelongsTo
    {
        return $this->belongsTo(RtspScheme::class);
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
        return $this->buildRtspUrl();
    }

    public function buildRtspUrl(): string
    {
        $schemeTemplate = $this->rtspScheme?->scheme_template;

        if ($schemeTemplate) {
            return strtr($schemeTemplate, [
                '{username}' => urlencode($this->username),
                '{password}' => urlencode($this->password),
                '{ip}' => $this->stream_host,
                '{port}' => (string) $this->port,
            ]);
        }

        $username = urlencode($this->username);
        $password = urlencode($this->password);
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
