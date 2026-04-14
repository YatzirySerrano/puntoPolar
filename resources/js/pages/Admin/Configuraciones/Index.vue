<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { index as configuracionesIndex } from '@/routes/admin/configuraciones'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Configuraciones',
                href: configuracionesIndex().url,
            },
        ],
    },
})

interface ConfiguracionRow {
    id: number
    clave: string
    valor: string | null
    created_at?: string | null
    updated_at?: string | null
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
    configuraciones: Paginated<ConfiguracionRow>
    filters?: {
        search?: string
    }
    endpoints: Endpoints
}>()

const search = ref(props.filters?.search ?? '')
const showForm = ref(false)
const editingId = ref<number | null>(null)

const form = useForm({
    clave: '',
    valor: '',
})

const isEditing = computed(() => editingId.value !== null)

const totalConValor = computed(() =>
    props.configuraciones.data.filter((item) => String(item.valor ?? '').trim() !== '').length,
)

const totalVacias = computed(() =>
    props.configuraciones.data.filter((item) => String(item.valor ?? '').trim() === '').length,
)

const summaryText = computed(() => {
    if (!props.configuraciones.total) return 'No hay configuraciones registradas aún.'

    const from = props.configuraciones.from ?? 1
    const to = props.configuraciones.to ?? props.configuraciones.data.length

    return `Mostrando ${from} - ${to} de ${props.configuraciones.total} configuraciones`
})

function formatDate(date?: string | null) {
    if (!date) return 'Sin registro'

    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date))
}

function shortValue(value: string | null | undefined) {
    const text = String(value ?? '').trim()
    if (!text) return 'Sin valor'
    if (text.length <= 90) return text
    return `${text.slice(0, 90)}…`
}

function resetForm() {
    editingId.value = null

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.clave = ''
    form.valor = ''
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(configuracion: ConfiguracionRow) {
    resetForm()

    editingId.value = configuracion.id
    form.clave = configuracion.clave ?? ''
    form.valor = configuracion.valor ?? ''

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

    if (!form.clave.trim()) {
        form.setError('clave', 'La clave es obligatoria.')
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
    if (!validateBeforeSubmit()) return

    const commonOptions = {
        preserveScroll: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.clave ||
                errors.valor ||
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
            .transform((data) => ({
                clave: data.clave,
                valor: data.valor,
                _method: 'put',
            }))
            .post(`${props.endpoints.updateBase}/${editingId.value}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Configuración actualizada', 'El valor se guardó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform((data) => ({
            clave: data.clave,
            valor: data.valor,
        }))
        .post(props.endpoints.store, {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Configuración creada', 'La configuración se registró correctamente.')
                })
            },
        })
}

async function destroyRow(id: number, clave: string) {
    const result = await Swal.fire({
        title: '¿Eliminar configuración?',
        text: `Se eliminará "${clave}" de forma permanente.`,
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
            successAlert('Configuración eliminada', 'La configuración fue eliminada correctamente.')
        },
        onError: (errors: Record<string, string>) => {
            errorAlert('Error', errors.general || 'No se pudo eliminar la configuración.')
        },
    })
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch(search, () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
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
    <Head title="Admin · Configuraciones" />

    <div class="space-y-5 p-4 sm:p-6 lg:p-8">
        <section class="rounded-[26px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] shadow-sm">
            <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                        Ajustes globales
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C] hover:shadow-md"
                    @click="openCreate"
                >
                    + Nueva configuración
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2">
            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Con valor</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalConValor }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Vacías</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalVacias }}</p>
            </article>
        </section>

        <section class="rounded-[26px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                <label class="block max-w-xl">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Buscar</span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Clave o valor..."
                        class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                    />
                </label>
            </div>

            <div class="px-5 py-4 sm:px-6">
                <p class="text-sm text-slate-500">{{ summaryText }}</p>
            </div>
        </section>

        <section
            v-if="configuraciones.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="row in configuraciones.data"
                :key="row.id"
                class="group overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg"
            >
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-5 transition-colors duration-300 group-hover:bg-slate-50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Clave</p>
                    <h3 class="mt-2 break-all text-base font-black text-slate-900">
                        {{ row.clave }}
                    </h3>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 transition-all duration-200 group-hover:bg-slate-50">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Valor</p>
                        <p class="mt-2 whitespace-pre-wrap break-words text-sm font-medium leading-6 text-slate-900">
                            {{ shortValue(row.valor) }}
                        </p>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Actualizado</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">
                                {{ formatDate(row.updated_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.06)]"
                            @click="openEdit(row)"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-100"
                            @click="destroyRow(row.id, row.clave)"
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
            <h2 class="text-lg font-black text-slate-900">No hay configuraciones registradas aún</h2>
            <p class="mt-2 text-sm text-slate-500">Crea tu primera configuración para empezar a guardar ajustes globales.</p>

            <button
                type="button"
                class="mt-5 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C]"
                @click="openCreate"
            >
                Nueva configuración
            </button>
        </section>

        <section
            v-if="configuraciones.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in configuraciones.links" :key="index">
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
                                            {{ isEditing ? 'Edición' : 'Alta' }}
                                        </p>
                                        <h3 class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">
                                            {{ isEditing ? 'Editar configuración' : 'Nueva configuración' }}
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
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Clave</label>
                                            <input
                                                v-model="form.clave"
                                                type="text"
                                                placeholder="Ej. telefono_contacto"
                                                :disabled="form.processing"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)] disabled:bg-slate-100 disabled:text-slate-500"
                                            />
                                            <p v-if="form.errors.clave" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.clave }}
                                            </p>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-semibold text-slate-700">Valor</label>
                                            <textarea
                                                v-model="form.valor"
                                                rows="12"
                                                placeholder="Escribe aquí el valor de la configuración..."
                                                :disabled="form.processing"
                                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)] disabled:bg-slate-100 disabled:text-slate-500"
                                            />
                                            <p v-if="form.errors.valor" class="mt-2 text-sm font-medium text-red-600">
                                                {{ form.errors.valor }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="order-1 border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                                Vista previa
                                            </p>

                                            <h4 class="mt-4 break-all text-xl font-black text-slate-900">
                                                {{ form.clave || 'clave_configuracion' }}
                                            </h4>

                                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Valor</p>
                                                <p class="mt-2 whitespace-pre-wrap break-words text-sm font-medium leading-6 text-slate-900">
                                                    {{ form.valor || 'Sin valor' }}
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
                                                                ? 'Actualizar configuración'
                                                                : 'Crear configuración'
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
