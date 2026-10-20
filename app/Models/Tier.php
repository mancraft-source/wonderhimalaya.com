<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Service level: Standard, Premium, Luxury. */
class Tier extends Model
{
    protected $guarded = ['id'];

    public function trips(): BelongsToMany
    {
        return $this->belongsToMany(Trip::class);
    }
}
