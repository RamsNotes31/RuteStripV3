<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockchainRegistryLog extends Model
{
    protected $fillable = [
        'gpx_version_id',
        'operation',
        'status',
        'network',
        'contract_address',
        'tx_hash',
        'registered_by',
        'block_number',
        'gas_used',
        'message',
        'logged_at',
    ];

    protected $casts = [
        'block_number' => 'integer',
        'gas_used' => 'integer',
        'logged_at' => 'datetime',
    ];

    public function gpxVersion()
    {
        return $this->belongsTo(GpxVersion::class);
    }
}
