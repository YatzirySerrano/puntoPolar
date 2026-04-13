<script setup lang="ts">
import logo from '@/img/logo.png';
</script>

<template>
    <footer id="contacto" class="relative mt-16 overflow-hidden bg-[#efefef]">
        <div
            class="absolute bottom-0 -left-16 h-64 w-64 rounded-tr-[140px] bg-gradient-to-br from-[var(--brand-green)] to-[var(--brand-blue)] opacity-90"
        />
        <div
            class="mx-auto grid w-full max-w-[1500px] gap-10 px-4 py-14 md:grid-cols-4 md:px-8"
        >
            <div class="relative z-10 md:col-span-1">
                <img :src="logo" alt="Mr. Lana" class="h-16 w-auto" />
            </div>

            <div
                id="sucursales"
                class="relative z-10 space-y-2 text-sm text-neutral-600"
            >
                <h3 class="text-base font-black text-neutral-900">
                    Sucursales
                </h3>
                <p>Huamantla, Tlaxcala</p>
                <p>Puebla, Puebla</p>
                <p>CDMX (Próximamente)</p>
            </div>

            <div class="relative z-10 space-y-2 text-sm text-neutral-600">
                <h3 class="text-base font-black text-neutral-900">
                    Contáctanos
                </h3>
                <p>Atención a clientes</p>
                <p>+52 (777) 379 1464</p>
                <p>+52 (247) 472 2975</p>
                <p>LUN-SAB: 10:00 am a 8:00 pm</p>
                <p>DOM: 10:00 am a 6:00 pm</p>
                <p>atencion@mr-lana.com</p>
            </div>

            <div class="relative z-10 space-y-2 text-sm text-neutral-600">
                <h3 class="text-base font-black text-neutral-900">
                    Síguenos en
                </h3>
                <p class="transition hover:text-[var(--brand-blue)]">
                    Facebook
                </p>
                <p class="transition hover:text-[var(--brand-blue)]">
                    Instagram
                </p>
                <div class="pt-4">
                    <p class="transition hover:text-[var(--brand-blue)]">
                        Términos y Condiciones
                    </p>
                    <p class="transition hover:text-[var(--brand-blue)]">
                        Política de Privacidad
                    </p>
                </div>
            </div>
        </div>
    </footer>
</template>
