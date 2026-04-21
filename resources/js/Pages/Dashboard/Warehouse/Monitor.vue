<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Warehouse, Plus, Trash2, X, Edit2, Save, Grid, Layers, Package, Box,
    ArrowRight, Send, MapPin, LayoutList, AlertCircle, ChevronUp, ChevronDown,
    Eye, ClipboardList, Calendar, Hash, Info, CheckCircle, ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    warehouse: Object,
    sections: Array,
    unassignedStock: Array,
    auth: Object,
});

// ─── UI & Layout State ──────────────────────────────────────────────
const editMode = ref(false);
const rows = ref(props.warehouse?.grid_rows || 3);
const cols = ref(props.warehouse?.grid_cols || 3);
const sectionsList = ref([]);
const shelvesList = ref({});

// Modal States
const showSectionModal = ref(false);
const showShelfModal = ref(false);
const showDetailsModal = ref(false);
const showUseModal = ref(false);
const showConfirmModal = ref(false);

// Data States
const editingSection = ref(null);
const editingShelfSection = ref(null);
const newSectionName = ref('');
const newShelfNumber = ref('');
const selectedCell = ref(null);
const detailItem = ref(null);
const selectedStockItem = ref(null);
const useFormErrors = ref({});      // FIX: track backend validation errors for the use modal

const confirmConfig = ref({ title: '', message: '', confirmText: '', type: 'blue', action: null });
const materialForm = useForm({ quantity: 0, manufacturing_department: '' });

// ─── Initialization ──────────────────────────────────────────────────
const initData = () => {
    if (props.sections) {
        sectionsList.value = JSON.parse(JSON.stringify(props.sections));
        const shelves = {};
        sectionsList.value.forEach(section => {
            shelves[section.id] = section.shelves || [];
        });
        shelvesList.value = shelves;

        const maxR = sectionsList.value.length > 0 ? Math.max(...sectionsList.value.map(s => s.grid_row)) : 2;
        const maxC = sectionsList.value.length > 0 ? Math.max(...sectionsList.value.map(s => s.grid_col)) : 2;
        rows.value = Math.max(props.warehouse?.grid_rows || 3, maxR + 1);
        cols.value = Math.max(props.warehouse?.grid_cols || 3, maxC + 1);
    }
};

onMounted(() => initData());
watch(() => props.sections, () => initData(), { deep: true });

// ─── Grid Display Logic ──────────────────────────────────────────────
const gridTemplate = computed(() => ({
    gridTemplateRows: `repeat(${rows.value}, minmax(160px, auto))`,
    gridTemplateColumns: `repeat(${cols.value}, 1fr)`,
}));

const gridCells = computed(() => {
    const cells = [];
    for (let r = 0; r < rows.value; r++) {
        for (let c = 0; c < cols.value; c++) {
            const section = sectionsList.value.find(s => s.grid_row === r && s.grid_col === c);
            cells.push({ row: r, col: c, section: section || null });
        }
    }
    return cells;
});

// ─── DELETE LOGIC ────────────────────────────────────────────────────
const deleteSection = (sectionId) => {
    const section = sectionsList.value.find(s => s.id === sectionId);
    if (!section) return;

    const hasItems = (section.stock_items_no_shelf?.length > 0) ||
                     (section.shelves?.some(sh => sh.stock_items?.length > 0));

    if (hasItems) {
        triggerConfirm('Action Denied', `Sector contains items. Move them to unassigned first.`, 'Understood', 'amber', () => showConfirmModal.value = false);
        return;
    }

    triggerConfirm('Delete Box', 'Are you sure you want to remove this specific box?', 'Delete', 'red', () => {
        sectionsList.value = sectionsList.value.filter(s => s.id !== sectionId);
        showConfirmModal.value = false;
    });
};

// ─── Actions ──────────────────────────────────────────────────────────
const saveSection = () => {
    if (!newSectionName.value.trim()) return;
    if (editingSection.value) {
        const idx = sectionsList.value.findIndex(s => s.id === editingSection.value.id);
        if (idx !== -1) sectionsList.value[idx].name = newSectionName.value;
    } else {
        const newId = 'temp-' + Date.now();
        const newSec = {
            id: newId,
            name: newSectionName.value,
            grid_row: selectedCell.value.row,
            grid_col: selectedCell.value.col,
            shelves: [],
            stock_items_no_shelf: []
        };
        sectionsList.value.push(newSec);
        shelvesList.value[newId] = [];
    }
    showSectionModal.value = false;
};

const addShelf = () => {
    if (!newShelfNumber.value.trim()) return;
    const sid = editingShelfSection.value.id;
    if (!shelvesList.value[sid]) shelvesList.value[sid] = [];
    const newShelf = { id: 'ts-' + Date.now(), shelf_number: newShelfNumber.value.toUpperCase(), stock_items: [] };
    shelvesList.value[sid].push(newShelf);
    const sec = sectionsList.value.find(s => s.id === sid);
    if (sec) {
        sec.shelves = JSON.parse(JSON.stringify(shelvesList.value[sid]));
    }
    newShelfNumber.value = '';
};

const removeShelf = (sid, shid) => {
    const shelf = (shelvesList.value[sid] || []).find(s => s.id === shid);
    if (shelf?.stock_items?.length > 0) {
        triggerConfirm('Denied', 'Shelf contains items.', 'OK', 'amber', () => showConfirmModal.value = false);
        return;
    }
    shelvesList.value[sid] = shelvesList.value[sid].filter(s => s.id !== shid);
    const sec = sectionsList.value.find(s => s.id === sid);
    if (sec) {
        sec.shelves = JSON.parse(JSON.stringify(shelvesList.value[sid]));
    }
};

const saveLayout = () => {
    showConfirmModal.value = false;
    const finalSections = sectionsList.value.map(s => ({
        id: String(s.id).startsWith('temp') ? null : s.id,
        name: s.name,
        row: s.grid_row,
        col: s.grid_col,
        shelves: (shelvesList.value[s.id] || []).map(sh => ({
            id: String(sh.id).startsWith('ts') ? null : sh.id,
            shelf_number: sh.shelf_number
        }))
    }));

    router.post(route('warehouse.monitor.layout', props.warehouse.id), {
        grid_rows: rows.value,
        grid_cols: cols.value,
        sections: finalSections
    }, {
        onSuccess: () => { editMode.value = false; router.reload(); },
        preserveScroll: true
    });
};

const onDrop = (event, shelfId, section = null) => {
    event.preventDefault();
    const item = JSON.parse(event.dataTransfer.getData('text/plain'));
    router.post(route('warehouse.monitor.assign'), { stock_item_id: item.id, shelf_id: shelfId, section_id: section?.id }, { preserveScroll: true });
};

const triggerConfirm = (title, message, confirmText, type, action) => {
    confirmConfig.value = { title, message, confirmText, type, action };
    showConfirmModal.value = true;
};

const openDetailsModal = (item) => { detailItem.value = item; showDetailsModal.value = true; };

// ─── FIX: Reset form fields each time the Use modal opens ────────────
const openUseModal = (item) => {
    selectedStockItem.value = item;
    useFormErrors.value = {};
    // Always set quantity from the item and RESET department to force user selection
    materialForm.quantity = item.quantity;
    materialForm.manufacturing_department = '';   // ← was missing: caused silently re-using stale department
    showUseModal.value = true;
};

const submitUse = () => {
    // Client-side guard: department is mandatory
    if (!materialForm.manufacturing_department) {
        useFormErrors.value = { manufacturing_department: 'Please select a destination department.' };
        return;
    }
    useFormErrors.value = {};

    triggerConfirm(
        'Release Material',
        `Send ${materialForm.quantity} ${selectedStockItem.value?.unit} of "${selectedStockItem.value?.material?.name}" to ${materialForm.manufacturing_department}?`,
        'Confirm',
        'green',
        () => {
            showConfirmModal.value = false;
            materialForm.post(route('warehouse.monitor.use', selectedStockItem.value.id), {
                onSuccess: () => {
                    showUseModal.value = false;
                    router.reload();
                },
                onError: (errors) => {
                    // Surface backend validation errors back in the modal
                    useFormErrors.value = errors;
                    showUseModal.value = true; // keep modal open so user can fix
                },
            });
        }
    );
};

const tryReduceRows = () => {
    const hasItems = sectionsList.value.some(s => s.grid_row === rows.value - 1);
    if (hasItems) triggerConfirm('Restriction', `Row ${rows.value} is not empty.`, 'OK', 'amber', () => showConfirmModal.value = false);
    else if (rows.value > 1) rows.value--;
};

const tryReduceCols = () => {
    const hasItems = sectionsList.value.some(s => s.grid_col === cols.value - 1);
    if (hasItems) triggerConfirm('Restriction', `Column ${cols.value} is not empty.`, 'OK', 'amber', () => showConfirmModal.value = false);
    else if (cols.value > 1) cols.value--;
};

const dragStart = (e, item) => { e.dataTransfer.setData('text/plain', JSON.stringify(item)); e.dataTransfer.effectAllowed = 'move'; };
const allowDrop = (e) => e.preventDefault();
</script>

<template>
    <Head :title="`Monitor: ${warehouse.name}`" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-[1600px] mx-auto px-4 uppercase font-black tracking-tight">

            <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-[2rem] border shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 p-2.5 rounded-xl text-white shadow-lg">
                        <Warehouse class="w-6 h-6" />
                    </div>
                    <div>
                        <h1 class="text-xl text-slate-900">WAREHOUSE MONITOR</h1>
                        <p class="text-slate-400 text-[9px] tracking-[0.4em]">{{ warehouse.name }}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button v-if="!editMode" @click="editMode = true" class="px-6 py-3 bg-slate-900 text-white rounded-xl shadow-lg hover:bg-black transition-all flex items-center gap-2 text-[10px]"><Edit2 class="w-3.5 h-3.5" /> Edit</button>
                    <button v-else @click="triggerConfirm('Apply Layout', 'Save grid changes?', 'Save', 'blue', saveLayout)" class="px-6 py-3 bg-emerald-600 text-white rounded-xl shadow-lg hover:bg-emerald-700 transition-all flex items-center gap-2 text-[10px]"><Save class="w-3.5 h-3.5" /> Save</button>
                    <button v-if="editMode" @click="editMode = false" class="px-6 py-3 bg-slate-50 text-slate-400 rounded-xl text-[10px]">Cancel</button>
                </div>
            </div>

            <div v-if="editMode" class="mb-8 bg-blue-50 border-2 border-dashed border-blue-200 rounded-[2rem] p-8 flex gap-10">
                <div class="flex flex-col gap-2">
                    <span class="text-[10px] text-blue-500 uppercase tracking-widest">Rows</span>
                    <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border shadow-sm">
                        <button @click="tryReduceRows" class="p-2 hover:bg-slate-50"><ChevronDown class="w-5 h-5"/></button>
                        <span class="text-xl w-8 text-center text-slate-900">{{ rows }}</span>
                        <button @click="rows++" class="p-2 hover:bg-slate-50"><ChevronUp class="w-5 h-5"/></button>
                    </div>
                </div>
                <div class="flex flex-col gap-2">
                    <span class="text-[10px] text-blue-500 uppercase tracking-widest">Columns</span>
                    <div class="flex items-center gap-4 bg-white p-2 rounded-2xl border shadow-sm">
                        <button @click="tryReduceCols" class="p-2 hover:bg-slate-50"><ChevronDown class="w-5 h-5"/></button>
                        <span class="text-xl w-8 text-center">{{ cols }}</span>
                        <button @click="cols++" class="p-2 hover:bg-slate-50"><ChevronUp class="w-5 h-5"/></button>
                    </div>
                </div>
            </div>

            <div v-if="unassignedStock.length > 0" class="mb-8 bg-white rounded-[2rem] border p-8 shadow-sm">
                <h3 class="text-[10px] text-slate-400 tracking-widest mb-6 flex items-center gap-2"><Package class="w-4 h-4 text-blue-500" /> Incoming Queue</h3>
                <div class="flex flex-wrap gap-4">
                    <div v-for="item in unassignedStock" :key="item.id" draggable="true" @dragstart="dragStart($event, item)" class="bg-slate-50 border border-slate-200 rounded-[1.5rem] px-5 py-4 flex items-center gap-4 cursor-move hover:border-blue-400 transition-all shadow-sm">
                        <Box class="w-5 h-5 text-blue-500" />
                        <span class="text-sm tracking-tight text-slate-700 font-bold">{{ item.material.name }}</span>
                        <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[10px] font-black">{{ item.quantity }}{{ item.unit }}</span>
                        <button @click="openDetailsModal(item)" class="text-slate-300 hover:text-blue-600 transition"><Eye class="w-4 h-4" /></button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-[3.5rem] border border-slate-100 p-10 shadow-inner">
                <div class="grid gap-8" :style="gridTemplate">
                    <div v-for="cell in gridCells" :key="`${cell.row}-${cell.col}`"
                         @click="!cell.section && editMode ? (selectedCell=cell, showSectionModal=true) : null"
                         @drop="cell.section ? onDrop($event, null, cell.section) : null" @dragover="allowDrop"
                         class="relative border rounded-[3rem] transition-all min-h-[200px] overflow-hidden"
                         :class="[
                             cell.section ? 'bg-white shadow-xl border-2' : 'bg-slate-50/50 border-dashed border-slate-200',
                             !cell.section && editMode ? 'hover:border-blue-400 hover:bg-blue-50/30 cursor-pointer scale-[0.98]' : ''
                         ]">

                        <div v-if="cell.section" class="p-6 h-full flex flex-col">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-[10px] bg-slate-900 text-white px-4 py-1.5 rounded-xl border border-slate-200">{{ cell.section.name }}</span>
                                <div v-if="editMode" class="flex gap-1.5">
                                    <button @click.stop="editingSection=cell.section; newSectionName=cell.section.name; showSectionModal=true" class="p-2 hover:bg-blue-50 text-slate-400 rounded-xl transition"><Edit2 class="w-4 h-4" /></button>
                                    <button @click.stop="deleteSection(cell.section.id)" class="p-2 hover:bg-red-50 text-slate-400 rounded-xl transition"><Trash2 class="w-4 h-4" /></button>
                                    <button @click.stop="editingShelfSection=cell.section; showShelfModal=true" class="p-2 hover:bg-emerald-50 text-slate-400 rounded-xl transition"><Plus class="w-4 h-4" /></button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto space-y-4 custom-scroll pr-1">
                                <div v-if="cell.section.stock_items_no_shelf?.length" class="mb-4 space-y-2">
                                    <p class="text-[9px] text-amber-500 tracking-[0.2em] px-2 mb-1">Common Area</p>
                                    <div v-for="s in cell.section.stock_items_no_shelf" :key="s.id" draggable="true" @dragstart="dragStart($event, s)"
                                         class="flex justify-between items-center bg-amber-50/50 p-3 rounded-2xl border border-amber-100 cursor-move hover:border-amber-400">
                                        <div class="flex items-center gap-2 overflow-hidden">
                                            <button @click="openDetailsModal(s)" class="text-amber-400 hover:text-amber-600"><Eye class="w-3.5 h-3.5"/></button>
                                            <span class="text-[10px] truncate">{{ s.material.name }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] text-amber-600">{{ s.quantity }}{{ s.unit }}</span>
                                            <button @click.stop="openUseModal(s)" class="text-[9px] text-amber-700 bg-white border border-amber-200 px-3 py-1 rounded-lg">Use</button>
                                        </div>
                                    </div>
                                </div>

                                <div v-for="shelf in (shelvesList[cell.section.id] || [])" :key="shelf.id" @drop.stop="onDrop($event, shelf.id)" @dragover="allowDrop" class="bg-slate-50/50 p-4 rounded-[2rem] border border-slate-100 hover:border-blue-200 transition-colors">
                                    <div class="flex justify-between items-center mb-3">
                                        <span class="text-[10px] text-slate-400 tracking-widest uppercase font-bold">Shelf {{ shelf.shelf_number }}</span>
                                    </div>
                                    <div v-if="shelf.stock_items?.length" class="space-y-2">
                                        <div v-for="st in shelf.stock_items" :key="st.id" draggable="true" @dragstart="dragStart($event, st)"
                                             class="flex justify-between items-center bg-white p-3 rounded-xl border border-slate-100 shadow-sm cursor-move hover:border-blue-400">
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <button @click="openDetailsModal(st)" class="text-slate-300 hover:text-blue-500"><Eye class="w-3.5 h-3.5"/></button>
                                                <span class="text-[10px] truncate text-slate-700">{{ st.material.name }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] text-blue-600 font-bold">{{ st.quantity }}{{ st.unit }}</span>
                                                <button @click.stop="openUseModal(st)" class="text-[9px] text-emerald-600 font-black hover:underline">Use</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-[9px] text-slate-300 text-center py-4 tracking-widest border border-dashed border-slate-200 rounded-xl">Empty</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="h-full flex flex-col items-center justify-center gap-3 opacity-20 group-hover:opacity-100 transition-opacity">
                            <Box class="w-10 h-10 text-slate-400" />
                            <span class="text-[10px] tracking-[0.3em] text-slate-400">Available Zone</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">

            <!-- Confirm Modal -->
            <div v-if="showConfirmModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                <div class="bg-white rounded-[2.5rem] w-full max-w-[320px] overflow-hidden shadow-2xl border border-slate-50 animate-in zoom-in duration-200 p-8 text-center uppercase font-black">
                    <div :class="{'bg-blue-50 text-blue-600': confirmConfig.type === 'blue', 'bg-amber-50 text-amber-600': confirmConfig.type === 'amber', 'bg-red-50 text-red-600': confirmConfig.type === 'red', 'bg-emerald-50 text-emerald-600': confirmConfig.type === 'green'}" class="h-16 w-16 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <AlertCircle v-if="confirmConfig.type !== 'green' && confirmConfig.type !== 'blue'" class="w-8 h-8" />
                        <Info v-if="confirmConfig.type === 'blue'" class="w-8 h-8" />
                        <CheckCircle v-if="confirmConfig.type === 'green'" class="w-8 h-8" />
                    </div>
                    <h3 class="text-lg text-slate-900 mb-2 tracking-tighter">{{ confirmConfig.title }}</h3>
                    <p class="text-[10px] text-slate-400 leading-relaxed mb-8 px-2 tracking-tighter uppercase">{{ confirmConfig.message }}</p>
                    <div class="flex gap-2">
                        <button @click="showConfirmModal = false" class="flex-1 py-3 bg-slate-50 text-slate-400 rounded-xl text-[9px]">Cancel</button>
                        <button @click="confirmConfig.action" :class="{'bg-blue-600': confirmConfig.type === 'blue', 'bg-amber-600': confirmConfig.type === 'amber', 'bg-red-600': confirmConfig.type === 'red', 'bg-emerald-600': confirmConfig.type === 'green'}"
                                class="flex-1 py-3 text-white rounded-xl shadow-lg text-[9px]">{{ confirmConfig.confirmText }}</button>
                    </div>
                </div>
            </div>

            <!-- Details Modal -->
            <div v-if="showDetailsModal && detailItem" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-[360px] animate-in zoom-in duration-200 uppercase font-black p-8">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-blue-50 p-2 rounded-lg text-blue-600"><ClipboardList class="w-5 h-5" /></div>
                        <h3 class="text-base text-slate-900 tracking-tighter">Details</h3>
                    </div>
                    <div class="space-y-3 mb-8">
                        <div class="bg-slate-50 p-6 rounded-2xl text-center border border-slate-100 shadow-inner font-black uppercase">
                            <p class="text-[8px] text-blue-500 tracking-[0.5em] mb-2 uppercase">Description</p>
                            <p class="text-sm text-slate-900 tracking-tight">{{ detailItem.material.name }}</p>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-xl shadow-sm text-[10px]">
                            <span class="text-slate-400">Control ID</span>
                            <span class="text-blue-600 tracking-widest font-black">{{ detailItem.control_number }}</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-xl shadow-sm text-[10px]">
                            <span class="text-slate-400">Stock In</span>
                            <span class="text-slate-900">{{ new Date(detailItem.received_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                    <button @click="showDetailsModal = false" class="w-full py-4 bg-slate-900 text-white rounded-xl text-[10px]">Dismiss</button>
                </div>
            </div>

            <!-- Use / Floor Release Modal (FIXED) -->
            <div v-if="showUseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-[380px] overflow-hidden animate-in zoom-in duration-200 uppercase font-black p-8">
                    <h3 class="text-lg text-slate-900 tracking-tighter mb-2">Floor Release</h3>
                    <p class="text-[10px] text-slate-400 mb-6 tracking-normal normal-case">
                        Select the department that will receive this material.
                    </p>

                    <div class="bg-emerald-50 p-5 rounded-2xl text-center mb-6 border border-emerald-100">
                        <p class="text-sm text-emerald-900">{{ selectedStockItem?.material?.name }}</p>
                        <p class="text-[10px] text-emerald-600 mt-1">{{ selectedStockItem?.control_number }}</p>
                    </div>

                    <div class="space-y-4 font-black uppercase">
                        <!-- Quantity -->
                        <div>
                            <label class="text-[8px] text-slate-400 block mb-1">Quantity to Transfer</label>
                            <input
                                v-model="materialForm.quantity"
                                type="number" step="0.01"
                                class="w-full p-4 bg-slate-50 border-none rounded-xl font-black text-lg text-center"
                            />
                            <p v-if="useFormErrors.quantity" class="text-red-500 text-[9px] mt-1 normal-case tracking-normal">
                                {{ useFormErrors.quantity }}
                            </p>
                        </div>

                        <!-- Destination department — REQUIRED, always reset to empty on open -->
                        <div>
                            <label class="text-[8px] text-slate-400 block mb-1">
                                Destination Dept <span class="text-red-400">*</span>
                            </label>
                            <select
                                v-model="materialForm.manufacturing_department"
                                class="w-full appearance-none p-4 bg-slate-50 border-none rounded-xl text-xs text-center font-black uppercase"
                                :class="useFormErrors.manufacturing_department ? 'ring-2 ring-red-400' : ''">
                                <option value="">— Select Department —</option>
                                <option value="knitting">Knitting</option>
                                <option value="dyeing">Dyeing</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="packaging">Packaging</option>
                            </select>
                            <p v-if="useFormErrors.manufacturing_department" class="text-red-500 text-[9px] mt-1 normal-case tracking-normal">
                                {{ useFormErrors.manufacturing_department }}
                            </p>
                        </div>

                        <!-- General backend error (e.g. material not found) -->
                        <p v-if="useFormErrors.error" class="text-red-500 text-[9px] normal-case tracking-normal bg-red-50 rounded-xl p-3">
                            {{ useFormErrors.error }}
                        </p>
                    </div>

                    <div class="flex gap-2 mt-8">
                        <button @click="showUseModal = false" class="flex-1 py-4 bg-slate-50 text-[10px] rounded-xl">Cancel</button>
                        <button
                            @click="submitUse"
                            :disabled="materialForm.processing"
                            class="flex-[2] py-4 bg-emerald-600 text-white rounded-xl text-[10px] shadow-lg disabled:opacity-50">
                            {{ materialForm.processing ? 'Releasing...' : 'Release to Production' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shelf Config Modal -->
            <div v-if="showShelfModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-50 w-full max-w-[380px] overflow-hidden animate-in zoom-in duration-200 uppercase font-black p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base text-slate-900 tracking-tighter">Storage Config</h3>
                        <button @click="showShelfModal = false" class="text-slate-300 hover:text-slate-900"><X class="w-5 h-5" /></button>
                    </div>
                    <div class="space-y-2 max-h-[220px] overflow-y-auto mb-6 pr-1 custom-scroll">
                        <div v-for="shelf in (shelvesList[editingShelfSection?.id] || [])" :key="shelf.id"
                             class="flex items-center justify-between p-4 bg-slate-50/50 border border-slate-100 rounded-xl">
                            <span class="text-[9px] tracking-widest text-slate-900 font-black">SHELF {{ shelf.shelf_number }}</span>
                            <button @click="removeShelf(editingShelfSection.id, shelf.id)" class="p-1.5 text-slate-300 hover:text-red-500 transition-all"><Trash2 class="w-4 h-4" /></button>
                        </div>
                    </div>
                    <div class="flex gap-2 mb-6 p-2 bg-slate-100 rounded-2xl shadow-inner">
                        <input v-model="newShelfNumber" @keyup.enter="addShelf" type="text" placeholder="ID" class="flex-1 px-4 py-3 bg-white border-none rounded-xl text-xs font-black" />
                        <button @click="addShelf" class="px-5 py-3 bg-blue-600 text-white rounded-xl text-[10px]">Add</button>
                    </div>
                    <button @click="showShelfModal = false" class="w-full py-4 bg-slate-900 text-white rounded-xl text-[10px]">Done</button>
                </div>
            </div>

            <!-- Section Name Modal -->
            <div v-if="showSectionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm animate-in fade-in duration-300">
                <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-[340px] p-8 text-center animate-in zoom-in duration-200 uppercase font-black">
                    <div class="h-14 w-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-inner"><MapPin class="w-6 h-6" /></div>
                    <h3 class="text-lg text-slate-900 tracking-tighter mb-2">{{ editingSection ? 'Edit' : 'New' }} Sector</h3>
                    <input v-model="newSectionName" placeholder="Sector label" class="w-full p-4 bg-slate-50 border-none rounded-xl shadow-inner focus:ring-2 focus:ring-blue-500/10 transition font-black text-center text-sm uppercase mb-6" />
                    <div class="flex gap-2">
                        <button @click="showSectionModal = false" class="flex-1 py-4 bg-slate-100 text-slate-400 rounded-xl text-[10px]">Cancel</button>
                        <button @click="saveSection" class="flex-[2] py-4 bg-blue-600 text-white rounded-xl text-[10px] shadow-lg">Confirm</button>
                    </div>
                </div>
            </div>

        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.grid { display: grid; user-select: none; }
.custom-scroll::-webkit-scrollbar { width: 5px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 20px; }

@keyframes zoom-in { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
.animate-in { animation-fill-mode: both; }
.zoom-in { animation: zoom-in 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
</style>