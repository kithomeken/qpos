<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPSTORM_META\map;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $appends = ['total_item'];

    public function products()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getTotalItemAttribute()
    {
        return $this->products()->sum('quantity');
    }

    public static function generateOrderReference()
    {
        // Characters that are easy to read (Excluded: I, L, 1, 0, O)
        $characters = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $finalCode = '';

        // Generate a random 13-character string from our clean set
        for ($i = 0; $i < 13; $i++) {
            $finalCode .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Ensure Uniqueness (Recursion)
        if (self::where('reference_no', $finalCode)->exists()) {
            return self::generateOrderReference();
        }

        return $finalCode;
    }
}
