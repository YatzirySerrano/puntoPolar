<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useCurrency } from '@/composables/useCurrency'
import { index as productosIndex } from '@/routes/admin/productos'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Productos',
                href: productosIndex().url,
            },
        ],
    },
})

interface ImagenProducto {
    id: number
    ruta: string | null
    orden: number
}

interface Producto {
    id: number
    nombre: string
    slug: string
    sku: string
    precio: number
    stock: number
    destacado: boolean
    activo: boolean
    visible: boolean
    descripcion?: string | null
    categoria_id?: number | null
    categoria_nombre?: string | null
    marca_id?: number | null
    marca_nombre?: string | null
    imagen_principal?: string | null
    imagenes: ImagenProducto[]
}

interface OptionItem {
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
    productos: Paginated<Producto>
    categorias: OptionItem[]
    marcas: OptionItem[]
    filters: {
        search?: string
        categoria_id?: number | null
        marca_id?: number | null
        status?: string
        destacado?: string
    }
    endpoints: Endpoints
}>()

const { formatCurrency } = useCurrency()

const showForm = ref(false)
const editingId = ref<number | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const search = ref(props.filters.search ?? '')
const categoriaId = ref<number | ''>(props.filters.categoria_id ?? '')
const marcaId = ref<number | ''>(props.filters.marca_id ?? '')
const status = ref(props.filters.status ?? 'all')
const destacado = ref(props.filters.destacado ?? 'all')

const selectedFiles = ref<File[]>([])
const selectedFileNames = ref<string[]>([])
const previewUrls = ref<string[]>([])
const existingImages = ref<ImagenProducto[]>([])
const removedImageIds = ref<number[]>([])

const form = useForm({
    nombre: '',
    slug: '',
    sku: '',
    precio: 0,
    stock: 0,
    categoria_id: '' as number | '',
    marca_id: '' as number | '',
    descripcion: '',
    destacado: false,
    visible: true,
    activo: true,
    imagenes_files: [] as File[],
    remove_image_ids: [] as number[],
})

const isEditing = computed(() => editingId.value !== null)

const totalStock = computed(() =>
    props.productos.data.reduce((acc, item) => acc + Number(item.stock || 0), 0),
)

const totalActivos = computed(() =>
    props.productos.data.filter((item) => item.activo).length,
)

const totalDestacados = computed(() =>
    props.productos.data.filter((item) => item.destacado).length,
)

const summaryText = computed(() => {
    if (!props.productos.total) return 'No hay productos registrados aún.'

    const from = props.productos.from ?? 1
    const to = props.productos.to ?? props.productos.data.length

    return `Mostrando ${from} - ${to} de ${props.productos.total} productos`
})

function revokePreviewUrls() {
    previewUrls.value.forEach((url) => {
        if (url.startsWith('blob:')) {
            URL.revokeObjectURL(url)
        }
    })
    previewUrls.value = []
}

function clearNativeFileInput() {
    if (fileInput.value) {
        fileInput.value.value = ''
    }
}

function resetForm() {
    editingId.value = null
    existingImages.value = []
    removedImageIds.value = []
    selectedFiles.value = []
    selectedFileNames.value = []
    revokePreviewUrls()
    clearNativeFileInput()

    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.nombre = ''
    form.slug = ''
    form.sku = ''
    form.precio = 0
    form.stock = 0
    form.categoria_id = ''
    form.marca_id = ''
    form.descripcion = ''
    form.destacado = false
    form.visible = true
    form.activo = true
    form.imagenes_files = []
    form.remove_image_ids = []
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(producto: Producto) {
    resetForm()

    editingId.value = producto.id
    existingImages.value = [...(producto.imagenes ?? [])]

    form.nombre = producto.nombre ?? ''
    form.slug = producto.slug ?? ''
    form.sku = producto.sku ?? ''
    form.precio = Number(producto.precio ?? 0)
    form.stock = Number(producto.stock ?? 0)
    form.categoria_id = producto.categoria_id ?? ''
    form.marca_id = producto.marca_id ?? ''
    form.descripcion = producto.descripcion ?? ''
    form.destacado = !!producto.destacado
    form.visible = !!producto.visible
    form.activo = !!producto.activo
    form.imagenes_files = []
    form.remove_image_ids = []

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

function onImagesChange(event: Event) {
    const input = event.target as HTMLInputElement
    const files = Array.from(input.files ?? [])

    selectedFiles.value = files
    selectedFileNames.value = files.map((file) => file.name)
    revokePreviewUrls()
    previewUrls.value = files.map((file) => URL.createObjectURL(file))
    form.imagenes_files = files
}

function removeSelectedNewImage(index: number) {
    const nextFiles = [...selectedFiles.value]
    const nextNames = [...selectedFileNames.value]
    const nextPreviews = [...previewUrls.value]

    const removedPreview = nextPreviews[index]
    if (removedPreview?.startsWith('blob:')) {
        URL.revokeObjectURL(removedPreview)
    }

    nextFiles.splice(index, 1)
    nextNames.splice(index, 1)
    nextPreviews.splice(index, 1)

    selectedFiles.value = nextFiles
    selectedFileNames.value = nextNames
    previewUrls.value = nextPreviews
    form.imagenes_files = nextFiles

    clearNativeFileInput()
}

function removeExistingImage(imageId: number) {
    removedImageIds.value = [...removedImageIds.value, imageId]
    existingImages.value = existingImages.value.filter((image) => image.id !== imageId)
    form.remove_image_ids = [...removedImageIds.value]
}

function successAlert(title: string, text: string) {
    Swal.fire({
        icon: 'success',
        title,
        text,
        timer: 1500,
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

    if (!form.sku.trim()) {
        form.setError('sku', 'El SKU es obligatorio.')
    }

    if (Number(form.precio) < 0) {
        form.setError('precio', 'El precio no puede ser menor a 0.')
    }

    if (Number(form.stock) < 0) {
        form.setError('stock', 'El stock no puede ser menor a 0.')
    }

    return Object.keys(form.errors).length === 0
}

function submit() {
    const wasEditing = isEditing.value
    const id = editingId.value

    if (wasEditing && !id) return
    if (!validateBeforeSubmit()) return

    const payloadTransform = (data: ReturnType<typeof form.data>) => ({
        ...data,
        categoria_id: data.categoria_id === '' ? null : data.categoria_id,
        marca_id: data.marca_id === '' ? null : data.marca_id,
        precio: Number(data.precio ?? 0),
        stock: Number(data.stock ?? 0),
        destacado: data.destacado ? 1 : 0,
        visible: data.visible ? 1 : 0,
        activo: data.activo ? 1 : 0,
        remove_image_ids: removedImageIds.value,
    })

    const commonOptions = {
        preserveScroll: true,
        forceFormData: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.nombre ||
                errors.slug ||
                errors.sku ||
                errors.precio ||
                errors.stock ||
                errors.categoria_id ||
                errors.marca_id ||
                errors.descripcion ||
                errors.imagenes_files ||
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
                ...payloadTransform(data),
                _method: 'put',
            }))
            .post(`${props.endpoints.updateBase}/${id}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Producto actualizado', 'El producto se actualizó correctamente.')
                    })
                },
            })

        return
    }

    form
        .transform((data) => payloadTransform(data))
        .post(props.endpoints.store, {
            ...commonOptions,
            onSuccess: () => {
                closeForm(true)
                requestAnimationFrame(() => {
                    successAlert('Producto creado', 'El producto se registró correctamente.')
                })
            },
        })
}

async function eliminar(id: number) {
    const result = await Swal.fire({
        title: '¿Eliminar producto?',
        text: 'Esta acción no se puede deshacer.',
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
        onSuccess: () => successAlert('Producto eliminado', 'El producto fue eliminado correctamente.'),
        onError: () => errorAlert('Error', 'No se pudo eliminar el producto.'),
    })
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch([search, categoriaId, marcaId, status, destacado], () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
                categoria_id: categoriaId.value || undefined,
                marca_id: marcaId.value || undefined,
                status: status.value !== 'all' ? status.value : undefined,
                destacado: destacado.value !== 'all' ? destacado.value : undefined,
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
    revokePreviewUrls()
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})
</script>

<template>
    <Head title="Admin · Productos" />

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
                                placeholder="Nombre, SKU o descripción..."
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            />
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Categoría</span>
                            <select
                                v-model="categoriaId"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Todas</option>
                                <option
                                    v-for="categoria in categorias"
                                    :key="categoria.id"
                                    :value="categoria.id"
                                >
                                    {{ categoria.nombre }}
                                </option>
                            </select>
                        </label>

                        <label>
                            <span class="mb-2 block text-sm font-bold text-neutral-700">Marca</span>
                            <select
                                v-model="marcaId"
                                class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Todas</option>
                                <option
                                    v-for="marca in marcas"
                                    :key="marca.id"
                                    :value="marca.id"
                                >
                                    {{ marca.nombre }}
                                </option>
                            </select>
                        </label>

                        <div class="grid grid-cols-2 gap-4">
                            <label>
                                <span class="mb-2 block text-sm font-bold text-neutral-700">Estado</span>
                                <select
                                    v-model="status"
                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                >
                                    <option value="all">Todos</option>
                                    <option value="active">Activos</option>
                                    <option value="inactive">Inactivos</option>
                                    <option value="visible">Visibles</option>
                                    <option value="hidden">Ocultos</option>
                                </select>
                            </label>

                            <label>
                                <span class="mb-2 block text-sm font-bold text-neutral-700">Destacado</span>
                                <select
                                    v-model="destacado"
                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                >
                                    <option value="all">Todos</option>
                                    <option value="yes">Sí</option>
                                    <option value="no">No</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="lg:ml-auto inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:brightness-95"
                        @click="openCreate"
                    >
                        + Nuevo producto
                    </button>
                </div>
            </div>

            <div class="grid gap-3 px-4 py-4 sm:grid-cols-3 sm:px-6 xl:grid-cols-4">
                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Productos visibles</p>
                    <p class="text-2xl font-black">{{ productos.data.length }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Stock total</p>
                    <p class="text-2xl font-black">{{ totalStock }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Activos</p>
                    <p class="text-2xl font-black">{{ totalActivos }}</p>
                </article>

                <article class="rounded-2xl bg-neutral-50 p-4">
                    <p class="text-xs uppercase text-neutral-500">Destacados</p>
                    <p class="text-2xl font-black">{{ totalDestacados }}</p>
                </article>
            </div>

            <div class="px-4 pb-4 sm:px-6">
                <p class="text-sm text-neutral-500">
                    {{ summaryText }}
                </p>
            </div>
        </section>

        <section
            v-if="productos.data.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="producto in productos.data"
                :key="producto.id"
                class="group [perspective:1200px]"
            >
                <div
                    class="relative min-h-[360px] rounded-[24px] transition-transform duration-500 [transform-style:preserve-3d] group-hover:[transform:rotateY(180deg)]"
                >
                    <div
                        class="absolute inset-0 overflow-hidden rounded-[24px] border border-neutral-200 bg-white shadow-sm [backface-visibility:hidden]"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden bg-neutral-100">
                            <img
                                v-if="producto.imagen_principal"
                                :src="producto.imagen_principal"
                                :alt="producto.nombre"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center text-sm font-medium text-neutral-400"
                            >
                                Sin imágenes
                            </div>

                            <div class="absolute left-3 top-3 flex flex-wrap gap-2">
                                <span
                                    v-if="producto.destacado"
                                    class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-bold text-amber-700 shadow-sm"
                                >
                                    Destacado
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-[11px] font-bold shadow-sm"
                                    :class="producto.activo
                                        ? 'bg-emerald-100 text-emerald-700'
                                        : 'bg-neutral-200 text-neutral-600'"
                                >
                                    {{ producto.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3 p-4">
                            <div>
                                <h3 class="line-clamp-2 text-lg font-black text-neutral-900">
                                    {{ producto.nombre }}
                                </h3>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                                    {{ producto.sku }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <p class="text-2xl font-black text-neutral-900">
                                    {{ formatCurrency(producto.precio) }}
                                </p>
                                <div class="rounded-2xl bg-neutral-100 px-3 py-2 text-center">
                                    <p class="text-[10px] uppercase tracking-wide text-neutral-400">Stock</p>
                                    <p class="text-sm font-black text-neutral-800">
                                        {{ producto.stock }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-xs text-neutral-500">
                                <div class="rounded-xl bg-neutral-50 px-3 py-2">
                                    <span class="font-semibold text-neutral-700">Marca:</span>
                                    {{ producto.marca_nombre || 'Sin marca' }}
                                </div>
                                <div class="rounded-xl bg-neutral-50 px-3 py-2">
                                    <span class="font-semibold text-neutral-700">Categoría:</span>
                                    {{ producto.categoria_nombre || 'Sin categoría' }}
                                </div>
                            </div>

                            <div class="flex gap-2 pt-1">
                                <button
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-2xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 transition-colors duration-200 hover:bg-sky-100"
                                    @click="openEdit(producto)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex flex-1 items-center justify-center rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-colors duration-200 hover:bg-red-100"
                                    @click="eliminar(producto.id)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="absolute inset-0 overflow-hidden rounded-[24px] border border-neutral-200 bg-[#11142C] p-5 text-white shadow-sm [backface-visibility:hidden] [transform:rotateY(180deg)]"
                    >
                        <div class="flex h-full flex-col">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.18em] text-white/60">
                                        Detalles
                                    </p>
                                    <h3 class="mt-2 line-clamp-2 text-lg font-black">
                                        {{ producto.nombre }}
                                    </h3>
                                </div>

                                <span
                                    class="rounded-full px-3 py-1 text-[11px] font-bold"
                                    :class="producto.visible
                                        ? 'bg-white/15 text-white'
                                        : 'bg-red-500/20 text-red-200'"
                                >
                                    {{ producto.visible ? 'Visible' : 'Oculto' }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-white/60">Slug</p>
                                    <p class="mt-1 break-words font-semibold">{{ producto.slug }}</p>
                                </div>

                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-wide text-white/60">Descripción</p>
                                    <p class="mt-1 line-clamp-5 text-white/90">
                                        {{ producto.descripcion || 'Sin descripción registrada.' }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl bg-white/10 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-white/60">Imágenes</p>
                                        <p class="mt-1 font-semibold">{{ producto.imagenes?.length || 0 }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 px-4 py-3">
                                        <p class="text-xs uppercase tracking-wide text-white/60">Estado</p>
                                        <p class="mt-1 font-semibold">
                                            {{ producto.activo ? 'Activo' : 'Inactivo' }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-4">
                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-bold text-[#11142C] transition hover:bg-[var(--brand-green)]"
                                    @click="openEdit(producto)"
                                >
                                    Abrir edición
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-[24px] border border-dashed border-neutral-300 bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-lg font-black text-neutral-900">
                No hay productos registrados aún
            </h2>
            <p class="mt-2 text-sm text-neutral-500">
                Crea tu primer producto para comenzar a poblar el catálogo.
            </p>
            <button
                type="button"
                class="mt-5 inline-flex items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-5 py-3 text-sm font-bold text-white shadow-md transition hover:brightness-95"
                @click="openCreate"
            >
                Crear producto
            </button>
        </section>

        <section
            v-if="productos.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in productos.links" :key="index">
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
                            class="flex h-[92dvh] w-full flex-col overflow-hidden rounded-t-[26px] bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-7xl sm:rounded-[26px]"
                        >
                            <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-5 sm:px-6">
                                <div class="min-w-0">

                                    <h3 class="text-lg font-black tracking-tight text-neutral-900 sm:text-xl md:text-2xl">
                                        {{ isEditing ? 'Editar producto' : 'Registrar producto' }}
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
                                    class="grid min-h-full gap-0 lg:grid-cols-[1.1fr_0.9fr]"
                                    @submit.prevent="submit"
                                >
                                    <div class="order-2 space-y-5 p-5 sm:p-6 lg:order-1">
                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">Nombre</label>
                                                <input
                                                    v-model="form.nombre"
                                                    type="text"
                                                    placeholder="Ej. Cámara IP 4MP"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.nombre" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.nombre }}
                                                </p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">SKU</label>
                                                <input
                                                    v-model="form.sku"
                                                    type="text"
                                                    placeholder="Ej. CAM-4MP-001"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                />
                                                <p v-if="form.errors.sku" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.sku }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">Slug</label>
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

                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <label class="mb-2 block text-sm font-bold text-neutral-700">Precio</label>
                                                    <input
                                                        v-model="form.precio"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    />
                                                    <p v-if="form.errors.precio" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.precio }}
                                                    </p>
                                                </div>

                                                <div>
                                                    <label class="mb-2 block text-sm font-bold text-neutral-700">Stock</label>
                                                    <input
                                                        v-model="form.stock"
                                                        type="number"
                                                        min="0"
                                                        class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                    />
                                                    <p v-if="form.errors.stock" class="mt-2 text-sm font-medium text-red-600">
                                                        {{ form.errors.stock }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-5 md:grid-cols-2">
                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">Categoría</label>
                                                <select
                                                    v-model="form.categoria_id"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                >
                                                    <option value="">Sin categoría</option>
                                                    <option
                                                        v-for="categoria in categorias"
                                                        :key="categoria.id"
                                                        :value="categoria.id"
                                                    >
                                                        {{ categoria.nombre }}
                                                    </option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-bold text-neutral-700">Marca</label>
                                                <select
                                                    v-model="form.marca_id"
                                                    class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                                >
                                                    <option value="">Sin marca</option>
                                                    <option
                                                        v-for="marca in marcas"
                                                        :key="marca.id"
                                                        :value="marca.id"
                                                    >
                                                        {{ marca.nombre }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="mb-2 block text-sm font-bold text-neutral-700">Descripción</label>
                                            <textarea
                                                v-model="form.descripcion"
                                                rows="5"
                                                placeholder="Describe el producto, usos, compatibilidad o características clave"
                                                class="w-full resize-none rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm text-neutral-900 shadow-sm transition-colors duration-200 placeholder:text-neutral-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100"
                                            />
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-3">
                                            <label class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-sm transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                                                <span class="text-sm font-bold text-neutral-700">Destacado</span>
                                                <input
                                                    v-model="form.destacado"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                />
                                            </label>

                                            <label class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-sm transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                                                <span class="text-sm font-bold text-neutral-700">Visible</span>
                                                <input
                                                    v-model="form.visible"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                />
                                            </label>

                                            <label class="flex items-center justify-between rounded-2xl border border-neutral-200 bg-white px-4 py-3 shadow-sm transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/60">
                                                <span class="text-sm font-bold text-neutral-700">Activo</span>
                                                <input
                                                    v-model="form.activo"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-neutral-300 text-sky-700 focus:ring-sky-300"
                                                />
                                            </label>
                                        </div>
                                    </div>

                                    <div class="order-1 border-b border-neutral-200 bg-neutral-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="space-y-5">
                                            <div class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <p class="text-sm font-bold text-neutral-800">
                                                        Imágenes actuales
                                                    </p>
                                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral-500">
                                                        {{ existingImages.length }}
                                                    </span>
                                                </div>

                                                <div
                                                    v-if="existingImages.length"
                                                    class="grid grid-cols-2 gap-3"
                                                >
                                                    <div
                                                        v-for="image in existingImages"
                                                        :key="image.id"
                                                        class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50"
                                                    >
                                                        <img
                                                            v-if="image.ruta"
                                                            :src="image.ruta"
                                                            alt="Imagen actual"
                                                            class="aspect-[4/3] h-full w-full object-cover"
                                                        />
                                                        <button
                                                            type="button"
                                                            class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-sm font-bold text-neutral-700 shadow-md transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                                                            @click="removeExistingImage(image.id)"
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                </div>

                                                <div
                                                    v-else
                                                    class="rounded-2xl border border-dashed border-neutral-300 px-4 py-8 text-center text-sm text-neutral-400"
                                                >
                                                    No hay imágenes actuales.
                                                </div>
                                            </div>

                                            <div class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                                <div class="mb-3 flex items-center justify-between gap-3">
                                                    <p class="text-sm font-bold text-neutral-800">
                                                        Nuevas imágenes
                                                    </p>
                                                    <span class="rounded-full bg-neutral-100 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-neutral-500">
                                                        {{ selectedFiles.length }}
                                                    </span>
                                                </div>

                                                <label class="inline-flex cursor-pointer items-center justify-center rounded-2xl bg-[var(--brand-blue)] px-4 py-3 text-sm font-semibold text-white transition-colors duration-200 hover:brightness-95">
                                                    Seleccionar imágenes
                                                    <input
                                                        ref="fileInput"
                                                        type="file"
                                                        accept="image/*"
                                                        multiple
                                                        class="hidden"
                                                        @change="onImagesChange"
                                                    />
                                                </label>

                                                <p class="mt-3 text-xs text-neutral-400">
                                                    Puedes cargar varias imágenes a la vez.
                                                </p>

                                                <div
                                                    v-if="previewUrls.length"
                                                    class="mt-4 grid grid-cols-2 gap-3"
                                                >
                                                    <div
                                                        v-for="(preview, index) in previewUrls"
                                                        :key="preview"
                                                        class="relative overflow-hidden rounded-2xl border border-neutral-200 bg-neutral-50"
                                                    >
                                                        <img
                                                            :src="preview"
                                                            alt="Vista previa"
                                                            class="aspect-[4/3] h-full w-full object-cover"
                                                        />
                                                        <button
                                                            type="button"
                                                            class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-sm font-bold text-neutral-700 shadow-md transition-colors duration-200 hover:bg-red-50 hover:text-red-600"
                                                            @click="removeSelectedNewImage(index)"
                                                        >
                                                            ×
                                                        </button>
                                                        <div class="border-t border-neutral-200 bg-white px-3 py-2 text-xs text-neutral-600">
                                                            {{ selectedFileNames[index] }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <p v-if="form.errors.imagenes_files" class="mt-2 text-sm font-medium text-red-600">
                                                    {{ form.errors.imagenes_files }}
                                                </p>
                                            </div>

                                            <div class="rounded-[24px] border border-neutral-200 bg-white p-4 shadow-sm">
                                                <p class="text-sm font-bold text-neutral-800">Resumen</p>
                                                <div class="mt-4 rounded-[20px] border border-neutral-200 bg-neutral-50 p-4">
                                                    <p class="line-clamp-2 text-sm font-black text-neutral-900">
                                                        {{ form.nombre || 'Nombre del producto' }}
                                                    </p>
                                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                                                        {{ form.sku || 'SKU del producto' }}
                                                    </p>
                                                    <p class="mt-3 text-lg font-black text-neutral-900">
                                                        {{ formatCurrency(Number(form.precio || 0)) }}
                                                    </p>
                                                    <p class="mt-1 text-sm text-neutral-500">
                                                        Stock: {{ form.stock || 0 }}
                                                    </p>
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
                                                            ? 'Actualizar producto'
                                                            : 'Registrar producto' }}
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
