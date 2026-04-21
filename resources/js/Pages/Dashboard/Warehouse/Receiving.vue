<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Truck, Package, X, CheckCircle, ChevronDown, 
    Eye, ClipboardList, Search, Calendar, User, 
    Info, Hash, ArrowDownToLine, AlertCircle, Loader2
} from 'lucide-vue-next';

const props = defineProps({
    receivings: { type: Array, default: () => [] },
    pendingPOs: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    auth: Object,
});

// Safe computed properties
const safePendingPOs = computed(() => props.pendingPOs ?? []);
const safeReceivings = computed(() => props.receivings ?? []);
const safeWarehouses = computed(() => props.warehouses ?? []);

// UI State
const showReceiveModal = ref(false);
const showPastReceivings = ref(false);
const showViewModal = ref(false); 
const selectedPO = ref(null);
const selectedReceiving = ref(null); 
const searchQuery = ref('');

// Form for receiving
const receiveForm = useForm({
    warehouse_id: '',
    po_id: null,
    items: [],
});

/**
 * Extracts material name from PO structure
 */
const getPOMaterialDisplay = (po) => {
    if (!po.items || po.items.length === 0) return 'No Materials';
    const firstItem = po.items[0];
    const name = firstItem.material?.name || firstItem.material_name || 'Unknown Item';
    return po.items.length > 1 ? `${name} (+${po.items.length - 1} more)` : name;
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleString('en-PH', { 
        dateStyle: 'medium', 
        timeStyle: 'short' 
    });
};

const statusBadge = (status) => {
    const styles = {
        pending: 'bg-amber-100 text-amber-700',
        partial: 'bg-blue-100 text-blue-700',
        completed: 'bg-emerald-100 text-emerald-700',
    };
    return styles[status] || 'bg-gray-100 text-gray-700';
};

const selectPO = (po) => {
    selectedPO.value = po;
    receiveForm.po_id = po.id;
    receiveForm.warehouse_id = safeWarehouses.value.length > 0 ? safeWarehouses.value[0].id : '';
    
    receiveForm.items = po.items.map(item => ({
        material_id: item.material_id,
        material_name: item.material?.name || item.material_name,
        expected_qty: parseFloat(item.qty),
        received_qty: parseFloat(item.qty),
        rejected_qty: 0,
        reject_reason: '',
        unit: item.unit,
    }));
    
    showReceiveModal.value = true;
};

const openViewModal = (rec) => {
    selectedReceiving.value = rec;
    showViewModal.value = true;
};

const updateReceivedQty = (index, value) => {
    const item = receiveForm.items[index];
    let received = parseFloat(value) || 0;
    if (received > item.expected_qty) received = item.expected_qty;
    item.received_qty = received;
    item.rejected_qty = item.expected_qty - received;
};

const submitReceive = () => {
    if (!receiveForm.warehouse_id) return alert('Please select a destination warehouse.');
    
    receiveForm.post(route('warehouse.receiving.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showReceiveModal.value = false;
            receiveForm.reset();
        },
        onError: () => alert('Error processing receipt.')
    });
};

const filteredReceivings = computed(() => {
    if (!searchQuery.value) return safeReceivings.value;
    const q = searchQuery.value.toLowerCase();
    return safeReceivings.value.filter(rec =>
        rec.receiving_number?.toLowerCase().includes(q) ||
        (rec.purchase_order?.po_number || '').toLowerCase().includes(q)
    );
});
</script>

<template>
    <Head title="Receiving & Logistics" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight uppercase italic">Receiving Unit</h1>
                <p class="text-slate-500 text-sm mt-1">Manage incoming supply deliveries and store as unique lots.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden h-fit">
                    <div class="px-8 py-6 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Truck class="w-5 h-5 text-emerald-500" />
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-800 dark:text-white">Awaiting Arrivals</h2>
                        </div>
                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 rounded-full text-[10px] font-black text-slate-500">{{ safePendingPOs.length }} ORDERS</span>
                    </div>

                    <div v-if="safePendingPOs.length === 0" class="p-16 text-center text-slate-400 font-bold uppercase text-xs">Queue Clear</div>

                    <div v-else class="divide-y divide-slate-50 dark:divide-slate-800">
                        <div v-for="po in safePendingPOs" :key="po.id" class="p-6 hover:bg-slate-50/50 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs font-black text-blue-600 uppercase">{{ po.po_number }}</span>
                                        <span class="text-[9px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 uppercase">{{ po.status }}</span>
                                    </div>
                                    <p class="text-xl font-black text-slate-900 dark:text-white leading-tight uppercase">{{ getPOMaterialDisplay(po) }}</p>
                                    <p class="text-xs font-bold text-slate-500">{{ po.supplier_name }}</p>
                                </div>
                                <button @click="selectPO(po)" class="px-6 py-3 bg-slate-900 text-white text-[10px] font-black uppercase rounded-2xl hover:bg-black transition shadow-lg">
                                    Receive
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden h-fit">
                    <button @click="showPastReceivings = !showPastReceivings" class="w-full px-8 py-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-2">
                            <ClipboardList class="w-5 h-5 text-slate-400" />
                            <h2 class="text-sm font-black uppercase tracking-widest text-slate-800 dark:text-white">Receiving History</h2>
                        </div>
                        <ChevronDown :class="['w-5 h-5 text-slate-400 transition-transform duration-300', showPastReceivings ? 'rotate-180' : '']" />
                    </button>

                    <div v-show="showPastReceivings" class="border-t border-slate-100 dark:border-slate-800">
                        <div class="p-4 border-b">
                            <div class="relative">
                                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="searchQuery" type="text" placeholder="Search PO # or Rec #..." class="w-full pl-10 pr-4 py-2.5 text-sm bg-slate-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500/20" />
                            </div>
                        </div>

                        <div class="divide-y divide-slate-50 max-h-[500px] overflow-y-auto">
                            <div v-for="rec in filteredReceivings" :key="rec.id" class="p-6 hover:bg-slate-50 transition-colors flex items-center justify-between">
                                <div class="space-y-1">
                                    <span class="font-mono text-xs font-black text-slate-400 uppercase tracking-tighter">{{ rec.receiving_number }}</span>
                                    <p class="text-sm font-black">PO: <span class="text-blue-600">{{ rec.purchase_order?.po_number || 'N/A' }}</span></p>
                                    <p class="text-[11px] text-slate-500 flex items-center gap-1.5"><Calendar class="w-3.5 h-3.5" /> {{ formatDate(rec.received_at) }}</p>
                                </div>
                                <button @click="openViewModal(rec)" class="p-3 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-2xl transition border border-transparent hover:border-blue-100">
                                    <Eye class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showViewModal && selectedReceiving" class="fixed inset-0 z-[110] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm" @click.self="showViewModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-2xl flex flex-col overflow-hidden animate-in zoom-in duration-200">
                    <div class="px-8 py-6 border-b flex items-center justify-between bg-slate-50 dark:bg-slate-800">
                        <div>
                            <h3 class="text-xl font-black uppercase italic tracking-tighter text-slate-900 dark:text-white">Receiving Log Details</h3>
                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest mt-1">LOG REF: {{ selectedReceiving.receiving_number }}</p>
                        </div>
                        <button @click="showViewModal = false" class="p-2 rounded-full hover:bg-white transition shadow-sm border"><X class="w-5 h-5" /></button>
                    </div>

                    <div class="p-8 space-y-6 overflow-y-auto max-h-[70vh]">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border dark:border-slate-700">
                                <p class="text-[9px] font-black text-slate-400 uppercase">Received At</p>
                                <p class="text-sm font-bold mt-1 text-slate-800 dark:text-slate-200">{{ formatDate(selectedReceiving.received_at) }}</p>
                            </div>
                            <div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border dark:border-slate-700">
                                <p class="text-[9px] font-black text-slate-400 uppercase">Warehouse</p>
                                <p class="text-sm font-bold mt-1 text-slate-800 dark:text-slate-200">{{ selectedReceiving.warehouse?.name }}</p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">Received Items Breakdown</p>
                            <div v-for="item in selectedReceiving.items" :key="item.id" class="p-5 rounded-2xl border dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between shadow-sm">
                                <div>
                                    <p class="font-black uppercase text-sm leading-tight text-slate-800 dark:text-slate-100">{{ item.material_name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Expected: {{ item.expected_qty }} {{ item.unit }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-emerald-600">+{{ item.received_qty }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 border-t bg-slate-50 dark:bg-slate-800 flex justify-end">
                        <button @click="showViewModal = false" class="px-10 py-3 bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-[10px] font-black uppercase rounded-2xl transition tracking-[0.2em]">Close Record</button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showReceiveModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showReceiveModal = false">
                <div class="bg-white dark:bg-slate-900 rounded-[3rem] shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden animate-in zoom-in duration-200">
                    
                    <div class="px-8 py-6 border-b flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
                        <div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white uppercase italic tracking-tighter">Inventory Check-In</h3>
                            <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">Verification for PO: <span class="text-blue-600">{{ selectedPO?.po_number }}</span></p>
                        </div>
                        <button @click="showReceiveModal = false" class="p-2 rounded-full hover:bg-white transition shadow-sm border"><X class="w-5 h-5" /></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 space-y-8">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Destination Warehouse *</label>
                            <select v-model="receiveForm.warehouse_id" class="w-full pl-4 pr-10 py-4 text-sm font-bold bg-slate-50 dark:bg-slate-800 border-none rounded-2xl focus:ring-4 focus:ring-emerald-500/10">
                                <option value="" disabled>Select a location...</option>
                                <option v-for="wh in safeWarehouses" :key="wh.id" :value="wh.id">{{ wh.name }} ({{ wh.location }})</option>
                            </select>
                        </div>

                        <div class="space-y-4">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">Verify Material Quantities</p>
                            
                            <div v-for="(item, idx) in receiveForm.items" :key="idx" class="p-6 rounded-[2rem] bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex flex-col md:flex-row md:items-center gap-6">
                                <div class="flex-1">
                                    <p class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">{{ item.material_name }}</p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Expected Qty: {{ item.expected_qty }} {{ item.unit }}</p>
                                </div>
                                
                                <div class="flex gap-4 flex-shrink-0">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-emerald-500 uppercase tracking-tighter ml-1">Received</label>
                                        <input type="number" v-model.number="item.received_qty" @input="updateReceivedQty(idx, $event.target.value)" min="0" :max="item.expected_qty" class="w-28 px-4 py-3 text-sm font-black bg-emerald-50 dark:bg-emerald-900/20 border-none rounded-xl focus:ring-2 focus:ring-emerald-500" />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-black text-red-500 uppercase tracking-tighter ml-1">Rejected</label>
                                        <input type="number" :value="item.expected_qty - item.received_qty" readonly class="w-28 px-4 py-3 text-sm font-black bg-red-50 dark:bg-red-900/20 border-none rounded-xl text-red-600" />
                                    </div>
                                    <div class="space-y-1 flex-1 min-w-[150px]" v-if="item.received_qty < item.expected_qty">
                                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-tighter ml-1">Reason</label>
                                        <input v-model="item.reject_reason" type="text" placeholder="Shortage reason..." class="w-full px-4 py-3 text-[11px] bg-slate-50 dark:bg-slate-700 border-none rounded-xl focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 border-t flex flex-col sm:flex-row gap-4 bg-slate-50 dark:bg-slate-800 flex-shrink-0">
                        <button @click="showReceiveModal = false" class="flex-1 py-4 text-xs font-black uppercase rounded-2xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-white transition-colors">Cancel</button>
                        <button @click="submitReceive" :disabled="receiveForm.processing || !receiveForm.warehouse_id" class="flex-[2] inline-flex items-center justify-center gap-2 py-4 text-xs font-black uppercase tracking-[0.2em] rounded-2xl bg-emerald-600 text-white hover:bg-emerald-700 transition shadow-xl shadow-emerald-200 disabled:opacity-40">
                            <CheckCircle class="w-4 h-4" /> 
                            {{ receiveForm.processing ? 'Processing...' : 'Finalize & Update Stock' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes zoom-in { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-in { animation: zoom-in 0.2s ease-out; }
</style>