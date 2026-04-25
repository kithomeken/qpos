<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethods extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'surcharge_type',
        'surcharge_value',
        'active',
        'primary_method'
    ];
}
