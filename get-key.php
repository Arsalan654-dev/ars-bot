<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\User::where('role', 'super_admin')->first();
echo 'admin api key: '.$admin->api_key."\n";
echo 'bot serving: '.($admin->botIsServing() ? 'YES' : 'NO')."\n";
echo 'ai model: '.App\Models\Setting::get('ai_model')."\n";
echo 'ai key set: '.(App\Models\Setting::get('ai_api_key') ? 'YES' : 'NO')."\n";
