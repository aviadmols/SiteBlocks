<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:reset-password {email} {password}', function (string $email, string $password) {
    $user = User::where('email', $email)->first();
    if (! $user) {
        $this->error("User not found: {$email}");
        return 1;
    }
    $user->password = $password;
    $user->save();
    $this->info("Password updated for {$email}");
    return 0;
})->purpose('Reset password for a user by email (e.g. php artisan user:reset-password aviadmols@gmail.com 987654321)');
