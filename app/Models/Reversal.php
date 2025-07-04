<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reversal extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'reversed_by_user_id',
        'reason',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function reversedByUser()
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }
}
