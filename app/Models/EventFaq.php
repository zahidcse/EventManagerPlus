<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventFaq extends Model
{
    protected $table = 'event_faqs';

    protected $fillable = [
        'event_id',
        'sort_order',
        'question',
        'answer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
