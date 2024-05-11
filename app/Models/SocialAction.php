<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'proof_img'

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id', 'user_id');
    }
}
