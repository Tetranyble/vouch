<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
