<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskLogs extends Model
{
    use HasFactory,HasUuids;
    use  HasUuids;

        protected $fillable = [
        'referral_point',
        'twitter_point',
        'earning_point',
        'status',
    ];
}