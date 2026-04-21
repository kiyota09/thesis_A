<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Package, Box, Layers, Edit, AlertCircle, CheckCircle2, Clock, Filter } from 'lucide-vue-next';

const props = defineProps({
    items: Array,
});

// ── Modal State ──────────────────────────────────────────────────────────
const showContainerModal = ref(false);
const selectedItem       = ref(null);
const containerForm      = ref({ total_units: '', unit_type: 'roll', unit_weight: '' });
const containerErrors    = ref({});

const consumeQuantities  = ref({});
const consumeErrors      = ref({});

// ── Department filter ────────────────────────────────────────────────────
const activeDept = ref('all');
const departments = computed(() => {
    const depts = [...new Set((props.items || []).map(i => i.department))];
    return depts.sort();
});

const filteredItems = computed(() => {
    if (activeDept.value === 'all') return props.items;
    return (props.items || []).filter(i => i.department === activeDept.value);
});

// ── Open Container Modal ─────────────────────────────────────────────────
const openContainerModal = (item) => {
    selectedItem.value  = item;
    containerErrors.value = {};
    containerForm.value = {
        total_units: item.total_units || '',
        unit_type:   item.unit_type   || 'roll',
        unit_weight: item.unit_weight || '',
    };
    showContainerModal.value = true;
};

const saveContainer = () => {
    containerErrors.value = {};
    router.post(route('man.inventory.container.update', selectedItem.value.id), containerForm.value, {
        preserveScroll: true,
        onSuccess: () => { showContainerModal.value = false; },
        onError:   (errors) => { containerErrors.value = errors; },
    });
};

// ── Consume ──────────────────────────────────────────────────────────────
const consumeUnits = (item) => {
    const units = consumeQuantities.value[item.id];
    if (!units || units <= 0) return;
    consumeErrors.value[item.id] = null;
    router.post(route('man.inventory.consume', item.id), { units_used: parseInt(units, 10) }, {
        preserveScroll: true,
        onSuccess: () => { consumeQuantities.value[item.id] = ''; },
        onError:   (errors) => { consumeErrors.value[item.id] = errors?.units_used; },
    });
};

// ── Helpers ──────────────────────────────────────────────────────────────
const getCategoryIcon = (category) => {
    switch ((category || '').toLowerCase()) {
        case 'yarn':     return Layers;
        case 'dye':      return Package;
        case 'supplies': return Box;
        default:         return Package;
    }
};

const deptColor = (dept) => {
    const map = {
        knitting:    'bg-blue-100 text-blue-700',
        dyeing:      'bg-purple-100 text-purple-700',
        maintenance: 'bg-amber-100 text-amber-700',
        packaging:   'bg-green-100 text-green-700',
    };
    return map[dept?.toLowerCase()] ?? 'bg-gray-100 text-gray-600';
};

const statusBadge = (status) => {
    if (status === 'available') return { icon: CheckCircle2, cls: 'text-green-500', label: 'Available' };
    if (status === 'partial')   return { icon: Clock,        cls: 'text-yellow-500', label: 'Partial'   };
    return                             { icon: AlertCircle,  cls: 'text-red-400',    label: 'Depleted'  };
};

const usagePercent = (item) => {
    if (!item.total_units) return 0;
    return Math.round((item.used_units / item.total_units) * 100);
};
</script>

<template>
    <AuthenticatedLayout title="Production Inventory">
        <div class="p-6 max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Production Inventory</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        Materials transferred from Warehouse — available for manufacturing use
                    </p>
                </div>
                <!-- Summary chips -->
                <div class="flex gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 dark:bg-zinc-800 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <Package class="w-3.5 h-3.5" />
                        {{ items.length }} item{{ items.length !== 1 ? 's' : '' }}
                    </span>
                </div>
            </div>

            <!-- Department tabs -->
            <div v-if="departments.length > 1" class="flex gap-2 mb-5 flex-wrap">
                <button
                    @click="activeDept = 'all'"
                    :class="[
                        'px-4 py-1.5 rounded-full text-xs font-semibold border transition',
                        activeDept === 'all'
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent'
                            : 'bg-white dark:bg-zinc-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-zinc-700 hover:border-gray-400',
                    ]">
                    All Departments
                </button>
                <button
                    v-for="dept in departments" :key="dept"
                    @click="activeDept = dept"
                    :class="[
                        'px-4 py-1.5 rounded-full text-xs font-semibold border transition capitalize',
                        activeDept === dept
                            ? 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 border-transparent'
                            : 'bg-white dark:bg-zinc-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-zinc-700 hover:border-gray-400',
                    ]">
                    {{ dept }}
                </button>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-zinc-800/60">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Material</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Control ID</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dept</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Container</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        <tr
                            v-for="item in filteredItems" :key="item.id"
                            class="hover:bg-gray-50/60 dark:hover:bg-zinc-800/40 transition-colors">

                            <!-- Material name -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <component :is="getCategoryIcon(item.category)" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                    <span class="font-medium text-gray-900 dark:text-white">{{ item.material_name }}</span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="px-5 py-4">
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-gray-300">
                                    {{ item.category }}
                                </span>
                            </td>

                            <!-- Control ID -->
                            <td class="px-5 py-4 font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ item.control_number }}
                            </td>

                            <!-- Department -->
                            <td class="px-5 py-4">
                                <span :class="['text-xs px-2.5 py-0.5 rounded-full font-semibold capitalize', deptColor(item.department)]">
                                    {{ item.department }}
                                </span>
                            </td>

                            <!-- Remaining stock -->
                            <td class="px-5 py-4">
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ item.remaining_quantity }} <span class="text-xs font-normal text-gray-500">{{ item.unit }}</span>
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">of {{ item.initial_quantity }} {{ item.unit }} initial</div>
                            </td>

                            <!-- Container details -->
                            <td class="px-5 py-4">
                                <div v-if="item.total_units" class="text-xs">
                                    <!-- Usage bar -->
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="flex-1 bg-gray-100 dark:bg-zinc-700 rounded-full h-1.5 w-24">
                                            <div
                                                class="h-1.5 rounded-full bg-blue-500 transition-all"
                                                :style="{ width: usagePercent(item) + '%' }">
                                            </div>
                                        </div>
                                        <span class="text-gray-500">{{ usagePercent(item) }}%</span>
                                    </div>
                                    <div class="text-gray-600 dark:text-gray-300">
                                        {{ item.used_units }} / {{ item.total_units }} {{ item.unit_type }}s used
                                    </div>
                                    <div v-if="item.unit_weight" class="text-gray-400">
                                        {{ item.unit_weight }} kg/{{ item.unit_type }}
                                    </div>
                                </div>
                                <button
                                    v-else
                                    @click="openContainerModal(item)"
                                    class="flex items-center gap-1 text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium hover:underline">
                                    <Edit class="w-3.5 h-3.5" />
                                    Open Container
                                </button>
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                <component
                                    :is="statusBadge(item.status).icon"
                                    :class="['w-4 h-4', statusBadge(item.status).cls]"
                                    :title="statusBadge(item.status).label" />
                            </td>

                            <!-- Actions: consume units -->
                            <td class="px-5 py-4">
                                <div v-if="item.total_units && item.status !== 'depleted'" class="flex flex-col gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <input
                                            type="number" min="1"
                                            :max="item.total_units - item.used_units"
                                            placeholder="Units"
                                            v-model.number="consumeQuantities[item.id]"
                                            class="w-20 border border-gray-200 dark:border-zinc-700 rounded-lg px-2 py-1 text-xs bg-white dark:bg-zinc-800"
                                        />
                                        <button
                                            @click="consumeUnits(item)"
                                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 rounded-lg text-xs font-semibold transition">
                                            Use
                                        </button>
                                    </div>
                                    <span v-if="consumeErrors[item.id]" class="text-red-500 text-xs">
                                        {{ consumeErrors[item.id] }}
                                    </span>
                                </div>
                                <span v-else-if="item.status === 'depleted'" class="text-gray-400 text-xs italic">Depleted</span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="filteredItems.length === 0">
                            <td colspan="8" class="text-center py-16">
                                <Package class="w-10 h-10 text-gray-200 dark:text-zinc-700 mx-auto mb-3" />
                                <p class="text-gray-400 dark:text-gray-500 text-sm font-medium">No materials in production inventory</p>
                                <p class="text-gray-300 dark:text-gray-600 text-xs mt-1">
                                    Release materials from the Warehouse Monitor to populate this list.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Open Container Modal -->
        <Teleport to="body">
            <div v-if="showContainerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl w-full max-w-md shadow-2xl border border-gray-100 dark:border-zinc-800">

                    <div class="px-6 py-5 border-b border-gray-100 dark:border-zinc-800">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Open Container
                        </h2>
                        <p class="text-sm text-gray-500 mt-0.5">{{ selectedItem?.material_name }}</p>
                    </div>

                    <form @submit.prevent="saveContainer" class="p-6 space-y-4">
                        <!-- Total units -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Total Units
                                <span class="text-gray-400 font-normal ml-1">
                                    (rolls for Yarn · boxes for Packaging · auto Kg for Dye/Supplies)
                                </span>
                            </label>
                            <input
                                v-model="containerForm.total_units"
                                type="number" min="1" required
                                class="w-full border border-gray-300 dark:border-zinc-700 rounded-xl p-3 dark:bg-zinc-800 text-sm"
                                placeholder="e.g. 24"
                            />
                            <p v-if="containerErrors.total_units" class="text-red-500 text-xs mt-1">{{ containerErrors.total_units }}</p>
                        </div>

                        <!-- Unit type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Unit Type</label>
                            <select
                                v-model="containerForm.unit_type" required
                                class="w-full border border-gray-300 dark:border-zinc-700 rounded-xl p-3 dark:bg-zinc-800 text-sm">
                                <option value="roll">Roll (for Yarn)</option>
                                <option value="box">Box (for Packaging)</option>
                            </select>
                        </div>

                        <!-- Weight per unit — Yarn only -->
                        <div v-if="selectedItem?.category?.toLowerCase() === 'yarn'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Weight per Roll <span class="text-gray-400 font-normal">(kg)</span>
                            </label>
                            <input
                                v-model="containerForm.unit_weight"
                                type="number" step="0.01" required
                                class="w-full border border-gray-300 dark:border-zinc-700 rounded-xl p-3 dark:bg-zinc-800 text-sm"
                                placeholder="e.g. 2.5"
                            />
                            <p v-if="containerErrors.unit_weight" class="text-red-500 text-xs mt-1">{{ containerErrors.unit_weight }}</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button" @click="showContainerModal = false"
                                class="px-4 py-2 text-sm border border-gray-200 dark:border-zinc-700 rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shadow-sm">
                                Save Container
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>