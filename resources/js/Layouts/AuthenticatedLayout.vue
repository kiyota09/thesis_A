<script setup>
import Sidebar from './Sidebar.vue'
import MobileSidebar from './MobileSidebar.vue'
import { usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import Swal from 'sweetalert2'

const page = usePage()
const user = computed(() => page.props.auth.user)
const isLocating = ref(true)
const locationReady = ref(false)

// Monitor for security flash messages from the backend
watch(() => page.props.flash?.geofence_error, (message) => {
    if (message) {
        Swal.fire({
            title: 'Security Violation',
            text: message,
            icon: 'error',
            confirmButtonText: 'Acknowledge',
            confirmButtonColor: '#2563eb',
            allowOutsideClick: false,
            customClass: { popup: 'rounded-[2rem]' }
        });
    }
}, { immediate: true })

onMounted(() => {
    if (navigator.geolocation) {
        // High Accuracy is removed to prevent "Timeout Expired" on laptops
        const options = {
            enableHighAccuracy: false, 
            maximumAge: 0 
        };

        const success = (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            isLocating.value = false;
            locationReady.value = true;

            // Set global headers for every Axios request
            axios.defaults.headers.common['X-User-Lat'] = lat;
            axios.defaults.headers.common['X-User-Lng'] = lng;

            // Share location with all child components via Inertia props
            page.props.auth.location = { lat, lng };
            console.log(`📡 GPS Synced: ${lat}, ${lng}`);
        };

        const error = (err) => {
            isLocating.value = false;
            console.warn("GPS Warning: Retrying in standard mode...");
            navigator.geolocation.getCurrentPosition(success, null, { enableHighAccuracy: false });
        };

        navigator.geolocation.watchPosition(success, error, options);
    }
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-zinc-950">
        <Sidebar />
        <div class="md:pl-64 flex flex-col flex-1">
            <main class="py-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="mb-4 flex items-center gap-4">
                        <div v-if="locationReady" class="text-[10px] text-emerald-500 font-bold uppercase tracking-widest bg-emerald-50 px-3 py-1 rounded-full border border-emerald-100 flex items-center gap-2">
                            <span class="size-2 bg-emerald-500 rounded-full animate-pulse"></span>
                            Secure Zone Verified
                        </div>
                        <div v-else class="text-[10px] text-blue-500 font-bold uppercase tracking-widest bg-blue-50 px-3 py-1 rounded-full animate-pulse border border-blue-100">
                            Searching Perimeter...
                        </div>
                    </div>
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>