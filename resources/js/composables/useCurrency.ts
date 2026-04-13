export const useCurrency = () => {
    const formatCurrency = (value: number | string, currency = 'MXN') => {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency,
        }).format(Number(value || 0));
    };

    return {
        formatCurrency,
    };
};
