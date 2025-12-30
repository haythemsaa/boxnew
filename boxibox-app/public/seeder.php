<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    $seeder = new \Database\Seeders\DatabaseSeeder();
    $seeder->__invoke(new \Database\Seeders\DatabaseSeeder());
    echo "✅ Database seeding completed successfully!";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
