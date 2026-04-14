<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuarios',
                href: '/admin/usuarios',
            },
        ],
    },
})

interface UsuarioRow {
    id: number
    name: string
    email: string
    rol: string
    email_verified_at?: string | null
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
    usuarios: Paginated<UsuarioRow>
    roles: string[]
    filters?: {
        search?: string
        rol?: string
        estado?: string
    }
    endpoints: Endpoints
}>()

const search = ref(props.filters?.search ?? '')
const rolFilter = ref(props.filters?.rol ?? 'all')
const estadoFilter = ref(props.filters?.estado ?? 'all')

const viewTab = ref<'all' | 'admin' | 'vendedor' | 'cliente'>('all')
const modalTab = ref<'general' | 'security'>('general')

const showForm = ref(false)
const editingId = ref<number | null>(null)

const form = useForm({
    name: '',
    email: '',
    rol: '',
    password: '',
    password_confirmation: '',
    send_email: true,
})

const isEditing = computed(() => editingId.value !== null)

const totalAdmins = computed(() => props.usuarios.data.filter(item => item.rol === 'admin').length)
const totalVendedores = computed(() => props.usuarios.data.filter(item => item.rol === 'vendedor').length)
const totalClientes = computed(() => props.usuarios.data.filter(item => item.rol === 'cliente').length)
const totalVerificados = computed(() => props.usuarios.data.filter(item => !!item.email_verified_at).length)

const visibleUsers = computed(() => {
    if (viewTab.value === 'all') return props.usuarios.data
    return props.usuarios.data.filter(item => item.rol === viewTab.value)
})

const summaryText = computed(() => {
    if (!props.usuarios.total) return 'No hay usuarios registrados aún.'
    const from = props.usuarios.from ?? 1
    const to = props.usuarios.to ?? props.usuarios.data.length
    return `Mostrando ${from} - ${to} de ${props.usuarios.total} usuarios`
})

function roleLabel(rol: string) {
    const value = String(rol).toLowerCase()
    if (value === 'admin') return 'Administrador'
    if (value === 'vendedor') return 'Vendedor'
    if (value === 'cliente') return 'Cliente'
    return rol
}

function roleBadgeClass(rol: string) {
    const value = String(rol).toLowerCase()

    if (value === 'admin') return 'border-slate-300 bg-slate-100 text-slate-700'
    if (value === 'vendedor') return 'border-[color:rgba(48,190,239,0.18)] bg-[color:rgba(48,190,239,0.08)] text-sky-700'
    if (value === 'cliente') return 'border-[color:rgba(125,208,60,0.20)] bg-[color:rgba(125,208,60,0.10)] text-emerald-700'

    return 'border-neutral-200 bg-neutral-100 text-neutral-700'
}

function roleDotClass(rol: string) {
    const value = String(rol).toLowerCase()

    if (value === 'admin') return 'bg-slate-500'
    if (value === 'vendedor') return 'bg-[var(--brand-blue)]'
    if (value === 'cliente') return 'bg-[var(--brand-green)]'

    return 'bg-neutral-400'
}

function formatDate(date?: string | null) {
    if (!date) return 'Sin registro'

    return new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date))
}

function resetForm() {
    editingId.value = null
    modalTab.value = 'general'
    form.reset()
    form.clearErrors()
    form.transform((data) => data)

    form.name = ''
    form.email = ''
    form.rol = ''
    form.password = ''
    form.password_confirmation = ''
    form.send_email = true
}

function openCreate() {
    resetForm()
    showForm.value = true
}

function openEdit(usuario: UsuarioRow) {
    resetForm()
    editingId.value = usuario.id
    form.name = usuario.name
    form.email = usuario.email
    form.rol = usuario.rol
    form.send_email = false
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
    window.removeEventListener('keydown', onEscape)
    if (searchTimeout) clearTimeout(searchTimeout)
})

function generatePassword(length = 12) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789'
    let result = ''

    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length))
    }

    form.password = result
    form.password_confirmation = result

    Swal.fire({
        icon: 'success',
        title: 'Contraseña generada',
        text: 'Se generó una contraseña automáticamente.',
        timer: 1200,
        showConfirmButton: false,
        background: '#ffffff',
        color: '#111827',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
        },
    })
}

function validateBeforeSubmit() {
    form.clearErrors()

    if (!form.name.trim()) form.setError('name', 'El nombre es obligatorio.')
    if (!form.email.trim()) form.setError('email', 'El correo es obligatorio.')
    if (!form.rol.trim()) form.setError('rol', 'El rol es obligatorio.')

    if (!isEditing.value && !form.password.trim()) {
        form.setError('password', 'La contraseña es obligatoria al crear el usuario.')
    }

    if (form.password || form.password_confirmation) {
        if (form.password.length < 8) {
            form.setError('password', 'La contraseña debe tener al menos 8 caracteres.')
        }

        if (form.password !== form.password_confirmation) {
            form.setError('password_confirmation', 'La confirmación no coincide.')
        }
    }

    return Object.keys(form.errors).length === 0
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
        confirmButtonColor: '#111827',
        customClass: {
            popup: 'rounded-[20px] shadow-xl',
            confirmButton: 'rounded-xl font-semibold',
        },
    })
}

function submit() {
    if (!validateBeforeSubmit()) return

    const commonOptions = {
        preserveScroll: true,
        onError: (errors: Record<string, string>) => {
            const firstError =
                errors.name ||
                errors.email ||
                errors.rol ||
                errors.password ||
                errors.password_confirmation ||
                errors.general ||
                'Revisa la información del formulario.'

            errorAlert('No se pudo guardar', firstError)
        },
        onFinish: () => {
            form.transform((data) => data)
        },
    }

    if (isEditing.value && editingId.value) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
            }))
            .post(`${props.endpoints.updateBase}/${editingId.value}`, {
                ...commonOptions,
                onSuccess: () => {
                    closeForm(true)
                    requestAnimationFrame(() => {
                        successAlert('Usuario actualizado', 'Los cambios se guardaron correctamente.')
                    })
                },
            })

        return
    }

    form.post(props.endpoints.store, {
        ...commonOptions,
        onSuccess: () => {
            closeForm(true)
            requestAnimationFrame(() => {
                successAlert('Usuario creado', 'El usuario se registró correctamente.')
            })
        },
    })
}

async function destroyRow(id: number, name: string) {
    const result = await Swal.fire({
        title: '¿Eliminar usuario?',
        text: `Se eliminará "${name}" de forma permanente.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#334155',
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
        onSuccess: () => successAlert('Usuario eliminado', 'El usuario fue eliminado correctamente.'),
        onError: (errors: Record<string, string>) => {
            errorAlert('Error', errors.general || 'No se pudo eliminar el usuario.')
        },
    })
}

let searchTimeout: ReturnType<typeof setTimeout> | null = null

watch([search, rolFilter, estadoFilter], () => {
    if (searchTimeout) clearTimeout(searchTimeout)

    searchTimeout = setTimeout(() => {
        router.get(
            props.endpoints.index,
            {
                search: search.value || undefined,
                rol: rolFilter.value !== 'all' ? rolFilter.value : undefined,
                estado: estadoFilter.value !== 'all' ? estadoFilter.value : undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        )
    }, 350)
})
</script>

<template>
    <Head title="Admin · Usuarios" />

    <div class="space-y-5 p-4 sm:p-6 lg:p-8">
        <section class="rounded-[26px] border border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] shadow-sm">
            <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-2xl">

                    <h1 class="mt-2 text-[30px] font-black tracking-tight text-slate-900">
                        Accesos, roles y seguridad
                    </h1>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C] hover:shadow-md"
                    @click="openCreate"
                >
                    + Nuevo usuario
                </button>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Administradores</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalAdmins }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Vendedores</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalVendedores }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Clientes</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalClientes }}</p>
            </article>

            <article class="rounded-[22px] border border-slate-200 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Verificados</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ totalVerificados }}</p>
            </article>
        </section>

        <section class="rounded-[26px] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-5 sm:px-6">
                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px]">
                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Buscar</span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Nombre, correo o rol..."
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                        />
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Rol</span>
                        <select
                            v-model="rolFilter"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                        >
                            <option value="all">Todos</option>
                            <option v-for="rol in roles" :key="rol" :value="rol">
                                {{ roleLabel(rol) }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Verificación</span>
                        <select
                            v-model="estadoFilter"
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                        >
                            <option value="all">Todos</option>
                            <option value="verified">Verificados</option>
                            <option value="unverified">No verificados</option>
                        </select>
                    </label>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="viewTab === 'all' ? 'border-slate-300 bg-slate-100 text-slate-900 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        @click="viewTab = 'all'"
                    >
                        Todos
                    </button>

                    <button
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="viewTab === 'admin' ? 'border-slate-300 bg-slate-100 text-slate-900 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        @click="viewTab = 'admin'"
                    >
                        Admin
                    </button>

                    <button
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="viewTab === 'vendedor' ? 'border-[color:rgba(48,190,239,0.18)] bg-[color:rgba(48,190,239,0.10)] text-sky-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        @click="viewTab = 'vendedor'"
                    >
                        Vendedor
                    </button>

                    <button
                        type="button"
                        class="rounded-full border px-4 py-2 text-sm font-medium transition-all duration-200"
                        :class="viewTab === 'cliente' ? 'border-[color:rgba(125,208,60,0.20)] bg-[color:rgba(125,208,60,0.10)] text-emerald-700 shadow-sm' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                        @click="viewTab = 'cliente'"
                    >
                        Cliente
                    </button>
                </div>
            </div>

            <div class="px-5 py-4 sm:px-6">
                <p class="text-sm text-slate-500">{{ summaryText }}</p>
            </div>
        </section>

        <section
            v-if="visibleUsers.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
        >
            <article
                v-for="usuario in visibleUsers"
                :key="usuario.id"
                class="group overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-slate-300 hover:shadow-lg"
            >
                <div class="border-b border-slate-100 bg-slate-50/70 px-5 py-5 transition-colors duration-300 group-hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Usuario</p>
                            <h3 class="mt-2 truncate text-lg font-black text-slate-900">{{ usuario.name }}</h3>
                        </div>

                        <span
                            class="rounded-full border px-3 py-1 text-[11px] font-bold"
                            :class="roleBadgeClass(usuario.rol)"
                        >
                            {{ roleLabel(usuario.rol) }}
                        </span>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 transition-all duration-200 group-hover:bg-slate-50">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Correo</p>
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ usuario.email }}</p>
                    </div>

                    <div class="grid gap-3">
                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Estado</p>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :class="usuario.email_verified_at ? 'bg-[var(--brand-green)]' : 'bg-amber-400'"
                                />
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ usuario.email_verified_at ? 'Correo verificado' : 'Correo no verificado' }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50/60">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Registro</p>
                            <p class="mt-2 text-sm font-medium text-slate-900">
                                {{ formatDate(usuario.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600">
                            <span class="h-2 w-2 rounded-full" :class="roleDotClass(usuario.rol)" />
                            {{ roleLabel(usuario.rol) }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.06)]"
                            @click="openEdit(usuario)"
                        >
                            Editar
                        </button>

                        <button
                            type="button"
                            class="rounded-2xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 transition-all duration-200 hover:-translate-y-0.5 hover:bg-red-100"
                            @click="destroyRow(usuario.id, usuario.name)"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-[26px] border border-dashed border-slate-300 bg-white px-6 py-16 text-center shadow-sm"
        >
            <h2 class="text-lg font-black text-slate-900">No hay usuarios para mostrar</h2>
            <p class="mt-2 text-sm text-slate-500">Prueba con otros filtros o registra un nuevo usuario.</p>
            <button
                type="button"
                class="mt-5 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C]"
                @click="openCreate"
            >
                Nuevo usuario
            </button>
        </section>

        <section
            v-if="usuarios.links?.length > 3"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <template v-for="(link, index) in usuarios.links" :key="index">
                <component
                    :is="link.url ? 'a' : 'span'"
                    :href="link.url || undefined"
                    v-html="link.label"
                    class="inline-flex min-w-10 items-center justify-center rounded-xl border px-3 py-2 text-sm transition-all duration-200"
                    :class="link.active
                        ? 'border-slate-300 bg-slate-100 text-slate-900'
                        : link.url
                            ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400'"
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
            <div v-if="showForm" class="fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-[3px]">
                <div class="flex h-dvh items-end justify-center sm:items-center sm:p-4">
                    <Transition
                        appear
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-5 opacity-0 sm:translate-y-0 sm:scale-[0.985]"
                        enter-to-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-active-class="transition duration-200 ease-in"
                        leave-from-class="translate-y-0 opacity-100 sm:scale-100"
                        leave-to-class="translate-y-3 opacity-0 sm:translate-y-0 sm:scale-[0.99]"
                    >
                        <div
                            v-if="showForm"
                            class="flex h-[94dvh] w-full flex-col overflow-hidden rounded-t-[28px] bg-white shadow-2xl sm:h-auto sm:max-h-[90vh] sm:max-w-5xl sm:rounded-[28px]"
                        >
                            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,#ffffff_0%,#fafafa_100%)] px-5 py-5 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">
                                            {{ isEditing ? 'Edición' : 'Alta' }}
                                        </p>
                                        <h3 class="mt-2 text-xl font-black text-slate-900 sm:text-2xl">
                                            {{ isEditing ? 'Editar usuario' : 'Registrar usuario' }}
                                        </h3>
                                    </div>

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-lg text-slate-500 transition-all duration-200 hover:rotate-90 hover:bg-slate-100 hover:text-slate-800"
                                        @click="closeForm()"
                                    >
                                        ×
                                    </button>
                                </div>

                                <div class="mt-5 inline-flex rounded-2xl border border-slate-200 bg-slate-100 p-1 shadow-inner">
                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200"
                                        :class="modalTab === 'general'
                                            ? 'bg-white text-slate-900 shadow-sm'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="modalTab = 'general'"
                                    >
                                        General
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl px-4 py-2 text-sm font-medium transition-all duration-200"
                                        :class="modalTab === 'security'
                                            ? 'bg-white text-slate-900 shadow-sm'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        @click="modalTab = 'security'"
                                    >
                                        Seguridad
                                    </button>
                                </div>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <form class="grid gap-0 lg:grid-cols-[1.08fr_0.92fr]" @submit.prevent="submit">
                                    <div class="order-2 p-5 sm:p-6 lg:order-1">
                                        <div v-show="modalTab === 'general'" class="space-y-5">
                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Nombre</label>
                                                <input
                                                    v-model="form.name"
                                                    type="text"
                                                    placeholder="Nombre completo"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                                />
                                                <p v-if="form.errors.name" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.name }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Correo electrónico</label>
                                                <input
                                                    v-model="form.email"
                                                    type="email"
                                                    placeholder="correo@dominio.com"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                                />
                                                <p v-if="form.errors.email" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.email }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Rol</label>
                                                <select
                                                    v-model="form.rol"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 focus:-translate-y-[1px] focus:border-[var(--brand-blue)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(48,190,239,0.10)]"
                                                >
                                                    <option value="" disabled>Selecciona un rol</option>
                                                    <option v-for="rol in roles" :key="rol" :value="rol">
                                                        {{ roleLabel(rol) }}
                                                    </option>
                                                </select>
                                                <p v-if="form.errors.rol" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.rol }}</p>
                                            </div>

                                            <label
                                                v-if="!isEditing"
                                                class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 transition-all duration-200 hover:border-[var(--brand-blue)] hover:bg-[color:rgba(48,190,239,0.05)]"
                                            >
                                                <div>
                                                    <p class="text-sm font-semibold text-slate-800">Enviar correo de acceso</p>
                                                    <p class="mt-1 text-xs text-slate-500">Mandará las credenciales iniciales al registrar.</p>
                                                </div>

                                                <input
                                                    v-model="form.send_email"
                                                    type="checkbox"
                                                    class="h-5 w-5 rounded border-slate-300 text-[var(--brand-blue)] focus:ring-[color:rgba(48,190,239,0.20)]"
                                                />
                                            </label>
                                        </div>

                                        <div v-show="modalTab === 'security'" class="space-y-5">
                                            <div class="rounded-[22px] border border-slate-200 bg-slate-50 p-4 transition-all duration-200 hover:border-slate-300">
                                                <p class="text-sm font-semibold text-slate-800">
                                                    {{ isEditing ? 'Cambio de contraseña' : 'Contraseña inicial' }}
                                                </p>
                                                <p class="mt-1 text-sm text-slate-600">
                                                    {{ isEditing
                                                        ? 'Si llenas estos campos, se actualizará la contraseña del usuario.'
                                                        : 'Puedes escribir una contraseña o generarla automáticamente.' }}
                                                </p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[var(--brand-green)] hover:bg-[color:rgba(125,208,60,0.08)]"
                                                    @click="generatePassword()"
                                                >
                                                    Generar contraseña
                                                </button>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Contraseña</label>
                                                <input
                                                    v-model="form.password"
                                                    type="text"
                                                    placeholder="Contraseña"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-green)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(125,208,60,0.12)]"
                                                />
                                                <p v-if="form.errors.password" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.password }}</p>
                                            </div>

                                            <div>
                                                <label class="mb-2 block text-sm font-semibold text-slate-700">Confirmar contraseña</label>
                                                <input
                                                    v-model="form.password_confirmation"
                                                    type="text"
                                                    placeholder="Repite la contraseña"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition-all duration-200 placeholder:text-slate-400 focus:-translate-y-[1px] focus:border-[var(--brand-green)] focus:outline-none focus:ring-4 focus:ring-[color:rgba(125,208,60,0.12)]"
                                                />
                                                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm font-medium text-red-600">{{ form.errors.password_confirmation }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-1 border-b border-slate-200 bg-slate-50/70 p-5 sm:p-6 lg:order-2 lg:border-b-0 lg:border-l">
                                        <div class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-md">
                                            <p class="text-xs uppercase tracking-[0.22em] text-slate-400">
                                                Vista previa
                                            </p>

                                            <h4 class="mt-4 text-2xl font-black text-slate-900">
                                                {{ form.name || 'Nuevo usuario' }}
                                            </h4>

                                            <p class="mt-2 break-all text-sm text-slate-600">
                                                {{ form.email || 'correo@dominio.com' }}
                                            </p>

                                            <div class="mt-5 flex flex-wrap gap-2">
                                                <span
                                                    class="rounded-full border px-3 py-1 text-xs font-semibold transition-all duration-200"
                                                    :class="form.rol ? roleBadgeClass(form.rol) : 'border-slate-200 bg-slate-100 text-slate-600'"
                                                >
                                                    {{ form.rol ? roleLabel(form.rol) : 'Sin rol' }}
                                                </span>

                                                <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                    {{ isEditing ? 'Edición' : 'Registro' }}
                                                </span>
                                            </div>

                                            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <p class="text-xs uppercase tracking-wide text-slate-500">Seguridad</p>
                                                <p class="mt-2 text-sm font-semibold text-slate-900">
                                                    {{ form.password ? 'Contraseña lista para guardar' : 'Sin cambio de contraseña' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="order-3 lg:col-span-2">
                                        <div class="sticky bottom-0 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur sm:px-6">
                                            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all duration-200 hover:bg-slate-50"
                                                    @click="closeForm()"
                                                >
                                                    Cancelar
                                                </button>

                                                <button
                                                    type="submit"
                                                    :disabled="form.processing"
                                                    class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-[var(--brand-blue)] hover:text-[#11142C] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
                                                >
                                                    {{
                                                        form.processing
                                                            ? 'Guardando...'
                                                            : isEditing
                                                                ? 'Actualizar usuario'
                                                                : 'Registrar usuario'
                                                    }}
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
