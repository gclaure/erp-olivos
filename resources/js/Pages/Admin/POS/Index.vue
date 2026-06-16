<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

// Composables
import { useCart } from '@/Composables/POS/useCart';
import { useProductSearch } from '@/Composables/POS/useProductSearch';
import { usePOSConfig } from '@/Composables/POS/usePOSConfig';

// Components
import ProductCatalog from './Partials/ProductCatalog.vue';
import CartSidebar from './Partials/CartSidebar.vue';
import ConfirmSaleModal from './Partials/ConfirmSaleModal.vue';

const props = defineProps({
    initialConfig: Object,
    pointsOfSale: Array,
    warehouses: Array,
    shippingHistory: Object,
    initialQuotation: Object,
});

defineOptions({ layout: AdminLayout });

// Initialize Composables
const { 
    items, globalDiscount, isFixedDiscount, deliveryCost, deliveryMode,
    grossSubtotal, subtotal, globalDiscountAmount, totalDiscounts, finalTotal, 
    addItem, removeItem, updateQuantity, updateDiscount, clearCart, loadQuotation 
} = useCart(props.initialConfig?.isFixedDiscount ?? false);

const { warehouseId, posId, setWarehouse, setPOS } = usePOSConfig(props.initialConfig || {});

const { 
    query: productQuery, products, loading: loadingProducts, pagination, search: searchProducts 
} = useProductSearch(warehouseId);

const clientQuery = ref('');
const clients = ref([]);
const loadingClients = ref(false);
const searchClients = () => {};

// State
const selectedClient = ref(null);
const showConfirmModal = ref(false);
const showClientModal = ref(false);
const activeTab = ref('products'); // 'products' o 'cart'
const cartCount = computed(() => items.value.length);
const receiptType = ref(props.initialConfig.receiptType || 'media');

const toggleReceiptType = () => {
    const newVal = receiptType.value === 'media' ? 'rollo' : 'media';
    receiptType.value = newVal;
    form.receipt_type = newVal;
    
    // Persistir en el servidor para que se mantenga al recargar o navegar
    router.post(route('admin.pos.update-receipt-type'), {
        receipt_type: newVal
    }, {
        preserveScroll: true,
        preserveState: true
    });
};

const openConfirmModal = (type = 'sale') => {
    form.operation_type = type;
    showConfirmModal.value = true;
};

const form = useForm({
    operation_type: props.initialConfig.operationType || 'sale',
    client_id: null,
    warehouse_id: warehouseId.value,
    point_of_sale_id: posId.value,
    cart: [],
    global_discount: 0,
    is_fixed_discount: isFixedDiscount.value,
    payment_type: 'efectivo',
    payment_method: 'efectivo',
    amount_received: 0,
    delivery_mode: deliveryMode.value,
    delivery_cost: deliveryCost.value,
    receipt_type: receiptType.value,
});

// Sync form with composable
watch(() => form.delivery_mode, (val) => { deliveryMode.value = val; });
watch(() => form.delivery_cost, (val) => { deliveryCost.value = val; });
watch(isFixedDiscount, (val) => { form.is_fixed_discount = val; });
watch(globalDiscount, (val) => { form.global_discount = val; });


// Handlers
const handleAddProduct = (product) => {
    // En modo consumo, enriquecer el producto con el almacén activo
    // para permitir agregar el mismo producto de distintos almacenes
    if (props.initialConfig?.operationType === 'consumption') {
        const activeWarehouse = (props.warehouses || []).find(w => w.id === warehouseId.value);
        addItem({
            ...product,
            warehouse_id: warehouseId.value,
            warehouse_name: activeWarehouse?.name ?? props.initialConfig?.warehouseName ?? 'Almacén'
        });
    } else {
        addItem(product);
    }
};

const handleChangeWarehouse = (warehouse) => {
    setWarehouse(warehouse.id);
    // En modo consumo conservar el carrito (preserveState: true)
    // para poder agregar productos de distintos almacenes
    const isConsumption = props.initialConfig?.operationType === 'consumption';
    router.post(route('admin.pos.update-context'), {
        warehouse_id: warehouse.id
    }, {
        preserveScroll: true,
        preserveState: isConsumption,
    });
};

const handleSelectClient = (client) => {
    selectedClient.value = null;
    form.client_id = null;
    clientQuery.value = '';
};

const handleClientCreated = (client) => {};

const handleConfirmSale = (paymentData) => {
    form.cart = items.value;
    form.global_discount = globalDiscount.value;
    form.amount_received = paymentData.amount_received || 0;
    form.warehouse_id = warehouseId.value;
    form.point_of_sale_id = posId.value;
    form.receipt_type = receiptType.value;
    form.operation_type = paymentData.type || 'sale';
    form.is_fixed_discount = isFixedDiscount.value;
    
    form.post(route('admin.pos.store'), {
        onSuccess: (page) => {
            const data = page.props.flash?.success_data;

            if (data?.id) {
                const printUrl = data.type === 'sale' 
                    ? route('admin.sales.print', { sale: data.id, format: form.receipt_type })
                    : route('admin.quotations.print', { quotation: data.id, format: form.receipt_type });
                
                window.open(printUrl, '_blank');
            }

            clearCart();
            selectedClient.value = null;
            clientQuery.value = '';
            form.reset();
            // Asegurar que los IDs persistentes se mantengan tras el reset
            form.warehouse_id = warehouseId.value;
            form.point_of_sale_id = posId.value;
            showConfirmModal.value = false;
            
            // Actualizar stock de productos
            searchProducts(pagination.value.current_page);

            Swal.fire({
                icon: 'success',
                title: 'Operación Exitosa',
                text: page.props.flash.success || 'Operación completada.',
                timer: 3000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            Swal.fire('Error', firstError, 'error');
        }
    });
};

const handleSubmitConsumption = (consumptionData) => {
    // Enviar cada ítem con su warehouse_id individual para permitir
    // que el backend cree solicitudes separadas por almacén
    router.post(route('admin.consumption-requests.store'), {
        requested_by: consumptionData.requested_by,
        notes: consumptionData.notes,
        cart: items.value.map(item => ({
            id: item.id,
            quantity: item.quantity,
            warehouse_id: item.warehouse_id ?? warehouseId.value
        }))
    }, {
        onSuccess: (page) => {
            const data = page.props.flash?.success_data;
            if (data?.id) {
                const printUrl = route('admin.consumption-requests.print', { consumption_request: data.id });
                window.open(printUrl, '_blank');
            }
            clearCart();
            Swal.fire({
                icon: 'success',
                title: 'Solicitud Enviada',
                text: page.props.flash?.success || 'La solicitud de consumo ha sido enviada al almacenero.',
                timer: 3000,
                showConfirmButton: false
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            Swal.fire('Error', firstError, 'error');
        }
    });
};

onMounted(() => {
    searchProducts(1);
    if (props.initialQuotation) {
        loadQuotation(props.initialQuotation);
        handleSelectClient({
            id: props.initialQuotation.client_id,
            name: props.initialQuotation.client_name,
            phone: props.initialQuotation.client_phone
        });
        clientQuery.value = props.initialQuotation.client_name;
    }
});
</script>

<template>
    <Head title="Caja" />    <div class="flex flex-col lg:flex-row h-[calc(100vh-4rem)] lg:h-[calc(100vh-4rem)] bg-zinc-100 dark:bg-secondary-900 -m-4 lg:-m-6 overflow-hidden relative pb-16 lg:pb-0">
        <div class="flex flex-col lg:flex-row items-stretch w-full h-full lg:h-full overflow-hidden">
            <!-- PANEL IZQUIERDO: CATÁLOGO DE PRODUCTOS (55%) -->
            <ProductCatalog 
                v-model:searchQuery="productQuery"
                :products="products"
                :loading="loadingProducts"
                :pagination="pagination"
                :warehouse-name="initialConfig.warehouseName"
                :warehouses="warehouses"
                :active-warehouse-id="initialConfig.activeWarehouseId"
                :operation-type="initialConfig.operationType"
                @page-change="searchProducts"
                @add-to-cart="handleAddProduct"
                @change-warehouse="handleChangeWarehouse"
                :class="activeTab === 'products' ? 'flex' : 'hidden lg:flex'"
            />

            <!-- PANEL DERECHO: CARRITO Y ACCIONES (45%) -->
            <CartSidebar 
                :cart="items"
                :subtotal="subtotal"
                :global-discount-amount="globalDiscountAmount"
                :final-total="finalTotal"
                :selected-client="selectedClient"
                :is-fixed-discount="isFixedDiscount"
                :clients="clients"
                :loading-clients="loadingClients"
                @select-client="handleSelectClient"
                @remove-from-cart="removeItem"
                @update-quantity="updateQuantity"
                @update-discount="updateDiscount"
                @clear-cart="clearCart"
                @open-confirm="openConfirmModal"
                @toggle-discount-type="isFixedDiscount = !isFixedDiscount"
                @toggle-receipt-type="toggleReceiptType"
                @submit-consumption="handleSubmitConsumption"
                :receipt-type="receiptType"
                :is-editing="!!initialQuotation"
                :operation-type="initialConfig.operationType"
                class="border-zinc-200 dark:border-secondary-700"
                :class="activeTab === 'cart' ? 'flex' : 'hidden lg:flex'"
            />
        </div>

        <!-- BOTTOM NAVIGATION BAR (lg:hidden) -->
        <div class="lg:hidden fixed bottom-0 left-0 right-0 h-16 bg-white dark:bg-secondary-800 border-t border-zinc-200 dark:border-secondary-700 flex items-center justify-around z-50 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] select-none">
            <!-- Pestaña Productos -->
            <button 
                @click="activeTab = 'products'" 
                type="button"
                class="flex flex-col items-center justify-center gap-1 w-1/2 h-full text-[10px] font-black uppercase tracking-widest transition-all duration-200"
                :class="activeTab === 'products' ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-secondary-400'"
            >
                <span class="material-symbols-outlined text-[22px]">grid_view</span>
                <span>Productos</span>
            </button>

            <!-- Pestaña Carrito -->
            <button 
                @click="activeTab = 'cart'" 
                type="button"
                class="flex flex-col items-center justify-center gap-1 w-1/2 h-full text-[10px] font-black uppercase tracking-widest transition-all duration-200 relative"
                :class="activeTab === 'cart' ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400 dark:text-secondary-400'"
            >
                <div class="relative">
                    <span class="material-symbols-outlined text-[22px]">shopping_cart</span>
                    <!-- Badge del total de artículos en el carrito -->
                    <span 
                        v-if="cartCount > 0" 
                        class="absolute -top-1.5 -right-2 bg-red-500 text-white text-[9px] font-black w-4.5 h-4.5 rounded-full flex items-center justify-center animate-pulse"
                    >
                        {{ cartCount }}
                    </span>
                </div>
                <span>Carrito</span>
            </button>
        </div>

        <!-- MODAL DE CONFIRMACIÓN -->
        <ConfirmSaleModal 
            :show="showConfirmModal"
            :gross-subtotal="grossSubtotal"
            :total-discounts="totalDiscounts"
            :final-total="finalTotal"
            :processing="form.processing"
            :form="form"
            :clients="clients"
            :selected-client="selectedClient"
            :loading-clients="loadingClients"
            :shipping-history="shippingHistory"
            :is-fixed-discount="isFixedDiscount"
            :default-client="initialConfig?.defaultClient"
            :permissions="initialConfig?.permissions"
            v-model:clientSearch="clientQuery"
            v-model:global-discount="globalDiscount"
            @close="showConfirmModal = false"
            @confirm="handleConfirmSale"
            @open-client-modal="showClientModal = true"
            @client-updated="v => selectedClient = v"
        />

        <!-- ClientModal removido ya que el módulo de clientes fue eliminado -->
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d4d4d8; border-radius: 4px; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background: #a1a1aa; }
</style>
