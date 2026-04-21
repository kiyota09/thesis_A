<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { XCircle, ArrowLeft, RefreshCw, Archive, Shirt, Package } from 'lucide-vue-next';

const props = defineProps({
    rejectedFabrics: Array,  // { id, code, yarn_type, weight, rejection_reason, rejection_action, rejected_at, sales_order }
    rejectedForms: Array,    // existing structure
    warehouses: Array,       // { id, name, location }
});

const activeTab = ref('fabrics'); // 'fabrics' or 'forms'

// Modal for total reject
const showWarehouseModal = ref(false);
const selectedFabric = ref(null);
const warehouseForm = ref({ warehouse_id: '' });

const recolorFabric = (fabricId) => {
    router.post(route('man.manager.rejected.recolor', fabricId), {}, {
        preserveScroll: true,
    });
};

const openWarehouseModal = (fabric) => {
    selectedFabric.value = fabric;
    warehouseForm.value.warehouse_id = '';
    showWarehouseModal.value = true;
};

const submitTotalReject = () => {
    if (!warehouseForm.value.warehouse_id) {
        alert('Please select a warehouse.');
        return;
    }
    router.post(route('man.manager.rejected.total-reject', selectedFabric.value.id), warehouseForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showWarehouseModal.value = false;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Rejected Items" />
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-3">
                    <Link :href="route('man.manager.production')"
                        class="flex items-center gap-1 text-gray-600 hover:text-gray-900">
                        <ArrowLeft class="w-4 h-4" /> Back to Production
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Rejected Items</h1>
                </div>
                <div class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-bold">
                    {{ (rejectedFabrics?.length || 0) + (rejectedForms?.length || 0) }} items rejected
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 dark:border-zinc-700 mb-6">
                <nav class="-mb-px flex space-x-6">
                    <button
                        @click="activeTab = 'fabrics'"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2',
                            activeTab === 'fabrics'
                                ? 'border-red-500 text-red-600 dark:text-red-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                    >
                        <Shirt class="w-4 h-4" />
                        Fabrics
                        <span class="ml-1 text-xs">({{ rejectedFabrics?.length || 0 }})</span>
                    </button>
                    <button
                        @click="activeTab = 'forms'"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm flex items-center gap-2',
                            activeTab === 'forms'
                                ? 'border-red-500 text-red-600 dark:text-red-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                        ]"
                    >
                        <Package class="w-4 h-4" />
                        Forms
                        <span class="ml-1 text-xs">({{ rejectedForms?.length || 0 }})</span>
                    </button>
                </nav>
            </div>

            <!-- Fabrics Tab -->
            <div v-if="activeTab === 'fabrics'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Yarn Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">JO / Color</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rejection Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                <tr v-for="fabric in rejectedFabrics" :key="fabric.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30">
                                    <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ fabric.code }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ fabric.yarn_type }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ fabric.weight }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                                        <span v-if="fabric.sales_order">
                                            {{ fabric.sales_order.jo_number }} / {{ fabric.sales_order.color }}
                                        </span>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 max-w-xs truncate" :title="fabric.rejection_reason">
                                        {{ fabric.rejection_reason || 'No reason provided' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex gap-2">
                                            <button
                                                @click="recolorFabric(fabric.id)"
                                                class="inline-flex items-center gap-1 px-3 py-1 bg-blue-100 text-blue-700 rounded-md text-xs font-medium hover:bg-blue-200"
                                                title="Send back for recoloring"
                                            >
                                                <RefreshCw class="w-3.5 h-3.5" /> Recolor
                                            </button>
                                            <button
                                                @click="openWarehouseModal(fabric)"
                                                class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 rounded-md text-xs font-medium hover:bg-gray-200"
                                                title="Totally reject and send to warehouse"
                                            >
                                                <Archive class="w-3.5 h-3.5" /> Total Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!rejectedFabrics || rejectedFabrics.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <Shirt class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p>No rejected fabrics found.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Forms Tab -->
            <div v-if="activeTab === 'forms'">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-zinc-800/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rejected By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                <tr v-for="form in rejectedForms" :key="form.id" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30">
                                    <td class="px-6 py-4 font-mono text-sm text-gray-900 dark:text-white">{{ form.code }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ form.product_name }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ form.quantity }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ form.rejected_by }}</td>
                                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300 max-w-md truncate" :title="form.reason">{{ form.reason }}</td>
                                    <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-sm">{{ new Date(form.rejected_at).toLocaleString() }}</td>
                                </tr>
                                <tr v-if="!rejectedForms || rejectedForms.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <Package class="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                        <p>No rejected forms found.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Warehouse Selection Modal for Total Reject -->
        <Teleport to="body">
            <div v-if="showWarehouseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showWarehouseModal = false">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-xl">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4 dark:text-white">Send to Warehouse</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Select destination warehouse for rejected fabric <strong>{{ selectedFabric?.code }}</strong>.
                        </p>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1 dark:text-gray-300">Warehouse</label>
                            <select v-model="warehouseForm.warehouse_id" class="w-full border rounded-lg p-2 dark:bg-zinc-800 dark:border-zinc-700">
                                <option value="">Select Warehouse</option>
                                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                                    {{ wh.name }} ({{ wh.location }})
                                </option>
                            </select>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showWarehouseModal = false" class="px-4 py-2 border rounded-lg dark:border-zinc-700">Cancel</button>
                            <button @click="submitTotalReject" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>