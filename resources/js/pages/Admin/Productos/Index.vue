<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
    categoria?: { nombre: string } | null;
    marca?: { nombre: string } | null;
}

interface OptionItem {
    id: number;
    nombre: string;
}

defineProps<{
    productos: {
        data: Producto[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    categorias: OptionItem[];
    marcas: OptionItem[];
    filters: { search?: string };
}>();

const { formatCurrency } = useCurrency();
const editandoId = ref<number | null>(null);

const form = useForm({
    nombre: '',
    sku: '',
    precio: 0,
    stock: 0,
    categoria_id: null as number | null,
    marca_id: null as number | null,
    descripcion: '',
    destacado: false,
    visible: true,
    activo: true,
});

const enEdicion = computed(() => editandoId.value !== null);

const submit = () => {
    if (enEdicion.value && editandoId.value) {
        form.put(`/admin/productos/${editandoId.value}`, {
            preserveScroll: true,
            onSuccess: () => resetForm(),
        });

        return;
    }

    form.post('/admin/productos', {
        preserveScroll: true,
        onSuccess: () => resetForm(),
    });
};

const editar = (producto: Producto) => {
    editandoId.value = producto.id;
    form.nombre = producto.nombre;
    form.sku = producto.sku;
    form.precio = Number(producto.precio);
    form.stock = producto.stock;
    form.categoria_id = null;
    form.marca_id = null;
    form.descripcion = '';
    form.destacado = producto.destacado;
    form.visible = producto.visible;
    form.activo = producto.activo;
};

const eliminar = (id: number) => {
    if (!confirm('¿Deseas eliminar este producto?')) {
        return;
    }

    router.delete(`/admin/productos/${id}`, { preserveScroll: true });
};

const resetForm = () => {
    editandoId.value = null;
    form.reset();
};
</script>

<template>
    <Head title="Admin · Productos" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header class="rounded-2xl border bg-white p-4 shadow-sm sm:p-6">
            <h1 class="text-2xl font-black">Gestión de productos</h1>
            <p class="text-sm text-neutral-500">
                CRUD administrativo para catálogo.
            </p>
        </header>

        <section class="grid gap-6 xl:grid-cols-[1.2fr_2fr]">
            <form
                class="rounded-2xl border bg-white p-4 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <h2 class="text-lg font-black">
                    {{ enEdicion ? 'Editar producto' : 'Nuevo producto' }}
                </h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <input
                        v-model="form.nombre"
                        type="text"
                        placeholder="Nombre"
                        class="h-11 rounded-xl border px-3 text-sm"
                    />
                    <input
                        v-model="form.sku"
                        type="text"
                        placeholder="SKU"
                        class="h-11 rounded-xl border px-3 text-sm"
                    />
                    <input
                        v-model.number="form.precio"
                        type="number"
                        step="0.01"
                        placeholder="Precio"
                        class="h-11 rounded-xl border px-3 text-sm"
                    />
                    <input
                        v-model.number="form.stock"
                        type="number"
                        placeholder="Stock"
                        class="h-11 rounded-xl border px-3 text-sm"
                    />
                    <select
                        v-model="form.categoria_id"
                        class="h-11 rounded-xl border px-3 text-sm"
                    >
                        <option :value="null">Categoría</option>
                        <option
                            v-for="categoria in categorias"
                            :key="categoria.id"
                            :value="categoria.id"
                        >
                            {{ categoria.nombre }}
                        </option>
                    </select>
                    <select
                        v-model="form.marca_id"
                        class="h-11 rounded-xl border px-3 text-sm"
                    >
                        <option :value="null">Marca</option>
                        <option
                            v-for="marca in marcas"
                            :key="marca.id"
                            :value="marca.id"
                        >
                            {{ marca.nombre }}
                        </option>
                    </select>
                </div>

                <textarea
                    v-model="form.descripcion"
                    rows="3"
                    placeholder="Descripción"
                    class="mt-3 w-full rounded-xl border p-3 text-sm"
                />

                <div class="mt-3 flex flex-wrap gap-3 text-sm">
                    <label class="flex items-center gap-2"
                        ><input v-model="form.destacado" type="checkbox" />
                        Destacado</label
                    >
                    <label class="flex items-center gap-2"
                        ><input v-model="form.visible" type="checkbox" />
                        Visible</label
                    >
                    <label class="flex items-center gap-2"
                        ><input v-model="form.activo" type="checkbox" />
                        Activo</label
                    >
                </div>

                <div class="mt-4 flex gap-2">
                    <button
                        type="submit"
                        class="rounded-full bg-[var(--brand-blue)] px-5 py-2 text-sm font-bold text-white"
                    >
                        Guardar
                    </button>
                    <button
                        type="button"
                        class="rounded-full border px-5 py-2 text-sm font-bold"
                        @click="resetForm"
                    >
                        Cancelar
                    </button>
                </div>
            </form>

            <div class="rounded-2xl border bg-white p-4 shadow-sm sm:p-6">
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <h2 class="text-lg font-black">Listado</h2>
                    <input
                        :value="filters.search || ''"
                        type="text"
                        placeholder="Buscar..."
                        class="h-10 rounded-xl border px-3 text-sm"
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
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead
                            class="bg-neutral-50 text-left text-xs text-neutral-500 uppercase"
                        >
                            <tr>
                                <th class="px-3 py-2">Producto</th>
                                <th class="px-3 py-2">Precio</th>
                                <th class="px-3 py-2">Stock</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="producto in productos.data"
                                :key="producto.id"
                                class="border-t"
                            >
                                <td class="px-3 py-2">
                                    <p class="font-bold">
                                        {{ producto.nombre }}
                                    </p>
                                    <p class="text-xs text-neutral-500">
                                        {{ producto.sku }}
                                    </p>
                                </td>
                                <td class="px-3 py-2">
                                    {{ formatCurrency(producto.precio) }}
                                </td>
                                <td class="px-3 py-2">{{ producto.stock }}</td>
                                <td class="px-3 py-2">
                                    <span
                                        class="rounded-full bg-neutral-100 px-2 py-1 text-xs"
                                        >{{
                                            producto.activo
                                                ? 'Activo'
                                                : 'Inactivo'
                                        }}</span
                                    >
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-full border px-3 py-1 text-xs"
                                            @click="editar(producto)"
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
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
