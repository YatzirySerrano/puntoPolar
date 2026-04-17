<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

interface Direccion {
    id: number;
    alias: string;
    nombre_receptor: string;
    telefono: string;
    calle: string;
    numero_exterior: string;
    numero_interior?: string | null;
    colonia: string;
    municipio: string;
    estado: string;
    pais: string;
    codigo_postal: string;
    referencias?: string | null;
    predeterminada: boolean;
}

interface Endpoints {
    store: string;
    updateBase: string;
    destroyBase: string;
}

const props = defineProps<{
    direcciones: Direccion[];
    endpoints: Endpoints;
}>();

const editingId = ref<number | null>(null);

const form = useForm({
    alias: '',
    nombre_receptor: '',
    telefono: '',
    calle: '',
    numero_exterior: '',
    numero_interior: '',
    colonia: '',
    municipio: '',
    estado: '',
    pais: 'México',
    codigo_postal: '',
    referencias: '',
    predeterminada: false,
});

const isEditing = computed(() => editingId.value !== null);

function resetForm() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.pais = 'México';
    form.predeterminada = false;
}

function editDireccion(direccion: Direccion) {
    editingId.value = direccion.id;
    form.alias = direccion.alias;
    form.nombre_receptor = direccion.nombre_receptor;
    form.telefono = direccion.telefono;
    form.calle = direccion.calle;
    form.numero_exterior = direccion.numero_exterior;
    form.numero_interior = direccion.numero_interior || '';
    form.colonia = direccion.colonia;
    form.municipio = direccion.municipio;
    form.estado = direccion.estado;
    form.pais = direccion.pais || 'México';
    form.codigo_postal = direccion.codigo_postal;
    form.referencias = direccion.referencias || '';
    form.predeterminada = direccion.predeterminada;
}

function submit() {
    if (isEditing.value) {
        form.put(`${props.endpoints.updateBase}/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Dirección actualizada',
                    timer: 1400,
                    showConfirmButton: false,
                });
                resetForm();
            },
        });
        return;
    }

    form.post(props.endpoints.store, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Dirección guardada',
                timer: 1400,
                showConfirmButton: false,
            });
            resetForm();
        },
    });
}

async function removeDireccion(id: number) {
    const result = await Swal.fire({
        icon: 'warning',
        title: '¿Eliminar dirección?',
        text: 'Esta acción no se puede deshacer.',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    form.delete(`${props.endpoints.destroyBase}/${id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Mis direcciones" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-4 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black">Mis direcciones</h1>
            <p class="text-sm text-neutral-500">
                Administra tus direcciones para futuras compras.
            </p>
        </header>

        <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-black">
                        {{ isEditing ? 'Editar dirección' : 'Nueva dirección' }}
                    </h2>

                    <button
                        v-if="isEditing"
                        type="button"
                        class="rounded-full border border-neutral-200 px-4 py-2 text-sm font-bold transition hover:bg-neutral-50"
                        @click="resetForm"
                    >
                        Cancelar edición
                    </button>
                </div>

                <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <label class="mb-2 block text-sm font-semibold">Alias</label>
                        <input v-model="form.alias" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.alias" class="mt-1 text-xs text-red-600">{{ form.errors.alias }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Nombre receptor</label>
                        <input v-model="form.nombre_receptor" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.nombre_receptor" class="mt-1 text-xs text-red-600">{{ form.errors.nombre_receptor }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Teléfono</label>
                        <input v-model="form.telefono" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.telefono" class="mt-1 text-xs text-red-600">{{ form.errors.telefono }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Código postal</label>
                        <input v-model="form.codigo_postal" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.codigo_postal" class="mt-1 text-xs text-red-600">{{ form.errors.codigo_postal }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-semibold">Calle</label>
                        <input v-model="form.calle" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.calle" class="mt-1 text-xs text-red-600">{{ form.errors.calle }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">No. exterior</label>
                        <input v-model="form.numero_exterior" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.numero_exterior" class="mt-1 text-xs text-red-600">{{ form.errors.numero_exterior }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">No. interior</label>
                        <input v-model="form.numero_interior" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Colonia</label>
                        <input v-model="form.colonia" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.colonia" class="mt-1 text-xs text-red-600">{{ form.errors.colonia }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Municipio</label>
                        <input v-model="form.municipio" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.municipio" class="mt-1 text-xs text-red-600">{{ form.errors.municipio }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">Estado</label>
                        <input v-model="form.estado" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.estado" class="mt-1 text-xs text-red-600">{{ form.errors.estado }}</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold">País</label>
                        <input v-model="form.pais" type="text" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.pais" class="mt-1 text-xs text-red-600">{{ form.errors.pais }}</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-2 block text-sm font-semibold">Referencias</label>
                        <textarea v-model="form.referencias" rows="3" class="w-full rounded-xl border px-3 py-2.5 text-sm" />
                        <p v-if="form.errors.referencias" class="mt-1 text-xs text-red-600">{{ form.errors.referencias }}</p>
                    </div>

                    <label class="sm:col-span-2 flex items-center gap-3 rounded-2xl bg-neutral-50 px-4 py-3">
                        <input v-model="form.predeterminada" type="checkbox" />
                        <span class="text-sm font-medium">Marcar como dirección predeterminada</span>
                    </label>

                    <div class="sm:col-span-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-full bg-[var(--brand-blue)] px-4 py-2.5 text-sm font-bold text-white transition hover:brightness-90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Guardando...' : isEditing ? 'Actualizar dirección' : 'Guardar dirección' }}
                        </button>
                    </div>
                </form>
            </article>

            <article class="rounded-2xl border bg-white p-5 shadow-sm">
                <h2 class="text-lg font-black">Direcciones guardadas</h2>

                <div v-if="direcciones.length" class="mt-4 space-y-4">
                    <div
                        v-for="direccion in direcciones"
                        :key="direccion.id"
                        class="rounded-2xl border border-neutral-200 p-4"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-black">{{ direccion.alias }}</p>
                                    <span
                                        v-if="direccion.predeterminada"
                                        class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase text-emerald-700"
                                    >
                                        Predeterminada
                                    </span>
                                </div>

                                <p class="mt-2 text-sm text-neutral-700">
                                    {{ direccion.nombre_receptor }} · {{ direccion.telefono }}
                                </p>

                                <p class="mt-2 text-sm text-neutral-600">
                                    {{
                                        [
                                            direccion.calle,
                                            direccion.numero_exterior,
                                            direccion.numero_interior,
                                            direccion.colonia,
                                            direccion.municipio,
                                            direccion.estado,
                                            direccion.pais,
                                            direccion.codigo_postal,
                                        ]
                                            .filter(Boolean)
                                            .join(', ')
                                    }}
                                </p>

                                <p
                                    v-if="direccion.referencias"
                                    class="mt-2 text-sm text-neutral-500"
                                >
                                    {{ direccion.referencias }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-full border border-neutral-200 px-4 py-2 text-sm font-bold transition hover:bg-neutral-50"
                                    @click="editDireccion(direccion)"
                                >
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full border border-red-200 px-4 py-2 text-sm font-bold text-red-600 transition hover:bg-red-50"
                                    @click="removeDireccion(direccion.id)"
                                >
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="mt-4 rounded-2xl border border-dashed px-6 py-12 text-center"
                >
                    <p class="text-sm text-neutral-500">
                        Aún no tienes direcciones guardadas.
                    </p>
                </div>
            </article>
        </section>
    </div>
</template>
