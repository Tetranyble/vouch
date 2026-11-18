<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'record_type',
        'record_id',
        'account_id',
        'tsvector',
        'ngram',
    ];

    public function record()
    {
        return $this->morphTo();
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
