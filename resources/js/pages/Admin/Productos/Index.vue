<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, watch } from 'vue';
import { useCurrency } from '@/composables/useCurrency';

interface Producto {
    id: number;
    nombre: string;
    sku: string;
    precio: number | string;
    stock: number;
    destacado: boolean;
    activo: boolean;
    visible: boolean;
    descripcion?: string | null;
    categoria_id?: number | null;
    marca_id?: number | null;
}

interface OptionItem {
    id: number;
    nombre: string;
}

const props = defineProps<{
    productos: {
        data: Producto[];
    };
    categorias: OptionItem[];
    marcas: OptionItem[];
    filters: { search?: string };
}>();

const { formatCurrency } = useCurrency();
const page = usePage();

const totalStock = computed(() =>
    props.productos.data.reduce((acc, item) => acc + item.stock, 0),
);

const mostrarFlash = () => {
    const ok = page.props.flash?.success;
    const err = page.props.flash?.error;

    if (ok) {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: String(ok),
            timer: 1800,
            showConfirmButton: false,
        });
    }

    if (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: String(err) });
    }
};

onMounted(mostrarFlash);
watch(() => page.props.flash, mostrarFlash, { deep: true });

const selectOptions = (items: OptionItem[], selected?: number | null) => {
    const base = '<option value="">Selecciona...</option>';
    const options = items
        .map(
            (item) =>
                `<option value="${item.id}" ${selected === item.id ? 'selected' : ''}>${item.nombre}</option>`,
        )
        .join('');

    return base + options;
};

const obtenerCamposProducto = (producto?: Producto) => {
    return `
        <div class="grid gap-3 text-left">
            <input id="swal-nombre" class="swal2-input" placeholder="Nombre" value="${producto?.nombre ?? ''}">
            <input id="swal-sku" class="swal2-input" placeholder="SKU" value="${producto?.sku ?? ''}">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <input id="swal-precio" class="swal2-input" type="number" step="0.01" placeholder="Precio" value="${producto?.precio ?? 0}">
                <input id="swal-stock" class="swal2-input" type="number" placeholder="Stock" value="${producto?.stock ?? 0}">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
                <select id="swal-categoria" class="swal2-select">${selectOptions(props.categorias, producto?.categoria_id)}</select>
                <select id="swal-marca" class="swal2-select">${selectOptions(props.marcas, producto?.marca_id)}</select>
            </div>
            <textarea id="swal-descripcion" class="swal2-textarea" placeholder="Descripción">${producto?.descripcion ?? ''}</textarea>
            <label style="display:flex;gap:8px;align-items:center;"><input id="swal-destacado" type="checkbox" ${producto?.destacado ? 'checked' : ''}>Destacado</label>
            <label style="display:flex;gap:8px;align-items:center;"><input id="swal-visible" type="checkbox" ${(producto?.visible ?? true) ? 'checked' : ''}>Visible</label>
            <label style="display:flex;gap:8px;align-items:center;"><input id="swal-activo" type="checkbox" ${(producto?.activo ?? true) ? 'checked' : ''}>Activo</label>
        </div>
    `;
};

const modalProducto = async (producto?: Producto) => {
    const isEdit = Boolean(producto);

    const result = await Swal.fire({
        title: isEdit ? 'Editar producto' : 'Nuevo producto',
        html: obtenerCamposProducto(producto),
        width: 700,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: isEdit ? 'Actualizar' : 'Crear',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const nombre = (
                document.getElementById('swal-nombre') as HTMLInputElement
            )?.value;
            const sku = (
                document.getElementById('swal-sku') as HTMLInputElement
            )?.value;

            if (!nombre || !sku) {
                Swal.showValidationMessage('Nombre y SKU son obligatorios.');

                return;
            }

            return {
                nombre,
                sku,
                precio: Number(
                    (document.getElementById('swal-precio') as HTMLInputElement)
                        ?.value || 0,
                ),
                stock: Number(
                    (document.getElementById('swal-stock') as HTMLInputElement)
                        ?.value || 0,
                ),
                categoria_id:
                    Number(
                        (
                            document.getElementById(
                                'swal-categoria',
                            ) as HTMLSelectElement
                        )?.value,
                    ) || null,
                marca_id:
                    Number(
                        (
                            document.getElementById(
                                'swal-marca',
                            ) as HTMLSelectElement
                        )?.value,
                    ) || null,
                descripcion: (
                    document.getElementById(
                        'swal-descripcion',
                    ) as HTMLTextAreaElement
                )?.value,
                destacado: (
                    document.getElementById(
                        'swal-destacado',
                    ) as HTMLInputElement
                )?.checked,
                visible: (
                    document.getElementById('swal-visible') as HTMLInputElement
                )?.checked,
                activo: (
                    document.getElementById('swal-activo') as HTMLInputElement
                )?.checked,
            };
        },
    });

    if (!result.isConfirmed || !result.value) {
        return;
    }

    if (isEdit && producto) {
        router.put(`/admin/productos/${producto.id}`, result.value, {
            preserveScroll: true,
        });

        return;
    }

    router.post('/admin/productos', result.value, { preserveScroll: true });
};

const eliminar = async (id: number) => {
    const result = await Swal.fire({
        title: '¿Eliminar producto?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(`/admin/productos/${id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admin · Productos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-5 shadow-sm sm:p-6"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-black sm:text-3xl">
                        Gestión de productos
                    </h1>
                    <p class="text-sm text-neutral-500">
                        Crea y edita productos desde modales.
                    </p>
                </div>

                <button
                    type="button"
                    class="rounded-full bg-[var(--brand-blue)] px-5 py-2 text-sm font-bold text-white transition hover:brightness-90"
                    @click="modalProducto()"
                >
                    + Nuevo producto
                </button>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Productos</p>
                    <p class="text-2xl font-black">
                        {{ productos.data.length }}
                    </p>
                </article>
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Stock</p>
                    <p class="text-2xl font-black">{{ totalStock }}</p>
                </article>
                <article
                    class="rounded-2xl bg-white p-4 shadow-sm sm:col-span-1 xl:col-span-2"
                >
                    <input
                        :value="filters.search || ''"
                        type="text"
                        placeholder="Buscar..."
                        class="h-10 w-full rounded-xl border px-3 text-sm"
                        @input="
                            router.get(
                                '/admin/productos',
                                {
                                    search: ($event.target as HTMLInputElement)
                                        .value,
                                },
                                { preserveState: true, replace: true },
                            )
                        "
                    />
                </article>
            </div>
        </header>

        <section
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="producto in productos.data"
                :key="producto.id"
                class="rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-1 hover:shadow-md"
            >
                <p class="font-black">{{ producto.nombre }}</p>
                <p class="text-xs text-neutral-500">{{ producto.sku }}</p>
                <p class="mt-2 text-xl font-black">
                    {{ formatCurrency(producto.precio) }}
                </p>
                <p class="text-sm text-neutral-600">
                    Stock: {{ producto.stock }}
                </p>

                <div class="mt-3 flex gap-2">
                    <button
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs"
                        @click="modalProducto(producto)"
                    >
                        Editar
                    </button>
                    <button
                        type="button"
                        class="rounded-full bg-red-500 px-3 py-1 text-xs text-white"
                        @click="eliminar(producto.id)"
                    >
                        Eliminar
                    </button>
                </div>
            </article>
        </section>
    </div>
</template>
