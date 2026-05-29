<script setup>
import { onMounted, ref } from 'vue';

const darkMode = ref(localStorage.getItem('darkMode') === 'true');

onMounted(() => {
    // Sincronizar modo oscuro con el sistema/localStorage
    if (darkMode.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <div class="min-h-screen bg-zinc-50 dark:bg-zinc-950 transition-colors duration-300">
        <!-- El POS es pantalla completa, así que solo renderizamos el slot -->
        <slot />
    </div>
</template>

<style>
/* Estilos globales específicos para el POS si fuera necesario */
body {
    overflow: hidden; /* Evitar scroll en el body para manejarlo internamente en el POS */
}
</style>
