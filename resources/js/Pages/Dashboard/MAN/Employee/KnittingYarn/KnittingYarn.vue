<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Scissors, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    machines: Array,   // List of available knitting machines
    yarns: Array,      // Available yarn inventory items in production
    jobOrders: Array,  // Assigned job orders with recipe details
});

// ─── Main Yarn Selection ──────────────────────────────────────────────────────

const selectedMainYarnId = ref('');
const mainRollsUsed = ref('');

const selectedMainYarn = computed(() => {
    if (!selectedMainYarnId.value) return null;
    return props.yarns.find(y => y.id == selectedMainYarnId.value) ?? null;
});

// Sync form.yarn_type from the selected yarn's material name
watch(selectedMainYarnId, (newId) => {
    form.yarn_type = newId
        ? (props.yarns.find(y => y.id == newId)?.material_name ?? '')
        : '';
    mainRollsUsed.value = '';
    form.weight = '';
});

// Auto-calculate weight when rolls used changes
watch(mainRollsUsed, (rolls) => {
    if (selectedMainYarn.value?.unit_weight && rolls > 0) {
        form.weight = (parseFloat(selectedMainYarn.value.unit_weight) * parseInt(rolls)).toFixed(2);
    }
});

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
    machine_id: '',
    yarn_type: '',
    weight: '',
    remarks: '',
    yarns_used: [],
});

// ─── Additional Yarns (optional extras) ──────────────────────────────────────

const additionalYarnItems = ref([]);

const addYarnRow = () => {
    additionalYarnItems.value.push({ inventory_item_id: null, units_used: 1 });
};

const removeYarnRow = (index) => {
    additionalYarnItems.value.splice(index, 1);
};

const getYarnById = (id) => props.yarns.find(y => y.id == id);

const updateAvailableUnits = (row) => {
    const yarn = getYarnById(row.inventory_item_id);
    if (yarn && row.units_used > yarn.available_units) row.units_used = yarn.available_units;
    if (row.units_used < 1) row.units_used = 1;
};

// ─── Validation ───────────────────────────────────────────────────────────────

const mainYarnExceedsAvailable = computed(() => {
    if (!selectedMainYarn.value || !mainRollsUsed.value) return false;
    return parseInt(mainRollsUsed.value) > selectedMainYarn.value.available_units;
});

const hasInvalidAdditionalUnits = computed(() => {
    return additionalYarnItems.value.some(row => {
        if (!row.inventory_item_id) return false;
        const yarn = getYarnById(row.inventory_item_id);
        return !yarn || row.units_used > yarn.available_units || row.units_used < 1;
    });
});

const canSubmit = computed(() => {
    return (
        form.machine_id &&
        selectedMainYarnId.value &&
        mainRollsUsed.value >= 1 &&
        !mainYarnExceedsAvailable.value &&
        !hasInvalidAdditionalUnits.value &&
        form.weight &&
        !form.processing
    );
});

// ─── Submit ───────────────────────────────────────────────────────────────────

const submitFabric = () => {
    form.yarns_used = [
        {
            inventory_item_id: parseInt(selectedMainYarnId.value),
            units_used: parseInt(mainRollsUsed.value),
        },
        ...additionalYarnItems.value
            .filter(item => item.inventory_item_id && item.units_used >= 1)
            .map(item => ({
                inventory_item_id: parseInt(item.inventory_item_id),
                units_used: parseInt(item.units_used),
            })),
    ];

    form.post(route('man.staff.knitting-yarn.store-fabric'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            selectedMainYarnId.value = '';
            mainRollsUsed.value = '';
            additionalYarnItems.value = [];
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Knitting Yarn">
        <div class="p-6 max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Knitting Yarn Workspace</h1>
                <Link :href="route('man.staff.knitting-yarn.reports')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-bold">
                    View Reports
                </Link>
            </div>

            <!-- Job Orders & Recipes -->
            <div v-if="jobOrders.length" class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-3">Assigned Job Orders & Recipes</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="order in jobOrders" :key="order.id"
                         class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-200 dark:border-zinc-700 shadow-sm">
                        <div class="font-mono text-sm font-bold text-blue-700 dark:text-blue-400">{{ order.jo_number }}</div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                            <span class="font-medium">Color:</span> {{ order.color }} |
                            <span class="font-medium">Qty:</span> {{ order.quantity }}
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Yarn Type:</span> {{ order.yarn_type }}
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">Design:</span> {{ order.design }}
                        </div>
                        <div v-if="order.recipe" class="mt-3 border-t pt-2 text-sm">
                            <p class="font-medium text-gray-800 dark:text-gray-200">Recipe Materials:</p>
                            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400">
                                <li v-for="mat in order.recipe.materials" :key="mat.name">
                                    {{ mat.name }}: {{ mat.quantity }} {{ mat.unit }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-800/50">
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Record New Fabric</h2>
                            <p class="text-sm text-gray-500">Enter details from the knitting machine</p>
                        </div>

                        <form @submit.prevent="submitFabric" class="p-6 space-y-4">

                            <!-- Server errors -->
                            <div v-if="Object.keys(form.errors).length"
                                 class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-3 text-sm text-red-700 dark:text-red-300 space-y-1">
                                <p v-for="(error, key) in form.errors" :key="key">{{ error }}</p>
                            </div>

                            <!-- 1. Machine No. -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Machine No. <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.machine_id" required
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800">
                                    <option value="">Select Machine</option>
                                    <option v-for="machine in machines" :key="machine.id" :value="machine.id">
                                        {{ machine.machine_no }}
                                    </option>
                                </select>
                            </div>

                            <!-- 2 & 3. Yarn Type + Rolls Used (linked block) -->
                            <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-700 rounded-xl p-4 space-y-3">
                                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wide">
                                    Primary Yarn &amp; Consumption
                                </p>

                                <!-- 2. Yarn Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Yarn Type <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="selectedMainYarnId" required
                                        class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800">
                                        <option value="">Select Yarn Type</option>
                                        <option v-for="yarn in yarns" :key="yarn.id" :value="yarn.id">
                                            {{ yarn.material_name }} ({{ yarn.control_number }}) —
                                            {{ yarn.remaining_quantity }} kg, {{ yarn.available_units }} rolls left
                                        </option>
                                    </select>
                                </div>

                                <!-- Yarn info bar -->
                                <div v-if="selectedMainYarn"
                                     class="flex flex-wrap gap-3 text-xs text-gray-600 dark:text-gray-400 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-lg px-3 py-2">
                                    <span>📦 <strong>Lot:</strong> {{ selectedMainYarn.control_number }}</span>
                                    <span>⚖️ <strong>Remaining:</strong> {{ selectedMainYarn.remaining_quantity }} {{ selectedMainYarn.unit }}</span>
                                    <span>🧶 <strong>Available Rolls:</strong> {{ selectedMainYarn.available_units }}</span>
                                    <span v-if="selectedMainYarn.unit_weight">📏 <strong>Per Roll:</strong> {{ selectedMainYarn.unit_weight }} kg</span>
                                </div>

                                <!-- 3. Rolls Used -->
                                <div v-if="selectedMainYarn">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Rolls Used <span class="text-red-500">*</span>
                                        <span class="text-xs text-gray-400 font-normal ml-1">(max {{ selectedMainYarn.available_units }})</span>
                                    </label>
                                    <input
                                        v-model.number="mainRollsUsed"
                                        type="number"
                                        min="1"
                                        :max="selectedMainYarn.available_units"
                                        required
                                        class="w-full border rounded-lg p-2 bg-white dark:bg-zinc-800 text-sm"
                                        :class="mainYarnExceedsAvailable
                                            ? 'border-red-400 dark:border-red-500'
                                            : 'border-gray-300 dark:border-zinc-700'"
                                        placeholder="e.g. 30" />
                                    <p v-if="mainYarnExceedsAvailable" class="text-xs text-red-500 mt-1">
                                        ⚠ Exceeds available rolls — only {{ selectedMainYarn.available_units }} left.
                                    </p>
                                    <p v-else-if="mainRollsUsed >= 1" class="text-xs text-green-600 dark:text-green-400 mt-1">
                                        ✓ {{ selectedMainYarn.available_units - mainRollsUsed }} rolls will remain after saving.
                                    </p>
                                </div>
                            </div>

                            <!-- 4. Weight -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Weight (kg) <span class="text-red-500">*</span>
                                    <span v-if="selectedMainYarn?.unit_weight && mainRollsUsed >= 1"
                                          class="text-xs text-blue-500 font-normal ml-1">(auto-calculated)</span>
                                </label>
                                <input type="number" step="0.01" v-model="form.weight" required
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="0.00" />
                            </div>

                            <!-- Additional Yarns (optional) -->
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Additional Yarns Consumed
                                        <span class="text-xs text-gray-400 font-normal ml-1">(optional)</span>
                                    </label>
                                    <button type="button" @click="addYarnRow"
                                        class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                                        <Plus class="w-4 h-4" /> Add Yarn
                                    </button>
                                </div>
                                <div v-if="additionalYarnItems.length === 0" class="text-sm text-gray-400 italic">
                                    No additional yarns. Click "Add Yarn" if more lots were used.
                                </div>
                                <div v-for="(row, index) in additionalYarnItems" :key="index"
                                     class="flex flex-wrap gap-2 mb-3 items-start">
                                    <select v-model="row.inventory_item_id" @change="updateAvailableUnits(row)"
                                        class="flex-1 min-w-[200px] border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800 text-sm">
                                        <option value="">Select Yarn (Control ID)</option>
                                        <option v-for="yarn in yarns" :key="yarn.id" :value="yarn.id">
                                            {{ yarn.material_name }} ({{ yarn.control_number }}) — {{ yarn.available_units }} rolls left
                                        </option>
                                    </select>
                                    <div class="w-28">
                                        <input v-model.number="row.units_used" type="number" min="1"
                                            :max="getYarnById(row.inventory_item_id)?.available_units || 1"
                                            @input="updateAvailableUnits(row)"
                                            class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800 text-sm"
                                            placeholder="Rolls" />
                                    </div>
                                    <button type="button" @click="removeYarnRow(index)" class="text-red-500 hover:text-red-700 p-2">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                    <div v-if="row.inventory_item_id && row.units_used > (getYarnById(row.inventory_item_id)?.available_units || 0)"
                                         class="text-xs text-red-500 w-full">
                                        ⚠ Exceeds available rolls (max {{ getYarnById(row.inventory_item_id)?.available_units }})
                                    </div>
                                </div>
                            </div>

                            <!-- 5. Remarks -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Remarks</label>
                                <textarea v-model="form.remarks" rows="3"
                                    class="w-full border border-gray-300 dark:border-zinc-700 rounded-lg p-2 bg-white dark:bg-zinc-800"
                                    placeholder="Optional notes..."></textarea>
                            </div>

                            <!-- Submit -->
                            <div class="pt-2">
                                <button type="submit" :disabled="!canSubmit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-3 rounded-lg font-bold transition">
                                    {{ form.processing ? 'Recording...' : 'Record Fabric' }}
                                </button>
                                <p v-if="!selectedMainYarnId && form.machine_id" class="text-orange-500 text-xs mt-2">
                                    Please select a yarn type.
                                </p>
                                <p v-if="selectedMainYarnId && mainRollsUsed < 1" class="text-orange-500 text-xs mt-2">
                                    Please enter the number of rolls used.
                                </p>
                                <p v-if="mainYarnExceedsAvailable" class="text-red-500 text-xs mt-2">
                                    Rolls used exceeds available inventory.
                                </p>
                                <p v-if="hasInvalidAdditionalUnits" class="text-red-500 text-xs mt-2">
                                    One or more additional yarns exceed available rolls.
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="lg:col-span-1">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-800 p-6">
                        <Scissors class="w-8 h-8 text-blue-600 dark:text-blue-400 mb-3" />
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Knitting Instructions</h3>
                        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                            <li>① Select the machine currently in use</li>
                            <li>② Choose the primary yarn lot from the dropdown</li>
                            <li>③ Enter how many rolls are being consumed</li>
                            <li>④ Weight auto-fills based on rolls × unit weight</li>
                            <li>⑤ Optionally add more yarn lots consumed</li>
                            <li>⑥ Add remarks for any issues or notes</li>
                        </ul>
                        <div class="mt-4 pt-4 border-t border-blue-200 dark:border-blue-700">
                            <p class="text-xs text-gray-500">
                                Saving automatically deducts consumed rolls from the production inventory.
                                The fabric receives a unique code and is forwarded to the checker.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>