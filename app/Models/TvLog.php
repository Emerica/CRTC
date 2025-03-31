<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TvLog extends Model {
    protected $table = 'tv_logs'; // Match the table name

    // Disable timestamps if you don’t need Laravel to manage created_at/updated_at
    public $timestamps = true;

    // Define all fillable fields to allow mass assignment
    protected $fillable = [
        'log_format',
        'program_class',
        'affiliation_type',
        'call_sign',
        'log_date',
        'start_time',
        'end_time',
        'duration',
        'program_title',
        'program_sub_title',
        'producer1',
        'producer2',
        'production_number',
        'special_attention',
        'origin',
        'timecredits',
        'exhibition',
        'production_source',
        'target_audience',
        'categories',
        'accessible_programming',
        'dubbing_credit',
        'ethnic_program',
    ];

    // Optionally, cast fields to specific types
    protected $casts = [
        'duration' => 'string', // Keep as string (HHMMSS) for now
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}