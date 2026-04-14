<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { index as marcasIndex } from '@/routes/admin/marcas'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Marcas',
                href: marcasIndex().url,
            },
        ],
    },
})

interface CategoriaRow {
    id: number
    categoria_padre_id: number | null
    categoria_padre_nombre: string | null
    nombre: string
    slug: string
    descripcion: string | null
    imagen: string | null
    activa: boolean
    orden: number
    productos_count?: number
    created_at?: string | null
}

interface CategoriaPadreOption {
    id: number
    nombre: string
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
    categorias: Paginated<CategoriaRow>
    categoriasPadre: CategoriaPadreOption[]
    endpoints: Endpoints
    filters?: {
        search?: string
        status?: string
    }
}>()

const showForm = ref(false)
const editingId = ref<number | null>(null)

const currentImageUrl = ref<string | null>(null)
const previewUrl = ref<string | null>(null)
const selectedFileName = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

const form = useForm({
    categoria_padre_id: '' as number | '' | null,
    nombre: '',
    slug: '',
    descripcion: '',
    imagen_file: null as File | null,
    activa: true,
    orden: 0,
    remove_image: false,
})

const isEditing = computed(() => editingId.value !== null)

const displayPreview = computed(() => {
    if (form.remove_image) return null
    return previewUrl.value || currentImageUrl.value
})

const hasImagePreview = computed(() => !!displayPreview.value)

const generalError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>
    return errors.general ?? ''
})

const summaryText = computed(() => {
    if (!props.categorias.total) return 'No hay categorías registradas aún.'

    const from = props.categorias.from ?? 1
    const to = props.categorias.to ?? props.categorias.data.length

    return `Mostrando ${from} - ${to} de ${props.categorias.total} categorías`
})

const categoriasPadreDisponibles = computed(() => {
    if (!isEditing.value) return props.categoriasPadre

    return props.categoriasPadre.filter((item) => item.id !== editingId.value)
})

function storeUrl(): string {
    return props.endpoints.store
}

function updateUrl(id: number): string {
    return `${props.endpoints.updateBase}/${id}`
}

function destroyUrl(id: number): string {
    return `${props.endpoints.destroyBase}/${id}`
}

function revokePreview() {
    if (previewUrl.value && previewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(previewUrl.value)
    }
    previewUrl.value = null
}

function clearNativeFileInput() {
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

function resetForm() {
    revokePreview()
    clearNativeFileInput()

    editingId.value = null
    currentImageUrl.value = null
    selectedFileName.value = ''

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.categoria_padre_id = ''
    form.nombre = ''
    form.slug = ''
    form.descripcion = ''
    form.imagen_file = null
    form.activa = true
    form.orden = 0
    form.remove_image = false
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(row: CategoriaRow) {
    resetForm()

    editingId.value = row.id
    currentImageUrl.value = row.imagen ?? null

    form.categoria_padre_id = row.categoria_padre_id ?? ''
    form.nombre = row.nombre ?? ''
    form.slug = row.slug ?? ''
    form.descripcion = row.descripcion ?? ''
    form.imagen_file = null
    form.activa = !!row.activa
    form.orden = row.orden ?? 0
    form.remove_image = false

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

function onImageChange(event: Event) {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0] ?? null

    revokePreview()

    form.imagen_file = file
    form.remove_image = false
    selectedFileName.value = file?.name ?? ''

    if (file) {
        previewUrl.value = URL.createObjectURL(file)
    } else {
        previewUrl.value = null
    }
}

function removeImage() {
    revokePreview()
    form.imagen_file = null
    selectedFileName.value = ''
    clearNativeFileInput()

    if (isEditing.value && currentImageUrl.value) {
        form.remove_image = true
    } else {
        currentImageUrl.value = null
        form.remove_image = false
    }
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

    if (isEditing.value && form.remove_image && !form.imagen_file) {
        form.setError('imagen_file', 'Debes seleccionar una imagen para reemplazar la actual.')
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
        forceFormData: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.nombre ||
                errors.slug ||
                errors.descripcion ||
                errors.imagen_file ||
                errors.categoria_padre_id ||
                errors.orden ||
                errors.general ||
                'Revisa la información del formulario.'

            errorAlert('No se pudo guardar', firstError)
        },
        onFinish: () => {
            form.transform((data) => data)
        },
    }

    const payloadTransform = (data: ReturnType<typeof form.data>) => ({
        ...data,
        categoria_padre_id: data.categoria_padre_id === '' ? null : data.categoria_padre_id,
        orden: Number(data.orden ?? 0),
        activa: data.activa ? 1 : 0,
        remove_image: data.remove_image ? 1 : 0,
    })

    if (wasEditing && id) {
        form
            .transform((data) => ({
                ...payloadTransform(data),
                _method: 'put',
            }))
            .post(updateUrl(id), {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Categoría actualizada', 'La categoría se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform((data) => payloadTransform(data))
        .post(storeUrl(), {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Categoría registrada', 'La categoría se registró correctamente.')
                })
            },
        })
}

async function destroyRow(row: CategoriaRow) {
    const result = await Swal.fire({
        title: '¿Eliminar categoría?',
        text: `Se eliminará "${row.nombre}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        background: '#ffffff',
        color: '#111827',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#11142C',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
            confirmButton: 'rounded-xl font-semibold',
            cancelButton: 'rounded-xl font-semibold',
        },
    })

    if (!result.isConfirmed) return

    router.delete(destroyUrl(row.id), {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Eliminada', 'La categoría fue eliminada correctamente.')
        },
        onError: (errors: Record<string, string>) => {
            errorAlert(
                'No se pudo eliminar',
                errors.general || 'Ocurrió un problema al eliminar la categoría.'
            )
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
            }
        )
    }, 350)
})

onBeforeUnmount(() => {
    revokePreview()
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})
</script>

<template>
    <div>
        <Head title="Admin · Categorías" />

        <div class="space-y-5 p-4 sm:space-y-6 sm:p-6 lg:px-8 lg:py-8 2xl:px-10 2xl:py-10">
            <section class="overflow-hidden rounded-[24px] border border-neutral-200 bg-white shadow-sm sm:rounded-[28px]">
                <div class="flex flex-col gap-5 border-b border-neutral-200 px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-end">
                        <div class="grid flex-1 gap-4 lg:grid-cols-[minmax(0,1fr)_240px]">
                            <label>
                                <span class="mb-2 block text-sm font-bold text-neutral-700">
                                    Buscar
                                </span>
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Nombre, slug o descripción..."
                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                />
                            </label>

                            <label>
                                <span class="mb-2 block text-sm font-bold text-neutral-700">
                                    Estado
                                </span>
                                <select
                                    v-model="status"
                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                >
                                    <option value="all">Todas</option>
                                    <option value="active">Activas</option>
                                    <option value="inactive">Inactivas</option>
                                </select>
                            </label>
                        </div>

                        <button
                            type="button"
                            class="md:ml-auto inline-flex items-center justify-center rounded-2xl bg-[#11142C] px-5 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                            @click="openCreate"
                        >
                            <span class="mr-2 text-base">+</span>
                            Nueva categoría
                        </button>
                    </div>
                </div>

                <div class="px-4 py-4 sm:px-6">
                    <p class="text-sm text-neutral-500">
                        {{ summaryText }}
                    </p>
                </div>
            </section>

            <section
                v-if="props.categorias.data.length"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="row in props.categorias.data"
                    :key="row.id"
                    class="group overflow-hidden rounded-[22px] border border-neutral-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="relative aspect-[16/8] overflow-hidden bg-neutral-100">
                        <img
                            v-if="row.imagen"
                            :src="row.imagen"
                            :alt="row.nombre"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                        />

                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-neutral-50 text-sm font-medium text-neutral-400"
                        >
                            Sin imagen
                        </div>

                        <div class="absolute left-3 top-3">
                            <span
                                class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold shadow-sm"
                                :class="row.activa
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-neutral-200 text-neutral-600'"
                            >
                                {{ row.activa ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="line-clamp-2 text-base font-black text-neutral-900">
                                    {{ row.nombre }}
                                </h3>
                                <p class="mt-1 truncate text-xs font-semibold uppercase tracking-wide text-neutral-400">
                                    {{ row.slug }}
                                </p>
                                <p v-if="row.categoria_padre_nombre" class="mt-2 text-xs text-neutral-500">
                                    Padre: {{ row.categoria_padre_nombre }}
                                </p>
                            </div>

                            <div class="shrink-0 rounded-2xl bg-neutral-100 px-3 py-2 text-center">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-neutral-400">
                                    Orden
                                </p>
                                <p class="text-sm font-black text-neutral-800">
                                    {{ row.orden }}
                                </p>
                            </div>
                        </div>

                        <p v-if="row.descripcion" class="line-clamp-3 text-sm text-neutral-500">
                            {{ row.descripcion }}
                        </p>
                        <p v-else class="line-clamp-3 text-sm text-neutral-400">
                            Sin descripción registrada.
                        </p>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition-colors duration-200 hover:bg-sky-100"
                                @click="openEdit(row)"
                            >
                                Editar
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-colors duration-200 hover:bg-red-100"
                                @click="destroyRow(row)"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                </article>
            </section>

            <section
                v-else
                class="rounded-[24px] border border-dashed border-neutral-300 bg-white px-6 py-16 text-center shadow-sm sm:rounded-[28px]"
            >
                <h2 class="text-lg font-black text-neutral-900">
                    No hay categorías registradas aún
                </h2>
                <p class="mt-2 text-sm text-neutral-500">
                    Registra tu primera categoría para comenzar a organizar tus productos.
                </p>
                <button
                    type="button"
                    class="mt-5 inline-flex items-center justify-center rounded-2xl bg-[#11142C] px-5 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                    @click="openCreate"
                >
                    Crear categoría
                </button>
            </section>

            <section
                v-if="props.categorias.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-2"
            >
                <template v-for="(link, index) in props.categorias.links" :key="index">
                    <component
                        :is="link.url ? 'a' : 'span'"
                        :href="link.url || undefined"
                        v-html="link.label"
                        class="inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm transition duration-200"
                        :class="link.active
                            ? 'border-[#11142C] bg-[#11142C] text-white'
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
                                class="flex h-[92dvh] w-full flex-col overflow-hidden rounded-t-[26px] bg-white shadow-2xl sm:h-auto sm:max-h-[88vh] sm:max-w-6xl sm:rounded-[26px]"
                            >
                                <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-5 sm:px-6">
                                    <div class="min-w-0">
                                        <div class="mb-2 inline-flex items-center rounded-full bg-sky-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] text-sky-700">
                                            {{ isEditing ? 'Actualizar' : 'Crear' }}
                                        </div>

                                        <h3 class="text-lg font-black tracking-tight text-neutral-900 sm:text-xl md:text-2xl">
                                            {{ isEditing ? 'Editar categoría' : 'Nueva categoría' }}
                                        </h3>

                                        <p class="mt-1 text-sm leading-6 text-neutral-500">
                                            Captura la información principal de la categoría.
                                        </p>
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
                                                        Nombre
                                                    </label>
                                                    <input
                                                        v-model="form.nombre"
                                                        type="text"
                                                        placeholder="Ej. Videovigilancia"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    />
                                                    <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.nombre }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                        Slug
                                                    </label>
                                                    <input
                                                        v-model="form.slug"
                                                        type="text"
                                                        placeholder="Se genera automáticamente si lo dejas vacío"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    />
                                                    <p v-if="form.errors.slug" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.slug }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid gap-5 md:grid-cols-2">
                                                <div>
                                                    <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                        Categoría padre
                                                    </label>
                                                    <select
                                                        v-model="form.categoria_padre_id"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    >
                                                        <option value="">Ninguna</option>
                                                        <option
                                                            v-for="option in categoriasPadreDisponibles"
                                                            :key="option.id"
                                                            :value="option.id"
                                                        >
                                                            {{ option.nombre }}
                                                        </option>
                                                    </select>
                                                    <p v-if="form.errors.categoria_padre_id" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.categoria_padre_id }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                        Orden
                                                    </label>
                                                    <input
                                                        v-model="form.orden"
                                                        type="number"
                                                        min="0"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    />
                                                    <p v-if="form.errors.orden" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.orden }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Descripción
                                                </label>
                                                <textarea
                                                    v-model="form.descripcion"
                                                    rows="5"
                                                    placeholder="Agrega una breve descripción para la categoría"
                                                    class="w-full resize-none rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.descripcion" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.descripcion }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="flex w-full items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-sm transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                                                    <span class="text-sm font-bold text-neutral-700">
                                                        Activa
                                                    </span>

                                                    <input
                                                        v-model="form.activa"
                                                        type="checkbox"
                                                        class="h-5 w-5 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                    />
                                                </label>
                                            </div>

                                            <div class="rounded-[24px] border border-neutral-200 bg-neutral-50 p-4">
                                                <p class="text-sm font-bold text-neutral-800">
                                                    Resumen
                                                </p>

                                                <div class="mt-4 rounded-[20px] border border-neutral-200 bg-white p-4">
                                                    <p class="line-clamp-2 text-sm font-black text-neutral-900">
                                                        {{ form.nombre || 'Nombre de la categoría' }}
                                                    </p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                                                        {{ form.slug || 'slug-de-la-categoria' }}
                                                    </p>
                                                    <p v-if="form.descripcion" class="mt-2 line-clamp-4 text-sm text-neutral-500">
                                                        {{ form.descripcion }}
                                                    </p>
                                                    <p class="mt-2 text-xs text-neutral-500">
                                                        {{ form.activa ? 'Categoría activa' : 'Categoría inactiva' }}
                                                    </p>
                                                </div>
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
                                                        Imagen
                                                    </span>
                                                </div>

                                                <div class="relative overflow-hidden rounded-[20px] border border-dashed border-neutral-300 bg-neutral-50">
                                                    <div class="aspect-[16/9]">
                                                        <img
                                                            v-if="displayPreview"
                                                            :src="displayPreview"
                                                            alt="Vista previa de la categoría"
                                                            class="h-full w-full object-cover"
                                                        />

                                                        <div
                                                            v-else
                                                            class="flex h-full flex-col items-center justify-center px-5 text-center"
                                                        >
                                                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl shadow-sm">
                                                                🗂️
                                                            </div>
                                                            <p class="text-sm font-semibold text-neutral-600">
                                                                Aquí se mostrará la imagen
                                                            </p>
                                                            <p class="mt-1 text-xs text-neutral-400">
                                                                Selecciona una imagen
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <button
                                                        v-if="hasImagePreview"
                                                        type="button"
                                                        class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-base font-bold text-neutral-700 shadow-md transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                                                        @click="removeImage()"
                                                    >
                                                        ×
                                                    </button>
                                                </div>

                                                <div class="mt-4">
                                                    <label class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-[#11142C] px-4 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C]">
                                                        Seleccionar imagen
                                                        <input
                                                            ref="fileInput"
                                                            type="file"
                                                            accept="image/*"
                                                            class="hidden"
                                                            @change="onImageChange"
                                                        />
                                                    </label>

                                                    <p class="mt-3 truncate text-sm font-medium text-neutral-700">
                                                        {{ selectedFileName || 'No has seleccionado ninguna imagen.' }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-neutral-400">
                                                        Formatos permitidos: JPG, PNG, WEBP. Máximo 15 MB.
                                                    </p>
                                                </div>

                                                <p v-if="form.errors.imagen_file" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.imagen_file }}
                                                </p>
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
                                                        class="inline-flex items-center justify-center rounded-2xl bg-[#11142C] px-5 py-3 text-sm font-semibold text-white shadow-md transition-colors duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C] disabled:cursor-not-allowed disabled:opacity-60"
                                                    >
                                                        {{ form.processing
                                                            ? 'Guardando...'
                                                            : isEditing
                                                                ? 'Actualizar categoría'
                                                                : 'Registrar categoría' }}
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
    </div>
</template>
