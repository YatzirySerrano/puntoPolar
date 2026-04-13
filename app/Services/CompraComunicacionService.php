<?php

namespace App\Services;

use App\Mail\CompraExitosaMail;
use App\Models\Pedido;
use Illuminate\Support\Facades\Mail;

class CompraComunicacionService
{
    public function __construct(private VentaPdfService $pdfService)
    {
    }

    public function enviarConfirmacion(Pedido $pedido): void
    {
        Mail::to($pedido->correo_cliente)->send(new CompraExitosaMail($pedido));
    }

    public function generarNotaVenta(Pedido $pedido): string
    {
        return $this->pdfService->generar($pedido);
    }
}
