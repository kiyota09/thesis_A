<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, ClipboardList, ArrowRightCircle, X, AlertTriangle, ChevronDown, ChevronUp } from 'lucide-vue-next';

const props = defineProps({
    orders: Array,
});

// ── Custom confirm modal (replaces native confirm()) ──
const showConfirmModal  = ref(false);
const pendingOrder      = ref(null);
const expandedOrder     = ref(null); // for mobile item accordion
const isSubmitting      = ref(false); // prevent double click

// Show flash message if exists (from Laravel redirect)
const page = usePage();
if (page.props.flash?.message) {
    alert(page.props.flash.message);
}

const openConfirm = (order) => {
    pendingOrder.value = order;
    showConfirmModal.value = true;
};

const closeConfirm = () => {
    if (isSubmitting.value) return;
    showConfirmModal.value = false;
    pendingOrder.value = null;
};

const confirmForward = () => {
    if (!pendingOrder.value || isSubmitting.value) return;
    isSubmitting.value = true;

    router.post(route('man.manager.forward-to-checker', pendingOrder.value.id), {
        type: pendingOrder.value.type,
    }, {
        onSuccess: () => {
            alert(`Order ${pendingOrder.value.order_number} has been forwarded to Quality Checker.`);
            closeConfirm();
            // Optional: remove order from local list to avoid double send before redirect
            // But redirect will reload the page anyway.
        },
        onError: (errors) => {
            alert('Failed to forward order. Please try again.');
            console.error(errors);
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const toggleItems = (id) => {
    expandedOrder.value = expandedOrder.value === id ? null : id;
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric',
});

const orderLabel = (type) => type === 'sales_order' ? 'Job Order' : 'Purchase Order';
const orderBadge = (type) => type === 'sales_order'
    ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200'
    : 'bg-blue-50 text-blue-700 ring-1 ring-blue-200';
const orderDot = (type) => type === 'sales_order' ? 'bg-amber-400' : 'bg-blue-500';
</script>

<template>
    <AuthenticatedLayout>
        <div class="min-h-screen bg-white">

            <!-- ── Page Header ── -->
            <div class="border-b border-slate-100 bg-white sticky top-0 z-10">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0">
                            <ClipboardList class="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg font-semibold text-slate-900 leading-tight">
                                Received Orders
                            </h1>
                            <p class="text-xs text-slate-400 hidden sm:block">
                                Orders awaiting production
                            </p>
                        </div>
                        <!-- Count pill -->
                        <span v-if="orders?.length" class="ml-auto text-xs font-semibold text-blue-700 bg-blue-50 ring-1 ring-blue-200 px-2.5 py-1 rounded-full">
                            {{ orders.length }} order{{ orders.length !== 1 ? 's' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- ── Main Content ── -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

                <!-- Empty State -->
                <div v-if="!orders?.length" class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
                        <Package class="w-7 h-7 text-slate-300" />
                    </div>
                    <p class="text-sm font-medium text-slate-500">No orders waiting for production</p>
                    <p class="text-xs text-slate-400 mt-1">New orders will appear here once received.</p>
                </div>

                <!-- ── Desktop Table ── -->
                <div v-else class="hidden md:block bg-white border border-slate-100 rounded-2xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Type</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Order #</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Client</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Items</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-400 uppercase tracking-wider">Qty</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Received</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="order in orders"
                                :key="order.id + order.type"
                                class="hover:bg-slate-50/50 transition-colors duration-150"
                            >
                                <!-- Type badge -->
                                <td class="px-5 py-4">
                                    <span :class="orderBadge(order.type)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium">
                                        <span :class="orderDot(order.type)" class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                        {{ orderLabel(order.type) }}
                                    </span>
                                </td>
                                <!-- Order number -->
                                <td class="px-5 py-4">
                                    <span class="font-mono text-xs font-bold text-slate-800 bg-slate-100 px-2 py-1 rounded-lg">
                                        {{ order.order_number }}
                                    </span>
                                </td>
                                <!-- Client -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-[10px] font-bold text-slate-500">
                                                {{ order.client_name?.charAt(0)?.toUpperCase() }}
                                            </span>
                                        </div>
                                        <span class="text-slate-800 font-medium text-sm">{{ order.client_name }}</span>
                                    </div>
                                </td>
                                <!-- Items list -->
                                <td class="px-5 py-4">
                                    <ul class="space-y-0.5">
                                        <li
                                            v-for="(item, idx) in order.items"
                                            :key="idx"
                                            class="text-xs text-slate-600 flex items-center gap-1.5"
                                        >
                                            <span class="w-1 h-1 rounded-full bg-slate-300 flex-shrink-0"></span>
                                            {{ item.product_name }}
                                            <span class="text-slate-400">({{ item.quantity }})</span>
                                        </li>
                                    </ul>
                                </td>
                                <!-- Total qty -->
                                <td class="px-5 py-4 text-center">
                                    <span class="text-sm font-bold text-slate-900">{{ order.total_quantity }}</span>
                                </td>
                                <!-- Date -->
                                <td class="px-5 py-4">
                                    <span class="text-xs text-slate-400">{{ formatDate(order.created_at) }}</span>
                                </td>
                                <!-- Action -->
                                <td class="px-5 py-4 text-right">
                                    <button
                                        @click="openConfirm(order)"
                                        :disabled="isSubmitting"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <ArrowRightCircle class="w-3.5 h-3.5" />
                                        Start Production
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── Mobile Cards ── -->
                <div v-if="orders?.length" class="md:hidden space-y-3">
                    <div
                        v-for="order in orders"
                        :key="order.id + order.type"
                        class="bg-white border border-slate-100 rounded-2xl overflow-hidden"
                    >
                        <!-- Card top -->
                        <div class="p-4 space-y-3">
                            <!-- Header row -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-slate-500">
                                            {{ order.client_name?.charAt(0)?.toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-slate-900 truncate">{{ order.client_name }}</p>
                                        <span class="font-mono text-[10px] font-bold text-slate-400">{{ order.order_number }}</span>
                                    </div>
                                </div>
                                <span :class="orderBadge(order.type)" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0">
                                    <span :class="orderDot(order.type)" class="w-1.5 h-1.5 rounded-full"></span>
                                    {{ orderLabel(order.type) }}
                                </span>
                            </div>

                            <!-- Stats row -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="bg-slate-50 rounded-xl p-2.5">
                                    <p class="text-[10px] text-slate-400 mb-0.5">Total Qty</p>
                                    <p class="text-sm font-bold text-slate-900">{{ order.total_quantity }}</p>
                                </div>
                                <div class="bg-slate-50 rounded-xl p-2.5">
                                    <p class="text-[10px] text-slate-400 mb-0.5">Received</p>
                                    <p class="text-xs font-medium text-slate-700">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>

                            <!-- Items accordion -->
                            <div>
                                <button
                                    @click="toggleItems(order.id)"
                                    class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors"
                                >
                                    <ChevronDown v-if="expandedOrder !== order.id" class="w-3.5 h-3.5" />
                                    <ChevronUp v-else class="w-3.5 h-3.5" />
                                    {{ order.items?.length }} item{{ order.items?.length !== 1 ? 's' : '' }}
                                </button>
                                <div v-if="expandedOrder === order.id" class="mt-2 space-y-1.5 pl-3 border-l-2 border-slate-100">
                                    <div
                                        v-for="(item, idx) in order.items"
                                        :key="idx"
                                        class="flex items-center justify-between text-xs"
                                    >
                                        <span class="text-slate-700">{{ item.product_name }}</span>
                                        <span class="text-slate-400 font-medium">× {{ item.quantity }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card footer action -->
                        <div class="px-4 pb-4">
                            <button
                                @click="openConfirm(order)"
                                :disabled="isSubmitting"
                                class="w-full inline-flex items-center justify-center gap-2 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <ArrowRightCircle class="w-4 h-4" />
                                Start Production
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── Confirm Modal ── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showConfirmModal"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeConfirm"></div>

                    <Transition
                        enter-active-class="transition duration-200 ease-out"
                        enter-from-class="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                        enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-to-class="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                    >
                        <div
                            v-if="showConfirmModal"
                            class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl shadow-slate-900/10 overflow-hidden flex flex-col"
                        >
                            <!-- Drag handle (mobile only) -->
                            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
                            </div>

                            <!-- Header -->
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-amber-400 flex items-center justify-center flex-shrink-0">
                                        <ArrowRightCircle class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Start Production</h3>
                                        <p class="text-xs text-slate-400">Forward order to Quality Checker</p>
                                    </div>
                                </div>
                                <button
                                    @click="closeConfirm"
                                    :disabled="isSubmitting"
                                    class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="px-5 py-5 space-y-4">
                                <!-- Confirmation message -->
                                <div class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 leading-relaxed">
                                    Forward
                                    <span class="font-semibold text-slate-900">
                                        {{ orderLabel(pendingOrder?.type) }}
                                    </span>
                                    <span class="font-mono font-bold text-blue-600 mx-1">{{ pendingOrder?.order_number }}</span>
                                    to <span class="font-semibold text-slate-900">Checker Quality</span>?
                                </div>

                                <!-- Order summary -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-slate-50 rounded-xl p-3">
                                        <p class="text-[10px] text-slate-400 mb-0.5">Client</p>
                                        <p class="text-xs font-semibold text-slate-800 truncate">{{ pendingOrder?.client_name }}</p>
                                    </div>
                                    <div class="bg-slate-50 rounded-xl p-3">
                                        <p class="text-[10px] text-slate-400 mb-0.5">Total Qty</p>
                                        <p class="text-xs font-semibold text-slate-800">{{ pendingOrder?.total_quantity }}</p>
                                    </div>
                                </div>

                                <!-- Alert note -->
                                <div class="flex gap-2.5 items-start bg-amber-50 border border-amber-100 rounded-xl p-3.5">
                                    <AlertTriangle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                    <p class="text-xs text-amber-800 leading-relaxed">
                                        This action will move the order out of the production queue and cannot be undone.
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="px-5 py-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button
                                    @click="closeConfirm"
                                    :disabled="isSubmitting"
                                    class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 hover:border-slate-300 rounded-xl hover:bg-slate-50 transition-all"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="confirmForward"
                                    :disabled="isSubmitting"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition-all active:scale-95 disabled:opacity-50"
                                >
                                    <ArrowRightCircle class="w-4 h-4" />
                                    Confirm & Forward
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>