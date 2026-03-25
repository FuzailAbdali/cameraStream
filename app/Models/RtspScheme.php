<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RtspScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scheme_template',
    ];

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }
}
