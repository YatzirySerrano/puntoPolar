<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- SEO --}}
        <meta name="description" content="Punto Polar: agua purificada y hielo disponibles 24/7. Despachador automático en Jiutepec, Morelos. Agua en garrafón 20 L, 10 L, 4 L. Hielo en bolsa 5 kg y 3 kg.">
        <meta name="keywords" content="agua purificada, hielo en bolsa, venta de hielo, despachador de agua, Punto Polar, agua y hielo 24/7, hielo Jiutepec, agua purificada Morelos, garrafón de agua, agua 20 litros">
        <meta name="robots" content="index, follow">
        <meta name="author" content="Punto Polar">
        <meta name="theme-color" content="#062A5E">

        {{-- Open Graph --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="Punto Polar">
        <meta property="og:title" content="Punto Polar · Agua Purificada y Hielo 24/7">
        <meta property="og:description" content="Despachador automático de agua purificada y hielo en Jiutepec, Morelos. Disponible 24/7. Agua en garrafón y hielo en bolsa. Contáctanos por WhatsApp.">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('og-punto-polar.PNG') }}">
        <meta property="og:image:secure_url" content="{{ asset('og-punto-polar.PNG') }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Punto Polar - Agua purificada y hielo 24/7">
        <meta property="og:locale" content="es_MX">

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="Punto Polar · Agua Purificada y Hielo 24/7">
        <meta name="twitter:description" content="Despachador automático de agua purificada y hielo en Jiutepec, Morelos. Disponible 24/7. Agua en garrafón y hielo en bolsa.">
        <meta name="twitter:image" content="{{ asset('og-punto-polar.PNG') }}">
        <meta name="twitter:image:alt" content="Punto Polar - Agua purificada y hielo 24/7">

        {{-- Schema.org LocalBusiness --}}
        @php
        $schemaOrg = [
            '@context' => 'https://schema.org',
            '@type'    => 'Store',
            'name'        => 'Punto Polar',
            'description' => 'Despachador automático de agua purificada y hielo en bolsa. Disponible 24/7 en Jiutepec, Morelos.',
            'telephone'   => '+527771148125',
            'email'       => 'contactopuntopolar@gmail.com',
            'image' => asset('og-punto-polar.PNG'),
            'url'   => config('app.url'),
            'priceRange' => '$',
            'address' => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Privada Chilpancingo #20',
                'addressLocality' => 'Vicente Guerrero, Jiutepec',
                'addressRegion'   => 'Morelos',
                'addressCountry'  => 'MX',
            ],
            'hasMap' => 'https://maps.app.goo.gl/PXmUr6LGAF76msMc8',
            'openingHoursSpecification' => [
                '@type'      => 'OpeningHoursSpecification',
                'dayOfWeek'  => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
                'opens'      => '00:00',
                'closes'     => '23:59',
            ],
            'sameAs' => [
                'https://www.facebook.com/profile.php?id=61578482788839',
                'https://www.instagram.com/puntopolar.hieloagua/',
                'https://www.tiktok.com/@punto.polar.hielo',
            ],
        ];
        @endphp
        <script type="application/ld+json">{!! json_encode($schemaOrg, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        {{-- Favicons --}}
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" href="{{ asset('favicon.PNG') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.PNG') }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
