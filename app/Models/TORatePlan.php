<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TORatePlan extends Model
{
    use HasFactory;

    protected $connection = "tradersonly";
    protected $table = "new_rate_plans";
}
