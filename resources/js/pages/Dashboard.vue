<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  ArrowUpRight,
  Boxes,
  ClipboardList,
  CreditCard,
  MapPin,
  PackageCheck,
  ShoppingCart,
  Truck,
  UserRound,
  Wallet,
  ShieldCheck,
} from 'lucide-vue-next'
import { dashboard } from '@/routes'

defineOptions({
  layout: {
    breadcrumbs: [
      {
        title: 'Inicio',
        href: dashboard(),
      },
    ],
  },
})

interface SummaryCard {
  label: string
  value: string | number
  hint: string
}

interface QuickAction {
  title: string
  description: string
  href: string
}

interface PanelItem {
  label: string
  value: string | number
}

interface Panel {
  title: string
  items: PanelItem[]
}

interface DashboardStats {
  resumen: SummaryCard[]
  quickActions: QuickAction[]
  panels: Panel[]
}

interface AuthUser {
  id: number
  name: string
  email: string
  rol?: string
}

interface SharedPageProps {
  auth?: {
    user?: AuthUser | null
  }
  stats: DashboardStats
}

const page = usePage<SharedPageProps>()

const authUser = computed(() => page.props.auth?.user ?? null)
const stats = computed(() => page.props.stats)

const role = computed(() => authUser.value?.rol ?? 'cliente')

const roleLabel = computed(() => {
  if (role.value === 'admin') return 'Administrador'
  if (role.value === 'vendedor') return 'Vendedor'
  return 'Cliente'
})

const heroTitle = computed(() => {
  if (role.value === 'admin') return 'Panel principal'
  if (role.value === 'vendedor') return 'Seguimiento operativo'
  return 'Mi cuenta'
})

const heroDescription = computed(() => {
  if (role.value === 'admin') {
    return 'Resumen general del negocio con foco en pedidos, pagos y operación.'
  }
  if (role.value === 'vendedor') {
    return 'Vista clara para preparar, enviar y cerrar pedidos.'
  }
  return 'Consulta tus pedidos, direcciones y accesos rápidos.'
})

const firstPanel = computed(() => stats.value.panels?.[0] ?? null)
const secondPanel = computed(() => stats.value.panels?.[1] ?? null)

function numericValue(value: string | number): number {
  if (typeof value === 'number') return value
  const clean = String(value).replace(/[^\d.-]/g, '')
  const parsed = Number(clean)
  return Number.isFinite(parsed) ? parsed : 0
}

function iconForAction(title: string) {
  const key = title.toLowerCase()

  if (key.includes('producto')) return Boxes
  if (key.includes('pedido')) return ClipboardList
  if (key.includes('pago')) return Wallet
  if (key.includes('método') || key.includes('metodo')) return CreditCard
  if (key.includes('direcci')) return MapPin
  if (key.includes('carrito')) return ShoppingCart
  if (key.includes('catálogo') || key.includes('catalogo')) return PackageCheck

  if (role.value === 'admin') return ShieldCheck
  if (role.value === 'vendedor') return Truck
  return UserRound
}

const summaryMax = computed(() => {
  const values = stats.value.resumen.map((item) => numericValue(item.value))
  return Math.max(...values, 1)
})

function summaryWidth(value: string | number) {
  const n = numericValue(value)
  if (n <= 0) return '0%'
  return `${Math.min(Math.max((n / summaryMax.value) * 100, 12), 100)}%`
}

const firstValues = computed(() => (firstPanel.value?.items ?? []).map((item) => numericValue(item.value)))
const firstPanelMax = computed(() => Math.max(...firstValues.value, 1))
const hasFirstChartData = computed(() => firstValues.value.some((n) => n > 0))

const secondValues = computed(() => (secondPanel.value?.items ?? []).map((item) => numericValue(item.value)))
const secondPanelMax = computed(() => Math.max(...secondValues.value, 1))

function widthPercent(value: string | number, max: number) {
  const n = numericValue(value)
  if (n <= 0) return '0%'
  return `${Math.min(Math.max((n / Math.max(max, 1)) * 100, 10), 100)}%`
}

const linePoints = computed(() => {
  const items = firstPanel.value?.items ?? []
  if (!items.length) return ''

  const values = items.map((item) => numericValue(item.value))
  const max = Math.max(...values, 1)
  const step = items.length === 1 ? 100 : 100 / (items.length - 1)

  return values
    .map((value, index) => {
      const x = index * step
      const y = value <= 0 ? 100 : 100 - (value / max) * 78
      return `${x},${y}`
    })
    .join(' ')
})

const areaPoints = computed(() => {
  if (!linePoints.value) return ''
  return `0,100 ${linePoints.value} 100,100`
})

function actionTone(index: number) {
  const tones = [
    'bg-slate-50 border-slate-200',
    'bg-zinc-50 border-zinc-200',
    'bg-neutral-50 border-neutral-200',
    'bg-stone-50 border-stone-200',
  ]
  return tones[index % tones.length]
}

function chipTone(index: number) {
  const tones = [
    'bg-slate-900 text-white',
    'bg-zinc-800 text-white',
    'bg-neutral-800 text-white',
    'bg-stone-700 text-white',
  ]
  return tones[index % tones.length]
}
</script>

<template>
  <Head title="Dashboard" />

  <main class="flex flex-1 flex-col gap-5 bg-[#f6f7f9] p-4 sm:p-6 lg:p-8">
    <section class="rounded-[28px] border border-slate-200 bg-white px-6 py-6 shadow-[0_16px_40px_rgba(15,23,42,0.045)]">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <header class="max-w-3xl">
          <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-[11px] font-black uppercase tracking-[0.2em] text-slate-700">
            {{ roleLabel }}
          </span>

          <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 sm:text-5xl">
            {{ heroTitle }}
          </h1>

          <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base">
            {{ heroDescription }}
          </p>
        </header>

      </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="(card, index) in stats.resumen"
        :key="card.label"
        class="rounded-[24px] border border-slate-200 bg-white px-5 py-5 shadow-[0_12px_30px_rgba(15,23,42,0.035)]"
      >
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
          {{ card.label }}
        </p>

        <p class="mt-3 text-3xl font-black tracking-tight text-slate-900">
          {{ card.value }}
        </p>

        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
          <div
            v-if="numericValue(card.value) > 0"
            class="h-full rounded-full bg-slate-900"
            :style="{ width: summaryWidth(card.value) }"
          />
        </div>

        <p class="mt-4 text-sm leading-6 text-slate-500">
          {{ card.hint }}
        </p>
      </article>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.05fr_0.95fr]">
      <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_16px_40px_rgba(15,23,42,0.045)]">
        <header class="flex items-center justify-between gap-3">
          <div>
            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
              Accesos rápidos
            </p>
            <h2 class="mt-2 text-2xl font-black text-slate-900">
              Atajos principales
            </h2>
          </div>
        </header>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
          <Link
            v-for="(action, index) in stats.quickActions"
            :key="action.title"
            :href="action.href"
            class="group rounded-[22px] border px-5 py-5 transition-all duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-[0_16px_36px_rgba(15,23,42,0.055)]"
            :class="actionTone(index)"
          >
            <div class="flex items-start justify-between gap-4">
              <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm">
                <component :is="iconForAction(action.title)" class="h-5 w-5" />
              </span>

              <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-900 text-white transition-all duration-300 group-hover:translate-x-1 group-hover:-translate-y-1">
                <ArrowUpRight class="h-4 w-4" />
              </span>
            </div>

            <h3 class="mt-5 text-lg font-black text-slate-900">
              {{ action.title }}
            </h3>

            <p class="mt-2 text-sm leading-6 text-slate-600">
              {{ action.description }}
            </p>
          </Link>
        </div>
      </article>

      <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_16px_40px_rgba(15,23,42,0.045)]">
        <header>
          <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
            Gráfica
          </p>
          <h2 class="mt-2 text-2xl font-black text-slate-900">
            {{ firstPanel?.title || 'Panorama principal' }}
          </h2>
        </header>

        <div v-if="hasFirstChartData" class="mt-6">
          <div class="rounded-[22px] border border-slate-200 bg-slate-50 p-4">
            <svg
              viewBox="0 0 100 100"
              preserveAspectRatio="none"
              class="h-[230px] w-full"
            >
              <defs>
                <linearGradient id="lineFillSoft" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="rgba(15,23,42,0.18)" />
                  <stop offset="100%" stop-color="rgba(15,23,42,0.02)" />
                </linearGradient>
              </defs>

              <polygon
                :points="areaPoints"
                fill="url(#lineFillSoft)"
              />

              <polyline
                :points="linePoints"
                fill="none"
                stroke="rgba(15,23,42,0.95)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>

          <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article
              v-for="(item, index) in firstPanel?.items ?? []"
              :key="item.label"
              class="rounded-[18px] border border-slate-200 px-4 py-4"
              :class="numericValue(item.value) > 0 ? 'bg-white' : 'bg-slate-50'"
            >
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-semibold text-slate-600">
                  {{ item.label }}
                </p>
                <span
                  class="inline-flex min-w-9 items-center justify-center rounded-full px-2.5 py-1 text-xs font-black"
                  :class="numericValue(item.value) > 0 ? chipTone(index) : 'bg-slate-200 text-slate-500'"
                >
                  {{ item.value }}
                </span>
              </div>
            </article>
          </div>
        </div>

        <div
          v-else
          class="mt-6 rounded-[22px] border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center"
        >
          <p class="text-sm font-medium text-slate-500">
            Aún no hay datos suficientes para mostrar esta gráfica.
          </p>
        </div>
      </article>
    </section>

    <article class="rounded-[28px] border border-slate-200 bg-white p-6 shadow-[0_16px_40px_rgba(15,23,42,0.045)]">
      <header>
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-400">
          Distribución
        </p>
        <h2 class="mt-2 text-2xl font-black text-slate-900">
          {{ secondPanel?.title || 'Indicadores' }}
        </h2>
      </header>

      <div v-if="secondPanel?.items?.length" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="(item, index) in secondPanel.items"
          :key="item.label"
          class="rounded-[22px] border border-slate-200 bg-slate-50 px-4 py-4"
        >
          <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-slate-700">
              {{ item.label }}
            </p>
            <p class="text-2xl font-black text-slate-900">
              {{ item.value }}
            </p>
          </div>

          <div class="mt-4 h-2 overflow-hidden rounded-full bg-white">
            <div
              v-if="numericValue(item.value) > 0"
              class="h-full rounded-full bg-slate-900"
              :style="{ width: widthPercent(item.value, secondPanelMax) }"
            />
          </div>
        </article>
      </div>
    </article>
  </main>
</template>
