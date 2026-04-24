<?php

namespace BevoMedia\MediaEnhancer\Models;

use Illuminate\Database\Eloquent\Model;

class MediaJob extends Model
{
    protected $fillable = [
        'uuid',
        'type',
        'status',
        'input_path',
        'output_path',
        'metadata',
        'error_message',
        'completed_at'
    ];
}