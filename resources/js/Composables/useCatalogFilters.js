import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

export function useCatalogFilters(initialFilters, maxPriceLimit, branchIdRef) {
    const page = usePage();
    const search = ref(initialFilters.q || '');
    const selectedCategories = ref(initialFilters.c || []);
    const minPrice = ref(initialFilters.min || 0);
    const maxPrice = ref(initialFilters.max || maxPriceLimit);
    const sortBy = ref(initialFilters.sort || 'newest');
    const brand = ref(initialFilters.brand || '');

    const applyFilters = () => {
        router.get(route('public.catalog', { company_slug: page.props.company_slug }), {
            q: search.value,
            c: selectedCategories.value,
            min: minPrice.value,
            max: maxPrice.value,
            sort: sortBy.value,
            b: branchIdRef.value,
            brand: brand.value
        }, {
            only: ['products', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    };

    const debouncedApplyFilters = debounce(applyFilters, 300);

    watch(search, () => {
        debouncedApplyFilters();
    });

    watch([selectedCategories, sortBy, branchIdRef, brand], () => {
        applyFilters();
    });

    const resetFilters = () => {
        search.value = '';
        selectedCategories.value = [];
        minPrice.value = 0;
        maxPrice.value = maxPriceLimit;
        sortBy.value = 'newest';
        brand.value = '';
        applyFilters();
    };

    return {
        search,
        selectedCategories,
        minPrice,
        maxPrice,
        sortBy,
        brand,
        applyFilters,
        resetFilters
    };
}
