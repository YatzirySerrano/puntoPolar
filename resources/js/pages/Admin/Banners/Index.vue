<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { index as bannersIndex } from '@/routes/admin/banners'

defineOptions({
    inheritAttrs: false,
    layout: {
        breadcrumbs: [
            {
                title: 'Banners',
                href: bannersIndex().url,
            },
        ],
    },
})

interface BannerRow {
    id: number
    titulo: string
    descripcion: string | null
    imagen: string | null
    activo: boolean
    orden: number
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
    links: PaginationLink[]
}

interface Endpoints {
    index: string
    store: string
    reorder: string
    updateBase: string
    destroyBase: string
}

const props = defineProps<{
    banners: Paginated<BannerRow>
    endpoints: Endpoints
}>()

const showForm = ref(false)
const editingId = ref<number | null>(null)
const currentImageUrl = ref<string | null>(null)
const previewUrl = ref<string | null>(null)
const selectedFileName = ref('')
const dragIndex = ref<number | null>(null)
const dropIndex = ref<number | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const bannerItems = ref<BannerRow[]>([...props.banners.data])

watch(
    () => props.banners.data,
    (value) => {
        bannerItems.value = [...value]
    }
)

const form = useForm({
    titulo: '',
    descripcion: '',
    imagen_file: null as File | null,
    activo: true,
    remove_image: false,
})

const isEditing = computed(() => editingId.value !== null)

const displayPreview = computed(() => {
    if (form.remove_image) return null
    return previewUrl.value || currentImageUrl.value
})

const hasImagePreview = computed(() => !!displayPreview.value)

function storeUrl(): string {
    return props.endpoints.store
}

function updateUrl(id: number): string {
    return `${props.endpoints.updateBase}/${id}`
}

function destroyUrl(id: number): string {
    return `${props.endpoints.destroyBase}/${id}`
}

function reorderUrl(): string {
    return props.endpoints.reorder
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

    form.titulo = ''
    form.descripcion = ''
    form.imagen_file = null
    form.activo = true
    form.remove_image = false
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(row: BannerRow) {
    resetForm()

    editingId.value = row.id
    currentImageUrl.value = row.imagen ?? null

    form.titulo = row.titulo ?? ''
    form.descripcion = row.descripcion ?? ''
    form.activo = !!row.activo
    form.imagen_file = null
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

onBeforeUnmount(() => {
    revokePreview()
    window.removeEventListener('keydown', onEscape)
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

    if (!form.titulo.trim()) {
        form.setError('titulo', 'El título es obligatorio.')
    }

    if (!isEditing.value && !form.imagen_file) {
        form.setError('imagen_file', 'Debes seleccionar una imagen para el banner.')
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
        onError: () => {
            errorAlert('No se pudo guardar', 'Revisa la información del formulario.')
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
                        successAlert('Banner actualizado', 'El banner se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form.post(storeUrl(), {
        ...commonOptions,
        onSuccess: () => {
            closeForm(true)
        },
    })
}

async function destroyRow(row: BannerRow) {
    const result = await Swal.fire({
        title: '¿Eliminar banner?',
        text: `Se eliminará "${row.titulo}" de forma permanente.`,
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
            successAlert('Eliminado', 'El banner fue eliminado correctamente.')
        },
        onError: () => {
            errorAlert('No se pudo eliminar', 'Ocurrió un problema al eliminar el banner.')
        },
    })
}

function onDragStart(index: number) {
    dragIndex.value = index
}

function onDragEnter(index: number) {
    dropIndex.value = index
}

function onDragEnd() {
    dragIndex.value = null
    dropIndex.value = null
}

function moveItem<T>(list: T[], from: number, to: number) {
    const clone = [...list]
    const [moved] = clone.splice(from, 1)
    clone.splice(to, 0, moved)
    return clone
}

function onDrop(index: number) {
    if (dragIndex.value === null || dragIndex.value === index) {
        onDragEnd()
        return
    }

    const original = [...props.banners.data]
    bannerItems.value = moveItem(bannerItems.value, dragIndex.value, index)

    const payload = bannerItems.value.map((item) => ({
        id: item.id,
    }))

    router.post(reorderUrl(), { items: payload }, {
        preserveScroll: true,
        onSuccess: () => {
            successAlert('Orden actualizado', 'Los banners se acomodaron correctamente.')
            onDragEnd()
        },
        onError: () => {
            bannerItems.value = original
            onDragEnd()
            errorAlert('No se pudo reordenar', 'Ocurrió un problema al guardar el nuevo orden.')
        },
    })
}
</script>

<template>
    <div>
        <Head title="Banners" />

        <div class="space-y-5 p-4 sm:space-y-6 sm:p-6 lg:p-8 2xl:p-10">
            <section class="overflow-hidden rounded-[24px] border border-neutral-200 bg-white shadow-sm sm:rounded-[28px]">
                <div class="flex flex-col gap-4 border-b border-neutral-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="text-base font-bold text-neutral-900 sm:text-lg">
                            Listado de banners
                        </p>
                        <p class="mt-1 text-sm text-neutral-500">
                            Arrastra las tarjetas para cambiar su orden.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center self-start rounded-2xl bg-[#11142C] px-5 py-3 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-[var(--brand-green)] hover:text-[#11142C] sm:self-auto"
                        @click="openCreate"
                    >
                        <span class="mr-2 text-base">+</span>
                        Nuevo banner
                    </button>
                </div>

                <div v-if="bannerItems.length" class="p-4 sm:p-5 lg:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        <article
                            v-for="(row, index) in bannerItems"
                            :key="row.id"
                            draggable="true"
                            class="group cursor-move overflow-hidden rounded-[22px] border border-neutral-200 bg-white shadow-sm transition-all duration-200 hover:shadow-md"
                            :class="[
                                dragIndex === index ? 'opacity-80' : '',
                                dropIndex === index ? 'ring-2 ring-sky-200' : '',
                            ]"
                            @dragstart="onDragStart(index)"
                            @dragenter.prevent="onDragEnter(index)"
                            @dragover.prevent
                            @drop.prevent="onDrop(index)"
                            @dragend="onDragEnd"
                        >
                            <div class="relative aspect-[16/8] overflow-hidden bg-neutral-100">
                                <img
                                    v-if="row.imagen"
                                    :src="row.imagen"
                                    :alt="row.titulo"
                                    class="h-full w-full object-cover"
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
                                        :class="row.activo
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-neutral-200 text-neutral-600'"
                                    >
                                        {{ row.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-3 p-4">
                                <div>
                                    <h3 class="line-clamp-2 text-base font-black text-neutral-900">
                                        {{ row.titulo }}
                                    </h3>
                                    <p v-if="row.descripcion" class="mt-1 line-clamp-2 text-sm text-neutral-500">
                                        {{ row.descripcion }}
                                    </p>
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
                    </div>
                </div>
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
                                            {{ isEditing ? 'Editar banner' : 'Nuevo banner' }}
                                        </h3>

                                        <p class="mt-1 text-sm leading-6 text-neutral-500">
                                            Carga la imagen del banner y completa su información.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-neutral-200 bg-white text-lg text-neutral-500 transition-colors duration-200 hover:bg-neutral-50 hover:text-neutral-800"
                                        @click="closeForm"
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
                                                    Título
                                                </label>
                                                <input
                                                    v-model="form.titulo"
                                                    type="text"
                                                    placeholder="Ej. Promoción de temporada"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100 focus:outline-none"
                                                />
                                                <p v-if="form.errors.titulo" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.titulo }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">
                                                    Descripción
                                                </label>
                                                <textarea
                                                    v-model="form.descripcion"
                                                    rows="5"
                                                    placeholder="Agrega una breve descripción para el banner"
                                                    class="w-full resize-none rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:ring-4 focus:ring-sky-100 focus:outline-none"
                                                />
                                                <p v-if="form.errors.descripcion" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.descripcion }}
                                                </p>
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

                                            <div class="rounded-[24px] border border-neutral-200 bg-neutral-50 p-4">
                                                <p class="text-sm font-bold text-neutral-800">
                                                    Resumen
                                                </p>

                                                <div class="mt-4 rounded-[20px] border border-neutral-200 bg-white p-4">
                                                    <p class="line-clamp-2 text-sm font-black text-neutral-900">
                                                        {{ form.titulo || 'Título del banner' }}
                                                    </p>
                                                    <p v-if="form.descripcion" class="mt-2 line-clamp-4 text-sm text-neutral-500">
                                                        {{ form.descripcion }}
                                                    </p>
                                                    <p class="mt-2 text-xs text-neutral-500">
                                                        {{ form.activo ? 'Visible en la tienda' : 'Oculto temporalmente' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="order-1 border-b border-neutral-200 bg-neutral-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                            <div class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <p class="text-sm font-bold text-neutral-800">
                                                        Vista previa
                                                    </p>
                                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral-500">
                                                        Banner
                                                    </span>
                                                </div>

                                                <div class="relative overflow-hidden rounded-[20px] border border-dashed border-neutral-300 bg-neutral-50">
                                                    <div class="aspect-[16/9]">
                                                        <img
                                                            v-if="displayPreview"
                                                            :src="displayPreview"
                                                            alt="Vista previa del banner"
                                                            class="h-full w-full object-cover"
                                                        />

                                                        <div
                                                            v-else
                                                            class="flex h-full flex-col items-center justify-center px-5 text-center"
                                                        >
                                                            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl shadow-sm">
                                                                🖼️
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
                                                        @click="removeImage"
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
                                                        @click="closeForm"
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
                                                                ? 'Actualizar banner'
                                                                : 'Registrar banner' }}
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
