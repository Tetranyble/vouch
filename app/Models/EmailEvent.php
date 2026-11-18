<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'emailable_type',
        'emailable_id',
        'message_id',
        'tag',
        'event_type',
        'email',
        'data',
        'event_datetime',
    ];

    protected $casts = [
        'data' => 'array',
        'event_datetime' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function emailable()
    {
        return $this->morphTo();
    }
}
