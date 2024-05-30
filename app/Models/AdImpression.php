<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdImpression extends Model
{
    use HasFactory;
    protected $fillable = [
        'campaign_id',
        'advertiser_code',
        'impression_id',
        'device_type',
        'device_os',
        'ip_addr',
        'country',
                
    ];
    protected $casts = [
      'created_at' => "datetime:d F Y - h:i:s",
    ];
}