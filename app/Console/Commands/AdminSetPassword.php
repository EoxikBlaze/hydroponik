<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('admin:set-password {username} {password}')]
#[Description('Ganti password user dari CLI — dipakai via Cron Jobs di hosting (tanpa login web)')]
class AdminSetPassword extends Command
{
    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $password = (string) $this->argument('password');

        if (strlen($password) < 6) {
            $this->error('Password minimal 6 karakter.');

            return self::FAILURE;
        }

        $user = User::where('username', $username)->first();
        if (! $user) {
            $this->error("User '{$username}' tidak ditemukan.");

            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);
        $this->info("Password user '{$username}' berhasil diperbarui.");

        return self::SUCCESS;
    }
}
