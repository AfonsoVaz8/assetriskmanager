<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualReport extends Model
{
    use HasFactory;

    // Isto permite-nos criar o registo na base de dados automaticamente
    protected $fillable = [
        'year',
        'file_path',
        'type'
    ];
}