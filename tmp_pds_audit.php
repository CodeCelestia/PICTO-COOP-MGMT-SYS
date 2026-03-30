<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$rows = DB::table('pds_submissions')->whereNotNull('c1_data')->orWhereNotNull('c2_data')->orWhereNotNull('c3_data')->orWhereNotNull('c4_data')->limit(10)->get();
foreach ($rows as $r) {
    echo '--- id '.$r->id."\n";
    echo 'c1=' . json_encode(json_decode($r->c1_data, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    echo 'c2=' . json_encode(json_decode($r->c2_data, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    echo 'c3=' . json_encode(json_decode($r->c3_data, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
    echo 'c4=' . json_encode(json_decode($r->c4_data, true), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
}
