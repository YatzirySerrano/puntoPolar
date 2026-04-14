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

interface MarcaRow {
    id: number
    nombre: string
    slug: string
    logo: string | null
    activa: boolean
    productos_count?: number
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
    marcas: Paginated<MarcaRow>
    endpoints: Endpoints
    filters?: {
        search?: string
        status?: string
    }
}>()

const showForm = ref(false)
const editingId = ref<number | null>(null)

const currentLogoUrl = ref<string | null>(null)
const previewUrl = ref<string | null>(null)
const selectedFileName = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

const search = ref(props.filters?.search ?? '')
const status = ref(props.filters?.status ?? 'all')

const form = useForm({
    nombre: '',
    slug: '',
    logo_file: null as File | null,
    activa: true,
    remove_logo: false,
})

const isEditing = computed(() => editingId.value !== null)

const displayPreview = computed(() => {
    if (form.remove_logo) return null
    return previewUrl.value || currentLogoUrl.value
})

const hasLogoPreview = computed(() => !!displayPreview.value)

const generalError = computed(() => {
    const errors = form.errors as Record<string, string | undefined>
    return errors.general ?? ''
})

const summaryText = computed(() => {
    if (!props.marcas.total) return 'No hay marcas registradas aún.'

    const from = props.marcas.from ?? 1
    const to = props.marcas.to ?? props.marcas.data.length

    return `Mostrando ${from} - ${to} de ${props.marcas.total} marcas`
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
    currentLogoUrl.value = null
    selectedFileName.value = ''

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.nombre = ''
    form.slug = ''
    form.logo_file = null
    form.activa = true
    form.remove_logo = false
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(row: MarcaRow) {
    resetForm()

    editingId.value = row.id
    currentLogoUrl.value = row.logo ?? null

    form.nombre = row.nombre ?? ''
    form.slug = row.slug ?? ''
    form.activa = !!row.activa
    form.logo_file = null
    form.remove_logo = false

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

function onLogoChange(event: Event) {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0] ?? null

    revokePreview()

    form.logo_file = file
    form.remove_logo = false
    selectedFileName.value = file?.name ?? ''

    if (file) {
        previewUrl.value = URL.createObjectURL(file)
    } else {
        previewUrl.value = null
    }
}

function removeLogo() {
    revokePreview()
    form.logo_file = null
    selectedFileName.value = ''
    clearNativeFileInput()

    if (isEditing.value && currentLogoUrl.value) {
        form.remove_logo = true
    } else {
        currentLogoUrl.value = null
        form.remove_logo = false
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

    if (isEditing.value && form.remove_logo && !form.logo_file) {
        form.setError('logo_file', 'Debes seleccionar un logo para reemplazar el actual.')
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
                errors.logo_file ||
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
            }))
            .post(updateUrl(id), {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Marca actualizada', 'La marca se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form.post(storeUrl(), {
        ...commonOptions,
        onSuccess: () => {
            closeForm(true)
            requestAnimationFrame(() => {
                successAlert('Marca registrada', 'La marca se registró correctamente.')
            })
        },
    })
}

async function destroyRow(row: MarcaRow) {
    if ((row.productos_count ?? 0) > 0) {
        errorAlert('No se puede eliminar', 'Esta marca tiene productos relacionados.')
        return
    }

    const result = await Swal.fire({
        title: '¿Eliminar marca?',
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
            successAlert('Eliminada', 'La marca fue eliminada correctamente.')
        },
        onError: (errors: Record<string, string>) => {
            errorAlert(
                'No se pudo eliminar',
                errors.general || 'Ocurrió un problema al eliminar la marca.'
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
        <Head title="Marcas" />

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
                                    placeholder="Nombre o slug de la marca..."
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
                            Nueva marca
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
                v-if="props.marcas.data.length"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
            >
                <article
                    v-for="row in props.marcas.data"
                    :key="row.id"
                    class="group overflow-hidden rounded-[22px] border border-neutral-200 bg-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="relative aspect-[16/8] overflow-hidden bg-neutral-100">
                        <img
                            v-if="row.logo"
                            :src="row.logo"
                            :alt="row.nombre"
                            class="h-full w-full object-contain bg-white p-4 transition-transform duration-300 group-hover:scale-[1.03]"
                        />

                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-neutral-50 text-sm font-medium text-neutral-400"
                        >
                            Sin logo
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
                            </div>

                            <div
                                v-if="typeof row.productos_count === 'number'"
                                class="shrink-0 rounded-2xl bg-neutral-100 px-3 py-2 text-center"
                            >
                                <p class="text-[10px] font-bold uppercase tracking-wide text-neutral-400">
                                    Productos
                                </p>
                                <p class="text-sm font-black text-neutral-800">
                                    {{ row.productos_count }}
                                </p>
                            </div>
                        </div>

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
                    No hay marcas registradas aún
                </h2>
                <p class="mt-2 text-sm text-neutral-500">
                    Registra tu primera marca para comenzar a organizar tu catálogo.
                </p>
                <button
                    type="button"
                    class="mt-5 inline-flex items-center justify-center rounded-2xl bg-[#11142C] px-5 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C]"
                    @click="openCreate"
                >
                    Crear marca
                </button>
            </section>

            <section
                v-if="props.marcas.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-2"
            >
                <template v-for="(link, index) in props.marcas.links" :key="index">
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
                <div
                    v-if="showForm"
                    class="fixed inset-0 z-50 bg-black/45"
                >
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
                                            {{ isEditing ? 'Editar marca' : 'Nueva marca' }}
                                        </h3>

                                        <p class="mt-1 text-sm leading-6 text-neutral-500">
                                            Captura el nombre, slug y logo de la marca.
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
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Nombre
                                                </label>
                                                <input
                                                    v-model="form.nombre"
                                                    type="text"
                                                    placeholder="Ej. Hikvision"
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
                                                        {{ form.nombre || 'Nombre de la marca' }}
                                                    </p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                                                        {{ form.slug || 'slug-de-la-marca' }}
                                                    </p>
                                                    <p class="mt-2 text-xs text-neutral-500">
                                                        {{ form.activa ? 'Marca activa' : 'Marca inactiva' }}
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
                                                        Logo
                                                    </span>
                                                </div>

                                                <div class="relative overflow-hidden rounded-[20px] border border-dashed border-neutral-300 bg-neutral-50">
                                                    <div class="aspect-[16/9]">
                                                        <img
                                                            v-if="displayPreview"
                                                            :src="displayPreview"
                                                            alt="Vista previa del logo"
                                                            class="h-full w-full object-contain bg-white p-5"
                                                        />

                                                        <div
                                                            v-else
                                                            class="flex h-full flex-col items-center justify-center px-5 text-center"
                                                        >
                                                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl shadow-sm">
                                                                🏷️
                                                            </div>
                                                            <p class="text-sm font-semibold text-neutral-600">
                                                                Aquí se mostrará el logo
                                                            </p>
                                                            <p class="mt-1 text-xs text-neutral-400">
                                                                Selecciona una imagen
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <button
                                                        v-if="hasLogoPreview"
                                                        type="button"
                                                        class="absolute right-3 top-3 inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-base font-bold text-neutral-700 shadow-md transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                                                        @click="removeLogo()"
                                                    >
                                                        ×
                                                    </button>
                                                </div>

                                                <div class="mt-4">
                                                    <label class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-[#11142C] px-4 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C]">
                                                        Seleccionar logo
                                                        <input
                                                            ref="fileInput"
                                                            type="file"
                                                            accept="image/*"
                                                            class="hidden"
                                                            @change="onLogoChange"
                                                        />
                                                    </label>

                                                    <p class="mt-3 truncate text-sm font-medium text-neutral-700">
                                                        {{ selectedFileName || 'No has seleccionado ningún logo.' }}
                                                    </p>
                                                    <p class="mt-1 text-xs text-neutral-400">
                                                        Formatos permitidos: JPG, PNG, WEBP. Máximo 15 MB.
                                                    </p>
                                                </div>

                                                <p v-if="form.errors.logo_file" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.logo_file }}
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
                                                                ? 'Actualizar marca'
                                                                : 'Registrar marca' }}
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
