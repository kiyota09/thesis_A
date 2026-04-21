<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Scissors, Plus, Trash2, ClipboardList, AlertTriangle,
    CheckCircle2, X, Package, Layers, Palette, Blend,
    CheckCheck, RotateCcw, Clock, Link2,
} from 'lucide-vue-next';

const props = defineProps({
    machines:         Array,
    yarns:            Array,
    jobOrders:        Array,
    availableFabrics: Array,   // pending, unlinked fabrics — shown in fabric-link modal
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
const dismissFlash = () => { showFlash.value = false; };

// ─── Job Order Tab Filter ─────────────────────────────────────────────────────
const activeTab = ref('all');
const filteredOrders = computed(() => {
    if (activeTab.value === 'pending') return props.jobOrders.filter(o => !o.is_knitting_done);
    if (activeTab.value === 'done')    return props.jobOrders.filter(o =>  o.is_knitting_done);
    return props.jobOrders;
});
const pendingCount = computed(() => props.jobOrders.filter(o => !o.is_knitting_done).length);
const doneCount    = computed(() => props.jobOrders.filter(o =>  o.is_knitting_done).length);
const progress     = computed(() =>
    props.jobOrders.length ? Math.round((doneCount.value / props.jobOrders.length) * 100) : 0
);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getYarnById = (id) => props.yarns.find(y => y.id == id) ?? null;
const fmtNum      = (n)  => parseFloat(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// ─── Fabric-Link Modal (Mark Done) ────────────────────────────────────────────
// Replaces the old simple confirm for the "done" action.
// The operator picks which pending fabrics belong to this JO, then submits.
const fabricLinkModal    = ref(false);
const fabricLinkTarget   = ref(null);    // the JO being marked done
const selectedFabricIds  = ref([]);      // checkbox selections
const fabricLinkProcessing = ref(false);

const openFabricLink = (order) => {
    fabricLinkTarget.value  = order;
    selectedFabricIds.value = [];
    fabricLinkModal.value   = true;
};
const closeFabricLink = () => {
    fabricLinkModal.value  = false;
    fabricLinkTarget.value = null;
    selectedFabricIds.value = [];
};

const toggleFabricSelection = (id) => {
    const idx = selectedFabricIds.value.indexOf(id);
    if (idx === -1) selectedFabricIds.value.push(id);
    else            selectedFabricIds.value.splice(idx, 1);
};

const isFabricSelected = (id) => selectedFabricIds.value.includes(id);

const confirmFabricLink = () => {
    if (!fabricLinkTarget.value || fabricLinkProcessing.value) return;
    if (selectedFabricIds.value.length === 0) return;
    fabricLinkProcessing.value = true;

    router.post(
        route('man.staff.knitting-yarn.mark-done', fabricLinkTarget.value.id),
        { fabric_ids: selectedFabricIds.value },
        {
            preserveScroll: true,
            onFinish: () => { fabricLinkProcessing.value = false; closeFabricLink(); },
        }
    );
};

// ─── Undo Confirm Modal ───────────────────────────────────────────────────────
const undoModal      = ref(false);
const undoTarget     = ref(null);
const undoProcessing = ref(false);

const openUndo  = (order) => { undoTarget.value = order; undoModal.value = true; };
const closeUndo = () => { undoModal.value = false; undoTarget.value = null; };

const confirmUndo = () => {
    if (!undoTarget.value || undoProcessing.value) return;
    undoProcessing.value = true;
    router.post(route('man.staff.knitting-yarn.unmark-done', undoTarget.value.id), {}, {
        preserveScroll: true,
        onFinish: () => { undoProcessing.value = false; closeUndo(); },
    });
};

// ─── Recipe Modal ─────────────────────────────────────────────────────────────
const recipeModal           = ref(false);
const selectedRecipeOrderId = ref(null);
const selectedRecipeOrder   = computed(() =>
    selectedRecipeOrderId.value !== null
        ? props.jobOrders.find(o => o.id === selectedRecipeOrderId.value) ?? null
        : null
);
const openRecipe  = (order) => { selectedRecipeOrderId.value = order.id; recipeModal.value = true; };
const closeRecipe = () => { recipeModal.value = false; selectedRecipeOrderId.value = null; };

// ─── Main Yarn ────────────────────────────────────────────────────────────────
const selectedMainYarnId = ref('');
const mainRollsUsed      = ref('');
const selectedMainYarn   = computed(() => getYarnById(selectedMainYarnId.value));

watch(selectedMainYarnId, () => {
    mainRollsUsed.value = '';
    form.yarn_type = selectedMainYarn.value?.material_name ?? '';
    recalcWeight();
});

// ─── Additional Yarns ─────────────────────────────────────────────────────────
const additionalYarnItems = ref([]);

const addYarnRow = () => additionalYarnItems.value.push({ inventory_item_id: '', units_used: '' });
const removeYarnRow = (index) => {
    additionalYarnItems.value.splice(index, 1);
    recalcWeight();
};
const clampAdditionalRow = (row) => {
    const yarn = getYarnById(row.inventory_item_id);
    if (!yarn) return;
    if (row.units_used < 1)                   row.units_used = 1;
    if (row.units_used > yarn.available_units) row.units_used = yarn.available_units;
    recalcWeight();
};
const onAdditionalYarnChange = (row) => { row.units_used = ''; recalcWeight(); };

// ─── Weight Computation ───────────────────────────────────────────────────────
const weightIsPartial = ref(false);

const recalcWeight = () => {
    let total = 0;
    let allHaveUnitWeight = true;

    const mainYarn  = selectedMainYarn.value;
    const mainRolls = parseInt(mainRollsUsed.value);
    if (mainYarn && mainRolls >= 1) {
        if (mainYarn.unit_weight) total += parseFloat(mainYarn.unit_weight) * mainRolls;
        else allHaveUnitWeight = false;
    }

    for (const row of additionalYarnItems.value) {
        const yarn  = getYarnById(row.inventory_item_id);
        const rolls = parseInt(row.units_used);
        if (yarn && rolls >= 1) {
            if (yarn.unit_weight) total += parseFloat(yarn.unit_weight) * rolls;
            else allHaveUnitWeight = false;
        }
    }

    const hasEntry = (mainYarn && mainRolls >= 1) ||
        additionalYarnItems.value.some(r => getYarnById(r.inventory_item_id) && parseInt(r.units_used) >= 1);

    form.weight = hasEntry ? total.toFixed(2) : '';
    weightIsPartial.value = hasEntry && !allHaveUnitWeight;
};

const weightBreakdown = computed(() => {
    const parts = [];
    const mainYarn = selectedMainYarn.value;
    const mainRolls = parseInt(mainRollsUsed.value);
    if (mainYarn && mainRolls >= 1 && mainYarn.unit_weight)
        parts.push(`${mainYarn.material_name}: ${mainRolls} × ${mainYarn.unit_weight} kg = ${(parseFloat(mainYarn.unit_weight) * mainRolls).toFixed(2)} kg`);
    for (const row of additionalYarnItems.value) {
        const yarn = getYarnById(row.inventory_item_id);
        const rolls = parseInt(row.units_used);
        if (yarn && rolls >= 1 && yarn.unit_weight)
            parts.push(`${yarn.material_name}: ${rolls} × ${yarn.unit_weight} kg = ${(parseFloat(yarn.unit_weight) * rolls).toFixed(2)} kg`);
    }
    return parts;
});

watch(mainRollsUsed, recalcWeight);
watch(additionalYarnItems, recalcWeight, { deep: true });

// ─── Form ─────────────────────────────────────────────────────────────────────
const form = useForm({ machine_id: '', yarn_type: '', weight: '', remarks: '', yarns_used: [] });

const mainYarnExceedsAvailable = computed(() =>
    selectedMainYarn.value && mainRollsUsed.value
        ? parseInt(mainRollsUsed.value) > selectedMainYarn.value.available_units
        : false
);
const additionalRowErrors = computed(() =>
    additionalYarnItems.value.map(row => {
        if (!row.inventory_item_id || !row.units_used) return null;
        const yarn = getYarnById(row.inventory_item_id);
        return yarn && parseInt(row.units_used) > yarn.available_units
            ? `Only ${yarn.available_units} rolls available` : null;
    })
);
const hasInvalidAdditionalUnits = computed(() => additionalRowErrors.value.some(e => e !== null));
const canSubmit = computed(() =>
    form.machine_id && selectedMainYarnId.value && parseInt(mainRollsUsed.value) >= 1 &&
    !mainYarnExceedsAvailable.value && !hasInvalidAdditionalUnits.value &&
    parseFloat(form.weight) > 0 && !form.processing
);

const submitFabric = () => {
    form.yarns_used = [
        { inventory_item_id: parseInt(selectedMainYarnId.value), units_used: parseInt(mainRollsUsed.value) },
        ...additionalYarnItems.value
            .filter(item => item.inventory_item_id && parseInt(item.units_used) >= 1)
            .map(item => ({ inventory_item_id: parseInt(item.inventory_item_id), units_used: parseInt(item.units_used) })),
    ];
    form.post(route('man.staff.knitting-yarn.store-fabric'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedMainYarnId.value = ''; mainRollsUsed.value = '';
            additionalYarnItems.value = []; weightIsPartial.value = false;
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Knitting Yarn">
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
                        <button @click="dismissFlash"><X class="w-4 h-4 text-gray-400 hover:text-gray-600" /></button>
                    </div>
                </transition>

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 tracking-tight">Knitting Yarn Workspace</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Record fabrics · Link to job orders · Track progress</p>
                    </div>
                    <Link :href="route('man.staff.knitting-yarn.reports')"
                        class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition self-start sm:self-auto">
                        <ClipboardList class="w-4 h-4" /> Machine Reports
                    </Link>
                </div>

                <!-- ══ PRODUCTION TRACKER ══ -->
                <section>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-1 h-5 bg-yellow-400 rounded-full"></div>
                            <h2 class="text-base font-bold text-gray-800">Production Job Orders</h2>
                        </div>
                        <div v-if="jobOrders.length" class="flex items-center gap-3">
                            <div class="text-xs text-gray-500">
                                <span class="font-bold text-gray-800">{{ doneCount }}</span> / {{ jobOrders.length }} done
                            </div>
                            <div class="w-28 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full transition-all duration-500" :style="`width: ${progress}%`"></div>
                            </div>
                            <span class="text-xs font-bold text-green-600">{{ progress }}%</span>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div v-if="jobOrders.length" class="flex items-center gap-1 bg-white border border-gray-200 rounded-xl p-1 w-fit mb-4">
                        <button v-for="tab in [
                            { key: 'all', label: 'All', count: jobOrders.length },
                            { key: 'pending', label: 'Pending', count: pendingCount },
                            { key: 'done', label: 'Done', count: doneCount },
                        ]" :key="tab.key" @click="activeTab = tab.key" type="button"
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                            :class="activeTab === tab.key ? 'bg-gray-900 text-white shadow-sm' : 'text-gray-500 hover:text-gray-800'">
                            {{ tab.label }}
                            <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                                  :class="activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">
                                {{ tab.count }}
                            </span>
                        </button>
                    </div>

                    <!-- Job Order Cards -->
                    <div v-if="filteredOrders.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="order in filteredOrders" :key="order.id"
                             class="bg-white rounded-2xl border shadow-sm overflow-hidden transition-all duration-200"
                             :class="order.is_knitting_done ? 'border-green-200 opacity-80' : 'border-gray-200 hover:shadow-md'">

                            <!-- Card Header -->
                            <div class="flex items-center justify-between px-4 py-3 border-b"
                                 :class="order.is_knitting_done ? 'bg-green-50 border-green-100' : 'bg-gray-50 border-gray-100'">
                                <div>
                                    <span class="font-mono text-xs font-bold tracking-wide"
                                          :class="order.is_knitting_done ? 'text-green-700' : 'text-blue-600'">
                                        {{ order.jo_number }}
                                    </span>
                                    <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ order.control_number }}</p>
                                </div>
                                <div v-if="order.is_knitting_done"
                                     class="flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-lg">
                                    <CheckCheck class="w-3 h-3" /> Done
                                </div>
                                <div v-else class="flex items-center gap-1 bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-lg">
                                    <Clock class="w-3 h-3" /> Pending
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="px-4 py-3 space-y-3">
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Color</p>
                                        <p class="font-semibold text-gray-800 truncate">{{ order.color }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Quantity</p>
                                        <p class="font-semibold text-gray-800">{{ fmtNum(order.quantity) }} kg</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Yarn Type</p>
                                        <p class="font-semibold text-gray-800 truncate">{{ order.yarn_type }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Design</p>
                                        <p class="font-semibold text-gray-800 truncate">{{ order.design }}</p>
                                    </div>
                                </div>

                                <!-- Done timestamp -->
                                <div v-if="order.is_knitting_done && order.knitting_done_at"
                                     class="flex items-center gap-1.5 text-[10px] text-green-600 bg-green-50 rounded-lg px-2.5 py-1.5">
                                    <CheckCircle2 class="w-3 h-3 flex-shrink-0" />
                                    Completed {{ order.knitting_done_at }}
                                </div>

                                <!-- Linked fabrics count -->
                                <div v-if="order.linked_fabrics?.length"
                                     class="flex items-center gap-1.5 text-[10px] text-blue-600 bg-blue-50 rounded-lg px-2.5 py-1.5">
                                    <Link2 class="w-3 h-3 flex-shrink-0" />
                                    {{ order.linked_fabrics.length }} fabric{{ order.linked_fabrics.length !== 1 ? 's' : '' }} linked
                                    <span class="ml-1 text-blue-400">({{ order.linked_fabrics.map(f => f.code).join(', ') }})</span>
                                </div>

                                <!-- Recipe preview -->
                                <div v-if="order.recipe" class="bg-blue-50 rounded-xl px-3 py-2">
                                    <p class="text-[10px] text-blue-400 font-bold uppercase tracking-wide">Recipe</p>
                                    <p class="text-xs font-semibold text-blue-800 mt-0.5 truncate">{{ order.recipe.yarn_type }}</p>
                                    <p class="text-[10px] text-blue-500 mt-0.5 truncate">
                                        Dye: {{ order.recipe.dye_color }} · Weave: {{ order.recipe.weave_design }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 pt-1">
                                    <button v-if="order.recipe" @click="openRecipe(order)" type="button"
                                        class="flex-1 flex items-center justify-center gap-1.5 border border-blue-200 text-blue-600 hover:bg-blue-50 text-xs font-semibold px-3 py-2 rounded-xl transition">
                                        <Layers class="w-3.5 h-3.5" /> Full Recipe
                                    </button>
                                    <!-- Mark Done → opens fabric-link modal -->
                                    <button v-if="!order.is_knitting_done"
                                        @click="openFabricLink(order)" type="button"
                                        class="flex-1 flex items-center justify-center gap-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-bold px-3 py-2 rounded-xl transition">
                                        <CheckCheck class="w-3.5 h-3.5" /> Mark Done
                                    </button>
                                    <button v-else @click="openUndo(order)" type="button"
                                        class="flex-1 flex items-center justify-center gap-1.5 border border-gray-200 text-gray-500 hover:text-gray-700 text-xs font-semibold px-3 py-2 rounded-xl transition">
                                        <RotateCcw class="w-3.5 h-3.5" /> Undo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-white rounded-2xl border border-dashed border-gray-200 px-6 py-10 text-center">
                        <component :is="activeTab === 'done' ? CheckCheck : ClipboardList" class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                        <p class="text-sm font-medium text-gray-500">
                            {{ activeTab === 'done' ? 'No job orders marked as done yet.'
                               : activeTab === 'pending' ? 'All job orders are done — great work!'
                               : 'No active job orders at the moment.' }}
                        </p>
                    </div>
                </section>

                <!-- ══ RECORD FABRIC FORM ══ -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <Scissors class="w-4 h-4 text-white" />
                                </div>
                                <div>
                                    <h2 class="text-base font-bold text-gray-900">Record New Fabric</h2>
                                    <p class="text-xs text-gray-500">Enter details from the knitting machine output</p>
                                </div>
                            </div>

                            <form @submit.prevent="submitFabric" class="px-5 sm:px-6 py-5 space-y-5">
                                <div v-if="Object.keys(form.errors).length"
                                     class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                                    <AlertTriangle class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
                                    <div class="text-sm text-red-700 space-y-0.5">
                                        <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                                    </div>
                                </div>

                                <!-- Machine -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Machine No. <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="form.machine_id" required
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                        <option value="">— Select Machine —</option>
                                        <option v-for="machine in machines" :key="machine.id" :value="machine.id">{{ machine.machine_no }}</option>
                                    </select>
                                </div>

                                <!-- Yarns Section -->
                                <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-100">
                                        <div>
                                            <p class="text-xs font-bold text-gray-700 uppercase tracking-wide">Yarns Consumed</p>
                                            <p class="text-xs text-gray-400 mt-0.5">Each lot is deducted from inventory on save</p>
                                        </div>
                                        <button type="button" @click="addYarnRow"
                                            class="flex items-center gap-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-semibold px-3 py-2 rounded-xl transition">
                                            <Plus class="w-3.5 h-3.5" /> Add Lot
                                        </button>
                                    </div>

                                    <div class="p-4 space-y-4">
                                        <!-- Primary Yarn -->
                                        <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4 space-y-3">
                                            <div class="flex items-center gap-2">
                                                <span class="w-5 h-5 bg-blue-600 text-white rounded-full text-[10px] font-bold flex items-center justify-center">1</span>
                                                <p class="text-xs font-bold text-blue-700 uppercase tracking-wide">Primary Yarn</p>
                                            </div>
                                            <select v-model="selectedMainYarnId" required
                                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                                <option value="">— Select Yarn Lot —</option>
                                                <option v-for="yarn in yarns" :key="yarn.id" :value="yarn.id">
                                                    {{ yarn.material_name }} ({{ yarn.control_number }}) — {{ yarn.available_units }} rolls left
                                                </option>
                                            </select>
                                            <div v-if="selectedMainYarn" class="flex flex-wrap gap-2">
                                                <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-xs text-gray-600 px-2.5 py-1 rounded-lg">
                                                    <Package class="w-3 h-3 text-gray-400" /> {{ selectedMainYarn.control_number }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-xs text-gray-600 px-2.5 py-1 rounded-lg">
                                                    🧶 {{ selectedMainYarn.available_units }} rolls left
                                                </span>
                                                <span v-if="selectedMainYarn.unit_weight" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-xs text-gray-600 px-2.5 py-1 rounded-lg">
                                                    ⚖️ {{ selectedMainYarn.unit_weight }} kg / roll
                                                </span>
                                            </div>
                                            <div v-if="selectedMainYarn">
                                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                                    Rolls Used <span class="text-red-500">*</span>
                                                    <span class="text-gray-400 font-normal normal-case ml-1">(max {{ selectedMainYarn.available_units }})</span>
                                                </label>
                                                <input v-model.number="mainRollsUsed" type="number" min="1"
                                                    :max="selectedMainYarn.available_units" required
                                                    class="w-full border rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                                    :class="mainYarnExceedsAvailable ? 'border-red-300' : 'border-gray-200'"
                                                    placeholder="e.g. 30" />
                                                <p v-if="mainYarnExceedsAvailable" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                                    <AlertTriangle class="w-3 h-3" /> Only {{ selectedMainYarn.available_units }} rolls available.
                                                </p>
                                                <p v-else-if="parseInt(mainRollsUsed) >= 1" class="text-xs text-green-600 mt-1">
                                                    ✓ {{ selectedMainYarn.available_units - parseInt(mainRollsUsed) }} rolls will remain.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Additional Yarn Rows -->
                                        <div v-for="(row, index) in additionalYarnItems" :key="index"
                                             class="rounded-xl border border-gray-200 bg-gray-50/40 p-4 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-5 h-5 bg-gray-700 text-white rounded-full text-[10px] font-bold flex items-center justify-center">{{ index + 2 }}</span>
                                                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wide">Additional Yarn</p>
                                                </div>
                                                <button type="button" @click="removeYarnRow(index)"
                                                    class="flex items-center gap-1 text-red-500 hover:text-red-700 text-xs font-semibold">
                                                    <Trash2 class="w-3.5 h-3.5" /> Remove
                                                </button>
                                            </div>
                                            <select v-model="row.inventory_item_id" @change="onAdditionalYarnChange(row)"
                                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                                <option value="">— Select Yarn Lot —</option>
                                                <option v-for="yarn in yarns" :key="yarn.id" :value="yarn.id">
                                                    {{ yarn.material_name }} ({{ yarn.control_number }}) — {{ yarn.available_units }} rolls left
                                                </option>
                                            </select>
                                            <div v-if="row.inventory_item_id && getYarnById(row.inventory_item_id)" class="flex flex-wrap gap-2">
                                                <span class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-xs text-gray-600 px-2.5 py-1 rounded-lg">
                                                    🧶 {{ getYarnById(row.inventory_item_id).available_units }} rolls left
                                                </span>
                                                <span v-if="getYarnById(row.inventory_item_id).unit_weight" class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-xs text-gray-600 px-2.5 py-1 rounded-lg">
                                                    ⚖️ {{ getYarnById(row.inventory_item_id).unit_weight }} kg / roll
                                                </span>
                                            </div>
                                            <div v-if="row.inventory_item_id">
                                                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">
                                                    Rolls Used <span class="text-red-500">*</span>
                                                </label>
                                                <input v-model.number="row.units_used" type="number" min="1"
                                                    :max="getYarnById(row.inventory_item_id)?.available_units || 1"
                                                    @input="clampAdditionalRow(row)"
                                                    class="w-full border rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                                    :class="additionalRowErrors[index] ? 'border-red-300' : 'border-gray-200'"
                                                    placeholder="e.g. 10" />
                                                <p v-if="additionalRowErrors[index]" class="text-xs text-red-500 mt-1 flex items-center gap-1">
                                                    <AlertTriangle class="w-3 h-3" /> {{ additionalRowErrors[index] }}
                                                </p>
                                                <p v-else-if="parseInt(row.units_used) >= 1" class="text-xs text-green-600 mt-1">
                                                    ✓ {{ getYarnById(row.inventory_item_id).available_units - parseInt(row.units_used) }} rolls will remain.
                                                </p>
                                            </div>
                                        </div>

                                        <p v-if="additionalYarnItems.length === 0" class="text-xs text-gray-400 italic px-1">
                                            Click "Add Lot" if more than one yarn lot was used for this fabric.
                                        </p>
                                    </div>
                                </div>

                                <!-- Weight -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Total Fabric Weight (kg) <span class="text-red-500">*</span>
                                        <span v-if="weightBreakdown.length" class="text-blue-500 font-normal normal-case ml-1">(auto-calculated)</span>
                                    </label>
                                    <input type="number" step="0.01" v-model="form.weight" required
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                        placeholder="0.00" />
                                    <div v-if="weightBreakdown.length" class="mt-2 text-xs text-gray-500 bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 space-y-0.5">
                                        <p v-for="(line, i) in weightBreakdown" :key="i" class="pl-1">+ {{ line }}</p>
                                        <p class="pl-1 font-bold text-gray-700 border-t border-gray-200 pt-1 mt-1">= {{ form.weight }} kg total</p>
                                    </div>
                                    <p v-if="weightIsPartial" class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" /> Some yarns have no unit weight — verify total manually.
                                    </p>
                                </div>

                                <!-- Remarks -->
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">
                                        Remarks <span class="text-gray-400 font-normal normal-case">(optional)</span>
                                    </label>
                                    <textarea v-model="form.remarks" rows="3" placeholder="Any issues, special notes, or observations…"
                                        class="w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 transition"></textarea>
                                </div>

                                <!-- Submit -->
                                <div class="pt-1 space-y-2">
                                    <button type="submit" :disabled="!canSubmit"
                                        class="w-full py-3 rounded-xl text-sm font-bold transition"
                                        :class="canSubmit ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-sm' : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                                        {{ form.processing ? 'Recording…' : 'Record Fabric' }}
                                    </button>
                                    <div class="space-y-1 text-xs">
                                        <p v-if="!selectedMainYarnId && form.machine_id" class="text-amber-600 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" /> Select a primary yarn lot.
                                        </p>
                                        <p v-if="selectedMainYarnId && parseInt(mainRollsUsed) < 1" class="text-amber-600 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" /> Enter rolls used.
                                        </p>
                                        <p v-if="mainYarnExceedsAvailable" class="text-red-600 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3" /> Rolls exceed available inventory.
                                        </p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2">
                                <Scissors class="w-4 h-4 text-blue-600" />
                                <p class="text-sm font-bold text-gray-800">Knitting Guide</p>
                            </div>
                            <div class="px-4 py-4">
                                <ol class="space-y-3">
                                    <li v-for="(step, i) in [
                                        'Select the machine currently in use',
                                        'Choose primary yarn lot and enter rolls consumed',
                                        'Click Add Lot for each additional lot used',
                                        'Total weight auto-calculates (rolls × unit weight)',
                                        'All lots are deducted from inventory on save',
                                        'Click Mark Done on a JO to link fabrics and complete the order',
                                    ]" :key="i" class="flex items-start gap-2.5">
                                        <span class="w-5 h-5 bg-yellow-400 text-gray-900 rounded-full text-[10px] font-bold flex items-center justify-center flex-shrink-0 mt-0.5">{{ i + 1 }}</span>
                                        <p class="text-xs text-gray-600 leading-relaxed">{{ step }}</p>
                                    </li>
                                </ol>
                            </div>
                        </div>
                        <div v-if="yarns.length" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Package class="w-4 h-4 text-yellow-500" />
                                    <p class="text-sm font-bold text-gray-800">Yarn Inventory</p>
                                </div>
                                <span class="text-xs text-gray-400">{{ yarns.length }} lot{{ yarns.length !== 1 ? 's' : '' }}</span>
                            </div>
                            <div class="divide-y divide-gray-50">
                                <div v-for="yarn in yarns" :key="yarn.id" class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-semibold text-gray-800 truncate">{{ yarn.material_name }}</p>
                                            <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ yarn.control_number }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <p class="text-xs font-bold text-blue-600">{{ yarn.available_units }} rolls</p>
                                            <p class="text-[10px] text-gray-400">{{ yarn.remaining_quantity }} {{ yarn.unit }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-1.5 h-1 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-blue-400 rounded-full"
                                             :style="`width: ${Math.min(100, (yarn.available_units / yarn.total_units) * 100)}%`"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════════════════════════════
             FABRIC-LINK MODAL — shown when "Mark Done" is clicked
        ═══════════════════════════════════════════════════════════════════ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="fabricLinkModal && fabricLinkTarget"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeFabricLink">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">

                    <!-- Header -->
                    <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-xs text-gray-400 font-mono font-medium">{{ fabricLinkTarget.jo_number }}</p>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">Link Fabrics & Mark Done</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Select the fabric(s) produced for this job order</p>
                        </div>
                        <button @click="closeFabricLink" type="button"
                            class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 transition flex-shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- JO summary bar -->
                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 flex flex-wrap gap-3 text-xs text-gray-600">
                        <span><strong>Color:</strong> {{ fabricLinkTarget.color }}</span>
                        <span><strong>Qty:</strong> {{ fmtNum(fabricLinkTarget.quantity) }} kg</span>
                        <span><strong>Yarn:</strong> {{ fabricLinkTarget.yarn_type }}</span>
                    </div>

                    <!-- Fabric list -->
                    <div class="overflow-y-auto flex-1 px-5 py-4">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">
                            Available Fabrics
                            <span class="ml-1 bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-md font-semibold">
                                {{ availableFabrics.length }}
                            </span>
                        </p>

                        <div v-if="availableFabrics.length === 0"
                             class="text-center py-10 text-sm text-gray-400">
                            <Scissors class="w-10 h-10 text-gray-200 mx-auto mb-2" />
                            No pending fabrics available. Record a fabric first.
                        </div>

                        <div v-else class="space-y-2">
                            <label v-for="fabric in availableFabrics" :key="fabric.id"
                                   class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition"
                                   :class="isFabricSelected(fabric.id)
                                       ? 'border-blue-400 bg-blue-50'
                                       : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'">
                                <input type="checkbox" :value="fabric.id"
                                    :checked="isFabricSelected(fabric.id)"
                                    @change="toggleFabricSelection(fabric.id)"
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-bold text-gray-900 font-mono">{{ fabric.code }}</p>
                                        <span class="text-xs font-semibold text-blue-600 flex-shrink-0">{{ fabric.weight }} kg</span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ fabric.yarn_type }} · {{ fabric.machine_no }} · {{ fabric.shift }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ fabric.processed_at }}</p>
                                    <p v-if="fabric.remarks" class="text-[10px] text-gray-400 italic mt-0.5 truncate">{{ fabric.remarks }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 space-y-3">
                        <p v-if="selectedFabricIds.length" class="text-xs text-blue-600 font-semibold text-center">
                            {{ selectedFabricIds.length }} fabric{{ selectedFabricIds.length !== 1 ? 's' : '' }} selected
                        </p>
                        <div class="flex gap-2">
                            <button @click="closeFabricLink" type="button"
                                class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-white text-sm font-semibold rounded-xl transition">
                                Cancel
                            </button>
                            <button @click="confirmFabricLink" type="button"
                                :disabled="selectedFabricIds.length === 0 || fabricLinkProcessing"
                                class="flex-1 py-2.5 text-sm font-bold rounded-xl transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="selectedFabricIds.length > 0 ? 'bg-gray-900 hover:bg-gray-700 text-white' : 'bg-gray-100 text-gray-400'">
                                {{ fabricLinkProcessing ? 'Saving…' : `Confirm & Mark Done` }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ══ UNDO CONFIRM MODAL ══ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="undoModal && undoTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeUndo">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
                    <div class="flex flex-col items-center px-6 pt-6 pb-4 text-center">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 flex items-center justify-center mb-3">
                            <RotateCcw class="w-6 h-6 text-amber-600" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900">Re-open Job Order?</h3>
                        <p class="text-sm text-gray-500 mt-1">This removes the done mark and unlinks any pending fabrics.</p>
                    </div>
                    <div class="mx-6 mb-4 bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                        <p class="text-xs text-gray-400 font-medium">Job Order</p>
                        <p class="text-sm font-bold text-gray-900 font-mono mt-0.5">{{ undoTarget.jo_number }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ undoTarget.color }} · {{ fmtNum(undoTarget.quantity) }} kg</p>
                    </div>
                    <div class="flex gap-2 px-6 pb-6">
                        <button @click="closeUndo" type="button"
                            class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-semibold rounded-xl transition">
                            Cancel
                        </button>
                        <button @click="confirmUndo" type="button" :disabled="undoProcessing"
                            class="flex-1 py-2.5 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl transition disabled:opacity-60">
                            {{ undoProcessing ? 'Processing…' : 'Yes, Re-open' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>

        <!-- ══ RECIPE MODAL ══ -->
        <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="recipeModal && selectedRecipeOrder"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="closeRecipe">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
                    <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
                        <div>
                            <p class="text-xs text-gray-400 font-mono font-medium">{{ selectedRecipeOrder.jo_number }}</p>
                            <h3 class="text-base font-bold text-gray-900 mt-0.5">Production Recipe</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <div v-if="selectedRecipeOrder.is_knitting_done"
                                 class="flex items-center gap-1 bg-green-100 text-green-700 text-[10px] font-bold px-2 py-1 rounded-lg">
                                <CheckCheck class="w-3 h-3" /> Done
                            </div>
                            <div v-else class="flex items-center gap-1 bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-lg">
                                <Clock class="w-3 h-3" /> Pending
                            </div>
                            <button @click="closeRecipe" type="button"
                                class="w-8 h-8 flex items-center justify-center rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 transition">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    <div class="overflow-y-auto flex-1 px-5 py-4 space-y-5">
                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Control No.</p>
                                <p class="text-xs font-semibold text-gray-800 font-mono mt-0.5">{{ selectedRecipeOrder.control_number }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Color</p>
                                <p class="text-xs font-semibold text-gray-800 mt-0.5">{{ selectedRecipeOrder.color }}</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Quantity</p>
                                <p class="text-xs font-semibold text-gray-800 mt-0.5">{{ fmtNum(selectedRecipeOrder.quantity) }} kg</p>
                            </div>
                            <div class="bg-gray-50 rounded-xl px-3 py-2.5">
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide font-medium">Design</p>
                                <p class="text-xs font-semibold text-gray-800 mt-0.5">{{ selectedRecipeOrder.design }}</p>
                            </div>
                        </div>
                        <div v-if="selectedRecipeOrder.recipe" class="grid grid-cols-1 gap-2">
                            <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-xl px-3 py-2.5">
                                <div class="w-7 h-7 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <Blend class="w-3.5 h-3.5 text-white" />
                                </div>
                                <div>
                                    <p class="text-[10px] text-blue-500 uppercase tracking-wide font-bold">Yarn Composition</p>
                                    <p class="text-sm font-semibold text-blue-900 mt-0.5">{{ selectedRecipeOrder.recipe.yarn_type }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 bg-yellow-50 border border-yellow-100 rounded-xl px-3 py-2.5">
                                <div class="w-7 h-7 bg-yellow-400 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <Palette class="w-3.5 h-3.5 text-gray-800" />
                                </div>
                                <div>
                                    <p class="text-[10px] text-yellow-600 uppercase tracking-wide font-bold">Dye Color Formula</p>
                                    <p class="text-sm font-semibold text-yellow-900 mt-0.5">{{ selectedRecipeOrder.recipe.dye_color }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedRecipeOrder.is_knitting_done"
                             class="flex items-center gap-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                            <CheckCheck class="w-4 h-4 text-green-600 flex-shrink-0" />
                            <div>
                                <p class="text-xs font-bold text-green-700">Knitting Completed</p>
                                <p class="text-[10px] text-green-500 mt-0.5">{{ selectedRecipeOrder.knitting_done_at }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex gap-2">
                        <button @click="closeRecipe" type="button"
                            class="flex-1 py-2.5 border border-gray-200 text-gray-600 hover:bg-white text-sm font-semibold rounded-xl transition">
                            Close
                        </button>
                        <button v-if="!selectedRecipeOrder.is_knitting_done"
                            @click="() => { openFabricLink(selectedRecipeOrder); closeRecipe(); }" type="button"
                            class="flex-1 py-2.5 bg-gray-900 hover:bg-gray-700 text-white text-sm font-bold rounded-xl transition flex items-center justify-center gap-1.5">
                            <CheckCheck class="w-4 h-4" /> Mark Done
                        </button>
                        <button v-else
                            @click="() => { openUndo(selectedRecipeOrder); closeRecipe(); }" type="button"
                            class="flex-1 py-2.5 border border-amber-200 text-amber-600 hover:bg-amber-50 text-sm font-semibold rounded-xl transition flex items-center justify-center gap-1.5">
                            <RotateCcw class="w-4 h-4" /> Undo Done
                        </button>
                    </div>
                </div>
            </div>
        </transition>

    </AuthenticatedLayout>
</template>