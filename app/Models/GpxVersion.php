<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GpxVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'hiking_route_id',
        'version_number',
        'gpx_file_path',
        'file_hash',
        'verification_status',
        'change_log',
        'uploaded_by',
        'is_active',
        'ipfs_cid',
        'ipfs_url',
        'ipfs_status',
        'blockchain_tx_hash',
        'blockchain_status',
        'blockchain_network',
        'blockchain_contract_address',
        'blockchain_registered_by',
        'blockchain_registered_at',
        'blockchain_block_number',
        'blockchain_gas_used',
        'blockchain_confirmation_status',
        'blockchain_block_timestamp',
        'blockchain_receipt_checked_at',
        'ipfs_upload_time_ms',
        'ipfs_retrieval_time_ms',
        'verified_at',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'ipfs_upload_time_ms'    => 'integer',
        'ipfs_retrieval_time_ms' => 'integer',
        'verified_at'            => 'datetime',
        'blockchain_registered_at' => 'datetime',
        'blockchain_block_timestamp' => 'datetime',
        'blockchain_receipt_checked_at' => 'datetime',
    ];

    public function route()
    {
        return $this->belongsTo(HikingRoute::class, 'hiking_route_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verificationLogs()
    {
        return $this->hasMany(VerificationLog::class);
    }

    public function ipfsUploadLogs()
    {
        return $this->hasMany(IpfsUploadLog::class);
    }

    public function blockchainRegistryLogs()
    {
        return $this->hasMany(BlockchainRegistryLog::class);
    }

    public function getShortHashAttribute(): string
    {
        return substr($this->file_hash, 0, 12);
    }

    public function getVerificationStatusExplanationAttribute(): string
    {
        return match ($this->verification_status) {
            'verified' => 'File GPX cocok dengan hash tersimpan dan belum terdeteksi berubah.',
            'invalid' => 'Isi file GPX berbeda dari hash tersimpan sehingga perlu ditinjau.',
            'verification_failed' => 'Sistem belum berhasil memeriksa file GPX karena kendala teknis.',
            default => 'Status pemeriksaan file GPX belum tersedia.',
        };
    }

    public function getIpfsStatusExplanationAttribute(): string
    {
        return match ($this->ipfs_status) {
            'uploaded_ipfs' => 'Salinan GPX sudah tersedia di IPFS dan memiliki CID.',
            'failed_ipfs' => 'Upload atau retrieval IPFS gagal dan perlu dicoba ulang.',
            'pending_ipfs' => 'GPX masih menunggu upload ke IPFS.',
            default => 'Status IPFS belum tersedia.',
        };
    }

    public function getBlockchainStatusExplanationAttribute(): string
    {
        return match ($this->blockchain_status) {
            'registered_blockchain' => 'Metadata minimal GPX sudah tercatat di blockchain.',
            'failed_blockchain' => 'Registrasi atau pengecekan blockchain gagal dan perlu dicoba ulang.',
            'pending_blockchain' => 'Metadata GPX masih menunggu registrasi blockchain.',
            default => 'Status blockchain belum tersedia.',
        };
    }
}
