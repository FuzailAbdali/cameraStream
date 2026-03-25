<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_id',
        'file_path',
        'duration',
    ];

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'duration' => 'integer',
        ];
    }

    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }
}
