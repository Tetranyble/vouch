<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_event_id',
        'response_body',
        'response_status_code',
        'attempt',
    ];

    protected $casts = [
        'response_status_code' => 'integer',
        'attempt' => 'integer',
    ];

    public function webhookEvent()
    {
        return $this->belongsTo(WebhookEvent::class);
    }
}
