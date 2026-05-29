import { ref } from 'vue';

export function usePOSConfig(initialConfig) {
    const warehouseId = ref(initialConfig?.activeWarehouseId);
    const posId = ref(initialConfig?.activePosId);

    const setWarehouse = (id) => {
        warehouseId.value = id;
    };

    const setPOS = (id) => {
        posId.value = id;
    };

    return {
        warehouseId,
        posId,
        setWarehouse,
        setPOS
    };
}
