<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $table = 'ROLES';
    protected $primaryKey = 'IdRole';
    public $timestamps = true;

    protected $fillable = [
        'Role',
        'Type'
    ];
}
