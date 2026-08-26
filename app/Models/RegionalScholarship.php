<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegionalScholarship extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'body_name',
        'description',
        'website_url',
        'source_url',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
        ];
    }
}
