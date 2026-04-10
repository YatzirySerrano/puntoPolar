<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, onMounted, watch } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
    rol: 'cliente' | 'vendedor' | 'admin';
}

const page = usePage();

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

const props = defineProps<{
    usuarios: { data: Usuario[] };
    roles: string[];
    filters: { search?: string };
}>();

const resumenRoles = computed(() => ({
    clientes: props.usuarios.data.filter((u) => u.rol === 'cliente').length,
    vendedores: props.usuarios.data.filter((u) => u.rol === 'vendedor').length,
    admins: props.usuarios.data.filter((u) => u.rol === 'admin').length,
}));

const modalEditar = async (usuario: Usuario) => {
    const result = await Swal.fire({
        title: `Editar ${usuario.name}`,
        html: `
            <input id="swal-name" class="swal2-input" placeholder="Nombre" value="${usuario.name}">
            <input id="swal-email" class="swal2-input" type="email" placeholder="Correo" value="${usuario.email}">
            <select id="swal-rol" class="swal2-select">
                ${props.roles
                    .map(
                        (rol) =>
                            `<option value="${rol}" ${usuario.rol === rol ? 'selected' : ''}>${rol}</option>`,
                    )
                    .join('')}
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Guardar cambios',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const name = (
                document.getElementById('swal-name') as HTMLInputElement
            )?.value;
            const email = (
                document.getElementById('swal-email') as HTMLInputElement
            )?.value;
            const rol = (
                document.getElementById('swal-rol') as HTMLSelectElement
            )?.value;

            if (!name || !email) {
                Swal.showValidationMessage('Nombre y correo son obligatorios.');

                return;
            }

            return { name, email, rol };
        },
    });

    if (!result.isConfirmed || !result.value) {
        return;
    }

    router.put(`/admin/usuarios/${usuario.id}`, result.value, {
        preserveScroll: true,
    });
};

const eliminar = async (id: number) => {
    const result = await Swal.fire({
        title: '¿Eliminar usuario?',
        text: 'No podrás revertir esta acción.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (!result.isConfirmed) {
        return;
    }

    router.delete(`/admin/usuarios/${id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Admin · Usuarios" />

    <div class="space-y-6 p-4 sm:p-6 lg:p-8">
        <header
            class="rounded-3xl border border-[var(--brand-gray)]/60 bg-gradient-to-r from-white to-[var(--brand-soft)] p-5 shadow-sm sm:p-6"
        >
            <h1 class="text-2xl font-black sm:text-3xl">
                Gestión de usuarios y roles
            </h1>

            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Clientes</p>
                    <p class="text-2xl font-black">
                        {{ resumenRoles.clientes }}
                    </p>
                </article>
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Vendedores</p>
                    <p class="text-2xl font-black">
                        {{ resumenRoles.vendedores }}
                    </p>
                </article>
                <article class="rounded-2xl bg-white p-4 shadow-sm">
                    <p class="text-xs text-neutral-500 uppercase">Admins</p>
                    <p class="text-2xl font-black">{{ resumenRoles.admins }}</p>
                </article>
            </div>

            <input
                :value="filters.search || ''"
                type="text"
                placeholder="Buscar..."
                class="mt-4 h-10 w-full rounded-xl border px-3 text-sm"
                @input="
                    router.get(
                        '/admin/usuarios',
                        { search: ($event.target as HTMLInputElement).value },
                        { preserveState: true, replace: true },
                    )
                "
            />
        </header>

        <section
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="usuario in usuarios.data"
                :key="usuario.id"
                class="rounded-2xl border bg-white p-4 transition hover:-translate-y-1 hover:shadow-md"
            >
                <p class="font-black">{{ usuario.name }}</p>
                <p class="text-xs text-neutral-500">{{ usuario.email }}</p>
                <p
                    class="mt-2 inline-flex rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold uppercase"
                >
                    {{ usuario.rol }}
                </p>

                <div class="mt-4 flex gap-2">
                    <button
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs"
                        @click="modalEditar(usuario)"
                    >
                        Editar
                    </button>
                    <button
                        type="button"
                        class="rounded-full bg-red-500 px-3 py-1 text-xs text-white"
                        @click="eliminar(usuario.id)"
                    >
                        Eliminar
                    </button>
                </div>
            </article>
        </section>
    </div>
</template>
