import {
    ClipboardList,
    LayoutGrid,
    Package,
    Settings,
    ShieldCheck,
    ShoppingCart,
    House,
    Users,
} from 'lucide-vue-next';
import type { NavItem } from '@/types';

const commonItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Tienda pública',
        href: '/',
        icon: House,
    },
    {
        title: 'Carrito',
        href: '/carrito',
        icon: ShoppingCart,
    },
    {
        title: 'Configuración',
        href: '/settings/profile',
        icon: Settings,
    },
];

export const useRoleNavigation = (role?: string | null) => {
    if (role === 'admin') {
        return [
            ...commonItems,
            {
                title: 'Admin · Productos',
                href: '/admin/productos',
                icon: Package,
            },
            { title: 'Admin · Usuarios', href: '/admin/usuarios', icon: Users },
            {
                title: 'Admin · Pedidos',
                href: '/admin/pedidos',
                icon: ClipboardList,
            },
            {
                title: 'Vendedor · Pedidos',
                href: '/vendedor/pedidos',
                icon: ShieldCheck,
            },
            {
                title: 'Cliente · Mis pedidos',
                href: '/mi-cuenta/pedidos',
                icon: ClipboardList,
            },
        ];
    }

    if (role === 'vendedor') {
        return [
            ...commonItems,
            {
                title: 'Vendedor · Pedidos',
                href: '/vendedor/pedidos',
                icon: ClipboardList,
            },
        ];
    }

    return [
        ...commonItems,
        {
            title: 'Cliente · Mis pedidos',
            href: '/mi-cuenta/pedidos',
            icon: ClipboardList,
        },
    ];
};
