<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Instruction extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'description', 'actif'];

    protected $casts = ['actif' => 'boolean'];
}
