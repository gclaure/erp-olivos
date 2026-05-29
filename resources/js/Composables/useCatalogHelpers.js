import { usePage } from '@inertiajs/vue3';

export function useCatalogHelpers() {
    const page = usePage();
    
    const formatCurrency = (val) => {
        return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2 }).format(val);
    };

    const getWhatsAppUrl = (product, activeBranch, ecommerceSettings) => {
        const companyPhone = page.props.company?.phone || '';
        const phone = activeBranch?.whatsapp_number || companyPhone || '';
        let message = ecommerceSettings.mensaje_whatsapp_base || '¡Hola! Me interesa este producto:';
        
        if (product) {
            const companySlug = page.props.company_slug;
            const productUrl = `${window.location.origin}/tienda/${companySlug}/producto/${product.slug}`;
            message += `\n*Producto:* ${product.name}\n*Precio:* Bs. ${formatCurrency(product.price)}\n*Link:* ${productUrl}`;
        }
        
        return `https://wa.me/${phone.replace(/\D/g, '')}?text=${encodeURIComponent(message)}`;
    };

    const formatStock = (val) => {
        const num = Number(val || 0);
        // Si el número es entero (o el resto de dividir por 1 es 0), devolver sin decimales
        if (num % 1 === 0) {
            return num.toFixed(0);
        }
        // Si tiene decimales, mostrar 2 decimales
        return num.toFixed(2);
    };

    return {
        formatCurrency,
        getWhatsAppUrl,
        formatStock
    };
}
