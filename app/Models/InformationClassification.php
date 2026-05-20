<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformationClassification extends Model
{
    protected $fillable = ['name', 'level'];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}