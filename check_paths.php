<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Formacion;
use App\Models\Experiencia;

echo "Formaciones PDF paths:\n";
foreach (Formacion::whereNotNull('archivo_pdf')->take(5)->get() as $f) {
    echo "- [" . $f->archivo_pdf . "]\n";
}

echo "\nExperiencias PDF paths:\n";
foreach (Experiencia::whereNotNull('archivo_pdf')->take(5)->get() as $e) {
    echo "- [" . $e->archivo_pdf . "]\n";
}
