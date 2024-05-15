<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionPoint extends Model
{
    use HasFactory, HasUuids;
    use  HasUuids;

    protected $fillable = [
        'user_id',
        'balance',
        'verified_tweets',
        'last_tweet',
        'last_kyc_earning',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

}
