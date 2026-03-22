<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'external_ip',
        'port',
        'forwarded_port',
        'username',
        'password',
        'rtsp_url',
        'stream_path',
        'stream_status',
        'last_stream_started_at',
        'last_stream_heartbeat_at',
        'last_stream_failed_at',
        'last_stream_error',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'last_stream_started_at' => 'datetime',
            'last_stream_heartbeat_at' => 'datetime',
            'last_stream_failed_at' => 'datetime',
        ];
    }

    public function getResolvedRtspUrlAttribute(): string
    {
        if (! empty($this->rtsp_url)) {
            return $this->rtsp_url;
        }

        $host = $this->external_ip ?: $this->ip_address;
        $port = $this->forwarded_port ?: $this->port;
        $username = rawurlencode($this->username);
        $password = rawurlencode($this->password);

        return sprintf('rtsp://%s:%s@%s:%s/stream', $username, $password, $host, $port);
    }

    public function getStreamDirectoryAttribute(): string
    {
        return trim($this->stream_path ?: "streams/{$this->getKey()}", '/');
    }

    public function getStreamPlaylistAttribute(): string
    {
        return $this->stream_directory.'/index.m3u8';
    }
}
