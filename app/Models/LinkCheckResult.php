<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Last-known reachability of an external URL referenced somewhere in the
 * catalog. Written by App\Console\Commands\CheckLinks; read by the admin
 * data-freshness widget and the Broken Links resource.
 */
class LinkCheckResult extends Model
{
    protected $fillable = ['url', 'url_hash', 'status_code', 'ok', 'error', 'source', 'checked_at'];

    protected function casts(): array
    {
        return [
            'ok' => 'boolean',
            'status_code' => 'integer',
            'checked_at' => 'datetime',
        ];
    }

    public function scopeBroken(Builder $query): Builder
    {
        return $query->where('ok', false);
    }
}
