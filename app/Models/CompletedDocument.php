<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompletedDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'submitter_id',
        'filename',
        'storage_path',
        'attachment_uuid',
        'filesize',
        'sha256',
    ];

    public function submitter()
    {
        return $this->belongsTo(Submitter::class);
    }
}
