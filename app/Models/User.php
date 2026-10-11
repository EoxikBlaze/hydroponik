<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['username', 'email', 'password', 'level', 'is_demo'];

    protected $hidden = ['password', 'remember_token'];

    // JANGAN override getAuthIdentifierName() ke 'username' — Auth::id() akan
    // jadi string 'admin' → kolom sessions.user_id (BIGINT, MySQL strict)
    // menolaknya → session GAGAL tersimpan → login selalu loop logout.
    // Login tetap pakai username lewat Auth::attempt(['username'=>...]),
    // jadi override ini memang tidak diperlukan.

    public function isDemo(): bool
    {
        return (bool) $this->is_demo;
    }
}
