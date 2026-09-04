<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'canonical_name', 'slug'];

    public function getDisplayNameAttribute(): string
    {
        return $this->canonical_name ?: $this->name;
    }

    public function degreePrograms(): HasMany
    {
        return $this->hasMany(DegreeProgram::class);
    }
}
