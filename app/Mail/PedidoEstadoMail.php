<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PedidoEstadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Pedido $pedido;

    public string $tipo;

    public function __construct(Pedido $pedido, string $tipo)
    {
        $this->pedido = $pedido->loadMissing(['items', 'direccion', 'pagos.metodoPago']);
        $this->tipo = $tipo;
    }

    public function build(): self
    {
        return $this
            ->subject($this->correoSubject())
            ->view('emails.pedidos.estado')
            ->with([
                'pedido' => $this->pedido,
                'tipo' => $this->tipo,
                'contenido' => $this->contenido(),
            ]);
    }

    private function correoSubject(): string
    {
        return match ($this->tipo) {
            'pagado' => 'Recibimos tu pago · Pedido '.$this->pedido->folio,
            'preparando' => 'Estamos preparando tu pedido '.$this->pedido->folio,
            'listo_para_recoger' => 'Tu pedido '.$this->pedido->folio.' está listo para recoger',
            'salio_a_entrega' => 'Tu pedido '.$this->pedido->folio.' salió a entrega',
            'entregado' => 'Tu pedido '.$this->pedido->folio.' fue entregado',
            default => 'Actualización de tu pedido '.$this->pedido->folio,
        };
    }

    private function contenido(): array
    {
        return match ($this->tipo) {
            'pagado' => [
                'eyebrow' => 'Pago confirmado',
                'titulo' => 'Tu pago fue recibido correctamente',
                'mensaje' => 'Gracias por tu compra. Ya registramos el pago de tu pedido y pronto comenzaremos con la preparación.',
                'badge' => 'Pagado',
            ],
            'preparando' => [
                'eyebrow' => 'Pedido en preparación',
                'titulo' => 'Estamos preparando tu pedido',
                'mensaje' => 'Tu compra ya se encuentra en proceso de preparación. Te avisaremos cuando esté listo.',
                'badge' => 'Preparando',
            ],
            'listo_para_recoger' => [
                'eyebrow' => 'Listo para recoger',
                'titulo' => 'Tu pedido ya está listo para recoger',
                'mensaje' => 'Tu pedido está listo. Puedes acudir a Punto Polar y presentar tu código de recolección.',
                'badge' => 'Listo para recoger',
            ],
            'salio_a_entrega' => [
                'eyebrow' => 'Entrega local',
                'titulo' => 'Tu pedido salió a entrega',
                'mensaje' => 'Tu pedido ya salió a entrega local. Nuestro equipo se encargará de llevarlo a la dirección registrada.',
                'badge' => 'Salió a entrega',
            ],
            'entregado' => [
                'eyebrow' => 'Pedido entregado',
                'titulo' => 'Tu pedido fue entregado',
                'mensaje' => 'El pedido aparece como entregado. Gracias por comprar en Punto Polar.',
                'badge' => 'Entregado',
            ],
            default => [
                'eyebrow' => 'Actualización',
                'titulo' => 'Tu pedido fue actualizado',
                'mensaje' => 'Tenemos una actualización sobre tu pedido.',
                'badge' => 'Actualizado',
            ],
        };
    }
}