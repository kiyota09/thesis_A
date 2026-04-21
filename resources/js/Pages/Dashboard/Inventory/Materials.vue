<script setup>
import { ref, computed, shallowRef } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package, Plus, X, Search, AlertTriangle, 
    ShoppingCart, Eye, History, Save,
    CheckCircle, Trash2, CheckCircle2, Send,
    ListChecks, Edit3, CheckSquare, Square
} from 'lucide-vue-next';

const props = defineProps({
    materials: { type: Array, default: () => [] },
    auth: Object,
});

// --- UI STATE ---
const searchQuery = ref('');
const isSelectionMode = ref(false);
const selectedIds = ref([]); 

const showAddModal = ref(false);
const showEditModal = ref(false);
const showBulkProcurementModal = ref(false);
const showConfirmModal = ref(false); 
const activeMaterial = ref(null);

// --- CONFIRMATION SYSTEM ---
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('confirm'); 
let pendingAction = null; 

const triggerConfirm = (title, message, type, actionFunc) => {
    confirmTitle.value = title;
    confirmMessage.value = message;
    confirmType.value = type;
    pendingAction = actionFunc;
    showConfirmModal.value = true;
};

const handleProceed = () => {
    if (pendingAction) {
        pendingAction();
        showConfirmModal.value = false;
        pendingAction = null;
    }
};

const closeConfirm = () => {
    showConfirmModal.value = false;
    pendingAction = null;
};

// --- FORMS ---
const addForm = useForm({
    name: '', category: 'Yarn', unit: 'Kg', reorder_point: 0,
});

const editForm = useForm({
    name: '', reorder_point: 0,
});

const bulkForm = useForm({
    items: [] 
});

// --- HELPERS ---
const filteredMaterials = computed(() => {
    if (!searchQuery.value) return props.materials;
    const q = searchQuery.value.toLowerCase();
    return props.materials.filter(mat =>
        mat.name.toLowerCase().includes(q) || 
        mat.mat_id.toLowerCase().includes(q) ||
        mat.category.toLowerCase().includes(q)
    );
});

const isAllSelected = computed(() => {
    return filteredMaterials.value.length > 0 && selectedIds.value.length === filteredMaterials.value.length;
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIds.value = [];
    } else {
        selectedIds.value = filteredMaterials.value.map(m => m.id);
    }
};

// --- ACTION METHODS ---

const toggleSelectionMode = () => {
    isSelectionMode.value = !isSelectionMode.value;
    if (!isSelectionMode.value) selectedIds.value = [];
};

const openBulkModal = () => {
    const selectedItems = props.materials.filter(m => selectedIds.value.includes(m.id));
    bulkForm.items = selectedItems.map(m => ({
        material_id: m.id, name: m.name, unit: m.unit, qty: 1, urgency: 'Medium', notes: ''
    }));
    showBulkProcurementModal.value = true;
};

// 1. CONFIRMATION & VALIDATION: BULK PROCURE
const submitBulkProcurement = () => {
    // Validation
    const hasInvalid = bulkForm.items.some(item => !item.qty || item.qty <= 0);
    if (hasInvalid) {
        triggerConfirm('Validation Error', 'All items must have a quantity greater than 0.', 'danger', () => {});
        return;
    }

    triggerConfirm(
        'Confirm Bulk Request', 
        `Submit procurement requests for ${bulkForm.items.length} items to SCM?`, 
        'confirm', 
        () => {
            bulkForm.post(route('inv.checker.bulk_procurement'), {
                onSuccess: () => {
                    showBulkProcurementModal.value = false;
                    isSelectionMode.value = false;
                    selectedIds.value = [];
                },
            });
        }
    );
};

// 2. CONFIRMATION & VALIDATION: EDIT MATERIAL
const openEditModal = (mat) => {
    activeMaterial.value = mat;
    editForm.name = mat.name;
    editForm.reorder_point = mat.reorder_point;
    showEditModal.value = true;
};

const submitUpdate = () => {
    if (!editForm.name) {
        triggerConfirm('Validation Error', 'Material name is required.', 'danger', () => {});
        return;
    }

    triggerConfirm(
        'Update Record',
        `Apply changes to ${activeMaterial.value.name}?`,
        'confirm',
        () => {
            editForm.patch(route('inv.materials.update', activeMaterial.value.id), {
                onSuccess: () => { showEditModal.value = false; }
            });
        }
    );
};

// 3. CONFIRMATION & VALIDATION: ADD MATERIAL
const addMaterial = () => {
    if (!addForm.name || addForm.reorder_point < 0) {
        triggerConfirm('Validation Error', 'Please provide a valid name and reorder point.', 'danger', () => {});
        return;
    }

    triggerConfirm(
        'Register Material',
        `Confirm registration of ${addForm.name}?`,
        'confirm',
        () => {
            addForm.post(route('inv.materials.store'), {
                onSuccess: () => { 
                    showAddModal.value = false; 
                    addForm.reset(); 
                }
            });
        }
    );
};

const stockStatus = (mat) => {
    if (mat.total_stock <= 0) return 'Out of Stock';
    if (mat.total_stock <= mat.reorder_point) return 'Low Stock';
    return 'In Stock';
};

const statusColor = (status) => {
    if (status === 'In Stock') return 'bg-emerald-100 text-emerald-700';
    if (status === 'Low Stock') return 'bg-amber-100 text-amber-700';
    return 'bg-red-100 text-red-600';
};
</script>

<template>
    <Head title="Inventory Management" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tighter italic">
                        Material <span class="text-blue-600 font-light">Inventory</span>
                    </h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Validated Control & Procurement</p>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="selectedIds.length > 0 ? openBulkModal() : toggleSelectionMode()" 
                        :disabled="bulkForm.processing"
                        :class="[isSelectionMode && selectedIds.length > 0 ? 'bg-blue-600 text-white shadow-blue-200' : isSelectionMode ? 'bg-rose-500 text-white shadow-rose-200' : 'bg-white text-slate-900 border border-slate-200']"
                        class="px-6 py-4 rounded-2xl font-black uppercase text-[11px] tracking-widest shadow-xl transition-all active:scale-95 disabled:opacity-50">
                        <component :is="selectedIds.length > 0 ? Edit3 : ListChecks" class="size-4 inline mr-2" />
                        {{ selectedIds.length > 0 ? `Procure (${selectedIds.length})` : isSelectionMode ? 'Cancel Selection' : 'Select Materials' }}
                    </button>

                    <button @click="showAddModal = true" :disabled="addForm.processing"
                        class="px-6 py-4 bg-slate-900 text-white rounded-2xl font-black uppercase text-[11px] tracking-widest hover:bg-blue-600 transition-all shadow-xl active:scale-95">
                        <Plus class="size-4 inline mr-2" /> New Material
                    </button>
                </div>
            </div>

            <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
                <div class="relative w-full max-w-md">
                    <Search class="absolute left-4 top-1/2 -translate-y-1/2 size-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search Master Records..." 
                        class="w-full pl-12 pr-4 py-4 bg-white border-none rounded-2xl shadow-sm font-bold text-sm focus:ring-2 focus:ring-blue-500/20" />
                </div>
                <button v-if="isSelectionMode" @click="toggleSelectAll" 
                    class="px-6 py-3 bg-white border border-slate-100 rounded-xl font-black text-[10px] uppercase tracking-widest text-slate-400 hover:text-blue-600 transition-all shadow-sm">
                    {{ isAllSelected ? 'Deselect All' : 'Select All Filtered' }}
                </button>
            </div>

            <div class="bg-white rounded-[2.5rem] border border-slate-100 overflow-hidden shadow-sm">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th v-if="isSelectionMode" class="p-6 w-12 text-center"></th>
                            <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Serial</th>
                            <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Description</th>
                            <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Category</th>
                            <th class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Status</th>
                            <th v-if="!isSelectionMode" class="p-6 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 font-bold uppercase">
                        <tr v-for="mat in filteredMaterials" :key="mat.id" 
                            :class="selectedIds.includes(mat.id) ? 'bg-blue-50/50' : 'hover:bg-slate-50/30'" class="transition-colors">
                            <td v-if="isSelectionMode" class="p-6 text-center">
                                <input type="checkbox" v-model="selectedIds" :value="mat.id" class="size-5 text-blue-600 rounded-lg border-slate-300 focus:ring-blue-500" />
                            </td>
                            <td class="p-6 font-mono text-xs text-blue-600 uppercase">{{ mat.mat_id }}</td>
                            <td class="p-6 text-slate-700 uppercase tracking-tight text-sm">{{ mat.name }}</td>
                            <td class="p-6">
                                <span class="px-3 py-1 bg-slate-100 text-slate-400 rounded-lg text-[10px] font-black">{{ mat.category }}</span>
                            </td>
                            <td class="p-6 text-center">
                                <span :class="statusColor(stockStatus(mat))" class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-tighter">
                                    {{ stockStatus(mat) }}
                                </span>
                            </td>
                            <td v-if="!isSelectionMode" class="p-6 text-center">
                                <button @click="openEditModal(mat)" class="p-3 bg-slate-50 text-slate-400 hover:text-blue-600 rounded-xl border border-slate-100 transition-all">
                                    <Edit3 class="size-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="showBulkProcurementModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-md">
            <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-5xl flex flex-col max-h-[90vh] overflow-hidden">
                <div class="p-8 border-b bg-blue-600 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <ShoppingCart class="size-6" />
                        <h3 class="text-xl font-black uppercase italic">Bulk Request <span class="text-blue-200 font-light">Builder</span></h3>
                    </div>
                    <button @click="showBulkProcurementModal = false" class="p-2 hover:bg-white/20 rounded-full transition-all"><X class="size-6" /></button>
                </div>
                <div class="p-8 overflow-y-auto space-y-4 bg-slate-50">
                    <div v-for="item in bulkForm.items" :key="item.material_id" class="bg-white p-6 rounded-[2rem] border border-slate-100 grid grid-cols-1 md:grid-cols-12 gap-6 items-end">
                        <div class="md:col-span-4"><label class="text-[9px] font-black text-slate-400 uppercase block mb-2">Material</label><p class="text-xs font-black uppercase truncate text-slate-800">{{ item.name }}</p></div>
                        <div class="md:col-span-2"><label class="text-[9px] font-black text-slate-400 uppercase block mb-2">Qty ({{ item.unit }})</label><input v-model.number="item.qty" type="number" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-xs font-bold border-none" /></div>
                        <div class="md:col-span-3"><label class="text-[9px] font-black text-slate-400 uppercase block mb-2">Priority</label><select v-model="item.urgency" class="w-full px-4 py-3 bg-slate-50 rounded-xl text-[10px] font-bold uppercase border-none"><option value="Low">Low</option><option value="Medium">Medium</option><option value="High">High</option></select></div>
                        <div class="md:col-span-3"><label class="text-[9px] font-black text-slate-400 uppercase block mb-2">Notes</label><input v-model="item.notes" type="text" placeholder="..." class="w-full px-4 py-3 bg-slate-50 rounded-xl text-xs font-bold border-none" /></div>
                    </div>
                </div>
                <div class="p-8 bg-white border-t">
                    <button @click="submitBulkProcurement" :disabled="bulkForm.processing" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs tracking-widest shadow-xl flex items-center justify-center gap-3">
                        <Send v-if="!bulkForm.processing" class="size-4" />
                        <span v-else class="animate-spin size-4 border-2 border-white border-t-transparent rounded-full"></span>
                        {{ bulkForm.processing ? 'Syncing...' : 'Send Requests to SCM' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-xl">
            <div class="bg-white rounded-[2.5rem] p-10 max-w-sm w-full shadow-2xl text-center">
                <div :class="confirmType === 'danger' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'" 
                    class="size-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <AlertTriangle v-if="confirmType === 'danger'" class="size-10" />
                    <CheckCircle2 v-else class="size-10" />
                </div>
                <h3 class="text-xl font-black uppercase text-slate-900 mb-2">{{ confirmTitle }}</h3>
                <p class="text-sm font-medium text-slate-500 mb-8">{{ confirmMessage }}</p>
                <div class="flex gap-3">
                    <button @click="closeConfirm" class="flex-1 py-4 rounded-xl bg-slate-100 text-slate-500 font-black uppercase text-[10px]">Cancel</button>
                    <button @click="handleProceed" 
                        :class="confirmType === 'danger' ? 'bg-red-600' : 'bg-blue-600'"
                        class="flex-1 py-4 rounded-xl text-white font-black uppercase text-[10px] shadow-lg">Confirm</button>
                </div>
            </div>
        </div>

        <div v-if="showAddModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-8 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xl font-black uppercase tracking-tight italic">Register <span class="text-blue-600 font-light">Material</span></h3>
                    <button @click="showAddModal = false" class="p-2 hover:bg-white rounded-full transition-all"><X class="size-5" /></button>
                </div>
                <div class="p-8 space-y-5">
                    <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 uppercase">Name</label><input v-model="addForm.name" type="text" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm" /></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 uppercase">Category</label><select v-model="addForm.category" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm uppercase"><option v-for="c in ['Yarn', 'Dye', 'Supplies']" :key="c" :value="c">{{ c }}</option></select></div>
                        <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 uppercase">Unit</label><select v-model="addForm.unit" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm uppercase"><option v-for="u in ['Kg', 'Rolls', 'Pcs']" :key="u" :value="u">{{ u }}</option></select></div>
                    </div>
                    <div><label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2 uppercase">Reorder Point</label><input v-model="addForm.reorder_point" type="number" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm" /></div>
                </div>
                <div class="p-8 pt-0">
                    <button @click="addMaterial" :disabled="addForm.processing" class="w-full py-5 bg-slate-900 text-white rounded-2xl font-black uppercase text-xs shadow-lg">
                        {{ addForm.processing ? 'Saving...' : 'Finalize Record' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="showEditModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/40 backdrop-blur-sm">
            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="p-8 border-b bg-slate-50 flex justify-between items-center">
                    <h3 class="text-xl font-black uppercase italic">Edit <span class="text-blue-600 font-light">Metadata</span></h3>
                    <button @click="showEditModal = false" class="p-2 hover:bg-white rounded-full transition-all"><X class="size-5" /></button>
                </div>
                <div class="p-8 space-y-5">
                    <div><label class="text-[10px] font-black text-slate-400 uppercase block mb-2 uppercase">Description</label><input v-model="editForm.name" type="text" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm" /></div>
                    <div><label class="text-[10px] font-black text-slate-400 uppercase block mb-2 uppercase">Reorder Point</label><input v-model.number="editForm.reorder_point" type="number" class="w-full px-5 py-4 bg-slate-100 rounded-2xl border-none font-bold text-sm" /></div>
                </div>
                <div class="p-8 pt-0">
                    <button @click="submitUpdate" :disabled="editForm.processing" class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black uppercase text-xs shadow-lg shadow-blue-200">
                        {{ editForm.processing ? 'Saving...' : 'Update Profile' }}
                    </button>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
* { font-style: normal !important; }
</style>