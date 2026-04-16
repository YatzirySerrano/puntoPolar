<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { CalendarDays, Clock3, Percent, Sparkles, Tags, Layers3, Package } from 'lucide-vue-next'
import { Calendar } from '@/components/ui/calendar'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Switch } from '@/components/ui/switch'
import { useCurrency } from '@/composables/useCurrency'
import { index as ofertasIndex } from '@/routes/admin/ofertas'
import { CalendarDate, parseDate } from '@internationalized/date'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Ofertas',
                href: ofertasIndex().url,
            },
        ],
    },
})

interface OfertaRow {
    id: number
    nombre: string
    tipo: string
    valor: number
    aplica_a: 'productos' | 'categoria' | 'marca'
    categoria_id?: number | null
    categoria_nombre?: string | null
    marca_id?: number | null
    marca_nombre?: string | null
    productos_ids: number[]
    productos_nombres: string[]
    inicia_en?: string | null
    termina_en?: string | null
    activa: boolean
    created_at?: string | null
}

interface OptionRow {
    id: number
    nombre: string
}

interface ApplyOption {
    value: 'productos' | 'categoria' | 'marca'
    label: string
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

type DateField = 'inicia_en' | 'termina_en'

const props = defineProps<{
    ofertas: Paginated<OfertaRow>
    tipos: string[]
    aplicaOptions: ApplyOption[]
    categorias: OptionRow[]
    marcas: OptionRow[]
    productos: OptionRow[]
    filters?: {
        search?: string
        status?: string
        tipo?: string
        aplica_a?: string
    }
    endpoints: Endpoints
}>()

const { formatCurrency } = useCurrency()

const showForm = ref(false)
const editingId = ref<number | null>(null)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')
const tipoFilter = ref(props.filters?.tipo ?? 'all')
const aplicaAFilter = ref(props.filters?.aplica_a ?? 'all')

const form = useForm({
    nombre: '',
    tipo: '',
    valor: 0,
    aplica_a: 'productos' as 'productos' | 'categoria' | 'marca',
    categoria_id: '' as number | string,
    marca_id: '' as number | string,
    productos_ids: [] as number[],
    inicia_en: '',
    termina_en: '',
    activa: true,
})

const hourOptions = Array.from({ length: 24 }, (_, index) =>
    String(index).padStart(2, '0'),
)

const minuteOptions = Array.from({ length: 12 }, (_, index) =>
    String(index * 5).padStart(2, '0'),
)

const isEditing = computed(() => editingId.value !== null)

const generalError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>
    return errors.general ?? ''
})

const totalActivas = computed(() =>
    props.ofertas.data.filter((item) => item.activa).length,
)

const totalInactivas = computed(() =>
    props.ofertas.data.filter((item) => !item.activa).length,
)

const totalPorcentaje = computed(() =>
    props.ofertas.data.filter((item) =>
        String(item.tipo).toLowerCase().includes('por'),
    ).length,
)

const summaryText = computed(() => {
    if (!props.ofertas.total) return 'No hay ofertas registradas aún.'

    const from = props.ofertas.from ?? 1
    const to = props.ofertas.to ?? props.ofertas.data.length

    return `Mostrando ${from} - ${to} de ${props.ofertas.total} ofertas`
})

function resetForm() {
    editingId.value = null

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.nombre = ''
    form.tipo = ''
    form.valor = 0
    form.aplica_a = 'productos'
    form.categoria_id = ''
    form.marca_id = ''
    form.productos_ids = []
    form.inicia_en = ''
    form.termina_en = ''
    form.activa = true
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function normalizeDateTimeInput(value?: string | null) {
    if (!value) return ''

    if (value.includes('T') && value.length >= 16) {
        return value.slice(0, 16)
    }

    const parsed = new Date(value)

    if (Number.isNaN(parsed.getTime())) return ''

    const year = parsed.getFullYear()
    const month = String(parsed.getMonth() + 1).padStart(2, '0')
    const day = String(parsed.getDate()).padStart(2, '0')
    const hours = String(parsed.getHours()).padStart(2, '0')
    const minutes = String(parsed.getMinutes()).padStart(2, '0')

    return `${year}-${month}-${day}T${hours}:${minutes}`
}

function openEdit(oferta: OfertaRow) {
    resetForm()

    editingId.value = oferta.id
    form.nombre = oferta.nombre ?? ''
    form.tipo = oferta.tipo ?? ''
    form.valor = Number(oferta.valor ?? 0)
    form.aplica_a = oferta.aplica_a
    form.categoria_id = oferta.categoria_id ?? ''
    form.marca_id = oferta.marca_id ?? ''
    form.productos_ids = oferta.productos_ids ?? []
    form.inicia_en = normalizeDateTimeInput(oferta.inicia_en)
    form.termina_en = normalizeDateTimeInput(oferta.termina_en)
    form.activa = !!oferta.activa

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

    if (!form.nombre.trim()) {
        form.setError('nombre', 'El nombre es obligatorio.')
    }

    if (!form.tipo.trim()) {
        form.setError('tipo', 'El tipo es obligatorio.')
    }

    if (Number(form.valor) < 0) {
        form.setError('valor', 'El valor no puede ser menor a 0.')
    }

    if (form.inicia_en && form.termina_en && form.termina_en < form.inicia_en) {
        form.setError('termina_en', 'La fecha final no puede ser menor a la inicial.')
    }

    if (form.aplica_a === 'productos' && !form.productos_ids.length) {
        form.setError('productos_ids', 'Debes seleccionar al menos un producto.')
    }

    if (form.aplica_a === 'categoria' && !form.categoria_id) {
        form.setError('categoria_id', 'Debes seleccionar una categoría.')
    }

    if (form.aplica_a === 'marca' && !form.marca_id) {
        form.setError('marca_id', 'Debes seleccionar una marca.')
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
                errors.nombre ||
                errors.tipo ||
                errors.valor ||
                errors.aplica_a ||
                errors.categoria_id ||
                errors.marca_id ||
                errors.productos_ids ||
                errors.inicia_en ||
                errors.termina_en ||
                errors.general ||
                'Revisa la información del formulario.'

            errorAlert('No se pudo guardar', firstError)
        },
        onFinish: () => {
            form.transform((data) => data)
        },
    }

    const payload = {
        ...form.data(),
        activa: form.activa ? 1 : 0,
        categoria_id: form.aplica_a === 'categoria' ? form.categoria_id || null : null,
        marca_id: form.aplica_a === 'marca' ? form.marca_id || null : null,
        productos_ids: form.aplica_a === 'productos' ? form.productos_ids : [],
        inicia_en: form.inicia_en || null,
        termina_en: form.termina_en || null,
    }

    if (wasEditing && id) {
        form
            .transform(() => ({
                ...payload,
                _method: 'put',
            }))
            .post(`${props.endpoints.updateBase}/${id}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Oferta actualizada', 'La oferta se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform(() => payload)
        .post(props.endpoints.store, {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Oferta creada', 'La oferta se registró correctamente.')
                })
            },
        })
}

async function destroyRow(id: number, nombre: string) {
    const result = await Swal.fire({
        title: '¿Eliminar oferta?',
        text: `Se eliminará "${nombre}" de forma permanente.`,
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
            successAlert('Oferta eliminada', 'La oferta fue eliminada correctamente.')
        },
        onError: () => {
            errorAlert('Error', 'No se pudo eliminar la oferta.')
        },
    })
}

function formatOfferValue(tipo: string, valor: number) {
    const lower = String(tipo).toLowerCase()

    if (lower.includes('por')) {
        return `${Number(valor ?? 0)}%`
    }

    return formatCurrency(Number(valor ?? 0))
}

function formatDateTimePretty(value?: string | null) {
    if (!value) return 'Sin definir'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return 'Sin definir'

    return new Intl.DateTimeFormat('es-MX', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date)
}

function formatDateShort(value?: string | null) {
    if (!value) return 'Sin fecha'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return 'Sin fecha'

    return new Intl.DateTimeFormat('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(date)
}

function formatTimeOnly(value?: string | null) {
    if (!value) return 'Sin hora'

    const date = new Date(value)

    if (Number.isNaN(date.getTime())) return 'Sin hora'

    return new Intl.DateTimeFormat('es-MX', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(date)
}

function getDatePart(value?: string | null) {
    if (!value || !value.includes('T')) return ''
    return value.split('T')[0] ?? ''
}

function getTimePart(value?: string | null) {
    if (!value || !value.includes('T')) return ''
    return value.split('T')[1] ?? ''
}

function getHourPart(value?: string | null) {
    const time = getTimePart(value)
    return time ? time.slice(0, 2) : '00'
}

function getMinutePart(value?: string | null) {
    const time = getTimePart(value)
    return time ? time.slice(3, 5) : '00'
}

function buildDateTimeString(date: string, hour: string, minute: string) {
    if (!date) return ''
    return `${date}T${hour}:${minute}`
}

function getFieldValue(field: DateField) {
    return field === 'inicia_en' ? form.inicia_en : form.termina_en
}

function setFieldValue(field: DateField, value: string) {
    if (field === 'inicia_en') {
        form.inicia_en = value
        return
    }

    form.termina_en = value
}

function getCalendarValue(field: DateField) {
    const datePart = getDatePart(getFieldValue(field))
    return datePart ? parseDate(datePart) : undefined
}

function setCalendarValue(field: DateField, value?: CalendarDate) {
    if (!value) {
        setFieldValue(field, '')
        return
    }

    const current = getFieldValue(field)
    const hour = getHourPart(current)
    const minute = getMinutePart(current)

    setFieldValue(field, buildDateTimeString(value.toString(), hour, minute))
}

function setHourValue(field: DateField, hour: string) {
    const current = getFieldValue(field)
    const date = getDatePart(current)
    const minute = getMinutePart(current)

    if (!date) return

    setFieldValue(field, buildDateTimeString(date, hour, minute))
}

function setMinuteValue(field: DateField, minute: string) {
    const current = getFieldValue(field)
    const date = getDatePart(current)
    const hour = getHourPart(current)

    if (!date) return

    setFieldValue(field, buildDateTimeString(date, hour, minute))
}

function ensureDateTimeSeed(field: DateField) {
    const current = getFieldValue(field)

    if (current) return

    const now = new Date()
    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')
    const hour = String(now.getHours()).padStart(2, '0')
    const minute = String(Math.floor(now.getMinutes() / 5) * 5).padStart(2, '0')

    setFieldValue(field, `${year}-${month}-${day}T${hour}:${minute}`)
}

function applyLabel(value: string) {
    return props.aplicaOptions.find((item) => item.value === value)?.label ?? value
}

function selectedCategoriaNombre() {
    return props.categorias.find((item) => item.id === Number(form.categoria_id))?.nombre || 'Sin definir'
}

function selectedMarcaNombre() {
    return props.marcas.find((item) => item.id === Number(form.marca_id))?.nombre || 'Sin definir'
}

function hasScheduleRange() {
    return !!form.inicia_en || !!form.termina_en
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch([search, status, tipoFilter, aplicaAFilter], () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
                status: status.value !== 'all' ? status.value : undefined,
                tipo: tipoFilter.value !== 'all' ? tipoFilter.value : undefined,
                aplica_a: aplicaAFilter.value !== 'all' ? aplicaAFilter.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }, 350)
})

watch(
    () => form.aplica_a,
    (value) => {
        if (value !== 'productos') {
            form.productos_ids = []
        }

        if (value !== 'categoria') {
            form.categoria_id = ''
        }

        if (value !== 'marca') {
            form.marca_id = ''
        }
    },
)

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})
</script>

<template>
    <Head title="Admin · Ofertas" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <section class="overflow-hidden rounded-[28px] border border-neutral-200 bg-white shadow-sm">
            <div class="border-b border-neutral-200 px-4 py-4 sm:px-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
                    <div class="grid flex-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Buscar</span>
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Nombre o tipo..."
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 placeholder:text-neutral-400 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            />
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Estado</span>
                            <select
                                v-model="status"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="all">Todas</option>
                                <option value="active">Activas</option>
                                <option value="inactive">Inactivas</option>
                            </select>
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Tipo</span>
                            <select
                                v-model="tipoFilter"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
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

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Aplicación</span>
                            <select
                                v-model="aplicaAFilter"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="all">Todas</option>
                                <option
                                    v-for="option in aplicaOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </label>
                    </div>

                    <button
                        type="button"
                        class="lg:ml-auto inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:brightness-95"
                        @click="openCreate"
                    >
                        + Nueva oferta
                    </button>
                </div>
            </div>

            <div class="grid gap-3 px-4 py-4 sm:grid-cols-3 sm:px-6 xl:grid-cols-4">
                <article class="rounded-2xl bg-neutral-50 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
                    <p class="text-xs uppercase text-neutral-500">Ofertas visibles</p>
                    <p class="text-2xl font-black">{{ ofertas.data.length }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
                    <p class="text-xs uppercase text-neutral-500">Activas</p>
                    <p class="text-2xl font-black">{{ totalActivas }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
                    <p class="text-xs uppercase text-neutral-500">Inactivas</p>
                    <p class="text-2xl font-black">{{ totalInactivas }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4 transition-all duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm">
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
            v-if="ofertas.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="oferta in ofertas.data"
                :key="oferta.id"
                class="group overflow-hidden rounded-[24px] border border-neutral-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-sky-200 hover:shadow-lg"
            >
                <div class="border-b border-neutral-200 bg-neutral-50 px-4 py-4 transition-colors duration-300 group-hover:bg-sky-50/60">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-neutral-400">
                                Oferta
                            </p>
                            <h3 class="mt-2 break-words text-lg font-black text-neutral-900">
                                {{ oferta.nombre }}
                            </h3>
                        </div>

                        <span
                            class="rounded-full px-3 py-1 text-[11px] font-bold shadow-sm"
                            :class="oferta.activa
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-neutral-200 text-neutral-600'"
                        >
                            {{ oferta.activa ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <p class="text-sm text-neutral-500">
                            Tipo: <span class="font-semibold text-neutral-700">{{ oferta.tipo }}</span>
                        </p>
                        <p class="mt-1 text-sm text-neutral-500">
                            Aplica a: <span class="font-semibold text-neutral-700">{{ applyLabel(oferta.aplica_a) }}</span>
                        </p>
                    </div>

                    <div class="rounded-2xl bg-[#11142C] px-4 py-4 text-white shadow-[0_12px_30px_rgba(17,20,44,0.18)]">
                        <p class="text-xs uppercase tracking-wide text-white/60">Valor</p>
                        <p class="mt-1 text-2xl font-black">
                            {{ formatOfferValue(oferta.tipo, oferta.valor) }}
                        </p>
                    </div>

                    <div class="space-y-1 text-sm text-neutral-500">
                        <template v-if="oferta.aplica_a === 'categoria'">
                            <p>
                                <span class="font-semibold text-neutral-700">Categoría:</span>
                                {{ oferta.categoria_nombre || 'Sin categoría' }}
                            </p>
                        </template>

                        <template v-else-if="oferta.aplica_a === 'marca'">
                            <p>
                                <span class="font-semibold text-neutral-700">Marca:</span>
                                {{ oferta.marca_nombre || 'Sin marca' }}
                            </p>
                        </template>

                        <template v-else>
                            <p>
                                <span class="font-semibold text-neutral-700">Productos:</span>
                                {{ oferta.productos_nombres.length }}
                            </p>
                            <p class="line-clamp-2 text-xs">
                                {{ oferta.productos_nombres.join(', ') || 'Sin productos' }}
                            </p>
                        </template>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                                Inicia
                            </p>
                            <p class="mt-1 text-sm font-semibold text-neutral-900">
                                {{ formatDateTimePretty(oferta.inicia_en) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                                Termina
                            </p>
                            <p class="mt-1 text-sm font-semibold text-neutral-900">
                                {{ formatDateTimePretty(oferta.termina_en) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition-all duration-200 hover:-translate-y-0.5 hover:bg-sky-100 hover:shadow-sm"
                            @click="openEdit(oferta)"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-100 hover:shadow-sm"
                            @click="destroyRow(oferta.id, oferta.nombre)"
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
                No hay ofertas registradas aún
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Crea tu primera oferta para comenzar a gestionar promociones automáticas.
            </p>
            <button
                type="button"
                class="mt-5 inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:-translate-y-0.5 hover:shadow-lg hover:brightness-95"
                @click="openCreate"
            >
                Crear oferta
            </button>
        </section>

        <section
            v-if="ofertas.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in ofertas.links" :key="index">
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
            <div v-if="showForm" class="fixed inset-0 z-50 bg-black/45 backdrop-blur-[2px]">
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
                            class="flex h-[92dvh] w-full flex-col overflow-hidden rounded-t-[28px] bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-6xl sm:rounded-[30px]"
                        >
                            <div class="flex items-start justify-between gap-4 border-b border-neutral-200 bg-white px-5 py-5 sm:px-6">
                                <div class="min-w-0">
                                    <p class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-sky-700">
                                        <Sparkles class="size-3.5" />
                                        Promoción automática
                                    </p>

                                    <h3 class="mt-3 text-lg font-black tracking-tight text-neutral-900 sm:text-xl md:text-2xl">
                                        {{ isEditing ? 'Editar oferta' : 'Registrar oferta' }}
                                    </h3>

                                    <p class="mt-1 text-sm text-neutral-500">
                                        Configura alcance, vigencia y valor con una vista más elegante.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-neutral-200 bg-white text-lg text-neutral-500 transition-all duration-200 hover:bg-neutral-50 hover:text-neutral-800 hover:shadow-sm"
                                    @click="closeForm()"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <form
                                    class="grid min-h-full gap-0 lg:grid-cols-[1.1fr_0.9fr]"
                                    @submit.prevent="submit"
                                >
                                    <div class="order-2 space-y-6 p-5 sm:p-6 lg:order-1">
                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Nombre
                                                </label>
                                                <input
                                                    v-model="form.nombre"
                                                    type="text"
                                                    placeholder="Ej. Hot Sale"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 placeholder:text-neutral-400 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.nombre }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Tipo
                                                </label>

                                                <select v-model="form.tipo"
                                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100">
                                                    <option value="">Selecciona un tipo</option>
                                                    <option value="porcentaje">Porcentaje</option>
                                                    <option value="monto_fijo">Monto fijo</option>
                                                </select>

                                                <p v-if="form.errors.tipo" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.tipo }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 md:grid-cols-[minmax(0,1fr)_220px]">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Valor
                                                </label>
                                                <div class="group flex items-center rounded-2xl border border-neutral-200 bg-white px-4 shadow-sm transition-all duration-200 hover:border-neutral-300 hover:shadow-md focus-within:border-sky-400 focus-within:ring-4 focus-within:ring-sky-100">
                                                    <Percent class="mr-3 size-4 shrink-0 text-neutral-400 transition-colors duration-200 group-focus-within:text-sky-600" />
                                                    <input
                                                        v-model="form.valor"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="h-12 w-full bg-transparent text-sm font-semibold text-neutral-900 outline-none"
                                                    />
                                                </div>
                                                <p v-if="form.errors.valor" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.valor }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Estado
                                                </label>

                                                <div class="flex h-12 items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 shadow-sm transition-all duration-200 hover:border-neutral-300 hover:shadow-md">
                                                    <div>
                                                        <p class="text-sm font-semibold text-neutral-900">
                                                            {{ form.activa ? 'Activa' : 'Inactiva' }}
                                                        </p>
                                                        <p class="text-xs text-neutral-500">
                                                            Disponible para aplicar
                                                        </p>
                                                    </div>

                                                    <Switch
                                                        :checked="form.activa"
                                                        @update:checked="(value) => form.activa = !!value"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rounded-[24px] border border-neutral-200 bg-neutral-50/70 p-4 shadow-sm">
                                            <label class="mb-3 block text-sm font-bold text-neutral-700">
                                                Aplicar a
                                            </label>

                                            <div class="grid gap-3 md:grid-cols-3">
                                                <button
                                                    v-for="option in aplicaOptions"
                                                    :key="option.value"
                                                    type="button"
                                                    class="rounded-2xl border px-4 py-4 text-left transition-all duration-200"
                                                    :class="form.aplica_a === option.value
                                                        ? 'border-sky-300 bg-sky-50 shadow-sm'
                                                        : 'border-neutral-200 bg-white hover:border-neutral-300 hover:shadow-sm'"
                                                    @click="form.aplica_a = option.value"
                                                >
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="flex size-10 items-center justify-center rounded-2xl"
                                                            :class="form.aplica_a === option.value
                                                                ? 'bg-sky-100 text-sky-700'
                                                                : 'bg-neutral-100 text-neutral-600'"
                                                        >
                                                            <Package v-if="option.value === 'productos'" class="size-4" />
                                                            <Layers3 v-else-if="option.value === 'categoria'" class="size-4" />
                                                            <Tags v-else class="size-4" />
                                                        </div>

                                                        <div>
                                                            <p class="text-sm font-black text-neutral-900">
                                                                {{ option.label }}
                                                            </p>
                                                            <p class="text-xs text-neutral-500">
                                                                <template v-if="option.value === 'productos'">
                                                                    Elige artículos específicos
                                                                </template>
                                                                <template v-else-if="option.value === 'categoria'">
                                                                    Aplica a toda la categoría
                                                                </template>
                                                                <template v-else>
                                                                    Aplica a toda la marca
                                                                </template>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>

                                        <div v-if="form.aplica_a === 'categoria'" class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                            <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                Categoría
                                            </label>
                                            <select
                                                v-model="form.categoria_id"
                                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                            >
                                                <option value="">Selecciona una categoría</option>
                                                <option
                                                    v-for="categoria in categorias"
                                                    :key="categoria.id"
                                                    :value="categoria.id"
                                                >
                                                    {{ categoria.nombre }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.categoria_id" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.categoria_id }}
                                            </p>
                                        </div>

                                        <div v-if="form.aplica_a === 'marca'" class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                            <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                Marca
                                            </label>
                                            <select
                                                v-model="form.marca_id"
                                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:border-neutral-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                            >
                                                <option value="">Selecciona una marca</option>
                                                <option
                                                    v-for="marca in marcas"
                                                    :key="marca.id"
                                                    :value="marca.id"
                                                >
                                                    {{ marca.nombre }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.marca_id" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.marca_id }}
                                            </p>
                                        </div>

                                        <div v-if="form.aplica_a === 'productos'" class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <label class="block text-sm font-bold text-neutral-700">
                                                    Productos seleccionados
                                                </label>
                                                <span class="rounded-full bg-neutral-100 px-3 py-1 text-[11px] font-bold text-neutral-600">
                                                    {{ form.productos_ids.length }} seleccionados
                                                </span>
                                            </div>

                                            <div class="max-h-72 overflow-y-auto rounded-2xl border border-neutral-200 bg-neutral-50/70 p-3">
                                                <label
                                                    v-for="producto in productos"
                                                    :key="producto.id"
                                                    class="flex items-center gap-3 rounded-xl border border-transparent bg-white px-3 py-2.5 transition-all duration-200 hover:border-sky-100 hover:bg-sky-50/60"
                                                >
                                                    <input
                                                        :checked="form.productos_ids.includes(producto.id)"
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                        @change="
                                                            ($event) => {
                                                                const checked = ($event.target as HTMLInputElement).checked
                                                                if (checked) {
                                                                    if (!form.productos_ids.includes(producto.id)) {
                                                                        form.productos_ids = [...form.productos_ids, producto.id]
                                                                    }
                                                                } else {
                                                                    form.productos_ids = form.productos_ids.filter((id) => id !== producto.id)
                                                                }
                                                            }
                                                        "
                                                    />
                                                    <span class="text-sm font-medium text-neutral-800">{{ producto.nombre }}</span>
                                                </label>
                                            </div>

                                            <p v-if="form.errors.productos_ids" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.productos_ids }}
                                            </p>
                                        </div>

                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div class="rounded-[24px] border border-neutral-200 bg-neutral-50/70 p-4 shadow-sm transition-all duration-200 hover:border-sky-200 hover:bg-white hover:shadow-md">
                                                <div class="mb-4 flex items-center gap-2">
                                                    <CalendarDays class="size-4 text-sky-600" />
                                                    <h4 class="text-sm font-black text-neutral-900">
                                                        Inicia en
                                                    </h4>
                                                </div>

                                                <Popover>
                                                    <PopoverTrigger as-child>
                                                        <button
                                                            type="button"
                                                            class="flex h-12 w-full items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 text-left text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md"
                                                            @click="ensureDateTimeSeed('inicia_en')"
                                                        >
                                                            <span class="truncate font-semibold">
                                                                {{ formatDateTimePretty(form.inicia_en) }}
                                                            </span>
                                                            <CalendarDays class="size-4 shrink-0 text-neutral-400" />
                                                        </button>
                                                    </PopoverTrigger>

                                                    <PopoverContent class="w-auto rounded-[24px] border border-neutral-200 p-0 shadow-2xl">
                                                        <div class="rounded-[24px] bg-white p-4">
                                                            <Calendar
                                                                locale="es-MX"
                                                                :model-value="getCalendarValue('inicia_en')"
                                                                @update:model-value="(value) => setCalendarValue('inicia_en', value as CalendarDate)"
                                                            />

                                                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4">
                                                                <div>
                                                                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                                                                        Hora
                                                                    </label>
                                                                    <Select
                                                                        :model-value="getHourPart(form.inicia_en)"
                                                                        @update:model-value="(value) => setHourValue('inicia_en', String(value))"
                                                                    >
                                                                        <SelectTrigger class="h-11 rounded-2xl">
                                                                            <SelectValue placeholder="Hora" />
                                                                        </SelectTrigger>
                                                                        <SelectContent class="rounded-2xl">
                                                                            <SelectItem
                                                                                v-for="hour in hourOptions"
                                                                                :key="`inicia-hour-${hour}`"
                                                                                :value="hour"
                                                                            >
                                                                                {{ hour }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>

                                                                <div>
                                                                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                                                                        Minutos
                                                                    </label>
                                                                    <Select
                                                                        :model-value="getMinutePart(form.inicia_en)"
                                                                        @update:model-value="(value) => setMinuteValue('inicia_en', String(value))"
                                                                    >
                                                                        <SelectTrigger class="h-11 rounded-2xl">
                                                                            <SelectValue placeholder="Min" />
                                                                        </SelectTrigger>
                                                                        <SelectContent class="rounded-2xl">
                                                                            <SelectItem
                                                                                v-for="minute in minuteOptions"
                                                                                :key="`inicia-minute-${minute}`"
                                                                                :value="minute"
                                                                            >
                                                                                {{ minute }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </PopoverContent>
                                                </Popover>

                                                <div class="mt-3 flex items-center gap-2 text-xs text-neutral-500">
                                                    <Clock3 class="size-3.5" />
                                                    <span>{{ formatTimeOnly(form.inicia_en) }}</span>
                                                </div>

                                                <p v-if="form.errors.inicia_en" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.inicia_en }}
                                                </p>
                                            </div>

                                            <div class="rounded-[24px] border border-neutral-200 bg-neutral-50/70 p-4 shadow-sm transition-all duration-200 hover:border-sky-200 hover:bg-white hover:shadow-md">
                                                <div class="mb-4 flex items-center gap-2">
                                                    <CalendarDays class="size-4 text-sky-600" />
                                                    <h4 class="text-sm font-black text-neutral-900">
                                                        Termina en
                                                    </h4>
                                                </div>

                                                <Popover>
                                                    <PopoverTrigger as-child>
                                                        <button
                                                            type="button"
                                                            class="flex h-12 w-full items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 text-left text-sm text-neutral-900 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md"
                                                            @click="ensureDateTimeSeed('termina_en')"
                                                        >
                                                            <span class="truncate font-semibold">
                                                                {{ formatDateTimePretty(form.termina_en) }}
                                                            </span>
                                                            <CalendarDays class="size-4 shrink-0 text-neutral-400" />
                                                        </button>
                                                    </PopoverTrigger>

                                                    <PopoverContent class="w-auto rounded-[24px] border border-neutral-200 p-0 shadow-2xl">
                                                        <div class="rounded-[24px] bg-white p-4">
                                                            <Calendar
                                                                locale="es-MX"
                                                                :model-value="getCalendarValue('termina_en')"
                                                                @update:model-value="(value) => setCalendarValue('termina_en', value as CalendarDate)"
                                                            />

                                                            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-neutral-200 pt-4">
                                                                <div>
                                                                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                                                                        Hora
                                                                    </label>
                                                                    <Select
                                                                        :model-value="getHourPart(form.termina_en)"
                                                                        @update:model-value="(value) => setHourValue('termina_en', String(value))"
                                                                    >
                                                                        <SelectTrigger class="h-11 rounded-2xl">
                                                                            <SelectValue placeholder="Hora" />
                                                                        </SelectTrigger>
                                                                        <SelectContent class="rounded-2xl">
                                                                            <SelectItem
                                                                                v-for="hour in hourOptions"
                                                                                :key="`termina-hour-${hour}`"
                                                                                :value="hour"
                                                                            >
                                                                                {{ hour }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>

                                                                <div>
                                                                    <label class="mb-2 block text-xs font-bold uppercase tracking-[0.16em] text-neutral-400">
                                                                        Minutos
                                                                    </label>
                                                                    <Select
                                                                        :model-value="getMinutePart(form.termina_en)"
                                                                        @update:model-value="(value) => setMinuteValue('termina_en', String(value))"
                                                                    >
                                                                        <SelectTrigger class="h-11 rounded-2xl">
                                                                            <SelectValue placeholder="Min" />
                                                                        </SelectTrigger>
                                                                        <SelectContent class="rounded-2xl">
                                                                            <SelectItem
                                                                                v-for="minute in minuteOptions"
                                                                                :key="`termina-minute-${minute}`"
                                                                                :value="minute"
                                                                            >
                                                                                {{ minute }}
                                                                            </SelectItem>
                                                                        </SelectContent>
                                                                    </Select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </PopoverContent>
                                                </Popover>

                                                <div class="mt-3 flex items-center gap-2 text-xs text-neutral-500">
                                                    <Clock3 class="size-3.5" />
                                                    <span>{{ formatTimeOnly(form.termina_en) }}</span>
                                                </div>

                                                <p v-if="form.errors.termina_en" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.termina_en }}
                                                </p>
                                            </div>
                                        </div>

                                        <p v-if="generalError" class="text-sm font-medium text-red-600">
                                            {{ generalError }}
                                        </p>
                                    </div>

                                    <div class="order-1 border-b border-neutral-200 bg-[radial-gradient(circle_at_top,#f4faff,white_58%)] p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[28px] border border-neutral-200 bg-white p-4 shadow-sm transition-all duration-300 hover:shadow-lg">
                                            <div class="mb-4 flex items-center justify-between gap-3">
                                                <div>
                                                    <p class="text-sm font-bold text-neutral-800">
                                                        Vista previa
                                                    </p>
                                                    <p class="text-xs text-neutral-500">
                                                        Así se verá resumida la promoción
                                                    </p>
                                                </div>

                                                <span class="rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-sky-700">
                                                    Oferta
                                                </span>
                                            </div>

                                            <div class="overflow-hidden rounded-[24px] bg-[#0d1238] p-5 text-white shadow-[0_18px_45px_rgba(13,18,56,0.28)]">
                                                <p class="text-xs uppercase tracking-[0.18em] text-white/55">
                                                    {{ form.tipo || 'TIPO DE OFERTA' }}
                                                </p>

                                                <p class="mt-3 text-2xl font-black tracking-tight">
                                                    {{ form.nombre || 'Nombre de la oferta' }}
                                                </p>

                                                <div class="mt-5 rounded-[22px] bg-white/10 px-4 py-4 backdrop-blur-sm">
                                                    <p class="text-xs uppercase tracking-[0.18em] text-white/55">
                                                        Descuento
                                                    </p>
                                                    <p class="mt-2 text-4xl font-black">
                                                        {{ formatOfferValue(form.tipo, Number(form.valor)) }}
                                                    </p>
                                                </div>

                                                <div class="mt-5 grid gap-3">
                                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-white/50">
                                                            Alcance
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold">
                                                            {{ applyLabel(form.aplica_a) }}
                                                        </p>
                                                        <p v-if="form.aplica_a === 'categoria'" class="mt-1 text-xs text-white/70">
                                                            {{ selectedCategoriaNombre() }}
                                                        </p>
                                                        <p v-if="form.aplica_a === 'marca'" class="mt-1 text-xs text-white/70">
                                                            {{ selectedMarcaNombre() }}
                                                        </p>
                                                        <p v-if="form.aplica_a === 'productos'" class="mt-1 text-xs text-white/70">
                                                            {{ form.productos_ids.length }} productos seleccionados
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-white/50">
                                                            Inicio
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold">
                                                            {{ formatDateTimePretty(form.inicia_en) }}
                                                        </p>
                                                    </div>

                                                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                                        <p class="text-[11px] uppercase tracking-[0.18em] text-white/50">
                                                            Fin
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold">
                                                            {{ formatDateTimePretty(form.termina_en) }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="mt-5 flex items-center justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs uppercase tracking-[0.18em] text-white/50">
                                                            Estado
                                                        </p>
                                                        <p class="mt-1 text-sm font-semibold text-white/85">
                                                            {{ form.activa ? 'Oferta habilitada' : 'Oferta pausada' }}
                                                        </p>
                                                    </div>

                                                    <span
                                                        class="rounded-full px-3 py-1.5 text-[11px] font-bold shadow-sm"
                                                        :class="form.activa
                                                            ? 'bg-emerald-400/20 text-emerald-200'
                                                            : 'bg-white/10 text-white/70'"
                                                    >
                                                        {{ form.activa ? 'Activa' : 'Inactiva' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                                                        Fecha de inicio
                                                    </p>
                                                    <p class="mt-1 text-sm font-semibold text-neutral-900">
                                                        {{ formatDateShort(form.inicia_en) }}
                                                    </p>
                                                    <p class="text-xs text-neutral-500">
                                                        {{ formatTimeOnly(form.inicia_en) }}
                                                    </p>
                                                </div>

                                                <div class="rounded-2xl border border-neutral-200 bg-neutral-50 px-4 py-3">
                                                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-neutral-400">
                                                        Fecha de fin
                                                    </p>
                                                    <p class="mt-1 text-sm font-semibold text-neutral-900">
                                                        {{ formatDateShort(form.termina_en) }}
                                                    </p>
                                                    <p class="text-xs text-neutral-500">
                                                        {{ formatTimeOnly(form.termina_en) }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                v-if="!hasScheduleRange()"
                                                class="mt-4 rounded-2xl border border-dashed border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-500"
                                            >
                                                Aún no defines vigencia. Puedes dejarla abierta o configurarla desde el calendario.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-3 lg:col-span-2">
                                        <div class="sticky bottom-0 border-t border-neutral-200 bg-white/96 px-5 py-4 backdrop-blur sm:px-6">
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-2xl border border-neutral-200 bg-white px-5 py-3 text-sm font-semibold text-neutral-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-neutral-50 hover:shadow-md"
                                                    @click="closeForm()"
                                                >
                                                    Cancelar
                                                </button>

                                                <button
                                                    type="submit"
                                                    :disabled="form.processing"
                                                    class="inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {{ form.processing
                                                        ? 'Guardando...'
                                                        : isEditing
                                                            ? 'Actualizar oferta'
                                                            : 'Registrar oferta' }}
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
