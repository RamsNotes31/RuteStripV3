<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpfsUploadLog extends Model
{
    protected $fillable = [
        'gpx_version_id',
        'operation',
        'status',
        'cid',
        'url',
        'duration_ms',
        'message',
        'logged_at',
    ];

    protected $casts = [
        'duration_ms' => 'integer',
        'logged_at' => 'datetime',
    ];

    public function gpxVersion()
    {
        return $this->belongsTo(GpxVersion::class);
    }
}
