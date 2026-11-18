<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'submitter_id',
        'account_id',
        'data',
        'event_type',
        'event_timestamp',
    ];

    protected $casts = [
        'data' => 'array',
        'event_timestamp' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function submitter()
    {
        return $this->belongsTo(Submitter::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
