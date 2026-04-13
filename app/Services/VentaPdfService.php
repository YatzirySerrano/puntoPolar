<?php

namespace App\Services;

use App\Models\Pedido;
use Barryvdh\DomPDF\Facade\Pdf;

class VentaPdfService
{
    public function generar(Pedido $pedido): string
    {
        $pdf = Pdf::loadView('pdf.nota-venta', [
            'pedido' => $pedido->loadMissing('items'),
        ]);

        $path = storage_path('app/public/notas/nota-'.$pedido->folio.'.pdf');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }
}
