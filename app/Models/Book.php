<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Book extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'author',
        'isbn',
        'stock'
    ];

    // Relasi: satu buku memiliki satu kategori
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Relasi: satu buku dapat memiliki banyak transaksi peminjaman
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}
