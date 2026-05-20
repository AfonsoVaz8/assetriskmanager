<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskClassification extends Model
{
    protected $fillable = ['name', 'score'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}