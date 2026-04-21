<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    ClipboardList, Send, ArrowRight, X, CheckCircle, 
    Users, AlertTriangle, Clock, TrendingUp, SearchX, 
    Info, AlertCircle, Loader2 
} from 'lucide-vue-next';

const props = defineProps({
    materialRequests: Array,
    warehouses: Array,
    suppliers: Array,
    stats: Object,
});

const today = new Date().toISOString().split('T')[0];

const totalRequests = computed(() => props.materialRequests.length);
const highUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'High').length);
const mediumUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'Medium').length);
const lowUrgencyCount = computed(() => props.materialRequests.filter(r => r.urgency === 'Low').length);

const urgencyData = computed(() => [
    { label: 'High', count: highUrgencyCount.value, color: 'bg-red-500' },
    { label: 'Medium', count: mediumUrgencyCount.value, color: 'bg-yellow-500' },
    { label: 'Low', count: lowUrgencyCount.value, color: 'bg-green-500' },
]);
const maxUrgency = computed(() => Math.max(highUrgencyCount.value, mediumUrgencyCount.value, lowUrgencyCount.value, 1));

const showRFQModal = ref(false);
const selectedRequest = ref(null);
const rfqForm = useForm({
    mr_id: null,
    deadline: '',
    payment_terms: 'Cash on delivery',
    notes: '',
    selected_suppliers: [],
});
const supplierStep = ref(false);

const showConfirmModal = ref(false);
const confirmConfig = ref({
    title: '',
    message: '',
    type: 'confirm',
    action: null
});

const triggerModalAlert = (title, message, type = 'alert', action = null) => {
    confirmConfig.value = { title, message, type, action };
    showConfirmModal.value = true;
};

const openRFQ = (req) => {
    selectedRequest.value = req;
    rfqForm.mr_id = req.id;
    rfqForm.deadline = '';
    rfqForm.payment_terms = 'Cash on delivery';
    rfqForm.notes = '';
    rfqForm.selected_suppliers = [];
    supplierStep.value = false;
    showRFQModal.value = true;
};

const proceedToSuppliers = () => {
    if (!rfqForm.deadline) {
        triggerModalAlert('Missing Information', 'Please fill in the response deadline before proceeding.');
        return;
    }
    if (rfqForm.deadline < today) {
        triggerModalAlert('Invalid Date', 'The response deadline cannot be set to a past date.', 'alert');
        return;
    }
    supplierStep.value = true;
};

const toggleSupplier = (id) => {
    const idx = rfqForm.selected_suppliers.indexOf(id);
    if (idx === -1) rfqForm.selected_suppliers.push(id);
    else rfqForm.selected_suppliers.splice(idx, 1);
};

const submitRFQ = () => {
    if (rfqForm.selected_suppliers.length === 0) {
        triggerModalAlert('No Suppliers Selected', 'Please select at least one supplier to send the RFQ to.');
        return;
    }

    // PREVENT DOUBLE CLICK: Only trigger if not already processing
    if (rfqForm.processing) return;

    triggerModalAlert(
        'Confirm Dispatch', 
        `Are you sure you want to send this RFQ to ${rfqForm.selected_suppliers.length} selected supplier(s)?`,
        'confirm',
        () => {
            rfqForm.post(route('pro.manager.rfq.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    showRFQModal.value = false;
                    showConfirmModal.value = false;
                },
                onFinish: () => {
                    // This ensures showConfirmModal closes even if there's an error
                    showConfirmModal.value = false;
                }
            });
        }
    );
};
</script>

<template>
    <Head title="Procurement - Material Requests" />
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-6 sm:space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        Procurement Management <span class="text-blue-600">Dashboard</span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Forwarded requests from SCM awaiting RFQ generation</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Total Requests</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-2">{{ totalRequests }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl">
                            <ClipboardList class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800 transition-all text-red-600">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">High Urgency</p>
                            <p class="text-3xl font-black mt-2">{{ highUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-2xl">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800 transition-all text-yellow-500">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Medium Urgency</p>
                            <p class="text-3xl font-black mt-2">{{ mediumUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl">
                            <Clock class="w-6 h-6" />
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-900 rounded-3xl p-6 shadow-sm border border-gray-100 dark:border-zinc-800 transition-all text-green-500">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-black text-gray-400 uppercase tracking-widest">Low Urgency</p>
                            <p class="text-3xl font-black mt-2">{{ lowUrgencyCount }}</p>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-2xl">
                            <TrendingUp class="w-6 h-6" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <div class="p-8 border-b border-gray-100 dark:border-zinc-800">
                    <h3 class="text-lg font-black uppercase tracking-tighter">Material Requests Queue</h3>
                    <p class="text-xs text-gray-500">Initiate RFQ for forwarded production materials</p>
                </div>

                <div class="p-4 sm:p-6">
                    <div v-if="materialRequests.length === 0" class="py-20 text-center">
                        <SearchX class="w-12 h-12 mx-auto text-gray-300 mb-4" />
                        <p class="text-gray-400 font-bold uppercase text-sm">No pending requests</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div v-for="req in materialRequests" :key="req.id"
                            class="group flex flex-col sm:flex-row sm:items-center justify-between p-6 rounded-3xl border border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/20 hover:bg-white dark:hover:bg-zinc-800 transition-all duration-300">
                            <div class="flex items-center gap-6">
                                <div class="hidden sm:flex h-14 w-14 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-700 items-center justify-center shadow-sm text-blue-600">
                                    <ClipboardList class="w-6 h-6" />
                                </div>
                                <div class="space-y-1">
                                    <p class="font-black text-gray-900 dark:text-white text-xl tracking-tight">{{ req.material_name }}</p>
                                    <div class="flex flex-wrap gap-2 items-center text-[10px] font-black uppercase">
                                        <span class="px-3 py-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-700 rounded-full text-zinc-600">{{ req.required_qty }} {{ req.unit }}</span>
                                        <span :class="{'bg-red-50 text-red-600': req.urgency === 'High', 'bg-yellow-50 text-yellow-600': req.urgency === 'Medium', 'bg-green-50 text-green-600': req.urgency === 'Low'}" class="px-3 py-1 border rounded-full tracking-wider">{{ req.urgency }}</span>
                                        <span class="text-gray-400">REF: {{ req.req_number }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 sm:mt-0">
                                <button @click="openRFQ(req)" class="w-full sm:w-auto px-8 py-4 bg-gray-900 dark:bg-blue-600 hover:bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-3">
                                    Generate Request for Quotation <ArrowRight class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showRFQModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/60 backdrop-blur-md transition-all">
                <div class="bg-white dark:bg-zinc-900 rounded-[3rem] w-full max-w-3xl max-h-[90vh] overflow-hidden shadow-2xl flex flex-col border border-gray-100 dark:border-zinc-800">
                    <div class="p-8 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50 dark:bg-zinc-800/30">
                        <div>
                            <h3 class="text-2xl font-black tracking-tighter uppercase">Generate Request</h3>
                            <p class="text-xs text-gray-500 font-bold uppercase mt-1 tracking-widest">Step {{ supplierStep ? '2 of 2' : '1 of 2' }}</p>
                        </div>
                        <button @click="showRFQModal = false" class="p-3 rounded-2xl hover:bg-white dark:hover:bg-zinc-700 transition"><X class="w-6 h-6" /></button>
                    </div>

                    <div class="p-8 overflow-y-auto flex-1">
                        <div v-if="!supplierStep" class="space-y-8">
                            <div class="bg-blue-600 p-6 rounded-[2rem] text-white flex items-center justify-between shadow-xl shadow-blue-100">
                                <div><p class="text-[10px] font-black uppercase opacity-80">Material</p><p class="text-xl font-black">{{ selectedRequest.material_name }}</p></div>
                                <div class="text-right"><p class="text-[10px] font-black uppercase opacity-80">Quantity</p><p class="text-xl font-black">{{ selectedRequest.required_qty }} {{ selectedRequest.unit }}</p></div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Response Deadline *</label>
                                    <input v-model="rfqForm.deadline" type="date" :min="today" class="w-full px-6 py-4 bg-gray-50 dark:bg-zinc-800 border-none rounded-2xl font-bold text-sm" />
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Payment Terms</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button v-for="term in ['50% Downpayment - 50% COD', 'Cash on delivery', '30 Days', '60 Days', '150 Days']" :key="term" type="button"
                                            @click="rfqForm.payment_terms = term"
                                            :class="rfqForm.payment_terms === term ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-100 text-zinc-500 dark:bg-zinc-800'"
                                            class="px-4 py-3 rounded-xl text-[10px] font-black uppercase transition-all">
                                            {{ term }}
                                        </button>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 ml-1">Notes</label>
                                    <textarea v-model="rfqForm.notes" rows="3" placeholder="Technical requirements..." class="w-full px-6 py-4 bg-gray-50 dark:bg-zinc-800 border-none rounded-3xl font-bold text-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="sup in suppliers" :key="sup.id" @click="toggleSupplier(sup.id)"
                                class="group flex items-center p-6 rounded-3xl border-2 transition-all duration-300"
                                :class="rfqForm.selected_suppliers.includes(sup.id) ? 'border-blue-500 bg-blue-50/50' : 'border-gray-50 dark:border-zinc-800'">
                                <div class="h-14 w-14 rounded-2xl bg-white dark:bg-zinc-900 flex items-center justify-center mr-6 border border-gray-100">
                                    <Users v-if="!rfqForm.selected_suppliers.includes(sup.id)" class="w-6 h-6 text-gray-400" />
                                    <CheckCircle v-else class="w-6 h-6 text-blue-600" />
                                </div>
                                <div class="flex-1"><p class="font-black text-lg text-gray-900 dark:text-white tracking-tight">{{ sup.business_name }}</p><p class="text-xs text-gray-500 font-bold uppercase">{{ sup.email }}</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-8 border-t border-gray-100 dark:border-zinc-800 flex justify-between items-center bg-gray-50 dark:bg-zinc-800/30">
                        <button v-if="supplierStep" @click="supplierStep = false" class="text-sm font-black uppercase text-gray-400">Back</button>
                        <div v-else></div>
                        <button v-if="!supplierStep" @click="proceedToSuppliers" class="px-10 py-5 bg-gray-900 dark:bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest">Next</button>
                        
                        <button v-else 
                            @click="submitRFQ" 
                            :disabled="rfqForm.selected_suppliers.length === 0 || rfqForm.processing" 
                            class="px-10 py-5 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="rfqForm.processing" class="w-4 h-4 animate-spin" />
                            {{ rfqForm.processing ? 'Sending...' : `Send RFQ (${rfqForm.selected_suppliers.length})` }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
                <div class="bg-white dark:bg-zinc-900 rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl border border-gray-100 dark:border-zinc-800 animate-in zoom-in duration-200">
                    <div class="flex flex-col items-center text-center">
                        <div :class="confirmConfig.type === 'confirm' ? 'bg-blue-50' : 'bg-red-50'" class="h-20 w-20 rounded-full flex items-center justify-center mb-6">
                            <Info v-if="confirmConfig.type === 'confirm'" class="w-10 h-10 text-blue-600" />
                            <AlertCircle v-else class="w-10 h-10 text-red-600" />
                        </div>
                        <h3 class="text-2xl font-black uppercase tracking-tighter mb-2 text-zinc-900 dark:text-zinc-100">{{ confirmConfig.title }}</h3>
                        <p class="text-sm font-bold text-gray-500 leading-relaxed">{{ confirmConfig.message }}</p>
                        <div class="mt-8 flex w-full gap-3">
                            <button v-if="confirmConfig.type === 'confirm'" @click="showConfirmModal = false" :disabled="rfqForm.processing" class="flex-1 px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl text-xs font-black uppercase hover:bg-gray-200 transition disabled:opacity-50">Cancel</button>
                            <button @click="confirmConfig.type === 'confirm' ? confirmConfig.action() : (showConfirmModal = false)" 
                                :disabled="rfqForm.processing"
                                :class="confirmConfig.type === 'confirm' ? 'bg-blue-600' : 'bg-gray-900'" 
                                class="flex-1 px-6 py-4 text-white rounded-2xl text-xs font-black uppercase shadow-lg disabled:opacity-50 flex justify-center items-center gap-2">
                                <Loader2 v-if="rfqForm.processing" class="w-4 h-4 animate-spin" />
                                {{ confirmConfig.type === 'confirm' ? (rfqForm.processing ? 'Dispatching...' : 'Confirm') : 'Got it' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes zoom-in {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.zoom-in { animation: zoom-in 0.2s ease-out; }
</style>