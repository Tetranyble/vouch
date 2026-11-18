<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'webhook_url_id',
        'account_id',
        'record_id',
        'record_type',
        'event_type',
        'status',
        'response_code',
    ];

    public function webhookUrl()
    {
        return $this->belongsTo(WebhookUrl::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function record()
    {
        return $this->morphTo();
    }

    public function attempts()
    {
        return $this->hasMany(WebhookAttempt::class);
    }
}
