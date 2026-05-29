import { onMounted } from 'vue';

export function useLeafletMap(activeBranch) {
    let map = null;
    let marker = null;

    const initMap = () => {
        if (typeof L === 'undefined') return;
        
        const lat = activeBranch.value?.latitude || -17.3895;
        const lng = activeBranch.value?.longitude || -66.1568;

        if (!map) {
            map = L.map('branch-map', {
                center: [lat, lng],
                zoom: 15,
                scrollWheelZoom: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
        } else {
            map.setView([lat, lng], 15);
        }

        if (marker) marker.remove();
        marker = L.marker([lat, lng]).addTo(map)
            .bindPopup(`<b>${activeBranch.value?.name || 'Sucursal'}</b><br>${activeBranch.value?.address || ''}`)
            .openPopup();
    };

    const loadLeaflet = () => {
        if (!document.getElementById('leaflet-css')) {
            const link = document.createElement('link');
            link.id = 'leaflet-css';
            link.rel = 'stylesheet';
            link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
            document.head.appendChild(link);
        }

        if (!document.getElementById('leaflet-js')) {
            const script = document.createElement('script');
            script.id = 'leaflet-js';
            script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
            script.onload = () => {
                setTimeout(initMap, 500);
            };
            document.head.appendChild(script);
        } else {
            setTimeout(initMap, 500);
        }
    };

    return {
        initMap,
        loadLeaflet
    };
}
