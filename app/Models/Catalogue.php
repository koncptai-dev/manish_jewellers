<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalogue extends Model
{
    use HasFactory;
    protected $table = 'catalogues';

    protected $fillable = [
        'brand_id',
        'name',
    ];

    // Relationship: A catalogue belongs to a brand
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

}
