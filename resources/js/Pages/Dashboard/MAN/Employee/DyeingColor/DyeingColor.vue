<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Droplet, X, Plus, Trash2, AlertTriangle, CheckCircle2,
    FlaskConical, Palette, Layers, Package, Blend,
} from 'lucide-vue-next';

const props = defineProps({
    fabrics:  Array,   // status='dyeing', includes linked JO recipe
    machines: Array,
    dyes:     Array,   // ManufacturingInventoryItem where dept=dyeing, category=Dye
});

const page = usePage();

// ─── Flash ────────────────────────────────────────────────────────────────────
const showFlash    = ref(!!page.props.flash?.message);
const flashMessage = ref(page.props.flash?.message ?? '');
const flashIsError = ref(false);

watch(() => page.props.flash?.message, (val) => {
    if (val) { flashMessage.value = val; flashIsError.value = false; showFlash.value = true; }
});
watch(() => page.props.errors, (errs) => {
    const first = Object.values(errs ?? {})[0];
    if (first) { flashMessage.value = first; flashIsError.value = true; showFlash.value = true; }
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getDyeById   = (id) => props.dyes.find(d => d.id == id) ?? null;
const fmtNum       = (n)  => parseFloat(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ─── Recipe Modal ─────────────────────────────────────────────────────────────
const recipeModal      = ref(false);
const recipeModalFabric = ref(null);
const openRecipeModal  = (fabric) => { recipeModalFabric.value = fabric; recipeModal.value = true; };
const closeRecipeModal = () => { recipeModal.value = false; recipeModalFabric.value = null; };

// ─── Dye Modal ────────────────────────────────────────────────────────────────
const dyeModal      = ref(false);
const currentFabric = ref(null);

// Each dye row: { inventory_item_id, quantity_used }
const dyeRows = ref([]);

const addDyeRow    = () => dyeRows.value.push({ inventory_item_id: '', quantity_used: '' });
const removeDyeRow = (i) => dyeRows.value.splice(i, 1);

const onDyeItemChange = (row) => {
    row.quantity_used = '';
};

const form = useForm({
    fabric_id:  null,
    machine_id: '',
    remarks:    '',
    dyes_used:  [],
});

const openDyeModal = (fabric) => {
    currentFabric.value = fabric;
    form.fabric_id  = fabric.id;
    form.machine_id = '';
    form.remarks    = '';
    dyeRows.value   = [{ inventory_item_id: '', quantity_used: '' }];   // start with one empty row
    dyeModal.value  = true;
};
const closeDyeModal = () => {
    dyeModal.value  = false;
    currentFabric.value = null;
    dyeRows.value   = [];
    form.reset();
};

// ─── Dye Row Validation ───────────────────────────────────────────────────────
const dyeRowErrors = computed(() =>
    dyeRows.value.map(row => {
        if (!row.inventory_item_id || !row.quantity_used) return null;
        const dye = getDyeById(row.inventory_item_id);
        if (!dye) return null;
        return parseFloat(row.quantity_used) > parseFloat(dye.remaining_quantity)
            ? `Only ${fmtNum(dye.remaining_quantity)} ${dye.unit} available`
            : null;
    })
);

const hasInvalidDyeRows = computed(() => dyeRowErrors.value.some(e => e !== null));

const totalChemicalsKg = computed(() => {
    return dyeRows.value.reduce((sum, row) => {
        const q = parseFloat(row.quantity_used);
        return sum + (isNaN(q) ? 0 : q);
    }, 0);
});

const canSubmit = computed(() =>
    form.machine_id &&
    dyeRows.value.length > 0 &&
    dyeRows.value.every(r => r.inventory_item_id && parseFloat(r.quantity_used) > 0) &&
    !hasInvalidDyeRows.value &&
    !form.processing
);

// ─── Submit ───────────────────────────────────────────────────────────────────
const submitDye = () => {
    form.dyes_used = dyeRows.value
        .filter(r => r.inventory_item_id && parseFloat(r.quantity_used) > 0)
        .map(r => ({
            inventory_item_id: parseInt(r.inventory_item_id),
            quantity_used:     parseFloat(r.quantity_used),
        }));

    form.post(route('man.staff.dyeing-color.store-dye'), {
        preserveScroll: true,
        onSuccess: () => {
            closeDyeModal();
            router.reload({ only: ['fabrics', 'dyes'] });
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Dyeing Color">
        <div class="min-h-screen bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

                <!-- Flash -->
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200"
                    leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="showFlash" class="flex items-start gap-3 rounded-xl px-4 py-3 shadow-sm border"
                         :class="flashIsError ? 'bg-red-50 border-red-200' : 'bg-white border-green-200'">
                        <component :is="flashIsError ? AlertTriangle : CheckCircle2"
                            class="w-5 h-5 flex-shrink-0 mt-0.5"
                            :class="flashIsError ? 'text-red-500' : 'text-green-500'" />
                        <p class="text-sm flex-1" :class="flashIsError ? 'text-red-700' : 'text-gray-700'">{{ flashMessage }}</p>
                        <button @click="showFlash = false"><X class="w-4 h-4 text-gray-400 hover:text-gray-600" /></button>
                    </div>
                </transition>

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Dyeing Color Workspace</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Process fabrics · Apply dye per recipe · Deduct chemical inventory</p>
                    </div>
                </div>

                <!-- Fabric Cards -->
                <div v-if="fabrics && fabrics.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                    <div v-for="fabric in fabrics" :key="fabric.id"
                         class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-all duration-200">

                        <!-- Card Header -->
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="font-mono text-xs font-bold text-blue-600">{{ fabric.code }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ fabric.yarn_type }}</p>
                            </div>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-[10px] font-bold rounded-lg">
                                Pending Dyeing
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="px-4 py-3 space-y-3">

                            <!-- Fabric Info -->
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Weight</p>
                                    <p class="font-semibold text-gray-800">{{ fmtNum(fabric.weight) }} kg</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Machine</p>
                                    <p class="font-semibold text-gray-800">{{ fabric.machine?.machine_no ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Operator</p>
                                    <p class="font-semibold text-gray-800 truncate">{{ fabric.operator?.name ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Shift</p>
                                    <p class="font-semibold text-gray-800">{{ fabric.shift }}</p>
                                </div>
                            </div>

                            <!-- Linked JO -->
                            <div v-if="fabric.sales_order" class="bg-indigo-50 rounded-xl px-3 py-2">
                                <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-wide">Linked Job Order</p>
                                <p class="text-xs font-bold text-indigo-800 mt-0.5 font-mono">{{ fabric.sales_order.jo_number }}</p>
                                <p class="text-[10px] text-indigo-500 mt-0.5">
                                    Color: {{ fabric.sales_order.color }} · Qty: {{ fmtNum(fabric.sales_order.quantity) }} kg
                                </p>
                            </div>

                            <!-- Recipe Preview — dye color formula shown prominently -->
                            <div v-if="fabric.recipe"
                                 class="bg-yellow-50 border border-yellow-100 rounded-xl px-3 py-2 space-y-1">
                                <p class="text-[10px] text-yellow-600 font-bold uppercase tracking-wide flex items-center gap-1">
                                    <Palette class="w-3 h-3" /> Dye Color Formula
                                </p>
                                <p class="text-sm font-bold text-yellow-900">{{ fabric.recipe.dye_color }}</p>
                                <p class="text-[10px] text-yellow-600 truncate">Weave: {{ fabric.recipe.weave_design }}</p>
                            </div>
                            <div v-else class="bg-gray-50 border border-dashed border-gray-200 rounded-xl px-3 py-2 text-center">
                                <p class="text-[10px] text-gray-400">No recipe linked to this fabric</p>
                            </div>

                            <p v-if="fabric.remarks" class="text-[10px] text-gray-400 italic truncate">{{ fabric.remarks }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="px-4 pb-4 flex gap-2">
                            <button v-if="fabric.recipe" @click="openRecipeModal(fabric)" type="button"
                                class="flex-1 flex items-center justify-center gap-1.5 border border-yellow-200 text-yellow-700 hover:bg-yellow-50 text-xs font-semibold px-3 py-2 rounded-xl transition">
                                <Layers class="w-3.5 h-3.5" /> View Recipe
                            </button>
                            <button @click="openDyeModal(fabric)" type="button"
                                class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition">
                                <Droplet class="w-3.5 h-3.5" /> Dye Fabric
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 px-6 py-16 text-center">
                    <FlaskConical class="w-12 h-12 text-gray-200 mx-auto mb-3" />
                    <p class="text-sm font-medium text-gray-500">No fabrics pending dyeing at the moment.</p>
                    <p class="text-xs text-gray-400 mt-1">Fabrics arrive here after the checker passes them from knitting.</p>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             RECIPE MODAL
        ═══════════════════════════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="recipeModal && recipeModalFabric"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeRecipeModal">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

                    <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-xs text-gray-400 font-mono">{{ recipeModalFabric.code }}</p>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">Production Recipe</h3>
                        </div>
                        <button @click="closeRecipeModal" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">

                        <!-- JO Info -->
                        <div v-if="recipeModalFabric.sales_order" class="grid grid-cols-2 gap-2">
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Job Order</p>
                                <p class="text-xs font-bold text-gray-800 font-mono mt-0.5">{{ recipeModalFabric.sales_order.jo_number }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Color</p>
                                <p class="text-xs font-semibold text-gray-800 mt-0.5">{{ recipeModalFabric.sales_order.color }}</p>
                            </div>
                        </div>

                        <!-- Recipe Specs -->
                        <div v-if="recipeModalFabric.recipe" class="space-y-2">
                            <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2.5">
                                <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <Blend class="w-3.5 h-3.5 text-white" />
                                </div>
                                <div>
                                    <p class="text-[10px] text-blue-500 uppercase tracking-wide font-bold">Yarn Composition</p>
                                    <p class="text-sm font-semibold text-blue-900 mt-0.5">{{ recipeModalFabric.recipe.yarn_type }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-yellow-50 border border-yellow-100 rounded-xl px-3 py-2.5">
                                <div class="w-7 h-7 bg-yellow-400 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <Palette class="w-3.5 h-3.5 text-gray-800" />
                                </div>
                                <div>
                                    <p class="text-[10px] text-yellow-600 uppercase tracking-wide font-bold">Dye Color Formula</p>
                                    <p class="text-sm font-semibold text-yellow-900 mt-0.5">{{ recipeModalFabric.recipe.dye_color }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5">
                                <div class="w-7 h-7 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <Layers class="w-3.5 h-3.5 text-white" />
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase tracking-wide font-bold">Weave Design</p>
                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">{{ recipeModalFabric.recipe.weave_design }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex gap-2">
                        <button @click="closeRecipeModal" type="button"
                            class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-white text-sm font-semibold rounded-xl transition">
                            Close
                        </button>
                        <button @click="() => { openDyeModal(recipeModalFabric); closeRecipeModal(); }" type="button"
                            class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                            <Droplet class="w-4 h-4" /> Dye Fabric
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ══════════════════════════════════════════════════════════════════
             DYE MODAL
        ═══════════════════════════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="dyeModal && currentFabric"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeDyeModal">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[95vh] flex flex-col">

                    <!-- Header -->
                    <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-xs text-gray-400 font-mono">{{ currentFabric.code }}</p>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">Record Dyeing Process</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ currentFabric.yarn_type }} · {{ fmtNum(currentFabric.weight) }} kg</p>
                        </div>
                        <button @click="closeDyeModal" class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 transition flex-shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Dye color reminder from recipe -->
                    <div v-if="currentFabric.recipe" class="px-5 py-2.5 bg-yellow-50 border-b border-yellow-100 flex items-center gap-2">
                        <Palette class="w-4 h-4 text-yellow-600 flex-shrink-0" />
                        <div>
                            <p class="text-[10px] text-yellow-600 font-bold uppercase tracking-wide">Recipe — Dye Color Formula</p>
                            <p class="text-xs font-semibold text-yellow-900">{{ currentFabric.recipe.dye_color }}</p>
                        </div>
                    </div>

                    <!-- Server errors -->
                    <div v-if="Object.keys(form.errors).length" class="mx-5 mt-4 flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                        <AlertTriangle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-red-700 space-y-0.5">
                            <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                        </div>
                    </div>

                    <!-- Form Body -->
                    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-5">

                        <!-- 1. Machine No. -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                                Machine No. <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.machine_id" required
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                <option value="">— Select Machine —</option>
                                <option v-for="machine in machines" :key="machine.id" :value="machine.id">
                                    {{ machine.machine_no }}
                                </option>
                            </select>
                        </div>

                        <!-- 2. Dye Chemicals Section -->
                        <div class="border border-gray-200 rounded-2xl overflow-hidden">
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                <div>
                                    <p class="text-xs font-bold text-gray-700 uppercase tracking-wide">Dye Chemicals Used</p>
                                    <p class="text-xs text-gray-400 mt-0.5">Each lot is deducted from inventory on save</p>
                                </div>
                                <button type="button" @click="addDyeRow"
                                    class="flex items-center gap-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-semibold px-3 py-2 rounded-xl transition">
                                    <Plus class="w-3.5 h-3.5" /> Add Dye
                                </button>
                            </div>

                            <div class="p-4 space-y-4">
                                <div v-for="(row, index) in dyeRows" :key="index"
                                     class="rounded-xl border p-4 space-y-3"
                                     :class="index === 0 ? 'border-blue-100 bg-blue-50/40' : 'border-gray-200 bg-gray-50/40'">

                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full text-[10px] font-bold flex items-center justify-center"
                                                  :class="index === 0 ? 'bg-blue-600 text-white' : 'bg-gray-700 text-white'">
                                                {{ index + 1 }}
                                            </span>
                                            <p class="text-xs font-bold uppercase tracking-wide"
                                               :class="index === 0 ? 'text-blue-700' : 'text-gray-600'">
                                                {{ index === 0 ? 'Primary Dye' : 'Additional Dye' }}
                                            </p>
                                        </div>
                                        <button v-if="dyeRows.length > 1" type="button" @click="removeDyeRow(index)"
                                            class="flex items-center gap-1 text-red-500 hover:text-red-700 text-xs font-semibold">
                                            <Trash2 class="w-3.5 h-3.5" /> Remove
                                        </button>
                                    </div>

                                    <!-- Dye Type dropdown (inventory-sourced) -->
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                            Dye Type <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="row.inventory_item_id" @change="onDyeItemChange(row)"
                                            class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                            <option value="">— Select Dye Lot —</option>
                                            <option v-for="dye in dyes" :key="dye.id" :value="dye.id">
                                                {{ dye.material_name }} ({{ dye.control_number }}) — {{ fmtNum(dye.remaining_quantity) }} {{ dye.unit }} left
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Info bar for selected dye -->
                                    <div v-if="getDyeById(row.inventory_item_id)"
                                         class="flex flex-wrap gap-2 text-xs text-gray-600">
                                        <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 px-2.5 py-1 rounded-lg">
                                            <Package class="w-3 h-3 text-gray-400" /> {{ getDyeById(row.inventory_item_id).control_number }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 px-2.5 py-1 rounded-lg">
                                            🧪 {{ fmtNum(getDyeById(row.inventory_item_id).remaining_quantity) }} {{ getDyeById(row.inventory_item_id).unit }} remaining
                                        </span>
                                    </div>

                                    <!-- Chemicals Used (kg) -->
                                    <div v-if="row.inventory_item_id">
                                        <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                            Chemicals Used ({{ getDyeById(row.inventory_item_id)?.unit ?? 'kg' }})
                                            <span class="text-red-500">*</span>
                                            <span class="text-gray-400 font-normal normal-case ml-1">
                                                max {{ fmtNum(getDyeById(row.inventory_item_id)?.remaining_quantity) }}
                                            </span>
                                        </label>
                                        <input v-model.number="row.quantity_used" type="number" step="0.01" min="0.01"
                                            :max="getDyeById(row.inventory_item_id)?.remaining_quantity"
                                            class="w-full border rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                            :class="dyeRowErrors[index] ? 'border-red-300' : 'border-gray-200'"
                                            placeholder="e.g. 5.00" />
                                        <p v-if="dyeRowErrors[index]" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" /> {{ dyeRowErrors[index] }}
                                        </p>
                                        <p v-else-if="parseFloat(row.quantity_used) > 0" class="text-xs text-green-600 mt-1">
                                            ✓ {{ fmtNum(getDyeById(row.inventory_item_id).remaining_quantity - parseFloat(row.quantity_used)) }}
                                            {{ getDyeById(row.inventory_item_id).unit }} will remain.
                                        </p>
                                    </div>
                                </div>

                                <!-- Total chemicals summary -->
                                <div v-if="dyeRows.length > 1 && totalChemicalsKg > 0"
                                     class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-2.5">
                                    <p class="text-xs text-gray-500 font-medium">Total chemicals used</p>
                                    <p class="text-sm font-bold text-gray-900">{{ fmtNum(totalChemicalsKg) }} kg</p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Remarks -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                                Remarks <span class="text-gray-400 font-normal normal-case">(optional)</span>
                            </label>
                            <textarea v-model="form.remarks" rows="3"
                                placeholder="Any issues, color observations, or special notes…"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition"></textarea>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 space-y-2">
                        <div class="flex gap-2">
                            <button type="button" @click="closeDyeModal"
                                class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-white text-sm font-semibold rounded-xl transition">
                                Cancel
                            </button>
                            <button type="button" @click="submitDye" :disabled="!canSubmit"
                                class="flex-1 py-2.5 text-sm font-bold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="canSubmit ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-100 text-gray-400'">
                                {{ form.processing ? 'Recording…' : 'Submit Dye Job' }}
                            </button>
                        </div>
                        <div class="space-y-1 text-xs">
                            <p v-if="!form.machine_id" class="text-amber-600 flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3" /> Select a machine.
                            </p>
                            <p v-if="dyeRows.some(r => !r.inventory_item_id || !parseFloat(r.quantity_used))" class="text-amber-600 flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3" /> Fill in all dye type and quantity fields.
                            </p>
                            <p v-if="hasInvalidDyeRows" class="text-red-600 flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3" /> One or more dye quantities exceed available inventory.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>