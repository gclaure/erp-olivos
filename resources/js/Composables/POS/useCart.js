import { ref, computed } from 'vue';

export function useCart(initialFixedDiscount = false) {
    const items = ref([]);
    const globalDiscount = ref(0);
    const isFixedDiscount = ref(initialFixedDiscount);
    const deliveryCost = ref(0);
    const deliveryMode = ref('venta_directa');

    const grossSubtotal = computed(() => {
        return items.value.reduce((total, item) => total + (parseFloat(item.quantity || 0) * parseFloat(item.price || 0)), 0);
    });

    const subtotal = computed(() => {
        return items.value.reduce((total, item) => {
            const itemLineTotal = parseFloat(item.quantity || 0) * parseFloat(item.price || 0);
            let itemDiscountAmount = 0;

            if (isFixedDiscount.value) {
                itemDiscountAmount = parseFloat(item.discount) || 0;
            } else {
                const disc = Math.min(parseFloat(item.discount) || 0, 100);
                itemDiscountAmount = (itemLineTotal * disc) / 100;
            }

            return total + Math.max(0, itemLineTotal - itemDiscountAmount);
        }, 0);
    });

    const globalDiscountAmount = computed(() => {
        if (isFixedDiscount.value) {
            return parseFloat(globalDiscount.value) || 0;
        }
        const disc = Math.min(parseFloat(globalDiscount.value) || 0, 100);
        return (subtotal.value * disc) / 100;
    });

    const totalDiscounts = computed(() => {
        const itemsDiscount = items.value.reduce((total, item) => {
            const itemLineTotal = parseFloat(item.quantity || 0) * parseFloat(item.price || 0);
            let disc = 0;
            if (isFixedDiscount.value) {
                disc = parseFloat(item.discount) || 0;
            } else {
                const d = Math.min(parseFloat(item.discount) || 0, 100);
                disc = (itemLineTotal * d) / 100;
            }
            return total + disc;
        }, 0);

        return itemsDiscount + globalDiscountAmount.value;
    });

    const finalTotal = computed(() => {
        const total = subtotal.value - globalDiscountAmount.value;
        const shipping = deliveryMode.value === 'envio_domicilio' ? (parseFloat(deliveryCost.value) || 0) : 0;
        return Math.max(0, total + shipping);
    });

    /**
     * Agrega un producto al carrito.
     * Si el producto trae warehouse_id, se distingue por id + warehouse_id,
     * lo que permite el mismo producto de distintos almacenes en el carrito.
     */
    const addItem = (product) => {
        const existing = items.value.find(i => {
            if (product.warehouse_id) {
                return i.id === product.id && i.warehouse_id === product.warehouse_id;
            }
            return i.id === product.id;
        });

        if (existing) {
            existing.quantity++;
        } else {
            items.value.push({
                ...product,
                quantity: 1,
                discount: 0,
                glosa: ''
            });
        }
    };

    const updateQuantity = (productId, quantity, warehouseId = null) => {
        const item = items.value.find(i => {
            if (warehouseId) return i.id === productId && i.warehouse_id === warehouseId;
            return i.id === productId;
        });
        if (item) {
            item.quantity = parseFloat(quantity || 0);
        }
    };

    const updateDiscount = (productId, discount, warehouseId = null) => {
        const item = items.value.find(i => {
            if (warehouseId) return i.id === productId && i.warehouse_id === warehouseId;
            return i.id === productId;
        });
        if (item) {
            item.discount = parseFloat(discount || 0);
        }
    };

    const removeItem = (productId, warehouseId = null) => {
        items.value = items.value.filter(i => {
            if (warehouseId) return !(i.id === productId && i.warehouse_id === warehouseId);
            return i.id !== productId;
        });
    };

    const loadQuotation = (quotation) => {
        if (!quotation) return;
        items.value = quotation.items.map(item => ({
            ...item,
            glosa: ''
        }));
        globalDiscount.value = quotation.global_discount;
    };

    const clearCart = () => {
        items.value = [];
        globalDiscount.value = 0;
        deliveryCost.value = 0;
        deliveryMode.value = 'venta_directa';
    };

    return {
        items,
        globalDiscount,
        isFixedDiscount,
        deliveryCost,
        deliveryMode,
        grossSubtotal,
        subtotal,
        globalDiscountAmount,
        totalDiscounts,
        finalTotal,
        addItem,
        removeItem,
        updateQuantity,
        updateDiscount,
        clearCart,
        loadQuotation
    };
}
