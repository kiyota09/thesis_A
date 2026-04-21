<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ShoppingCart, Eye, X, Loader2, CheckCircle, Clock, AlertCircle, Send, Check, Package, AlertTriangle, Info } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({ orders: Array });

const isLoading = ref(false);
const selectedOrder = ref(null);
const showDetailModal = ref(false);
const actionLoading = ref({});

// ── Inventory Check Result Modal ───────────────────────────────────────────
const inventoryCheckLoading = ref(false);
const showInventoryResultModal = ref(false);
const inventoryResult = ref(null);
const currentCheckOrder = ref(null);

// ── Confirmation modal state (for push to production) ──────────────────────
const confirmModal = ref({
    show: false,
    order: null,
    action: null,   // 'push'
    title: '',
    message: '',
});

// ── Toast notification state ───────────────────────────────────────────────
const toast = ref({ show: false, message: '', type: 'success' });

const showToast = (message, type = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => { toast.value.show = false; }, 4000);
};

// ── Open confirmation modal for push ───────────────────────────────────────
const openPushToProductionConfirm = (order) => {
    confirmModal.value = {
        show: true,
        order,
        action: 'push',
        title: 'Push to Manufacturing?',
        message: 'This will forward the order to the Manufacturing plant. Ensure inventory has been verified and is sufficient before proceeding.',
    };
};

// ── Execute push action after confirmation ─────────────────────────────────
const executeConfirmedAction = () => {
    const { order, action } = confirmModal.value;
    if (!order || action !== 'push') return;

    confirmModal.value.show = false;
    actionLoading.value[order.id] = action;

    const url = order.type === 'sales_order'
        ? route('scm.sales-order.push-to-production-sales', order.sales_order_id)
        : route('scm.sales-order.push-to-production', order.id);

    router.post(url, {}, {
        preserveScroll: true,
        onSuccess: () => showToast('Order successfully pushed to Manufacturing!'),
        onError: (errors) => showToast(errors?.error ?? 'Failed to push order to production.', 'error'),
        onFinish: () => delete actionLoading.value[order.id],
    });
};

// ── INSTANT INVENTORY CHECK ─────────────────────────────────────────────────
const checkInventoryInstant = async (order) => {
    currentCheckOrder.value = order;
    inventoryCheckLoading.value = true;
    showInventoryResultModal.value = true;
    inventoryResult.value = null;

    const type = order.type === 'sales_order' ? 'sales_order' : 'purchase_order';
    const id = order.type === 'sales_order' ? order.sales_order_id : order.id;
    const url = route('scm.sales-order.check-inventory-instant', { type, id });

    try {
        const response = await axios.get(url);
        inventoryResult.value = response.data;
    } catch (error) {
        console.error('Inventory check failed:', error);
        showToast('Failed to check inventory. Please try again.', 'error');
        showInventoryResultModal.value = false;
    } finally {
        inventoryCheckLoading.value = false;
    }
};

// ── Proceed with actual inventory check (status update) after viewing result ─
const proceedWithInventoryCheck = () => {
    if (!currentCheckOrder.value || !inventoryResult.value) return;

    const order = currentCheckOrder.value;
    const sufficient = inventoryResult.value.sufficient;
    actionLoading.value[order.id] = 'check';

    const url = order.type === 'sales_order'
        ? route('scm.sales-order.check-inventory-sales', order.sales_order_id)
        : route('scm.sales-order.check-inventory', order.id);

    router.post(url, { sufficient }, {
        preserveScroll: true,
        onSuccess: () => {
            showToast(sufficient ? 'Inventory OK – ready for production.' : 'Inventory check recorded – insufficient stock.');
            showInventoryResultModal.value = false;
            inventoryResult.value = null;
            currentCheckOrder.value = null;
        },
        onError: (errors) => showToast(errors?.error ?? 'Failed to update status.', 'error'),
        onFinish: () => delete actionLoading.value[order.id],
    });
};

// ── Open detail modal ───────────────────────────────────────────────────────
const openDetail = (order) => {
    selectedOrder.value = order;
    showDetailModal.value = true;
};

const formatCurrency = (val) => '₱' + Number(val).toLocaleString();
const formatDate = (date) => new Date(date).toLocaleDateString();

const getStatusBadge = (stage, sufficient) => {
    if (stage === 'in_production' || stage === 'man_production') return { text: 'In Production', class: 'bg-purple-50 text-purple-700 border-purple-200' };
    if (stage === 'inv_checked' && sufficient) return { text: 'Inventory OK', class: 'bg-green-50 text-green-700 border-green-200' };
    if (stage === 'inv_check') return { text: 'Inventory Check', class: 'bg-amber-50 text-amber-700 border-amber-200' };
    if (stage === 'pushed_to_scm') return { text: 'Received from ECO', class: 'bg-blue-50 text-blue-700 border-blue-200' };
    return { text: stage.replace(/_/g, ' '), class: 'bg-slate-50 text-slate-600 border-slate-200' };
};

// Helper for inventory result status
const getMaterialStatusBadge = (status) => {
    return status === 'sufficient'
        ? { class: 'bg-green-100 text-green-700', icon: CheckCircle }
        : { class: 'bg-red-100 text-red-600', icon: AlertTriangle };
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Sales Orders · Monti" />
        <div class="min-h-screen bg-[#F8FAFC] text-slate-900 font-sans pb-20">
            <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8 p-4 sm:p-6 lg:p-10">

                <!-- Header -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 bg-white rounded-2xl sm:rounded-[2rem] p-6 sm:p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center gap-5">
                        <div class="h-14 w-14 rounded-2xl bg-black flex items-center justify-center text-white shadow-lg shadow-black/10 flex-shrink-0">
                            <ShoppingCart class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 mb-1">Supply Chain</p>
                            <h1 class="text-3xl sm:text-4xl font-black text-black tracking-tight">
                                Sales <span class="text-slate-400 font-light">Orders</span>
                            </h1>
                        </div>
                    </div>
                    <div class="text-sm text-slate-500 bg-slate-50 px-5 py-3 rounded-2xl border border-slate-100">
                        {{ orders.length }} order(s) pending
                    </div>
                </div>

                <!-- Orders List -->
                <div v-if="orders.length === 0" class="bg-white rounded-2xl sm:rounded-[2rem] border border-dashed border-slate-200 p-12 text-center">
                    <div class="mx-auto h-16 w-16 bg-slate-50 text-slate-300 rounded-full flex items-center justify-center mb-4">
                        <ShoppingCart class="h-8 w-8" />
                    </div>
                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest">No sales orders pending</p>
                </div>

                <div v-else class="space-y-4">
                    <!-- Mobile Card -->
                    <div v-for="order in orders" :key="order.id" class="md:hidden bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-md">
                        <div class="p-5 space-y-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                            {{ order.type === 'sales_order' ? 'JO' : 'PO' }} Number
                                        </span>
                                        <span :class="getStatusBadge(order.stage, order.inv_check_sufficient).class"
                                              class="text-[9px] px-2 py-0.5 rounded-full border font-bold">
                                            {{ getStatusBadge(order.stage, order.inv_check_sufficient).text }}
                                        </span>
                                    </div>
                                    <p class="text-base font-black text-black tracking-tight">{{ order.po_number }}</p>
                                </div>
                                <span v-if="order.type === 'sales_order'"
                                      class="text-[9px] font-black bg-yellow-50 text-yellow-600 px-2 py-1 rounded-lg border border-yellow-200">
                                    Sales Order
                                </span>
                            </div>

                            <div class="space-y-2">
                                <div>
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Client</p>
                                    <p class="text-sm font-bold text-slate-800">{{ order.client_name }}</p>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Total Amount</p>
                                        <p class="text-base font-black text-blue-600">{{ formatCurrency(order.total_amount) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Date</p>
                                        <p class="text-xs font-mono text-slate-500">{{ formatDate(order.created_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="order.type === 'sales_order' && order.quantity" class="bg-slate-50 p-2 rounded-xl">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Quantity</p>
                                    <p class="text-sm font-bold text-black">{{ order.quantity }} kg</p>
                                </div>
                            </div>

                            <!-- Mobile Action Buttons -->
                            <div class="flex gap-2 pt-1">
                                <button @click="openDetail(order)"
                                        class="flex-1 py-3 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors flex justify-center items-center gap-1.5 shadow-sm">
                                    <Eye class="h-3.5 w-3.5" /> View
                                </button>
                                <button v-if="['pushed_to_scm', 'inv_check'].includes(order.stage)"
                                        @click="checkInventoryInstant(order)"
                                        :disabled="actionLoading[order.id]"
                                        class="flex-1 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition disabled:opacity-50 flex justify-center items-center gap-1.5 shadow-md">
                                    <Loader2 v-if="actionLoading[order.id] === 'check'" class="h-3.5 w-3.5 animate-spin" />
                                    Check Inv
                                </button>
                                <button v-if="order.stage === 'inv_checked' && order.inv_check_sufficient"
                                        @click="openPushToProductionConfirm(order)"
                                        :disabled="actionLoading[order.id]"
                                        class="flex-1 py-3 bg-black hover:bg-green-700 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition disabled:opacity-50 flex justify-center items-center gap-1.5 shadow-md">
                                    <Loader2 v-if="actionLoading[order.id] === 'push'" class="h-3.5 w-3.5 animate-spin" />
                                    Push
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block bg-white rounded-2xl sm:rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-[10px] font-black uppercase text-slate-400 tracking-widest border-b border-slate-100">
                                <tr>
                                    <th class="px-8 py-5">Order Info</th>
                                    <th class="px-6 py-5">Client</th>
                                    <th class="px-6 py-5 text-center">Amount</th>
                                    <th class="px-6 py-5 text-center">Status</th>
                                    <th class="px-8 py-5 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="order in orders" :key="order.id"
                                    class="hover:bg-slate-50/50 transition-colors">

                                    <!-- Order Info -->
                                    <td class="px-8 py-6">
                                        <div class="flex items-center gap-3">
                                            <div class="h-9 w-9 rounded-xl flex items-center justify-center font-black text-[10px] flex-shrink-0"
                                                :class="order.type === 'sales_order' ? 'bg-yellow-50 text-yellow-600' : 'bg-blue-50 text-blue-600'">
                                                {{ order.type === 'sales_order' ? 'JO' : 'PO' }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-black">{{ order.po_number }}</p>
                                                <p class="text-[10px] font-mono text-slate-400 mt-0.5">{{ formatDate(order.created_at) }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Client -->
                                    <td class="px-6 py-6">
                                        <p class="text-sm font-bold text-slate-800">{{ order.client_name }}</p>
                                        <p v-if="order.type === 'sales_order' && order.quantity"
                                           class="text-[10px] text-slate-400 mt-0.5">{{ order.quantity }} kg</p>
                                    </td>

                                    <!-- Amount -->
                                    <td class="px-6 py-6 text-center">
                                        <p class="text-sm font-black text-blue-600">{{ formatCurrency(order.total_amount) }}</p>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-6 text-center">
                                        <span :class="getStatusBadge(order.stage, order.inv_check_sufficient).class"
                                              class="inline-flex items-center px-3 py-1.5 rounded-full border text-[10px] font-black uppercase tracking-wider">
                                            {{ getStatusBadge(order.stage, order.inv_check_sufficient).text }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-8 py-6 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openDetail(order)"
                                                    class="p-3 text-slate-400 hover:text-black transition-colors rounded-xl hover:bg-white border border-transparent hover:border-slate-200 shadow-sm">
                                                <Eye class="h-4 w-4" />
                                            </button>
                                            <button v-if="['pushed_to_scm', 'inv_check'].includes(order.stage)"
                                                    @click="checkInventoryInstant(order)"
                                                    :disabled="actionLoading[order.id]"
                                                    class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition disabled:opacity-50 flex items-center gap-1.5 shadow-md">
                                                <Loader2 v-if="actionLoading[order.id] === 'check'" class="h-3 w-3 animate-spin" />
                                                Check Inv
                                            </button>
                                            <button v-if="order.stage === 'inv_checked' && order.inv_check_sufficient"
                                                    @click="openPushToProductionConfirm(order)"
                                                    :disabled="actionLoading[order.id]"
                                                    class="px-4 py-2.5 bg-black hover:bg-green-700 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition disabled:opacity-50 flex items-center gap-1.5 shadow-md">
                                                <Loader2 v-if="actionLoading[order.id] === 'push'" class="h-3 w-3 animate-spin" />
                                                Push
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notification -->
        <Teleport to="body">
            <Transition name="toast">
                <div v-if="toast.show"
                    class="fixed bottom-6 right-6 z-[200] flex items-center gap-3 bg-black text-white px-6 py-4 rounded-2xl shadow-2xl shadow-black/20 max-w-sm">
                    <div class="h-8 w-8 rounded-xl flex items-center justify-center flex-shrink-0"
                        :class="toast.type === 'error' ? 'bg-red-500' : 'bg-green-500'">
                        <Check v-if="toast.type !== 'error'" class="h-4 w-4" />
                        <X v-else class="h-4 w-4" />
                    </div>
                    <p class="text-sm font-bold leading-snug">{{ toast.message }}</p>
                </div>
            </Transition>
        </Teleport>

        <!-- Inventory Check Result Modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="showInventoryResultModal"
                    class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm"
                    @click.self="showInventoryResultModal = false">
                    <div class="bg-white w-full max-w-2xl rounded-[2rem] shadow-2xl overflow-hidden animate-zoom-in flex flex-col max-h-[85vh]">

                        <!-- Modal Header -->
                        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Inventory Check
                                    </p>
                                </div>
                                <h3 class="font-black text-xl text-black tracking-tight">
                                    {{ currentCheckOrder?.po_number }}
                                </h3>
                                <p class="text-sm text-slate-500 mt-1">{{ currentCheckOrder?.client_name }}</p>
                            </div>
                            <button @click="showInventoryResultModal = false"
                                class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-black rounded-2xl transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-8 overflow-y-auto bg-[#FAFAFA]">
                            <div v-if="inventoryCheckLoading" class="flex flex-col items-center justify-center py-12">
                                <Loader2 class="h-8 w-8 text-amber-500 animate-spin mb-4" />
                                <p class="text-slate-500 font-medium">Checking inventory...</p>
                            </div>

                            <div v-else-if="inventoryResult">
                                <!-- Overall Sufficient / Insufficient Banner -->
                                <div :class="[
                                    'flex items-center gap-3 p-4 rounded-2xl mb-6',
                                    inventoryResult.sufficient ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
                                ]">
                                    <component :is="inventoryResult.sufficient ? CheckCircle : AlertTriangle"
                                        :class="['h-6 w-6', inventoryResult.sufficient ? 'text-green-600' : 'text-red-600']" />
                                    <div>
                                        <p class="font-black text-lg" :class="inventoryResult.sufficient ? 'text-green-800' : 'text-red-800'">
                                            {{ inventoryResult.sufficient ? 'All materials available' : 'Insufficient stock' }}
                                        </p>
                                        <p class="text-sm text-slate-600">
                                            {{ inventoryResult.sufficient
                                                ? 'Required quantities are in stock.'
                                                : 'Some materials are below required quantities.' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Materials Table -->
                                <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase border-b border-slate-100">
                                            <tr>
                                                <th class="text-left p-4">Material</th>
                                                <th class="text-right p-4">Required</th>
                                                <th class="text-right p-4">Available</th>
                                                <th class="text-right p-4">Shortage</th>
                                                <th class="text-center p-4">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-50">
                                            <tr v-for="mat in inventoryResult.materials" :key="mat.material_id">
                                                <td class="p-4 font-medium text-slate-800">
                                                    {{ mat.material_name }}
                                                    <span class="text-xs text-slate-400 ml-1">({{ mat.unit }})</span>
                                                </td>
                                                <td class="p-4 text-right font-mono">{{ mat.required.toLocaleString() }}</td>
                                                <td class="p-4 text-right font-mono">{{ mat.available.toLocaleString() }}</td>
                                                <td class="p-4 text-right font-mono" :class="mat.shortage > 0 ? 'text-red-600 font-bold' : 'text-slate-400'">
                                                    {{ mat.shortage > 0 ? mat.shortage.toLocaleString() : '—' }}
                                                </td>
                                                <td class="p-4 text-center">
                                                    <span :class="['inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-black', getMaterialStatusBadge(mat.status).class]">
                                                        <component :is="getMaterialStatusBadge(mat.status).icon" class="w-3 h-3" />
                                                        {{ mat.status === 'sufficient' ? 'OK' : 'LOW' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- No materials case -->
                                <div v-if="inventoryResult.materials.length === 0" class="text-center py-8 text-slate-400">
                                    <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                    <p>No material requirements found for this order.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-6 border-t border-slate-100 bg-white flex gap-3">
                            <button @click="showInventoryResultModal = false"
                                class="flex-1 py-4 rounded-2xl bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                Close
                            </button>
                            <button v-if="!inventoryCheckLoading && inventoryResult"
                                @click="proceedWithInventoryCheck"
                                :disabled="actionLoading[currentCheckOrder?.id] === 'check'"
                                class="flex-1 py-4 rounded-2xl text-white text-xs font-black uppercase tracking-widest transition-colors shadow-lg flex items-center justify-center gap-2 disabled:opacity-50"
                                :class="inventoryResult.sufficient ? 'bg-green-600 hover:bg-green-700 shadow-green-600/20' : 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/20'">
                                <Loader2 v-if="actionLoading[currentCheckOrder?.id] === 'check'" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                Confirm Inventory Check
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Push to Production Confirmation Modal -->
        <Teleport to="body">
            <Transition name="modal-fade">
                <div v-if="confirmModal.show"
                    class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 bg-slate-900/50 backdrop-blur-sm"
                    @click.self="confirmModal.show = false">
                    <div class="bg-white w-full max-w-md rounded-[2rem] shadow-2xl overflow-hidden animate-zoom-in flex flex-col">

                        <!-- Modal Header -->
                        <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-start">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="h-2 w-2 rounded-full animate-pulse bg-green-500"></span>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                        Confirm Action
                                    </p>
                                </div>
                                <h3 class="font-black text-xl text-black tracking-tight">{{ confirmModal.title }}</h3>
                            </div>
                            <button @click="confirmModal.show = false"
                                class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-black rounded-2xl transition-colors">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-8 space-y-4 bg-[#FAFAFA]">
                            <p class="text-sm text-slate-500 font-medium leading-relaxed">{{ confirmModal.message }}</p>

                            <!-- Order Summary Card -->
                            <div v-if="confirmModal.order" class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Order Number</span>
                                    <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded-md border border-blue-100 font-mono">
                                        {{ confirmModal.order.po_number }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Client</span>
                                    <span class="text-sm font-black text-black">{{ confirmModal.order.client_name }}</span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Amount</span>
                                    <span class="text-sm font-black text-blue-600">{{ formatCurrency(confirmModal.order.total_amount) }}</span>
                                </div>
                                <div v-if="confirmModal.order.quantity" class="flex justify-between items-center border-t border-slate-100 pt-3">
                                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Volume</span>
                                    <span class="text-sm font-black text-black">{{ confirmModal.order.quantity }} kg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-6 border-t border-slate-100 bg-white flex gap-3">
                            <button @click="confirmModal.show = false"
                                class="flex-1 py-4 rounded-2xl bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                                Cancel
                            </button>
                            <button @click="executeConfirmedAction"
                                :disabled="actionLoading[confirmModal.order?.id]"
                                class="flex-1 py-4 rounded-2xl text-white text-xs font-black uppercase tracking-widest transition-colors shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 bg-black hover:bg-green-700 shadow-black/10">
                                <Loader2 v-if="actionLoading[confirmModal.order?.id]" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                Push to Manufacturing
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Detail Modal -->
        <Teleport to="body">
            <div v-if="showDetailModal"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm"
                @click.self="showDetailModal = false">
                <div class="bg-white w-full max-w-xl rounded-2xl sm:rounded-[2rem] shadow-2xl overflow-hidden animate-zoom-in flex flex-col max-h-[90vh]">

                    <!-- Modal Header -->
                    <div class="px-6 sm:px-8 py-6 border-b border-slate-100 flex justify-between items-start bg-white">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="h-2 w-2 rounded-full bg-blue-600"></span>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                    {{ selectedOrder?.type === 'sales_order' ? 'Job Order' : 'Purchase Order' }}
                                </p>
                            </div>
                            <h3 class="font-black text-xl sm:text-2xl text-black tracking-tight">{{ selectedOrder?.po_number }}</h3>
                        </div>
                        <button @click="showDetailModal = false"
                                class="p-2.5 bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-black rounded-xl transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto bg-[#FAFAFA]">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="p-5 bg-white border border-slate-200 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Client</p>
                                <p class="font-black text-black text-sm">{{ selectedOrder?.client_name }}</p>
                            </div>
                            <div class="p-5 bg-white border border-slate-200 rounded-2xl">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Amount</p>
                                <p class="font-black text-blue-600 text-xl">{{ formatCurrency(selectedOrder?.total_amount) }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Current Status:</span>
                            <span v-if="selectedOrder"
                                  :class="getStatusBadge(selectedOrder.stage, selectedOrder.inv_check_sufficient).class"
                                  class="px-3 py-1.5 rounded-full border text-[10px] font-black uppercase tracking-wider">
                                {{ getStatusBadge(selectedOrder.stage, selectedOrder.inv_check_sufficient).text }}
                            </span>
                        </div>

                        <!-- Sales Order Extra Fields -->
                        <div v-if="selectedOrder?.type === 'sales_order'" class="space-y-3">
                            <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] flex items-center gap-2">
                                <span class="h-px bg-slate-200 flex-1"></span> Product Specifications <span class="h-px bg-slate-200 flex-1"></span>
                            </h4>
                            <div class="bg-white border border-slate-200 rounded-2xl p-5 space-y-3">
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span class="text-[10px] font-black text-slate-400">Color</span>
                                    <span class="text-sm font-bold text-black uppercase">{{ selectedOrder?.color || '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center border-b border-slate-100 pb-2">
                                    <span class="text-[10px] font-black text-slate-400">Yarn Type</span>
                                    <span class="text-sm font-bold text-black">{{ selectedOrder?.yarn_type || '—' }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-black text-slate-400">Quantity (kg)</span>
                                    <span class="text-lg font-black text-blue-600">{{ selectedOrder?.quantity || 0 }} kg</span>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table (for purchase orders) -->
                        <div v-if="selectedOrder?.items && selectedOrder.items.length" class="space-y-3">
                            <h4 class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em] flex items-center gap-2">
                                <span class="h-px bg-slate-200 flex-1"></span> Order Items <span class="h-px bg-slate-200 flex-1"></span>
                            </h4>
                            <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-[9px] font-black text-slate-400 uppercase border-b border-slate-100">
                                        <tr>
                                            <th class="text-left p-3">Product</th>
                                            <th class="text-right p-3">Qty</th>
                                            <th class="text-right p-3">Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="item in selectedOrder.items" :key="item.product_name">
                                            <td class="p-3 text-sm font-medium text-slate-700">{{ item.product_name || 'Custom Product' }}</td>
                                            <td class="p-3 text-right text-sm font-mono">{{ item.quantity }} {{ selectedOrder.type === 'sales_order' ? 'kg' : '' }}</td>
                                            <td class="p-3 text-right text-sm font-bold text-blue-600">{{ formatCurrency(item.unit_price) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 border-t border-slate-100 bg-white flex gap-3">
                        <button @click="showDetailModal = false"
                                class="flex-1 py-4 rounded-xl bg-slate-100 text-slate-700 text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition">
                            Close
                        </button>
                        <!-- Quick action from modal -->
                        <button v-if="selectedOrder && ['pushed_to_scm', 'inv_check'].includes(selectedOrder.stage)"
                                @click="() => { showDetailModal = false; checkInventoryInstant(selectedOrder); }"
                                class="flex-1 py-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-black uppercase tracking-widest transition shadow-lg shadow-amber-500/20 flex items-center justify-center gap-2">
                            Check Inventory
                        </button>
                        <button v-if="selectedOrder && selectedOrder.stage === 'inv_checked' && selectedOrder.inv_check_sufficient"
                                @click="() => { showDetailModal = false; openPushToProductionConfirm(selectedOrder); }"
                                class="flex-1 py-4 rounded-xl bg-black hover:bg-green-700 text-white text-xs font-black uppercase tracking-widest transition shadow-lg shadow-black/10 flex items-center justify-center gap-2">
                            Push to Manufacturing
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-zoom-in {
    animation: zoomIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Toast transition */
.toast-enter-active,
.toast-leave-active {
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(1rem) scale(0.95);
}

/* Confirmation modal fade */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>