<?php

// app/Models/WebhookHit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookHit extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event',
        'payload',
        'headers',
        'ip',
        'received_at',
        'job_id',         // correlation UUID we generate
        'job_driver_id',  // runtime id from queue driver (when available)
        'job_status',
        'job_attempts',
        'handler',
        'processed_at',
        'job_result',
        'job_response',
    ];

    protected $casts = [
        'payload'      => 'array',
        'headers'      => 'array',
        'received_at'  => 'datetime',
        'processed_at' => 'datetime',
        'job_response' => 'array',
    ];
}
