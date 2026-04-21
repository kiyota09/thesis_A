<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Package,
    Trash2,
    X,
    Edit2,
} from 'lucide-vue-next';

const props = defineProps({
    boms: { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    materials: { type: Array, default: () => [] },
    auth: Object,
});

// UI State
const searchQuery = ref('');
const showForm = ref(false);
const editingId = ref(null);
const processing = ref(false);

// Form for creating/editing recipe
const form = useForm({
    client_id: '',
    product_id: '',
    yarn_type: '',
    dye_color: '',
    weave_design: '',
    materials: [], // array of material IDs (no quantity)
});

// Material selection (no quantity)
const selectedMaterialId = ref('');

// Add material to recipe
const addMaterialToRecipe = () => {
    if (!selectedMaterialId.value) return;
    const matId = parseInt(selectedMaterialId.value);
    if (form.materials.includes(matId)) {
        alert('Material already added.');
        return;
    }
    form.materials.push(matId);
    selectedMaterialId.value = '';
};

// Remove material from recipe
const removeMaterialFromRecipe = (materialId) => {
    const index = form.materials.indexOf(materialId);
    if (index > -1) form.materials.splice(index, 1);
};

// Reset form
const resetForm = () => {
    form.client_id = '';
    form.product_id = '';
    form.yarn_type = '';
    form.dye_color = '';
    form.weave_design = '';
    form.materials = [];
    form.clearErrors();
    editingId.value = null;
};

// Open form to edit existing recipe
const openEdit = (recipe) => {
    editingId.value = recipe.id;
    form.client_id = recipe.client_id;
    form.product_id = recipe.product_id;
    form.yarn_type = recipe.yarn_type;
    form.dye_color = recipe.dye_color;
    form.weave_design = recipe.weave_design;
    // Convert materials object keys to array of IDs
    form.materials = Object.keys(recipe.materials || {}).map(id => parseInt(id));
    showForm.value = true;
};

// Submit form (create or update)
const submitForm = () => {
    if (!form.client_id || !form.product_id || !form.yarn_type || !form.dye_color || !form.weave_design) {
        alert('Please fill all required fields.');
        return;
    }
    if (form.materials.length === 0) {
        alert('Please add at least one material to the recipe.');
        return;
    }
    processing.value = true;
    
    // Convert materials array to object with quantity = 1 for storage compatibility
    const materialsObj = {};
    form.materials.forEach(id => { materialsObj[id] = 1; });
    
    const payload = {
        ...form.data(),
        materials: materialsObj,
    };
    
    const url = editingId.value ? route('inv.bom.update', editingId.value) : route('inv.bom.store');
    const method = editingId.value ? 'put' : 'post';
    
    router[method](url, payload, {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
            resetForm();
        },
        onFinish: () => (processing.value = false),
    });
};

// Delete recipe
const deleteRecipe = (id) => {
    if (!confirm('Delete this recipe? This action cannot be undone.')) return;
    router.delete(route('inv.bom.destroy', id), { preserveScroll: true });
};

// Filter recipes by search
const filteredRecipes = computed(() => {
    if (!searchQuery.value) return props.boms;
    const q = searchQuery.value.toLowerCase();
    return props.boms.filter(recipe =>
        recipe.client?.company_name?.toLowerCase().includes(q) ||
        recipe.product?.name?.toLowerCase().includes(q) ||
        recipe.yarn_type.toLowerCase().includes(q)
    );
});

// Helper: get client name by id
const getClientName = (id) => {
    const client = props.clients.find(c => c.id === id);
    return client ? client.company_name : '—';
};

// Helper: get product name by id
const getProductName = (id) => {
    const product = props.products.find(p => p.id === id);
    return product ? product.name : '—';
};

// Helper: get material name by id
const getMaterialName = (id) => {
    const mat = props.materials.find(m => m.id === id);
    return mat ? `${mat.mat_id} - ${mat.name}` : 'Unknown';
};
</script>

<template>
    <Head title="Recipes | Inventory" />
    <AuthenticatedLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Recipes</h1>
                        <p class="text-slate-500 text-sm mt-0.5">Client‑specific fabric formulas (yarn, dye, design, materials).</p>
                    </div>
                    <!-- "Add Recipe" button removed – recipes are created via ECO conversation -->
                </div>

                <!-- Search -->
                <div class="mb-6">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by client, product, or yarn type..."
                        class="w-full max-w-md px-4 py-2 text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                </div>

                <!-- Recipe List Table -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-800/60 border-b border-slate-100">
                                <tr>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Client</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Product</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Yarn Type</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Dye Color</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Weave Design</th>
                                    <th class="px-5 py-3.5 text-[10px] font-black uppercase tracking-wider">Materials</th>
                                    <th class="px-5 py-3.5 text-center w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <tr v-if="filteredRecipes.length === 0">
                                    <td colspan="7" class="px-5 py-16 text-center text-slate-400">
                                        <Package class="w-10 h-10 mx-auto mb-3 opacity-30" />
                                        <p class="font-bold">No recipes found.</p>
                                    </td>
                                </tr>
                                <tr v-for="recipe in filteredRecipes" :key="recipe.id" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="px-5 py-4 font-semibold">{{ recipe.client?.company_name || getClientName(recipe.client_id) }}</td>
                                    <td class="px-5 py-4">{{ recipe.product?.name || getProductName(recipe.product_id) }}</td>
                                    <td class="px-5 py-4"><span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">{{ recipe.yarn_type }}</span></td>
                                    <td class="px-5 py-4"><span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">{{ recipe.dye_color }}</span></td>
                                    <td class="px-5 py-4">{{ recipe.weave_design }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="(qty, matId) in recipe.materials" :key="matId" class="text-[10px] font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full">
                                                {{ getMaterialName(parseInt(matId)) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <button @click="openEdit(recipe)" class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit">
                                                <Edit2 class="w-4 h-4" />
                                            </button>
                                            <button @click="deleteRecipe(recipe.id)" class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition" title="Delete">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 border-t border-slate-100 text-xs text-slate-400">
                        Total: {{ filteredRecipes.length }} recipes
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Recipe Modal -->
        <Teleport to="body">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm overflow-y-auto" @click.self="showForm = false">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 w-full max-w-3xl my-8">
                    <div class="px-6 py-5 border-b flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-black">{{ editingId ? 'Edit Recipe' : 'Create Recipe' }}</h3>
                            <p class="text-xs text-slate-400">Client‑specific fabric formula.</p>
                        </div>
                        <button @click="showForm = false; resetForm()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600"><X class="w-4 h-4" /></button>
                    </div>
                    <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                        <!-- Client & Product -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Client *</label>
                                <select v-model="form.client_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border rounded-xl">
                                    <option value="">Select client...</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.company_name }}</option>
                                </select>
                                <div v-if="form.errors.client_id" class="text-red-500 text-xs mt-1">{{ form.errors.client_id }}</div>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider">Product *</label>
                                <select v-model="form.product_id" class="mt-1 w-full appearance-none pl-3 pr-8 py-2.5 text-sm bg-slate-50 dark:bg-slate-800 border rounded-xl">
                                    <option value="">Select product...</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }} ({{ product.sku }})</option>
                                </select>
                                <div v-if="form.errors.product_id" class="text-red-500 text-xs mt-1">{{ form.errors.product_id }}</div>
                            </div>
                        </div>

                        <!-- Yarn, Dye, Weave -->
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Yarn Type *</label>
                                <input v-model="form.yarn_type" type="text" placeholder="e.g. Cotton 20s" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Dye Color *</label>
                                <input v-model="form.dye_color" type="text" placeholder="e.g. Navy Blue" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                            <div>
                                <label class="text-[10px] font-black uppercase tracking-wider">Weave Design *</label>
                                <input v-model="form.weave_design" type="text" placeholder="e.g. Twill 2/1" class="mt-1 w-full px-3 py-2.5 text-sm bg-slate-50 border rounded-xl" />
                            </div>
                        </div>

                        <!-- Materials Selector (no quantity) -->
                        <div>
                            <label class="text-[10px] font-black uppercase tracking-wider mb-2 block">Raw Materials *</label>
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 border">
                                <!-- Add new material -->
                                <div class="flex gap-2 mb-4">
                                    <select v-model="selectedMaterialId" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-slate-900 border rounded-lg">
                                        <option value="">Select material...</option>
                                        <option v-for="mat in materials" :key="mat.id" :value="mat.id">{{ mat.mat_id }} – {{ mat.name }} ({{ mat.unit }})</option>
                                    </select>
                                    <button @click="addMaterialToRecipe" :disabled="!selectedMaterialId" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold disabled:opacity-50">Add</button>
                                </div>

                                <!-- List of added materials -->
                                <div v-if="form.materials.length === 0" class="text-center text-slate-400 py-4 text-sm">No materials added yet.</div>
                                <div v-for="matId in form.materials" :key="matId" class="flex items-center justify-between gap-3 py-2 border-b last:border-0">
                                    <div class="flex-1">
                                        <span class="font-mono text-sm">{{ getMaterialName(matId) }}</span>
                                    </div>
                                    <button @click="removeMaterialFromRecipe(matId)" class="text-red-500 hover:text-red-700">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t flex gap-3 bg-slate-50 dark:bg-slate-800">
                        <button @click="showForm = false; resetForm()" class="flex-1 py-2.5 text-sm font-bold rounded-xl border border-slate-200 text-slate-600">Cancel</button>
                        <button @click="submitForm" :disabled="processing" class="flex-1 py-2.5 text-sm font-bold rounded-xl bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50">
                            {{ processing ? 'Saving...' : (editingId ? 'Update Recipe' : 'Create Recipe') }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>