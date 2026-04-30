<?php

namespace App\Services\Payments;

use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoEstatusHistorial;
use App\Models\TransaccionPasarela;
use App\Services\Payments\Data\PaymentChargeData;
use App\Services\Payments\Data\PaymentResultData;
use Illuminate\Support\Facades\DB;

class PaymentService {

    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
    ) {
    }

    public function charge(Pedido $pedido, Pago $pago, PaymentChargeData $data): PaymentResultData
    {
        $gateway = $this->gatewayManager->gateway();

        $pago->update([
            'estatus' => 'procesando',
        ]);

        TransaccionPasarela::create([
            'pago_id' => $pago->id,
            'pasarela' => $gateway->name(),
            'tipo' => 'cargo_intento',
            'transaccion_id' => null,
            'payload' => [
                'amount' => $data->amount,
                'currency' => $data->currency,
                'description' => $data->description,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'customer_phone' => $data->customerPhone,
            ],
            'respuesta' => null,
        ]);

        $result = $gateway->charge($pago, $data);

        DB::transaction(function () use ($pedido, $pago, $gateway, $result) {
            $pago->update([
                'estatus' => $result->status,
                'referencia_externa' => $result->externalReference,
                'autorizacion' => $result->authorization,
                'respuesta_pasarela' => $result->rawResponse,
                'pagado_en' => $result->successful && $result->status === 'aprobado'
                    ? now()
                    : null,
            ]);

            TransaccionPasarela::create([
                'pago_id' => $pago->id,
                'pasarela' => $gateway->name(),
                'tipo' => 'cargo_respuesta',
                'transaccion_id' => $result->externalReference,
                'payload' => null,
                'respuesta' => $result->rawResponse,
            ]);

            if ($result->successful && $result->status === 'aprobado') {
                $pedido->update([
                    'estatus' => 'pagado',
                    'pagado_en' => now(),
                ]);

                PedidoEstatusHistorial::create([
                    'pedido_id' => $pedido->id,
                    'user_id' => auth()->id(),
                    'estatus' => 'pagado',
                    'comentario' => 'Pago aprobado por '.$gateway->name().'.',
                ]);
            }
        });

        return $result;
    }

}
