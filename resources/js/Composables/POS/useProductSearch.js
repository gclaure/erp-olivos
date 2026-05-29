import { ref, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';

export function useProductSearch(warehouseId) {
    const query = ref('');
    const products = ref([]);
    const loading = ref(false);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0,
        from: 0,
        to: 0
    });

    const search = debounce(async (page = 1) => {
        if (!warehouseId.value) return;
        
        loading.value = true;
        try {
            const response = await axios.get(route('admin.api.pos.products'), {
                params: {
                    warehouse_id: warehouseId.value,
                    search: query.value,
                    page: page
                }
            });
            products.value = response.data.data;
            pagination.value = {
                current_page: response.data.meta.current_page,
                last_page: response.data.meta.last_page,
                per_page: response.data.meta.per_page,
                total: response.data.meta.total,
                from: response.data.meta.from || 0,
                to: response.data.meta.to || 0
            };

        } catch (error) {
            console.error('Error searching products:', error);
        } finally {
            loading.value = false;
        }
    }, 300);

    watch([query, warehouseId], () => {
        search(1);
    }, { immediate: true });

    return {
        query,
        products,
        loading,
        pagination,
        search
    };
}
