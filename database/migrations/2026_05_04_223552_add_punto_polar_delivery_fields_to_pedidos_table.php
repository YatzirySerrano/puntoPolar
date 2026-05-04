<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->string('tipo_entrega', 30)
                ->default('recoleccion')
                ->after('estatus');

            $table->string('codigo_recoleccion', 30)
                ->nullable()
                ->after('tipo_entrega');

            $table->timestamp('listo_para_recoger_en')
                ->nullable()
                ->after('pagado_en');

            $table->timestamp('fecha_entrega_programada')
                ->nullable()
                ->after('listo_para_recoger_en');

            $table->timestamp('salio_a_entrega_en')
                ->nullable()
                ->after('fecha_entrega_programada');

            $table->string('zona_entrega', 120)
                ->nullable()
                ->after('salio_a_entrega_en');

            $table->text('instrucciones_entrega')
                ->nullable()
                ->after('zona_entrega');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_entrega',
                'codigo_recoleccion',
                'listo_para_recoger_en',
                'fecha_entrega_programada',
                'salio_a_entrega_en',
                'zona_entrega',
                'instrucciones_entrega',
            ]);
        });
    }
};