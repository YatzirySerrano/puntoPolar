import {
    House,
    ShoppingCart,
    ClipboardList,
    Package,
    Users,
    Tags,
    Layers3,
    Image,
    TicketPercent,
    CreditCard,
    Settings,
    Receipt,
    BadgePercent,
} from 'lucide-vue-next';
import type { NavItem } from '@/types';

const commonItems: NavItem[] = [
    {
        title: 'Inicio',
        href: '/dashboard',
        icon: House,
    },
];

const clienteItems: NavItem[] = [
    {
        title: 'Mi carrito',
        href: '/carrito',
        icon: ShoppingCart,
    },
    {
        title: 'Mis pedidos',
        href: '/mi-cuenta/pedidos',
        icon: ClipboardList,
    },
];

const vendedorItems: NavItem[] = [
    {
        title: 'Pedidos',
        href: '/vendedor/pedidos',
        icon: ClipboardList,
    },
    {
        title: 'Mi carrito',
        href: '/carrito',
        icon: ShoppingCart,
    },
    {
        title: 'Mis pedidos',
        href: '/mi-cuenta/pedidos',
        icon: Receipt,
    },
];

const adminItems: NavItem[] = [
    {
        title: 'Productos',
        href: '/admin/productos',
        icon: Package,
    },
    {
        title: 'Categorías',
        href: '/admin/categorias',
        icon: Layers3,
    },
    {
        title: 'Marcas',
        href: '/admin/marcas',
        icon: Tags,
    },
    {
        title: 'Pedidos',
        href: '/admin/pedidos',
        icon: ClipboardList,
    },
    {
        title: 'Usuarios',
        href: '/admin/usuarios',
        icon: Users,
    },
    {
        title: 'Cupones',
        href: '/admin/cupones',
        icon: TicketPercent,
    },
    {
        title: 'Ofertas',
        href: '/admin/ofertas',
        icon: BadgePercent,
    },
    {
        title: 'Banners',
        href: '/admin/banners',
        icon: Image,
    },
    {
        title: 'Métodos de pago',
        href: '/admin/metodos-pago',
        icon: CreditCard,
    },
    {
        title: 'Pagos',
        href: '/admin/pagos',
        icon: Receipt,
    },
    {
        title: 'Configuración',
        href: '/admin/configuraciones',
        icon: Settings,
    },
    {
        title: 'Mi carrito',
        href: '/carrito',
        icon: ShoppingCart,
    },
    {
        title: 'Mis pedidos',
        href: '/mi-cuenta/pedidos',
        icon: ClipboardList,
    },
];

export const useRoleNavigation = (role?: string | null): NavItem[] => {
    switch (role) {
        case 'admin':
            return [...commonItems, ...adminItems];

        case 'vendedor':
            return [...commonItems, ...vendedorItems];

        default:
            return [...commonItems, ...clienteItems];
    }
};
