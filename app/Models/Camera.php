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

    public function getRtspUrlAttribute(): string
    {
        $username = rawurlencode($this->username);
        $password = rawurlencode($this->password);

        return sprintf('rtsp://%s:%s@%s:%d/stream', $username, $password, $this->stream_host, $this->port);
    }
}
