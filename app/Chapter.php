<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class Chapter extends Model
{
    protected $table = 'chapters';
    protected $primaryKey = 'id';
    public $timestamps = false;



}
