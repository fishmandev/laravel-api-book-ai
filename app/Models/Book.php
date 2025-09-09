<?php

namespace App\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static BookFactory factory()
 */
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description'
    ];
}
