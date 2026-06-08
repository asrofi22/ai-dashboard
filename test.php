<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\EtlConnection;

$conn = EtlConnection::where('name', 'db_warehouse_localgueh')->first();
if ($conn) {
    echo "Connection Name: {$conn->name}\n";
    echo "Driver: {$conn->driver}\n";
    echo "Type: {$conn->type}\n";
    echo "Config: " . json_encode($conn->config) . "\n";
    echo "Tables: " . count($conn->metadata['tables'] ?? []) . "\n";
} else {
    echo "Connection db_warehouse_localgueh not found!\n";
}
