<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_company', 'address_company', 'pic_name', 'pic_phone_number', 'pic_email'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function licenses()
    {
        return $this->hasManyThrough(License::class, Order::class);
    }
}
