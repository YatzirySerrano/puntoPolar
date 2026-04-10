<script setup lang="ts">
import { computed, ref } from 'vue';
import ProductCard from '@/components/public/ProductCard.vue';
import sartenesTitle from '@/img/sartenes-title.jpeg';

interface Categoria {
    id: number;
    nombre: string;
    slug: string;
}

interface Producto {
    id: number;
    nombre: string;
    slug: string;
    sku: string;
    descripcion?: string | null;
    precio: number | string;
    precio_comparacion?: number | string | null;
    stock: number;
    imagen_principal?: string | null;
    categoria_id?: number | null;
    categoria?: Categoria | null;
}

const props = defineProps<{
    categorias: Categoria[];
    productos: Producto[];
}>();

const categoriaActiva = ref<string>('todas');
const busqueda = ref('');
const soloDisponibles = ref(false);
const orden = ref<'relevancia' | 'precio_asc' | 'precio_desc' | 'nombre'>(
    'relevancia',
);
const precioMax = ref(99999);

const normalizar = (valor: string | null | undefined) =>
    (valor ?? '').toLowerCase().trim();

const lista = computed(() => {
    const txt = normalizar(busqueda.value);

    const filtrados = props.productos.filter((producto) => {
        const coincideCategoria =
            categoriaActiva.value === 'todas' ||
            producto.categoria?.slug === categoriaActiva.value ||
            props.categorias.find((cat) => cat.id === producto.categoria_id)
                ?.slug === categoriaActiva.value;

        const coincideBusqueda =
            !txt ||
            normalizar(producto.nombre).includes(txt) ||
            normalizar(producto.descripcion).includes(txt) ||
            normalizar(producto.sku).includes(txt);

        const precio = Number(producto.precio);
        const coincidePrecio = precio <= precioMax.value;
        const coincideStock = !soloDisponibles.value || producto.stock > 0;

        return (
            coincideCategoria &&
            coincideBusqueda &&
            coincidePrecio &&
            coincideStock
        );
    });

    if (orden.value === 'precio_asc') {
        return [...filtrados].sort(
            (a, b) => Number(a.precio) - Number(b.precio),
        );
    }

    if (orden.value === 'precio_desc') {
        return [...filtrados].sort(
            (a, b) => Number(b.precio) - Number(a.precio),
        );
    }

    if (orden.value === 'nombre') {
        return [...filtrados].sort((a, b) => a.nombre.localeCompare(b.nombre));
    }

    return filtrados;
});
</script>

<template>
    <section class="bg-[var(--brand-soft)]">
        <div
            class="mx-auto grid w-full max-w-[1500px] gap-8 px-4 py-12 md:grid-cols-[300px_1fr] md:px-8"
        >
            <aside
                class="rounded-[30px] border border-[var(--brand-gray)]/70 bg-white p-5 shadow-sm"
            >
                <h2 class="mb-5 text-4xl font-black text-neutral-900">
                    Productos
                </h2>

                <div class="space-y-4">
                    <input
                        v-model="busqueda"
                        type="text"
                        placeholder="Buscar por nombre, SKU o descripción"
                        class="h-12 w-full rounded-2xl border border-[var(--brand-gray)] bg-white px-4 text-sm transition outline-none focus:border-[var(--brand-blue)] focus:shadow-[0_0_0_4px_rgba(48,190,239,0.15)]"
                    />

                    <select
                        v-model="orden"
                        class="h-12 w-full rounded-2xl border border-[var(--brand-gray)] bg-white px-4 text-sm transition outline-none focus:border-[var(--brand-blue)]"
                    >
                        <option value="relevancia">Ordenar: Relevancia</option>
                        <option value="precio_asc">
                            Precio: Menor a mayor
                        </option>
                        <option value="precio_desc">
                            Precio: Mayor a menor
                        </option>
                        <option value="nombre">Nombre A-Z</option>
                    </select>

                    <div>
                        <label
                            class="mb-1 block text-xs font-bold tracking-wide text-neutral-500 uppercase"
                            >Precio máximo</label
                        >
                        <input
                            v-model.number="precioMax"
                            type="range"
                            min="100"
                            max="99999"
                            step="50"
                            class="w-full accent-[var(--brand-blue)]"
                        />
                        <p class="text-sm font-semibold text-neutral-600">
                            Hasta ${{ precioMax.toLocaleString('es-MX') }}
                        </p>
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-xl bg-[var(--brand-soft)] px-3 py-2 text-sm font-semibold text-neutral-600 transition hover:bg-[var(--brand-gray)]/40"
                    >
                        <input
                            v-model="soloDisponibles"
                            type="checkbox"
                            class="accent-[var(--brand-green)]"
                        />
                        Mostrar solo disponibles
                    </label>
                </div>

                <div class="mt-6 space-y-2">
                    <button
                        type="button"
                        class="group flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left font-extrabold transition duration-300"
                        :class="
                            categoriaActiva === 'todas'
                                ? 'bg-[var(--brand-blue)] text-white shadow-lg'
                                : 'bg-[var(--brand-soft)] text-neutral-700 hover:bg-white hover:shadow-md'
                        "
                        @click="categoriaActiva = 'todas'"
                    >
                        <span>Todas</span>
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-[var(--brand-green)]"
                        />
                    </button>

                    <button
                        v-for="categoria in categorias"
                        :key="categoria.id"
                        type="button"
                        class="group flex w-full items-center justify-between rounded-2xl px-4 py-3 text-left font-extrabold transition duration-300"
                        :class="
                            categoriaActiva === categoria.slug
                                ? 'bg-[var(--brand-blue)] text-white shadow-lg'
                                : 'bg-[var(--brand-soft)] text-neutral-700 hover:bg-white hover:shadow-md'
                        "
                        @click="categoriaActiva = categoria.slug"
                    >
                        <span>{{ categoria.nombre }}</span>
                        <span
                            class="h-2.5 w-2.5 rounded-full bg-[var(--brand-green)] shadow-[0_0_10px_rgba(125,208,60,0.4)]"
                        />
                    </button>
                </div>
            </aside>

            <div class="space-y-8">
                <div
                    class="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-white p-5 shadow-sm"
                >
                    <img
                        :src="sartenesTitle"
                        alt="Sartenes"
                        class="h-16 w-auto drop-shadow-[0_8px_18px_rgba(48,190,239,0.2)] md:h-20"
                    />
                    <p
                        class="rounded-full bg-[var(--brand-soft)] px-5 py-2 text-sm font-bold text-neutral-500"
                    >
                        Mostrando {{ lista.length }} producto(s)
                    </p>
                </div>

                <div
                    v-if="lista.length"
                    class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <ProductCard
                        v-for="producto in lista"
                        :key="producto.id"
                        :producto="producto"
                    />
                </div>

                <div
                    v-else
                    class="rounded-3xl border border-dashed border-[var(--brand-gray)] bg-white p-10 text-center"
                >
                    <h3 class="text-xl font-black text-neutral-900">
                        No encontramos productos
                    </h3>
                    <p class="mt-2 text-neutral-500">
                        Intenta con otra categoría o cambia tus filtros.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
