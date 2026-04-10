import {
    ClipboardList,
    LayoutGrid,
    Package,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import type { NavItem } from '@/types';

export const useRoleNavigation = (role?: string | null) => {
    const baseItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
    ];

    if (role === 'admin') {
        return [
            ...baseItems,
            { title: 'Productos', href: '/admin/productos', icon: Package },
            { title: 'Usuarios', href: '/admin/usuarios', icon: Users },
            { title: 'Pedidos', href: '/admin/pedidos', icon: ClipboardList },
            { title: 'Roles', href: '/admin/usuarios', icon: ShieldCheck },
        ];
    }

    if (role === 'vendedor') {
        return [
            ...baseItems,
            {
                title: 'Pedidos',
                href: '/vendedor/pedidos',
                icon: ClipboardList,
            },
        ];
    }

    return [
        ...baseItems,
        {
            title: 'Mis pedidos',
            href: '/mi-cuenta/pedidos',
            icon: ClipboardList,
        },
    ];
};
