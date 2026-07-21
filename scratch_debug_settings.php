<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = \DB::table('pagebuilder_settings')->get();
foreach ($settings as $s) {
    echo "Setting key: {$s->setting} = {$s->value}\n";
}
