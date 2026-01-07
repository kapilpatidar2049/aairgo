<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Admin\Driver;

class Referral extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'referral_type',
        'referrer_amount',
        'new_amount',
        'status',
        'meta',
    ];

    protected $casts = [
        'referrer_amount' => 'float',
        'new_amount' => 'float',
        'meta' => 'array',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function referredDriver()
    {
        return $this->belongsTo(Driver::class, 'referred_user_id', 'user_id');
    }
}