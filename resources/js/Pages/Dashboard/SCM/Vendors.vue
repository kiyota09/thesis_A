<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Building2, CheckCircle, XCircle, Eye, X, Plus, Trash2, AlertTriangle, ChevronRight } from 'lucide-vue-next';

const props = defineProps({ registrations: Array });

const selectedVendor = ref(null);
const showModal = ref(false);
const modalMode = ref('view');
const rejectionReason = ref('');
const requirementLines = ref([{ requirement_name: '', description: '', value: '' }]);

const openModal = (vendor, mode) => {
    selectedVendor.value = vendor;
    modalMode.value = mode;
    rejectionReason.value = '';
    requirementLines.value = vendor.requirements?.length
        ? vendor.requirements.map(r => ({ ...r }))
        : [{ requirement_name: '', description: '', value: '' }];
    showModal.value = true;
};

const submitApprove = () => {
    const validReqs = requirementLines.value.filter(r => r.requirement_name.trim());
    router.post(route('scm.vendors.approve', selectedVendor.value.id), { requirements: validReqs }, {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const submitReject = () => {
    router.post(route('scm.vendors.reject', selectedVendor.value.id), { rejection_reason: rejectionReason.value }, {
        preserveScroll: true,
        onSuccess: () => showModal.value = false,
    });
};

const addReqLine = () => requirementLines.value.push({ requirement_name: '', description: '', value: '' });
const removeReqLine = (i) => requirementLines.value.splice(i, 1);

const statusConfig = (s) => ({
    pending:  { classes: 'bg-amber-50 text-amber-700 ring-1 ring-amber-200',  dot: 'bg-amber-400' },
    approved: { classes: 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',    dot: 'bg-blue-500' },
    rejected: { classes: 'bg-slate-100 text-slate-500 ring-1 ring-slate-200', dot: 'bg-slate-400' },
}[s] || { classes: 'bg-slate-100 text-slate-500', dot: 'bg-slate-400' });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="SCM Vendors" />

        <div class="min-h-screen bg-white">
            <!-- Page Header -->
            <div class="border-b border-slate-100 bg-white sticky top-0 z-10">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0">
                            <Building2 class="w-4 h-4 text-white" />
                        </div>
                        <div>
                            <h1 class="text-base sm:text-lg font-semibold text-slate-900 leading-tight">Vendor Registrations</h1>
                            <p class="text-xs text-slate-400 hidden sm:block">Review and manage vendor applications</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

                <!-- Stats Strip -->
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <div class="bg-white border border-slate-100 rounded-2xl p-3 sm:p-4">
                        <p class="text-xs text-slate-400 mb-1">Total</p>
                        <p class="text-xl sm:text-2xl font-bold text-slate-900">{{ registrations?.length ?? 0 }}</p>
                    </div>
                    <div class="bg-amber-50 border border-amber-100 rounded-2xl p-3 sm:p-4">
                        <p class="text-xs text-amber-500 mb-1">Pending</p>
                        <p class="text-xl sm:text-2xl font-bold text-amber-600">{{ registrations?.filter(v => v.status === 'pending').length ?? 0 }}</p>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-2xl p-3 sm:p-4">
                        <p class="text-xs text-blue-500 mb-1">Approved</p>
                        <p class="text-xl sm:text-2xl font-bold text-blue-600">{{ registrations?.filter(v => v.status === 'approved').length ?? 0 }}</p>
                    </div>
                </div>

                <!-- Desktop Table -->
                <div class="hidden md:block bg-white border border-slate-100 rounded-2xl overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/60">
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Business</th>
                                <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Contact</th>
                                <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr
                                v-for="vendor in registrations"
                                :key="vendor.id"
                                class="group hover:bg-slate-50/50 transition-colors duration-150"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold text-slate-500">{{ vendor.business_name?.charAt(0)?.toUpperCase() }}</span>
                                        </div>
                                        <span class="font-medium text-slate-900">{{ vendor.business_name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-slate-700 text-sm">{{ vendor.representative_name }}</p>
                                    <p class="text-slate-400 text-xs mt-0.5">{{ vendor.email }}</p>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span :class="statusConfig(vendor.status).classes" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium">
                                        <span :class="statusConfig(vendor.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                        {{ vendor.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            @click="openModal(vendor, 'view')"
                                            class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all duration-150"
                                            title="View Details"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <template v-if="vendor.status === 'pending'">
                                            <button
                                                @click="openModal(vendor, 'approve')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition-colors duration-150"
                                            >
                                                <CheckCircle class="w-3.5 h-3.5" />
                                                Approve
                                            </button>
                                            <button
                                                @click="openModal(vendor, 'reject')"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-slate-50 text-slate-600 hover:text-slate-900 border border-slate-200 rounded-lg text-xs font-medium transition-all duration-150"
                                            >
                                                <XCircle class="w-3.5 h-3.5" />
                                                Reject
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!registrations?.length">
                                <td colspan="4" class="px-5 py-16 text-center">
                                    <Building2 class="w-10 h-10 text-slate-200 mx-auto mb-3" />
                                    <p class="text-slate-400 text-sm">No vendor registrations yet</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden space-y-3">
                    <div
                        v-for="vendor in registrations"
                        :key="vendor.id"
                        class="bg-white border border-slate-100 rounded-2xl p-4"
                    >
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm font-bold text-slate-500">{{ vendor.business_name?.charAt(0)?.toUpperCase() }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-900 text-sm truncate">{{ vendor.business_name }}</p>
                                    <p class="text-xs text-slate-400 truncate">{{ vendor.email }}</p>
                                </div>
                            </div>
                            <span :class="statusConfig(vendor.status).classes" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium flex-shrink-0">
                                <span :class="statusConfig(vendor.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                {{ vendor.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">{{ vendor.representative_name }}</p>
                        <div class="flex items-center gap-2 pt-3 border-t border-slate-50">
                            <button
                                @click="openModal(vendor, 'view')"
                                class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors"
                            >
                                <Eye class="w-3.5 h-3.5" /> View
                            </button>
                            <template v-if="vendor.status === 'pending'">
                                <button
                                    @click="openModal(vendor, 'approve')"
                                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-medium transition-colors"
                                >
                                    <CheckCircle class="w-3.5 h-3.5" /> Approve
                                </button>
                                <button
                                    @click="openModal(vendor, 'reject')"
                                    class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 border border-slate-200 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors"
                                >
                                    <XCircle class="w-3.5 h-3.5" /> Reject
                                </button>
                            </template>
                        </div>
                    </div>

                    <div v-if="!registrations?.length" class="bg-white border border-slate-100 rounded-2xl py-16 text-center">
                        <Building2 class="w-10 h-10 text-slate-200 mx-auto mb-3" />
                        <p class="text-slate-400 text-sm">No vendor registrations yet</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- ─── MODALS ─── -->
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
                    v-if="showModal"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                    @click.self="showModal = false"
                >
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showModal = false"></div>

                    <!-- Modal Panel -->
                    <Transition
                        enter-active-class="transition duration-250 ease-out"
                        enter-from-class="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                        enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                        leave-to-class="translate-y-4 sm:translate-y-0 sm:scale-95 opacity-0"
                    >
                        <div
                            v-if="showModal"
                            class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl shadow-2xl shadow-slate-900/10 max-h-[92vh] sm:max-h-[85vh] flex flex-col overflow-hidden"
                        >
                            <!-- Drag handle (mobile) -->
                            <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
                            </div>

                            <!-- Modal Header -->
                            <div class="flex items-center justify-between px-5 sm:px-6 py-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <!-- Icon per mode -->
                                    <div
                                        :class="{
                                            'bg-blue-600': modalMode === 'view',
                                            'bg-amber-400': modalMode === 'approve',
                                            'bg-slate-800': modalMode === 'reject',
                                        }"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0"
                                    >
                                        <Eye v-if="modalMode === 'view'" class="w-4 h-4 text-white" />
                                        <CheckCircle v-if="modalMode === 'approve'" class="w-4 h-4 text-white" />
                                        <XCircle v-if="modalMode === 'reject'" class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-semibold text-slate-900">
                                            {{ modalMode === 'approve' ? 'Approve Vendor' : modalMode === 'reject' ? 'Reject Vendor' : 'Vendor Details' }}
                                        </h3>
                                        <p class="text-xs text-slate-400 leading-tight">{{ selectedVendor?.business_name }}</p>
                                    </div>
                                </div>
                                <button
                                    @click="showModal = false"
                                    class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="flex-1 overflow-y-auto px-5 sm:px-6 py-5">

                                <!-- VIEW MODE -->
                                <div v-if="modalMode === 'view'" class="space-y-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div class="bg-slate-50 rounded-xl p-3.5">
                                            <p class="text-xs text-slate-400 mb-1">Business Name</p>
                                            <p class="text-sm font-medium text-slate-900">{{ selectedVendor?.business_name }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3.5">
                                            <p class="text-xs text-slate-400 mb-1">Representative</p>
                                            <p class="text-sm font-medium text-slate-900">{{ selectedVendor?.representative_name }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3.5">
                                            <p class="text-xs text-slate-400 mb-1">Email Address</p>
                                            <p class="text-sm font-medium text-slate-900 break-all">{{ selectedVendor?.email }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3.5">
                                            <p class="text-xs text-slate-400 mb-1">Phone Number</p>
                                            <p class="text-sm font-medium text-slate-900">{{ selectedVendor?.phone_number }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3.5 sm:col-span-2">
                                            <p class="text-xs text-slate-400 mb-1">Address</p>
                                            <p class="text-sm font-medium text-slate-900">{{ selectedVendor?.address }}</p>
                                        </div>
                                        <div class="bg-slate-50 rounded-xl p-3.5">
                                            <p class="text-xs text-slate-400 mb-1">Status</p>
                                            <span :class="statusConfig(selectedVendor?.status).classes" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium">
                                                <span :class="statusConfig(selectedVendor?.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                                {{ selectedVendor?.status }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Rejection reason alert -->
                                    <div v-if="selectedVendor?.rejection_reason" class="flex gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                        <AlertTriangle class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" />
                                        <div>
                                            <p class="text-xs font-semibold text-slate-600 mb-1">Rejection Reason</p>
                                            <p class="text-sm text-slate-700">{{ selectedVendor?.rejection_reason }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- REJECT MODE -->
                                <div v-if="modalMode === 'reject'" class="space-y-4">
                                    <div class="flex gap-3 bg-slate-50 border border-slate-200 rounded-xl p-4">
                                        <AlertTriangle class="w-4 h-4 text-slate-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-sm text-slate-600 leading-relaxed">
                                            This will permanently reject <span class="font-semibold text-slate-900">{{ selectedVendor?.business_name }}</span>. Please provide a clear reason.
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-700 mb-2">
                                            Reason for Rejection <span class="text-red-400">*</span>
                                        </label>
                                        <textarea
                                            v-model="rejectionReason"
                                            rows="4"
                                            placeholder="Describe why this vendor is being rejected..."
                                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent transition resize-none"
                                        ></textarea>
                                    </div>
                                </div>

                                <!-- APPROVE MODE -->
                                <div v-if="modalMode === 'approve'" class="space-y-4">
                                    <div class="flex gap-3 bg-amber-50 border border-amber-100 rounded-xl p-4">
                                        <CheckCircle class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" />
                                        <p class="text-sm text-amber-800 leading-relaxed">
                                            Approving <span class="font-semibold">{{ selectedVendor?.business_name }}</span>. Set compliance requirements below (optional).
                                        </p>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <label class="text-xs font-semibold text-slate-700">Compliance Requirements</label>
                                            <button
                                                @click="addReqLine"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors"
                                            >
                                                <Plus class="w-3.5 h-3.5" />
                                                Add row
                                            </button>
                                        </div>

                                        <div class="space-y-2.5">
                                            <div v-for="(req, i) in requirementLines" :key="i" class="flex gap-2 items-start">
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 flex-1">
                                                    <input
                                                        v-model="req.requirement_name"
                                                        placeholder="Requirement name"
                                                        class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    />
                                                    <input
                                                        v-model="req.description"
                                                        placeholder="Description"
                                                        class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    />
                                                    <input
                                                        v-model="req.value"
                                                        placeholder="Value"
                                                        class="border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                                    />
                                                </div>
                                                <button
                                                    @click="removeReqLine(i)"
                                                    class="w-8 h-8 flex-shrink-0 mt-0.5 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-400 hover:bg-red-50 transition-all"
                                                    :disabled="requirementLines.length === 1"
                                                >
                                                    <Trash2 class="w-3.5 h-3.5" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- Modal Footer -->
                            <div class="px-5 sm:px-6 py-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button
                                    @click="showModal = false"
                                    class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 hover:text-slate-900 border border-slate-200 hover:border-slate-300 rounded-xl transition-all duration-150"
                                >
                                    {{ modalMode === 'view' ? 'Close' : 'Cancel' }}
                                </button>

                                <!-- Reject confirm -->
                                <button
                                    v-if="modalMode === 'reject'"
                                    @click="submitReject"
                                    :disabled="!rejectionReason.trim()"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-slate-900 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white rounded-xl text-sm font-medium transition-all duration-150 hover:bg-slate-700"
                                >
                                    <XCircle class="w-4 h-4" />
                                    Confirm Rejection
                                </button>

                                <!-- Approve confirm -->
                                <button
                                    v-if="modalMode === 'approve'"
                                    @click="submitApprove"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium transition-all duration-150"
                                >
                                    <CheckCircle class="w-4 h-4" />
                                    Approve Vendor
                                </button>
                            </div>

                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>