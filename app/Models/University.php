<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class University extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'city', 'region', 'website_url', 'description', 'logo'];

    public function degreePrograms(): HasMany
    {
        return $this->hasMany(DegreeProgram::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
