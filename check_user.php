<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$user = App\Models\User::where('email', 'ventas@banapal.cl')->first();
echo $user ? $user->name . ' - ' . $user->roleLabel() : 'NOT FOUND';
echo "\n";
