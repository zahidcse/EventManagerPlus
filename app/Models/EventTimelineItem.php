<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventTimelineItem extends Model
{
    protected $table = 'event_timeline_items';

    protected $fillable = [
        'event_id',
        'sort_order',
        'time_label',
        'title',
        'description',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
