<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Snapshot extends Model
{
    protected $fillable = [
        'balance',
        'note',
        'created_at',
        'updated_at'
    ];
}
