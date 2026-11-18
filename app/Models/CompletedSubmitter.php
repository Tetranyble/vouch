<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletedSubmitter extends Model
{
    use HasFactory;

    protected $fillable = [
        'submitter_id',
        'submission_id',
        'account_id',
        'template_id',
        'source',
        'sms_count',
        'completed_at',
        'verification_method',
        'is_first',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'sms_count' => 'integer',
        'is_first' => 'boolean',
    ];

    public function submitter()
    {
        return $this->belongsTo(Submitter::class);
    }

    public function submission()
    {
        return $this->belongsTo(Submission::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
