<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$f = DB::table('fac_facturas')->where('factura_fiscal', 3599)->first();
if ($f) {
    echo "Factura Fiscal ID: " . $f->factura_fiscal_id . "\n";
    $items = DB::table('fac_facturas_items')->where('factura_fiscal_id', $f->factura_fiscal_id)->get();
    foreach ($items as $item) {
        echo "FacFacturaItem ID: " . $item->item_id . " - " . ($item->descripcion ?? 'No desc') . "\n";
    }
} else {
    echo "Factura 3599 no encontrada.\n";
}