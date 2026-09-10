<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['username', 'email', 'password', 'level'];
    protected $hidden   = ['password', 'remember_token'];

    // Override: gunakan 'username' untuk login bukan 'email'
    public function getAuthIdentifierName(): string { return 'username'; }
}
