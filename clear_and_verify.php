<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

echo "Clearing config...\n";
Artisan::call('config:clear');
echo "Config cleared.\n";

echo "Current allowed_origins:\n";
print_r(Config::get('cors.allowed_origins'));
