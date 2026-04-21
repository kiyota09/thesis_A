<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { 
    MapPin, ShieldCheck, Loader2, Globe, Navigation, 
    Crosshair, Radio, Layers, Save, AlertCircle, CheckCircle2,
    Database, Activity, Clock
} from 'lucide-vue-next';

// Leaflet imports
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
    savedLocation: Object
});

// Helper function to safely convert to number
const toNum = (val) => val ? Number(val) : null;

// State
const coords = ref({ 
    latitude: toNum(props.savedLocation?.latitude) || 14.5995, 
    longitude: toNum(props.savedLocation?.longitude) || 120.9842, 
    accuracy: null 
});

// For the Status Bar - Ensuring these are Numbers to prevent .toFixed() errors
const lastSavedInfo = ref({
    lat: toNum(props.savedLocation?.latitude),
    lng: toNum(props.savedLocation?.longitude),
    time: props.savedLocation?.created_at ? new Date(props.savedLocation.created_at).toLocaleTimeString() : null
});

const rangeRadius = ref(toNum(props.savedLocation?.range_radius) || 500); 
const isSyncing = ref(false);
const isSaving = ref(false);
const statusMsg = ref(null);
const errorMsg = ref(null);

let map = null;
let marker = null;
let circle = null;

const initMap = () => {
    const streetView = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 });
    const satelliteView = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 20 });

    map = L.map('map', {
        center: [coords.value.latitude, coords.value.longitude],
        zoom: 15,
        layers: [streetView]
    });

    L.control.layers({ "Default": streetView, "Satellite": satelliteView }).addTo(map);

    marker = L.marker([coords.value.latitude, coords.value.longitude]).addTo(map);
    circle = L.circle([coords.value.latitude, coords.value.longitude], {
        color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.15, radius: rangeRadius.value
    }).addTo(map);
};

const updateMap = () => {
    if (map && marker && circle) {
        const pos = [coords.value.latitude, coords.value.longitude];
        marker.setLatLng(pos);
        circle.setLatLng(pos);
        circle.setRadius(rangeRadius.value);
        map.panTo(pos);
    }
};

watch([() => coords.value.latitude, () => coords.value.longitude, rangeRadius], updateMap);
onMounted(initMap);

const saveToDatabase = async () => {
    isSaving.value = true;
    errorMsg.value = null;
    statusMsg.value = null;

    const payload = {
        latitude: coords.value.latitude,
        longitude: coords.value.longitude,
        range_radius: parseInt(rangeRadius.value),
        label: 'CEO Manual Log'
    };

    try {
        const response = await axios.post(route('ceo.location.store'), payload);
        statusMsg.value = response.data.message;
        
        // Update the Live Status Bar with fresh Numbers
        lastSavedInfo.value = {
            lat: Number(payload.latitude),
            lng: Number(payload.longitude),
            time: new Date().toLocaleTimeString()
        };
    } catch (err) {
        errorMsg.value = err.response?.data?.message || "Failed to save to database.";
    } finally {
        isSaving.value = false;
    }
};

const syncGps = () => {
    isSyncing.value = true;
    navigator.geolocation.getCurrentPosition((pos) => {
        coords.value.latitude = pos.coords.latitude;
        coords.value.longitude = pos.coords.longitude;
        coords.value.accuracy = pos.coords.accuracy ? pos.coords.accuracy.toFixed(2) : 'N/A';
        map.setZoom(18);
        isSyncing.value = false;
    }, () => {
        errorMsg.value = "GPS Signal Denied.";
        isSyncing.value = false;
    }, { enableHighAccuracy: true });
};
</script>

<template>
    <Head title="Strategic Geolocation" />
    <AuthenticatedLayout>
        <div class="py-8 max-w-7xl mx-auto px-4 uppercase font-black tracking-tight">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                
                <div class="lg:col-span-1 space-y-4">
                    <div class="bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-100 shadow-xl">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="bg-blue-600 p-2 rounded-xl text-white shadow-lg">
                                <Crosshair class="w-5 h-5" />
                            </div>
                            <h2 class="text-lg text-slate-900 dark:text-white leading-tight">Controls</h2>
                        </div>
                        
                        <div class="space-y-4 mb-8">
                            <div>
                                <label class="text-[9px] text-slate-400 block mb-1">Latitude</label>
                                <input v-model.number="coords.latitude" type="number" step="any" class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl shadow-inner font-black" />
                            </div>
                            <div>
                                <label class="text-[9px] text-slate-400 block mb-1">Longitude</label>
                                <input v-model.number="coords.longitude" type="number" step="any" class="w-full p-4 bg-slate-50 dark:bg-slate-800 border-none rounded-2xl shadow-inner font-black" />
                            </div>
                            <div>
                                <label class="text-[9px] text-slate-400 block mb-1">Range Radius (M)</label>
                                <input v-model.number="rangeRadius" type="number" class="w-full p-4 bg-blue-50 dark:bg-blue-900/20 border-none rounded-2xl shadow-inner font-black text-blue-600" />
                            </div>
                        </div>

                        <div v-if="errorMsg" class="mb-4 p-4 bg-red-50 text-red-600 rounded-xl text-[9px] border border-red-100 flex items-center gap-2">
                            <AlertCircle class="w-4 h-4" /> {{ errorMsg }}
                        </div>
                        <div v-if="statusMsg" class="mb-4 p-4 bg-emerald-50 text-emerald-600 rounded-xl text-[9px] border border-emerald-100 flex items-center gap-2">
                            <CheckCircle2 class="w-4 h-4" /> {{ statusMsg }}
                        </div>

                        <div class="flex flex-col gap-3">
                            <!-- <button @click="syncGps" :disabled="isSyncing" class="w-full py-5 bg-slate-900 text-white rounded-2xl shadow-lg flex items-center justify-center gap-3 text-[10px] tracking-widest hover:bg-black transition-all">
                                <Loader2 v-if="isSyncing" class="w-4 h-4 animate-spin" />
                                <Navigation v-else class="w-4 h-4" /> 1. SYNC GPS
                            </button> -->
                            
                            <button @click="saveToDatabase" :disabled="isSaving" class="w-full py-5 bg-blue-600 text-white rounded-2xl shadow-lg flex items-center justify-center gap-3 text-[10px] tracking-widest hover:bg-blue-700 transition-all">
                                <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
                                <Save v-else class="w-4 h-4" /> ARCHIVE DATA
                            </button>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <div class="bg-white dark:bg-slate-900 p-2 rounded-[3.5rem] border border-slate-100 shadow-2xl h-[720px] overflow-hidden relative group">
                        <div id="map" class="w-full h-full rounded-[3rem] z-0"></div>
                        
                        <div class="absolute top-8 left-8 z-[1000] pointer-events-none">
                            <div class="bg-slate-900/80 backdrop-blur-md text-white px-4 py-2 rounded-xl text-[9px] tracking-widest flex items-center gap-3">
                                <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                                SECURE LIVE FEED
                            </div>
                        </div>

                        <div class="absolute bottom-6 left-6 right-6 z-[1000]">
                            <div class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-3xl p-4 shadow-2xl flex items-center justify-between">
                                <div class="flex items-center gap-6">
                                    <div class="flex items-center gap-2">
                                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                            <Database class="w-4 h-4 text-blue-600" />
                                        </div>
                                        <div>
                                            <p class="text-[8px] text-slate-400 uppercase">Database Status</p>
                                            <p class="text-[10px] text-slate-900 dark:text-white uppercase">Active / Archiving</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 border-l border-slate-200 dark:border-slate-800 pl-6">
                                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                            <Activity class="w-4 h-4 text-emerald-600" />
                                        </div>
                                        <div v-if="typeof lastSavedInfo.lat === 'number'">
                                            <p class="text-[8px] text-slate-400 uppercase">Last Saved Coordinates</p>
                                            <p class="text-[10px] text-slate-900 dark:text-white">
                                                {{ lastSavedInfo.lat.toFixed(4) }}, {{ lastSavedInfo.lng.toFixed(4) }}
                                            </p>
                                        </div>
                                        <div v-else>
                                            <p class="text-[8px] text-slate-400 uppercase">Last Saved Coordinates</p>
                                            <p class="text-[10px] text-slate-300 italic uppercase">No Archive Data</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 bg-slate-50 dark:bg-slate-800/50 px-4 py-2 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <Clock class="w-3.5 h-3.5 text-slate-400" />
                                    <p class="text-[10px] text-slate-500 uppercase">
                                        Sync: {{ lastSavedInfo.time ? lastSavedInfo.time : 'Waiting' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
#map { background: #0f172a; }
:deep(.leaflet-control-layers) { border-radius: 1.5rem !important; border: none !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 8px; font-weight: 900; font-size: 9px; text-transform: uppercase; }
</style>