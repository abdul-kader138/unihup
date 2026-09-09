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
        'amount_min',
        'amount_max',
        'isee_threshold',
        'website_url',
        'source_url',
        'last_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_verified_at' => 'datetime',
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'isee_threshold' => 'decimal:2',
        ];
    }
}
