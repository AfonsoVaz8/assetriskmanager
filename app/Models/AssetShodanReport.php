<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetShodanReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'ip_address',
        'open_ports',
        'vulnerabilities',
        'last_seen_at',
        'synced_at',
        'status',
        'error',
        'raw_payload',
        'normalized_payload',
    ];

    protected $casts = [
        'open_ports' => 'array',
        'vulnerabilities' => 'array',
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'last_seen_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
