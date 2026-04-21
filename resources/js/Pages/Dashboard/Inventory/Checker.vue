<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    ShoppingCart,
    AlertTriangle,
    AlertCircle,
    CheckCircle,
    RefreshCw,
    Search,
    X,
    Info
} from 'lucide-vue-next';

const props = defineProps({
    materials: {
        type: Array,
        default: () => [],
    },
    pendingOrdersCount: {
        type: Number,
        default: 0,
    },
    auth: Object,
});

// UI state
const searchQuery = ref('');
const processingMaterial = ref(null);
const checkingOrders = ref(false);

// MODAL CONFIRMATION STATE
const showConfirmModal = ref(false);
const confirmConfig = ref({
    title: '',
    message: '',
    confirmText: '',
    type: 'blue', // blue, green, amber
    action: null
});

// Helper to trigger confirmation
const triggerConfirm = (title, message, confirmText, type, action) => {
    confirmConfig.value = { title, message, confirmText, type, action };
    showConfirmModal.value = true;
};

// Filter materials
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) ||
        mat.mat_id.toLowerCase().includes(q)
    );
});

/**
 * PROCUREMENT LOGIC
 * Fixed: This now sends the data object instead of an empty {}
 */
const handleProcurementAction = (material) => {
    showConfirmModal.value = false;
    processingMaterial.value = material.id;
    
    // Prepare the payload for the controller
    const payload = {
        required_qty: material.reorder_point > 0 ? material.reorder_point : 100,
        urgency: 'Medium',
        notes: 'Auto-requested via Stock Checker dashboard.'
    };

    router.post(route('inv.checker.procurement', material.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            processingMaterial.value = null;
        },
    });
};

const requestProcurement = (material) => {
    triggerConfirm(
        'Request Procurement',
        `Send a procurement request for "${material.name}" to the SCM department?`,
        'Send Request',
        'amber',
        () => handleProcurementAction(material)
    );
};

/**
 * ORDER CHECK LOGIC
 */
const handleOrderCheckAction = () => {
    showConfirmModal.value = false;
    checkingOrders.value = true;
    router.post(route('inv.checker.orders'), {}, {
        preserveScroll: true,
        onFinish: () => {
            checkingOrders.value = false;
        },
    });
};

const checkOrders = () => {
    triggerConfirm(
        'System Sufficiency Check',
        'Verify material sufficiency for all pending orders? This will update order statuses and auto-generate material requests where needed.',
        'Run Check',
        'blue',
        handleOrderCheckAction
    );
};

// Status badge helper
const statusBadge = (status) => {
    switch (status) {
        case 'ok': return { class: 'bg-emerald-100 text-emerald-700', label: 'In Stock' };
        case 'low': return { class: 'bg-amber-100 text-amber-700', label: 'Low Stock' };
        case 'out': return { class: 'bg-red-100 text-red-600', label: 'Out of Stock' };
        default: return { class: 'bg-gray-100 text-gray-700', label: status };
    }
};
</script>

<template>
    <Head title="Stock Checker | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">Stock Checker</h1>
                        <p class="text-slate-500 text-sm mt-0.5 font-medium">Monitor material stock levels and trigger procurement or order checks.</p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="checkOrders"
                            :disabled="checkingOrders"
                            class="inline-flex items-center gap-2 px-6 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-xs font-black uppercase rounded-2xl hover:opacity-80 transition shadow-lg shadow-slate-200 disabled:opacity-50"
                        >
                            <RefreshCw :class="['w-4 h-4', checkingOrders && 'animate-spin']" />
                            Check Pending Orders ({{ pendingOrdersCount }})
                        </button>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="relative max-w-sm">
                        <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search materials..."
                            class="w-full pl-11 pr-4 py-3 bg-white dark:bg-slate-900 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 shadow-sm transition"
                        />
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800 overflow-hidden shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Material ID</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Name</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Total Stock</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Unit</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Reorder Point</th>
                                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <tr v-if="filteredMaterials.length === 0">
                                    <td colspan="8" class="px-6 py-20 text-center text-slate-400">
                                        <Package class="w-12 h-12 mx-auto mb-4 opacity-20" />
                                        <p class="font-bold uppercase tracking-widest text-xs italic">No materials found</p>
                                    </td>
                                </tr>
                                <tr v-for="mat in filteredMaterials" :key="mat.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-6 py-5 font-mono text-xs font-bold text-blue-600">{{ mat.mat_id }}</td>
                                    <td class="px-6 py-5 font-bold text-slate-700 dark:text-slate-200">{{ mat.name }}</td>
                                    <td class="px-6 py-5 text-slate-500 font-medium">{{ mat.category }}</td>
                                    <td class="px-6 py-5 text-right font-black text-slate-900 dark:text-white">{{ mat.total_stock.toLocaleString() }}</td>
                                    <td class="px-6 py-5 font-bold text-slate-500">{{ mat.unit }}</td>
                                    <td class="px-6 py-5 text-slate-500 font-bold">{{ mat.reorder_point.toLocaleString() }}</td>
                                    <td class="px-6 py-5">
                                        <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter', statusBadge(mat.status).class]">
                                            <AlertTriangle v-if="mat.status === 'low'" class="w-3 h-3" />
                                            <CheckCircle v-else-if="mat.status === 'ok'" class="w-3 h-3" />
                                            <AlertCircle v-else class="w-3 h-3" />
                                            {{ statusBadge(mat.status).label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <button
                                            v-if="mat.status !== 'ok'"
                                            @click="requestProcurement(mat)"
                                            :disabled="processingMaterial === mat.id"
                                            class="inline-flex items-center gap-2 px-4 py-2 text-[10px] font-black uppercase tracking-widest rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition shadow-sm disabled:opacity-50"
                                        >
                                            <ShoppingCart class="w-3.5 h-3.5" />
                                            {{ processingMaterial === mat.id ? 'Sending...' : 'Procure' }}
                                        </button>
                                        <span v-else class="text-slate-300 text-[10px] font-black uppercase tracking-widest italic">— Adequate —</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showConfirmModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl border border-slate-200 dark:border-slate-800 animate-in zoom-in duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div :class="{
                            'bg-blue-50 text-blue-600': confirmConfig.type === 'blue',
                            'bg-amber-50 text-amber-600': confirmConfig.type === 'amber',
                            'bg-emerald-50 text-emerald-600': confirmConfig.type === 'green'
                        }" class="h-20 w-20 rounded-full flex items-center justify-center mb-6">
                            <Info v-if="confirmConfig.type === 'blue'" class="w-10 h-10" />
                            <AlertTriangle v-if="confirmConfig.type === 'amber'" class="w-10 h-10" />
                            <CheckCircle v-if="confirmConfig.type === 'green'" class="w-10 h-10" />
                        </div>
                        
                        <h3 class="text-2xl font-black uppercase tracking-tighter mb-2 text-slate-900 dark:text-white leading-none">
                            {{ confirmConfig.title }}
                        </h3>
                        <p class="text-sm font-bold text-slate-500 leading-relaxed px-4">
                            {{ confirmConfig.message }}
                        </p>
                        
                        <div class="mt-10 flex w-full gap-3">
                            <button 
                                @click="showConfirmModal = false" 
                                class="flex-1 px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:opacity-80 transition"
                            >
                                Cancel
                            </button>
                            <button 
                                @click="confirmConfig.action" 
                                :class="{
                                    'bg-blue-600': confirmConfig.type === 'blue',
                                    'bg-amber-600': confirmConfig.type === 'amber',
                                    'bg-emerald-600': confirmConfig.type === 'green'
                                }"
                                class="flex-[1.5] px-6 py-4 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg hover:opacity-90 transition-all"
                            >
                                {{ confirmConfig.confirmText }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes zoom-in { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-in { animation-fill-mode: both; }
.zoom-in { animation: zoom-in 0.2s ease-out; }
</style>