<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'users: '.App\Models\User::count()."\n";
echo 'plans: '.App\Models\Plan::count()."\n";
echo 'page sections: '.App\Models\PageSection::count()."\n";
echo 'knowledge: '.App\Models\KnowledgeEntry::count()."\n";
echo 'settings: '.App\Models\Setting::count()."\n";
echo 'storage link: '.(file_exists(__DIR__.'/public/storage') ? 'YES' : 'NO')."\n";
echo 'storage target: '.(is_link(__DIR__.'/public/storage') ? readlink(__DIR__.'/public/storage') : 'not a link')."\n";
echo 'app url: '.config('app.url')."\n";
echo 'env: '.config('app.env')."\n";
echo 'db: '.config('database.default')."\n";
echo 'showreel exists: '.(file_exists(__DIR__.'/storage/app/public/page-sections/cds-showreel.mp4') ? 'YES' : 'NO')."\n";
