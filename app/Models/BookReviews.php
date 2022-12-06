<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookReviews extends Model
{
    use HasFactory;

    protected $table = "book_reviews";

    protected $fillable = [
        'id',
        'comment',
        'edited',
        'book_id',
        'user_id'
    ];

    public $timestamps = false;

    public function book(){
        return $this->belongsTo(Book::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
