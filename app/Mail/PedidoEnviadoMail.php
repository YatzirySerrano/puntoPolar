<?php

namespace App\Mail;

use App\Models\Pedido;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PedidoEnviadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Pedido $pedido;

    public ?string $urlRastreo;

    public function __construct(Pedido $pedido)
    {
        $this->pedido = $pedido->loadMissing(['items', 'direccion']);
        $this->urlRastreo = $this->resolverUrlRastreo(
            $this->pedido->paqueteria,
            $this->pedido->numero_guia
        );
    }

    public function build(): self
    {
        return $this
            ->subject('Tu pedido '.$this->pedido->folio.' está en camino')
            ->view('emails.pedidos.enviado')
            ->with([
                'pedido' => $this->pedido,
                'urlRastreo' => $this->urlRastreo,
            ]);
    }

    private function resolverUrlRastreo(?string $paqueteria, ?string $guia): ?string
    {
        if (! $paqueteria || ! $guia) {
            return null;
        }

        $carrier = Str::of($paqueteria)
            ->lower()
            ->ascii()
            ->toString();

        $guia = urlencode(trim($guia));

        if (str_contains($carrier, 'dhl')) {
            return "https://www.dhl.com/mx-es/home/tracking/tracking-express.html?submit=1&tracking-id={$guia}";
        }

        if (str_contains($carrier, 'estafeta')) {
            return "https://www.estafeta.com/Herramientas/Rastreo?trackingNumber={$guia}";
        }

        if (str_contains($carrier, 'fedex')) {
            return "https://www.fedex.com/fedextrack/?trknbr={$guia}";
        }

        if (str_contains($carrier, 'redpack')) {
            return "https://www.redpack.com.mx/rastreo/?guias={$guia}";
        }

        if (str_contains($carrier, 'ups')) {
            return "https://www.ups.com/track?tracknum={$guia}";
        }

        return null;
    }
}
