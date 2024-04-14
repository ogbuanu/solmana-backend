<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionPoint extends Model
{
    use HasFactory,HasUuids;
    use  HasUuids;

        protected $fillable = [
        'user_id',
        'token_for',
        'code',
        'status',
        'expires_at',
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
