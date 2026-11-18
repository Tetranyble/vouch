<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentGenerationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'submitter_id',
        'event_name',
    ];

    public function submitter()
    {
        return $this->belongsTo(Submitter::class);
    }
}
