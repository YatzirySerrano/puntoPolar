<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Usuario {
    id: number;
    name: string;
    email: string;
    rol: 'cliente' | 'vendedor' | 'admin';
}

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

const editandoId = ref<number | null>(null);
const form = useForm({
    name: '',
    email: '',
    rol: 'cliente',
});

const editar = (usuario: Usuario) => {
    editandoId.value = usuario.id;
    form.name = usuario.name;
    form.email = usuario.email;
    form.rol = usuario.rol;
};

const guardar = () => {
    if (!editandoId.value) {
        return;
    }

    form.put(`/admin/usuarios/${editandoId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            editandoId.value = null;
        },
    });
};

const eliminar = (id: number) => {
    if (!confirm('¿Eliminar usuario?')) {
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
            <p class="text-sm text-neutral-500">
                Administra perfiles cliente, vendedor y administrador.
            </p>

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
        </header>

        <section class="grid gap-6 xl:grid-cols-[1fr_2fr]">
            <form
                class="rounded-3xl border bg-white p-4 shadow-sm sm:p-6"
                @submit.prevent="guardar"
            >
                <h2 class="text-lg font-black">Editar usuario</h2>
                <div class="mt-3 space-y-3">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nombre"
                        class="h-11 w-full rounded-xl border px-3 text-sm"
                    />
                    <input
                        v-model="form.email"
                        type="email"
                        placeholder="Correo"
                        class="h-11 w-full rounded-xl border px-3 text-sm"
                    />
                    <select
                        v-model="form.rol"
                        class="h-11 w-full rounded-xl border px-3 text-sm"
                    >
                        <option v-for="rol in roles" :key="rol" :value="rol">
                            {{ rol }}
                        </option>
                    </select>
                    <button
                        type="submit"
                        class="rounded-full bg-[var(--brand-blue)] px-5 py-2 text-sm font-bold text-white transition hover:brightness-90"
                        :disabled="!editandoId"
                    >
                        Guardar cambios
                    </button>
                </div>
            </form>

            <div class="rounded-3xl border bg-white p-4 shadow-sm sm:p-6">
                <div
                    class="mb-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <h2 class="text-lg font-black">Usuarios</h2>
                    <input
                        :value="filters.search || ''"
                        type="text"
                        placeholder="Buscar..."
                        class="h-10 rounded-xl border px-3 text-sm"
                        @input="
                            router.get(
                                '/admin/usuarios',
                                {
                                    search: ($event.target as HTMLInputElement)
                                        .value,
                                },
                                { preserveState: true, replace: true },
                            )
                        "
                    />
                </div>

                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="usuario in usuarios.data"
                        :key="usuario.id"
                        class="rounded-2xl border p-4 transition hover:-translate-y-1 hover:shadow-md"
                    >
                        <p class="font-black">{{ usuario.name }}</p>
                        <p class="text-xs text-neutral-500">
                            {{ usuario.email }}
                        </p>
                        <p
                            class="mt-2 inline-flex rounded-full bg-neutral-100 px-3 py-1 text-xs font-semibold uppercase"
                        >
                            {{ usuario.rol }}
                        </p>

                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                class="rounded-full border px-3 py-1 text-xs"
                                @click="editar(usuario)"
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
                </div>
            </div>
        </section>
    </div>
</template>
