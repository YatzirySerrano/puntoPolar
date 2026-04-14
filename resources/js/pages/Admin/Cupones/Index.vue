<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useCurrency } from '@/composables/useCurrency'
import { index as cuponesIndex } from '@/routes/admin/cupones'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Cupones',
                href: cuponesIndex().url,
            },
        ],
    },
})

interface CuponRow {
    id: number
    codigo: string
    nombre: string
    tipo: string
    valor: number | string
    activo: boolean
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
    cupones: Paginated<CuponRow>
    tipos: string[]
    filters?: {
        search?: string
        status?: string
        tipo?: string
    }
    endpoints: Endpoints
}>()

const { formatCurrency } = useCurrency()

const showForm = ref(false)
const editingId = ref<number | null>(null)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')
const tipoFilter = ref(props.filters?.tipo ?? 'all')

const form = useForm({
    codigo: '',
    nombre: '',
    tipo: '',
    valor: 0,
    activo: true,
})

const isEditing = computed(() => editingId.value !== null)

const generalError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>
    return errors.general ?? ''
})

const totalActivos = computed(() =>
    props.cupones.data.filter((item) => item.activo).length,
)

const totalInactivos = computed(() =>
    props.cupones.data.filter((item) => !item.activo).length,
)

const totalPorcentaje = computed(() =>
    props.cupones.data.filter((item) => String(item.tipo).toLowerCase().includes('por')).length,
)

const summaryText = computed(() => {
    if (!props.cupones.total) return 'No hay cupones registrados aún.'

    const from = props.cupones.from ?? 1
    const to = props.cupones.to ?? props.cupones.data.length

    return `Mostrando ${from} - ${to} de ${props.cupones.total} cupones`
})

function resetForm() {
    editingId.value = null

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.codigo = ''
    form.nombre = ''
    form.tipo = ''
    form.valor = 0
    form.activo = true
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(cupon: CuponRow) {
    resetForm()

    editingId.value = cupon.id
    form.codigo = cupon.codigo ?? ''
    form.nombre = cupon.nombre ?? ''
    form.tipo = cupon.tipo ?? ''
    form.valor = Number(cupon.valor ?? 0)
    form.activo = !!cupon.activo

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
        confirmButtonColor: '#11142C',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
            confirmButton: 'rounded-xl font-semibold',
        },
    })
}

function validateBeforeSubmit() {
    form.clearErrors()

    if (!form.codigo.trim()) {
        form.setError('codigo', 'El código es obligatorio.')
    }

    if (!form.nombre.trim()) {
        form.setError('nombre', 'El nombre es obligatorio.')
    }

    if (!form.tipo.trim()) {
        form.setError('tipo', 'El tipo es obligatorio.')
    }

    if (Number(form.valor) < 0) {
        form.setError('valor', 'El valor no puede ser menor a 0.')
    }

    return Object.keys(form.errors).length === 0
}

function submit() {
    const wasEditing = isEditing.value
    const id = editingId.value

    if (wasEditing && !id) return
    if (!validateBeforeSubmit()) return

    const commonOptions = {
        preserveScroll: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.codigo ||
                errors.nombre ||
                errors.tipo ||
                errors.valor ||
                errors.general ||
                'Revisa la información del formulario.'

            errorAlert('No se pudo guardar', firstError)
        },
        onFinish: () => {
            form.transform((data) => data)
        },
    }

    if (wasEditing && id) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
                activo: data.activo ? 1 : 0,
            }))
            .post(`${props.endpoints.updateBase}/${id}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Cupón actualizado', 'El cupón se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform((data) => ({
            ...data,
            activo: data.activo ? 1 : 0,
        }))
        .post(props.endpoints.store, {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Cupón creado', 'El cupón se registró correctamente.')
                })
            },
        })
}

async function destroyRow(id: number, codigo: string) {
    const result = await Swal.fire({
        title: '¿Eliminar cupón?',
        text: `Se eliminará "${codigo}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#11142C',
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
            successAlert('Cupón eliminado', 'El cupón fue eliminado correctamente.')
        },
        onError: () => {
            errorAlert('Error', 'No se pudo eliminar el cupón.')
        },
    })
}

function formatCouponValue(tipo: string, valor: number | string) {
    const numericValue = Number(valor ?? 0)
    const lower = String(tipo).toLowerCase()

    if (lower.includes('por')) {
        return `${numericValue}%`
    }

    return formatCurrency(numericValue)
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch([search, status, tipoFilter], () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
                status: status.value !== 'all' ? status.value : undefined,
                tipo: tipoFilter.value !== 'all' ? tipoFilter.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }, 350)
})

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})
</script>

<template>
    <Head title="Admin · Cupones" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <section class="overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm">
            <div class="border-b border-neutral-200 px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Buscar</span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Código, nombre o tipo..."
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            />
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Estado</span>
                            <select
                                v-model="status"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="all">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Tipo</span>
                            <select
                                v-model="tipoFilter"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="all">Todos</option>
                                <option
                                    v-for="tipo in tipos"
                                    :key="tipo"
                                    :value="tipo"
                                >
                                    {{ tipo }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="lg:ml-auto inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:brightness-95"
                        @click="openCreate"
                    >
                        + Nuevo cupón
                    </button>
                </div>
            </div>

            <div class="grid gap-3 px-4 py-4 sm:grid-cols-3 sm:px-6 xl:grid-cols-4">
                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Cupones visibles</p>
                    <p class="text-2xl font-black">{{ cupones.data.length }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Activos</p>
                    <p class="text-2xl font-black">{{ totalActivos }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Inactivos</p>
                    <p class="text-2xl font-black">{{ totalInactivos }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Porcentaje</p>
                    <p class="text-2xl font-black">{{ totalPorcentaje }}</p>
                </article>
            </div>

            <div class="px-4 pb-4 sm:px-6">
                <p class="text-sm text-neutral-500">
                    {{ summaryText }}
                </p>
            </div>
        </section>

        <section
            v-if="cupones.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="cupon in cupones.data"
                :key="cupon.id"
                class="group overflow-hidden rounded-[24px] border border-neutral-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
            >
                <div class="border-b border-neutral-200 bg-neutral-50 px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-400">
                                Código
                            </p>
                            <h3 class="mt-2 break-words text-lg font-black text-neutral-900">
                                {{ cupon.codigo }}
                            </h3>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-[11px] font-bold shadow-sm"
                            :class="cupon.activo
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-neutral-200 text-neutral-600'"
                        >
                            {{ cupon.activo ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <p class="text-sm font-black text-neutral-900">
                            {{ cupon.nombre }}
                        </p>
                        <p class="mt-2 text-sm text-neutral-500">
                            Tipo: <span class="font-semibold text-neutral-700">{{ cupon.tipo }}</span>
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[#11142C] px-4 py-4 text-white">
                        <p class="text-xs uppercase tracking-wide text-white/60">Valor</p>
                        <p class="mt-1 text-2xl font-black">
                            {{ formatCouponValue(cupon.tipo, cupon.valor) }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition-colors duration-200 hover:bg-sky-100"
                            @click="openEdit(cupon)"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-colors duration-200 hover:bg-red-100"
                            @click="destroyRow(cupon.id, cupon.codigo)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-[24px] border border-dashed border-neutral-300 bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-lg font-black text-neutral-900">
                No hay cupones registrados aún
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Crea tu primer cupón para comenzar a gestionar promociones.
            </p>
            <button
                type="button"
                class="mt-5 inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:brightness-95"
                @click="openCreate"
            >
                Crear cupón
            </button>
        </section>

        <section
            v-if="cupones.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in cupones.links" :key="index">
                <component
                    :is="link.url ? 'a' : 'span'"
                    :href="link.url || undefined"
                    v-html="link.label"
                    class="inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm transition duration-200"
                    :class="link.active
                        ? 'border-[var(--brand-blue)] bg-[var(--brand-blue)] text-white'
                        : link.url
                            ? 'border-neutral-200 bg-white text-neutral-700 hover:bg-neutral-50'
                            : 'cursor-not-allowed border-neutral-200 bg-neutral-100 text-neutral-400'"
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
            <div v-if="showForm" class="fixed inset-0 z-50 bg-black/45">
                <div class="flex h-dvh items-end justify-center sm:items-center sm:p-4">
                    <Transition
                        appear
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-[0.985]"
                        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-to-class="translate-y-2 opacity-0 sm:translate-y-0 sm:scale-[0.99]"
                    >
                        <div
                            v-if="showForm"
                            class="flex h-[92dvh] w-full flex-col overflow-hidden rounded-t-[26px] bg-white shadow-2xl sm:h-auto sm:max-h-[88vh] sm:max-w-4xl sm:rounded-[26px]"
                        >
                            <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-5 sm:px-6">
                                <div class="min-w-0">

                                    <h3 class="text-lg font-black tracking-tight text-neutral-900 sm:text-xl md:text-2xl">
                                        {{ isEditing ? 'Editar cupón' : 'Registrar cupón' }}
                                    </h3>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-neutral-200 bg-white text-lg text-neutral-500 transition-colors duration-200 hover:bg-neutral-50 hover:text-neutral-800"
                                    @click="closeForm()"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <form
                                    class="grid min-h-full gap-0 lg:grid-cols-[1.05fr_0.95fr]"
                                    @submit.prevent="submit"
                                >
                                    <div class="order-2 space-y-5 p-5 sm:p-6 lg:order-1">
                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Código
                                                </label>
                                                <input
                                                    v-model="form.codigo"
                                                    type="text"
                                                    placeholder="Ej. BIENVENIDA10"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm uppercase text-neutral-900 shadow-sm transition-colors duration-200 placeholder:normal-case placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.codigo" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.codigo }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Nombre
                                                </label>
                                                <input
                                                    v-model="form.nombre"
                                                    type="text"
                                                    placeholder="Ej. Cupón de bienvenida"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.nombre }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Tipo
                                                </label>
                                                <input
                                                    v-model="form.tipo"
                                                    type="text"
                                                    placeholder="Ej. porcentaje o fijo"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.tipo" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.tipo }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Valor
                                                </label>
                                                <input
                                                    v-model="form.valor"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.valor" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.valor }}
                                                </p>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="flex w-full items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-sm transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                                                <span class="text-sm font-bold text-neutral-700">
                                                    Activo
                                                </span>

                                                <input
                                                    v-model="form.activo"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                />
                                            </label>
                                        </div>

                                        <p v-if="generalError" class="text-sm font-medium text-red-600">
                                            {{ generalError }}
                                        </p>
                                    </div>

                                    <div class="order-1 border-b border-neutral-200 bg-neutral-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <p class="text-sm font-bold text-neutral-800">
                                                    Vista previa
                                                </p>
                                                <span class="rounded-full bg-neutral-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral-500">
                                                    Cupón
                                                </span>
                                            </div>

                                            <div class="rounded-[22px] bg-[#11142C] p-5 text-white">
                                                <p class="text-xs uppercase tracking-[0.18em] text-white/60">
                                                    {{ form.codigo || 'CODIGO' }}
                                                </p>

                                                <p class="mt-3 text-lg font-black">
                                                    {{ form.nombre || 'Nombre del cupón' }}
                                                </p>

                                                <div class="mt-4 rounded-2xl bg-white/10 px-4 py-4">
                                                    <p class="text-xs uppercase tracking-wide text-white/60">
                                                        Descuento
                                                    </p>
                                                    <p class="mt-1 text-2xl font-black">
                                                        {{ formatCouponValue(form.tipo, form.valor) }}
                                                    </p>
                                                </div>

                                                <div class="mt-4 flex items-center justify-between text-sm">
                                                    <span class="text-white/70">Tipo: {{ form.tipo || 'Sin definir' }}</span>
                                                    <span
                                                        class="rounded-full px-3 py-1 text-[11px] font-bold"
                                                        :class="form.activo
                                                            ? 'bg-emerald-400/20 text-emerald-200'
                                                            : 'bg-white/10 text-white/70'"
                                                    >
                                                        {{ form.activo ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-3 lg:col-span-2">
                                        <div class="sticky bottom-0 border-t border-neutral-200 bg-white/96 px-5 py-4 backdrop-blur sm:px-6">
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-2xl border border-neutral-200 bg-white px-5 py-3 text-sm font-semibold text-neutral-700 shadow-sm transition-colors duration-200 hover:bg-neutral-50"
                                                    @click="closeForm()"
                                                >
                                                    Cancelar
                                                </button>

                                                <button
                                                    type="submit"
                                                    :disabled="form.processing"
                                                    class="inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-semibold text-white shadow-md transition-colors duration-200 hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {{ form.processing
                                                        ? 'Guardando...'
                                                        : isEditing
                                                            ? 'Actualizar cupón'
                                                            : 'Registrar cupón' }}
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
