export const SITE_NAME = 'Punto Polar'

export const pageTitles = {
    home: 'Agua Purificada y Hielo 24/7',
    productos: 'Catálogo de productos',
    productoDetalle: 'Detalle de producto',
    comprasProximamente: 'Compras en línea próximamente',
    carrito: 'Carrito',
    checkout: 'Checkout',
    checkoutGracias: 'Gracias por tu compra',
    dashboard: 'Panel',
} as const

export type PageTitleKey = keyof typeof pageTitles

export function getPageTitle(key: PageTitleKey): string {
    return pageTitles[key]
}

export function formatPageTitle(title?: string | null): string {
    if (!title) {
        return SITE_NAME
    }

    const normalizedTitle = title
        .replace(/\s+[·\-]\s+Punto Polar$/i, '')
        .trim()

    return `${normalizedTitle} · ${SITE_NAME}`
}
