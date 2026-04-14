<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Métodos de pago',
                href: '/admin/metodos-pago',
            },
        ],
    },
})

interface MetodoPagoRow {
    id: number
    nombre: string
    clave: string
    activo: boolean
    configuracion?: Record<string, unknown> | null
    created_at?: string | null
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
    store: string
    updateBase: string
    destroyBase: string
}

const props = defineProps<{
    metodos: Paginated<MetodoPagoRow>
    filters?: {
        search?: string
        status?: string
    }
    endpoints: Endpoints
}>()

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

const showForm = ref(false)
const editingId = ref<number | null>(null)
const modalTab = ref<'general' | 'config'>('general')

const form = useForm({
    nombre: '',
    clave: '',
    activo: true,
    configuracion_texto: '',
})

const isEditing = computed(() => editingId.value !== null)

const totalActivos = computed(() =>
    props.metodos.data.filter((item) => item.activo).length,
)

const totalInactivos = computed(() =>
    props.metodos.data.filter((item) => !item.activo).length,
)

const totalConfigurados = computed(() =>
    props.metodos.data.filter((item) => {
        const config = item.configuracion ?? {}
        return Object.keys(config).length > 0
    }).length,
)

const summaryText = computed(() => {
    if (!props.metodos.total) return 'No hay métodos de pago registrados aún.'

    const from = props.metodos.from ?? 1
    const to = props.metodos.to ?? props.metodos.data.length

    return `Mostrando ${from} - ${to} de ${props.metodos.total} métodos`
})

function formatDate(date?: string | null) {
    if (!date) return 'Sin registro'

    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date))
}

function prettifyJson(value: Record<string, unknown> | null | undefined) {
    if (!value || Object.keys(value).length === 0) return ''
    return JSON.stringify(value, null, 2)
}

function resetForm() {
    editingId.value = null
    modalTab.value = 'general'

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.nombre = ''
    form.clave = ''
    form.activo = true
    form.configuracion_texto = ''
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(metodo: MetodoPagoRow) {
    resetForm()

    editingId.value = metodo.id
    form.nombre = metodo.nombre ?? ''
    form.clave = metodo.clave ?? ''
    form.activo = !!metodo.activo
    form.configuracion_texto = prettifyJson(metodo.configuracion)

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

    if (!form.nombre.trim()) {
        form.setError('nombre', 'El nombre es obligatorio.')
    }

    if (!form.clave.trim()) {
        form.setError('clave', 'La clave es obligatoria.')
    }

    if (form.configuracion_texto.trim()) {
        try {
            JSON.parse(form.configuracion_texto)
        } catch {
            form.setError('configuracion_texto', 'La configuración debe ser un JSON válido.')
        }
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

function buildPayload() {
    let configuracion: Record<string, unknown> | null = null

    if (form.configuracion_texto.trim()) {
        configuracion = JSON.parse(form.configuracion_texto)
    }

    return {
        nombre: form.nombre,
        clave: form.clave,
        activo: form.activo ? 1 : 0,
        configuracion,
    }
}

function submit() {
    if (!validateBeforeSubmit()) return

    const commonOptions = {
        preserveScroll: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.nombre ||
                errors.clave ||
                errors.configuracion ||
                errors.configuracion_texto ||
                errors.general ||
                'Revisa la información del formulario.'

            errorAlert('No se pudo guardar', firstError)
        },
        onFinish: () => {
            form.transform((data) => data)
        },
    }

    if (isEditing.value && editingId.value) {
        form
            .transform(() => ({
                ...buildPayload(),
                _method: 'put',
            }))
            .post(`${props.endpoints.updateBase}/${editingId.value}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Método actualizado', 'El método de pago se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform(() => buildPayload())
        .post(props.endpoints.store, {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Método creado', 'El método de pago se registró correctamente.')
                })
            },
        })
}

async function destroyRow(id: number, nombre: string) {
    const result = await Swal.fire({
        title: '¿Eliminar método?',
        text: `Se eliminará "${nombre}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#334155',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
            confirmButton: 'rounded-xl font-semibold',
            cancelButton: 'rounded-xl font-semibold',
        },
    })

    if (!result.isConfirmed) return

    router.delete(`${props.endpoints.destroyBase}/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Método eliminado', 'El método de pago fue eliminado correctamente.')
        },
        onError: () => {
            errorAlert('Error', 'No se pudo eliminar el método de pago.')
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
    <Head title="Admin · Métodos de pago" />

    <div class="space-y-5 p-4 sm:p-6 lg:p-8">
        <section class="rounded-[26px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] shadow-sm">
            <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        Gestión de cobro
                    </p>

                    <h1 class="mt-2 text-[30px] font-black tracking-tight text-slate-900">
                        Métodos de pago
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        Define qué opciones de cobro estarán disponibles durante la compra.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C] hover:shadow-md"
                    @click="openCreate"
                >
                    + Nuevo método
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Activos</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalActivos }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Inactivos</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalInactivos }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Configurados</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalConfigurados }}</p>
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
                            placeholder="Nombre o clave..."
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
                            <option value="active">Activos</option>
                            <option value="inactive">Inactivos</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="px-5 py-4 sm:px-6">
                <p class="text-sm text-slate-500">{{ summaryText }}</p>
            </div>
        </section>

        <section
            v-if="metodos.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="metodo in metodos.data"
                :key="metodo.id"
                class="group overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg"
            >
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-5 transition-colors duration-300 group-hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Método</p>
                            <h3 class="mt-2 truncate text-lg font-black text-slate-900">{{ metodo.nombre }}</h3>
                        </div>

                        <span
                            class="rounded-full border px-3 py-1 text-[11px] font-bold"
                            :class="metodo.activo
                                ? 'border-[color:rgba(125,208,60,0.20)] bg-[color:rgba(125,208,60,0.10)] text-emerald-700'
                                : 'border-slate-300 bg-slate-100 text-slate-700'"
                        >
                            {{ metodo.activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Clave</p>
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ metodo.clave }}</p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Configuración</p>
                        <p class="mt-2 text-sm font-medium text-slate-900">
                            {{ metodo.configuracion && Object.keys(metodo.configuracion).length ? 'Configurado' : 'Sin configuración' }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Registro</p>
                        <p class="mt-2 text-sm font-medium text-slate-900">
                            {{ formatDate(metodo.created_at) }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.06)]"
                            @click="openEdit(metodo)"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-100"
                            @click="destroyRow(metodo.id, metodo.nombre)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-lg font-black text-slate-900">No hay métodos registrados aún</h2>
            <p class="mt-2 text-sm text-slate-500">Agrega el primero para empezar a configurar las formas de cobro.</p>
            <button
                type="button"
                class="mt-5 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C]"
                @click="openCreate"
            >
                Nuevo método
            </button>
        </section>

        <section
            v-if="metodos.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in metodos.links" :key="index">
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
                            class="flex h-[94dvh] w-full flex-col overflow-hidden rounded-t-[28px] bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-5xl sm:rounded-[28px]"
                        >
                            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] px-5 py-5 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                            {{ isEditing ? 'Edición' : 'Alta' }}
                                        </p>
                                        <h3 class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">
                                            {{ isEditing ? 'Editar método de pago' : 'Registrar método de pago' }}
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

                                <div class="mt-5 inline-flex rounded-2xl border border-slate-200 bg-slate-100 p-1 shadow-inner">
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200"
                                        :class="modalTab === 'general'
                                            ? 'bg-white text-slate-900 shadow-sm'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="modalTab = 'general'"
                                    >
                                        General
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200"
                                        :class="modalTab === 'config'
                                            ? 'bg-white text-slate-900 shadow-sm'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="modalTab = 'config'"
                                    >
                                        Configuración
                                    </button>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <form class="grid gap-0 lg:grid-cols-[1.08fr_0.92fr]" @submit.prevent="submit">
                                    <div class="order-2 p-5 sm:p-6 lg:order-1">
                                        <div v-show="modalTab === 'general'" class="space-y-5">
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre</label>
                                                <input
                                                    v-model="form.nombre"
                                                    type="text"
                                                    placeholder="Ej. Openpay tarjeta"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                                />
                                                <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.nombre }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Clave</label>
                                                <input
                                                    v-model="form.clave"
                                                    type="text"
                                                    placeholder="Ej. openpay_tarjeta"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                                />
                                                <p class="mt-2 text-xs text-slate-500">
                                                    Se recomienda usar una clave única y técnica para integraciones.
                                                </p>
                                                <p v-if="form.errors.clave" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.clave }}</p>
                                            </div>

                                            <label
                                                class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 transition-all duration-200 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.05)]"
                                            >
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-800">Método activo</p>
                                                    <p class="mt-1 text-xs text-slate-500">Si está activo podrá mostrarse en el flujo de compra.</p>
                                                </div>

                                                <input
                                                    v-model="form.activo"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-slate-300 text-[var(--brand-blue)] focus:ring-[color:rgba(48,190,239,0.20)]"
                                                />
                                            </label>
                                        </div>

                                        <div v-show="modalTab === 'config'" class="space-y-5">
                                            <div class="rounded-[22px] border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-sm font-semibold text-slate-800">
                                                    Configuración JSON
                                                </p>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    Aquí puedes guardar credenciales o parámetros simples del método, por ejemplo modo, comercio, cargos, llaves públicas, etc.
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Configuración</label>
                                                <textarea
                                                    v-model="form.configuracion_texto"
                                                    rows="12"
                                                    placeholder='{
  "modo": "sandbox",
  "comision": 0,
  "moneda": "MXN"
}'
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-green)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(125,208,60,0.12)]"
                                                />
                                                <p v-if="form.errors.configuracion_texto" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.configuracion_texto }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-1 border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                                Vista previa
                                            </p>

                                            <h4 class="mt-4 text-2xl font-black text-slate-900">
                                                {{ form.nombre || 'Nuevo método' }}
                                            </h4>

                                            <p class="mt-2 break-all text-sm text-slate-600">
                                                {{ form.clave || 'clave_metodo' }}
                                            </p>

                                            <div class="mt-5 flex flex-wrap gap-2">
                                                <span
                                                    class="rounded-full border px-3 py-1 text-xs font-semibold"
                                                    :class="form.activo
                                                        ? 'border-[color:rgba(125,208,60,0.20)] bg-[color:rgba(125,208,60,0.10)] text-emerald-700'
                                                        : 'border-slate-200 bg-slate-100 text-slate-600'"
                                                >
                                                    {{ form.activo ? 'Activo' : 'Inactivo' }}
                                                </span>

                                                <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                    {{ isEditing ? 'Edición' : 'Registro' }}
                                                </span>
                                            </div>

                                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Configuración</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-900">
                                                    {{ form.configuracion_texto.trim() ? 'JSON cargado' : 'Sin configuración' }}
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
                                                    {{
                                                        form.processing
                                                            ? 'Guardando...'
                                                            : isEditing
                                                                ? 'Actualizar método'
                                                                : 'Registrar método'
                                                    }}
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
