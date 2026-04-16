<script setup lang="ts">
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

type Active = 'inicio' | ''
type NavItem = { key: Active; href: string; label: string; icon: string }

const page = usePage()
const year = new Date().getFullYear()

const activeKey = computed<Active>(() => {
  const url = (page.url || '').toLowerCase()
  if (url === '/' || url.startsWith('/index')) return 'inicio'
  return ''
})

const isActive = (key: Active, href: string) => {
  const url = (page.url || '').toLowerCase()
  if (key) return key === activeKey.value
  return href !== '/' ? url.startsWith(href) : url === '/'
}

const icons = {
  home: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10.5 12 3l9 7.5"/><path d="M6.5 10v10.5h11V10"/></svg>`,
  layers: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 4.5-9 4.5L3 7.5 12 3Z"/><path d="m3 12 9 4.5 9-4.5"/><path d="m3 16.5 9 4.5 9-4.5"/></svg>`,
  sparkles: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l1.2 4.2L17 7.4l-3.8 1.2L12 13l-1.2-4.4L7 7.4l3.8-1.2L12 2z"/><path d="M19 13l.7 2.3 2.3.7-2.3.7L19 19l-.7-2.3-2.3-.7 2.3-.7L19 13z"/></svg>`,
  package: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8.25v7.5a2.25 2.25 0 0 1-1.125 1.95l-6.75 3.9a2.25 2.25 0 0 1-2.25 0l-6.75-3.9A2.25 2.25 0 0 1 3 15.75v-7.5a2.25 2.25 0 0 1 1.125-1.95l6.75-3.9a2.25 2.25 0 0 1 2.25 0l6.75 3.9A2.25 2.25 0 0 1 21 8.25Z"/><path d="M3.3 7.07 12 12l8.7-4.93"/><path d="M12 22V12"/></svg>`,
  phone: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.32 1.78.59 2.64a2 2 0 0 1-.45 2.11L8 9.91a16 16 0 0 0 6.09 6.09l1.44-1.25a2 2 0 0 1 2.11-.45c.86.27 1.74.47 2.64.59A2 2 0 0 1 22 16.92z"/></svg>`,
  mail: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>`,
  facebook: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.3l.4-2.7h-2.7V9.6c0-.8.2-1.3 1.4-1.3H16V5.9c-.2 0-.9-.1-1.8-.1-1.8 0-3 1.1-3 3.2v2.3H9v2.7h2.4v7h2.1Z"/></svg>`,
  instagram: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5h.01"/></svg>`,
  shield: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
  file: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>`,
} as const

const siteLinks = computed<NavItem[]>(() => [
  { key: 'inicio', label: 'Inicio', href: '/', icon: icons.home },
  { key: '', label: 'Categorías', href: '/#catalogo-categorias', icon: icons.layers },
  { key: '', label: 'Destacados', href: '/#productos-destacados', icon: icons.sparkles },
  { key: '', label: 'Productos', href: '/productos', icon: icons.package },
])

const legalLinks = [
  { label: 'Términos y condiciones', href: '/terminos-y-condiciones', icon: icons.file },
  { label: 'Aviso de privacidad', href: '/aviso-de-privacidad', icon: icons.shield },
]

const socialLinks = [
  { label: 'Facebook', href: 'https://www.facebook.com/people/Mr-Lana/61552688275964/', icon: icons.facebook },
  { label: 'Instagram', href: 'https://www.instagram.com/mr.lana_mx/', icon: icons.instagram },
]

const UI = {
  footer: 'w-full text-slate-900 bg-white',
  pad: 'px-6 lg:px-10 xl:px-14 2xl:px-16',
  h: 'text-[12px] font-black tracking-[0.24em] uppercase text-slate-700/80',
  pill: (active: boolean) =>
    [
      'group inline-flex w-full items-center gap-3 rounded-2xl px-4 py-3',
      'text-[14px] xl:text-[15px] font-semibold',
      'bg-white/80 ring-1 ring-black/5 shadow',
      'transition',
      'hover:-translate-y-[2px] hover:shadow-md',
      'hover:ring-cyan-400/40 hover:shadow-[0_16px_46px_-30px_rgba(34,211,238,0.55)]',
      'focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60',
      active && 'ring-2 ring-emerald-400/55',
    ].filter(Boolean).join(' '),
  icon: 'h-[18px] w-[18px] opacity-90 transition group-hover:opacity-100',
  underline:
    'underline decoration-transparent underline-offset-4 transition group-hover:decoration-emerald-500/70',
  legal:
    'group inline-flex w-full md:w-auto items-center gap-3 rounded-2xl px-3 py-2 text-[14px] font-semibold ' +
    'whitespace-nowrap text-slate-800 hover:text-slate-950 hover:bg-white/65 ring-1 ring-transparent ' +
    'transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400/60',
  legalU:
    'relative after:absolute after:-bottom-0.5 after:left-0 after:h-[2px] after:w-0 ' +
    'after:bg-gradient-to-r after:from-cyan-500 after:via-emerald-500 after:to-lime-400 ' +
    'after:transition-all after:duration-300 group-hover:after:w-full',
}
</script>

<template>
  <footer :class="UI.footer" role="contentinfo">
    <div class="relative">
      <img
        src="/img/footer/mobile-footer.webp"
        class="block w-full select-none object-cover"
        loading="lazy"
        alt="Mr. Lana"
        draggable="false"
      />
    </div>

    <section :class="[UI.pad, 'py-3']" aria-label="Footer">
      <div class="grid grid-cols-1 gap-10 md:grid-cols-12 md:gap-12">
        <!-- Contacto -->
        <address class="not-italic min-w-0 md:col-span-6 lg:col-span-4 xl:col-span-3">
          <div class="mt-4 space-y-4">
            <a
              href="tel:+527773791464"
              class="group inline-flex items-center gap-3 text-[15px] font-semibold text-slate-900 transition hover:text-emerald-600"
            >
              <span class="h-[18px] w-[18px] text-emerald-500" v-html="icons.phone" aria-hidden="true" />
              <span>(777) 422-5973</span>
            </a>
            <br>
            <a
              href="mailto:atencion@mr-lana.com"
              class="group inline-flex items-center gap-3 text-[15px] font-semibold text-slate-900 transition hover:text-cyan-600"
            >
              <span class="h-[18px] w-[18px] text-pink-500" v-html="icons.mail" aria-hidden="true" />
              <span>atencion@mr-lana.com</span>
            </a>
          </div>

          <p class="mt-4 text-sm font-semibold text-slate-800">
            Servicio al cliente:
            <span class="ml-2 text-slate-950">LUN–SAB: 10:00am a 7:00pm</span>
          </p>
        </address>

        <!-- Enlaces -->
        <nav class="min-w-0 md:col-span-6 lg:col-span-8 xl:col-span-9" aria-label="Sitio">
          <div :class="UI.h">Enlaces del sitio</div>

          <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 2xl:grid-cols-4">
            <li v-for="l in siteLinks" :key="l.href">
              <Link :href="l.href" :class="UI.pill(isActive(l.key, l.href))">
                <span :class="UI.icon" v-html="l.icon" aria-hidden="true" />
                <span :class="UI.underline">{{ l.label }}</span>
                <span class="ml-auto opacity-50 transition group-hover:opacity-100" aria-hidden="true">↗</span>
              </Link>
            </li>
          </ul>
        </nav>

        <!-- Redes -->
        <section class="min-w-0 md:col-span-12 xl:col-span-6">
          <div :class="UI.h">Síguenos en</div>

          <ul class="mt-3 flex flex-col gap-3 md:flex-row md:flex-wrap">
            <li v-for="s in socialLinks" :key="s.label">
              <a
                :href="s.href"
                target="_blank"
                rel="noopener noreferrer"
                class="group inline-flex items-center gap-3 rounded-2xl px-3 py-2 text-[14px] font-semibold text-slate-800 transition hover:bg-white/65 hover:text-slate-950"
              >
                <span class="h-[18px] w-[18px]" v-html="s.icon" aria-hidden="true" />
                <span class="relative after:absolute after:-bottom-0.5 after:left-0 after:h-[2px] after:w-0 after:bg-gradient-to-r after:from-cyan-500 after:via-emerald-500 after:to-lime-400 after:transition-all after:duration-300 group-hover:after:w-full">
                  {{ s.label }}
                </span>
              </a>
            </li>
          </ul>
        </section>

        <!-- Legal -->
        <section class="min-w-0 md:col-span-12 xl:col-span-6">
          <div :class="UI.h">Legal</div>

          <ul class="mt-3 flex flex-col gap-2 md:flex-row md:flex-wrap md:gap-3">
            <li v-for="l in legalLinks" :key="l.href">
              <Link :href="l.href" :class="UI.legal">
                <span class="h-[18px] w-[18px] opacity-90" v-html="l.icon" aria-hidden="true" />
                <span :class="UI.legalU">{{ l.label }}</span>
                <span class="ml-auto opacity-50 transition group-hover:opacity-100" aria-hidden="true">↗</span>
              </Link>
            </li>
          </ul>
        </section>
      </div>

      <div class="mt-10 border-t border-black/10 pt-4 text-center">
        <p class="text-xs font-semibold text-slate-600">
          © {{ year }} Mr. Lana. Todos los derechos reservados.
        </p>
      </div>
    </section>
  </footer>
</template>
