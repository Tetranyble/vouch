<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'url',
        'events',
        'sha1',
        'secret',
    ];

    protected $casts = [
        'events' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }
}
