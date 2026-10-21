<?php

/**
 * Diagnostic: verify every icon referenced by SimmakMenu resolves to a Blade view.
 * Usage: php scripts/check_icons.php  (run inside the app container)
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$icons = collect(\App\Support\SimmakMenu::items())
    ->pluck('icon')
    ->unique()
    ->values()
    ->all();

$missing = [];
foreach ($icons as $icon) {
    $exists = view()->exists('partials.icons.'.$icon);
    echo str_pad($icon, 20).' : '.($exists ? 'OK' : 'MISSING').PHP_EOL;
    if (! $exists) {
        $missing[] = $icon;
    }
}

echo PHP_EOL;
if ($missing) {
    echo 'MISSING ICONS: '.implode(', ', $missing).PHP_EOL;
    exit(1);
}
echo 'All '.count($icons).' menu icons resolved successfully.'.PHP_EOL;
exit(0);
