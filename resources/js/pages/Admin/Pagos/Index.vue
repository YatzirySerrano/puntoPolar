<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { index as pagosIndex } from '@/routes/admin/pagos'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pagos',
                 href: pagosIndex().url,
            },
        ],
    },
})

interface PagoRow {
    id: number
    estatus: string
    monto: number | string
    moneda?: string | null
    referencia_externa?: string | null
    autorizacion?: string | null
    pagado_en?: string | null
    created_at?: string | null
    pedido?: { folio: string } | null
    metodo_pago?: { nombre: string } | null
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface Paginated<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    from?: number | null
    to?: number | null
    links: PaginationLink[]
}

interface Endpoints {
    index: string
    updateBase: string
}

const props = defineProps<{
    pagos: Paginated<PagoRow>
    filters?: {
        search?: string
        status?: string
    }
    endpoints: Endpoints
    estatusOptions: string[]
}>()

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

const showForm = ref(false)
const editingId = ref<number | null>(null)

const form = useForm({
    estatus: '',
    referencia_externa: '',
    autorizacion: '',
    pagado_en: '',
})

const totalAprobados = computed(() =>
    props.pagos.data.filter((item) => String(item.estatus).toLowerCase() === 'aprobado').length,
)

const totalPendientes = computed(() =>
    props.pagos.data.filter((item) => String(item.estatus).toLowerCase() === 'pendiente').length,
)

const totalRechazados = computed(() =>
    props.pagos.data.filter((item) => String(item.estatus).toLowerCase() === 'rechazado').length,
)

const summaryText = computed(() => {
    if (!props.pagos.total) return 'No hay pagos registrados aún.'

    const from = props.pagos.from ?? 1
    const to = props.pagos.to ?? props.pagos.data.length

    return `Mostrando ${from} - ${to} de ${props.pagos.total} pagos`
})

function formatMoney(amount: number | string, moneda?: string | null) {
    const numeric = Number(amount ?? 0)

    try {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: moneda || 'MXN',
        }).format(numeric)
    } catch {
        return `$${numeric.toFixed(2)}`
    }
}

function formatDate(date?: string | null) {
    if (!date) return 'Sin registro'

    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date))
}

function formatForInput(date?: string | null) {
    if (!date) return ''
    const parsed = new Date(date)
    const offset = parsed.getTimezoneOffset()
    const local = new Date(parsed.getTime() - offset * 60000)
    return local.toISOString().slice(0, 16)
}

function statusBadgeClass(estatus: string) {
    const value = String(estatus).toLowerCase()

    if (value === 'aprobado') {
        return 'border-[color:rgba(125,208,60,0.20)] bg-[color:rgba(125,208,60,0.10)] text-emerald-700'
    }

    if (value === 'pendiente') {
        return 'border-amber-200 bg-amber-50 text-amber-700'
    }

    if (value === 'rechazado' || value === 'cancelado') {
        return 'border-red-200 bg-red-50 text-red-600'
    }

    return 'border-slate-300 bg-slate-100 text-slate-700'
}

function resetForm() {
    editingId.value = null
    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.estatus = ''
    form.referencia_externa = ''
    form.autorizacion = ''
    form.pagado_en = ''
}

function openEdit(pago: PagoRow) {
    resetForm()

    editingId.value = pago.id
    form.estatus = pago.estatus ?? ''
    form.referencia_externa = pago.referencia_externa ?? ''
    form.autorizacion = pago.autorizacion ?? ''
    form.pagado_en = formatForInput(pago.pagado_en)

    showForm.value = true
}

function closeForm(force = false) {
    if (form.processing && !force) return
    showForm.value = false
    resetForm()
}

function onEscape(event: KeyboardEvent) {
    if (event.key === 'Escape' && showForm.value && !form.processing) {
        closeForm()
    }
}

onMounted(() => {
    window.addEventListener('keydown', onEscape)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})

function validateBeforeSubmit() {
    form.clearErrors()

    if (!form.estatus.trim()) {
        form.setError('estatus', 'El estatus es obligatorio.')
    }

    return Object.keys(form.errors).length === 0
}

function successAlert(title: string, text: string) {
    Swal.fire({
        icon: 'success',
        title,
        text,
        timer: 1400,
        showConfirmButton: false,
        background: '#ffffff',
        color: '#111827',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
        },
    })
}

function errorAlert(title: string, text: string) {
    Swal.fire({
        icon: 'error',
        title,
        text,
        confirmButtonText: 'Entendido',
        background: '#ffffff',
        color: '#111827',
        confirmButtonColor: '#111827',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
            confirmButton: 'rounded-xl font-semibold',
        },
    })
}

function submit() {
    if (!editingId.value) return
    if (!validateBeforeSubmit()) return

    form
        .transform((data) => ({
            ...data,
            _method: 'put',
            pagado_en: data.pagado_en || null,
        }))
        .post(`${props.endpoints.updateBase}/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Pago actualizado', 'El pago se actualizó correctamente.')
                })
            },
            onError: (errors: Record<string, string>) => {
                const firstError =
                    errors.estatus ||
                    errors.referencia_externa ||
                    errors.autorizacion ||
                    errors.pagado_en ||
                    errors.general ||
                    'Revisa la información del formulario.'

                errorAlert('No se pudo guardar', firstError)
            },
            onFinish: () => {
                form.transform((data) => data)
            },
        })
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch([search, status], () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
                status: status.value !== 'all' ? status.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }, 350)
})
</script>

<template>
    <Head title="Admin · Pagos" />

    <div class="space-y-5 p-4 sm:p-6 lg:p-8">

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Aprobados</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalAprobados }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Pendientes</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalPendientes }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Rechazados</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalRechazados }}</p>
            </article>
        </section>

        <section class="rounded-[26px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px]">
                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Buscar</span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Pedido, método, referencia o estatus..."
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Estado</span>
                        <select
                            v-model="status"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                        >
                            <option value="all">Todos</option>
                            <option v-for="item in estatusOptions" :key="item" :value="item">
                                {{ item }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="px-5 py-4 sm:px-6">
                <p class="text-sm text-slate-500">{{ summaryText }}</p>
            </div>
        </section>

        <section
            v-if="pagos.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="row in pagos.data"
                :key="row.id"
                class="group overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg"
            >
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-5 transition-colors duration-300 group-hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Pago</p>
                            <h3 class="mt-2 truncate text-lg font-black text-slate-900">
                                #{{ row.id }}
                            </h3>
                        </div>

                        <span
                            class="rounded-full border px-3 py-1 text-[11px] font-bold"
                            :class="statusBadgeClass(row.estatus)"
                        >
                            {{ row.estatus }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Monto</p>
                        <p class="mt-2 text-lg font-black text-slate-900">
                            {{ formatMoney(row.monto, row.moneda) }}
                        </p>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Pedido</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">
                                {{ row.pedido?.folio || 'N/A' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Método</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">
                                {{ row.metodo_pago?.nombre || 'N/A' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Referencia</p>
                            <p class="mt-2 break-all text-sm font-medium text-slate-900">
                                {{ row.referencia_externa || 'Sin referencia' }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.06)]"
                        @click="openEdit(row)"
                    >
                        Editar pago
                    </button>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-lg font-black text-slate-900">No hay pagos registrados aún</h2>
            <p class="mt-2 text-sm text-slate-500">Cuando existan cobros registrados aparecerán aquí.</p>
        </section>

        <section
            v-if="pagos.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in pagos.links" :key="index">
                <component
                    :is="link.url ? 'a' : 'span'"
                    :href="link.url || undefined"
                    v-html="link.label"
                    class="inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm transition-all duration-200"
                    :class="link.active
                        ? 'border-slate-300 bg-slate-100 text-slate-900'
                        : link.url
                            ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'"
                />
            </template>
        </section>

        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showForm" class="fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-[3px]">
                <div class="flex h-dvh items-end justify-center sm:items-center sm:p-4">
                    <Transition
                        appear
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-5 opacity-0 sm:translate-y-0 sm:scale-[0.985]"
                        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-to-class="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-[0.99]"
                    >
                        <div
                            v-if="showForm"
                            class="flex h-[94dvh] w-full flex-col overflow-hidden rounded-t-[28px] bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-4xl sm:rounded-[28px]"
                        >
                            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] px-5 py-5 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                            Edición
                                        </p>
                                        <h3 class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">
                                            Editar pago
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg text-slate-500 transition-all duration-200 hover:rotate-90 hover:bg-slate-100 hover:text-slate-800"
                                        @click="closeForm()"
                                    >
                                        ×
                                    </button>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <form class="grid gap-0 lg:grid-cols-[1.05fr_0.95fr]" @submit.prevent="submit">
                                    <div class="order-2 space-y-5 p-5 sm:p-6 lg:order-1">
                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Estatus</label>
                                            <select
                                                v-model="form.estatus"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                            >
                                                <option value="" disabled>Selecciona un estatus</option>
                                                <option v-for="item in estatusOptions" :key="item" :value="item">
                                                    {{ item }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.estatus" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.estatus }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Referencia externa</label>
                                            <input
                                                v-model="form.referencia_externa"
                                                type="text"
                                                placeholder="Referencia del proveedor o comprobante"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Autorización</label>
                                            <input
                                                v-model="form.autorizacion"
                                                type="text"
                                                placeholder="Código de autorización"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                            />
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Pagado en</label>
                                            <input
                                                v-model="form.pagado_en"
                                                type="datetime-local"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:border-[var(--brand-green)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(125,208,60,0.12)]"
                                            />
                                        </div>
                                    </div>

                                    <div class="order-1 border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                                Vista previa
                                            </p>

                                            <h4 class="mt-4 text-2xl font-black text-slate-900">
                                                {{ form.estatus || 'Sin estatus' }}
                                            </h4>

                                            <div class="mt-5 flex flex-wrap gap-2">
                                                <span
                                                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                                                    :class="statusBadgeClass(form.estatus || 'pendiente')"
                                                >
                                                    {{ form.estatus || 'pendiente' }}
                                                </span>
                                            </div>

                                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Referencia</p>
                                                <p class="mt-2 break-all text-sm font-semibold text-slate-900">
                                                    {{ form.referencia_externa || 'Sin referencia' }}
                                                </p>
                                            </div>

                                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Autorización</p>
                                                <p class="mt-2 break-all text-sm font-semibold text-slate-900">
                                                    {{ form.autorizacion || 'Sin autorización' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-3 lg:col-span-2">
                                        <div class="sticky bottom-0 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur sm:px-6">
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50"
                                                    @click="closeForm()"
                                                >
                                                    Cancelar
                                                </button>

                                                <button
                                                    type="submit"
                                                    :disabled="form.processing"
                                                    class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {{ form.processing ? 'Guardando...' : 'Actualizar pago' }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </div>
</template>
