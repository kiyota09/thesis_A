<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { 
    Layers, Package, Clock, Hash, 
    Send, CheckCircle2, AlertCircle 
} from 'lucide-vue-next';

const props = defineProps({ 
    procurementRequests: { type: Array, default: () => [] } 
});

// --- BUNDLING LOGIC (Aggressive Grouping) ---
const bundledRequests = computed(() => {
    const groups = {};
    
    props.procurementRequests.forEach(req => {
        // Group by batch_number first, fallback to timestamp if null (old data)
        const key = req.batch_number || req.created_at;

        if (!groups[key]) {
            groups[key] = {
                batchID: key,
                urgency: req.urgency,
                requested_by: req.requested_by || 'Inventory Dept',
                time: req.created_at,
                // Check if this is a real batch from the new system
                isRealBatch: req.batch_number && req.batch_number.startsWith('BATCH'),
                items: [] 
            };
        }
        groups[key].items.push(req);
    });
    
    // Sort Newest First
    return Object.values(groups).sort((a, b) => new Date(b.time) - new Date(a.time));
});

// --- INDIVIDUAL BUTTON TRACKING ---
// We store the IDs of batches currently in flight
const processingBatches = ref([]);

const handleSendAction = (batchID) => {
    // If this specific batch is already processing, ignore click
    if (processingBatches.value.includes(batchID)) return;

    // Add to loading list
    processingBatches.value.push(batchID);

    // Using the exact route name from your web.php (scm. + scm.procurement-order.send-bundle)
    router.post(route('scm.scm.procurement-order.send-bundle'), {
        batch_number: batchID
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Filter out the ID once done
            processingBatches.value = processingBatches.value.filter(id => id !== batchID);
        },
        onError: () => {
            processingBatches.value = processingBatches.value.filter(id => id !== batchID);
        },
        onFinish: () => {
            processingBatches.value = processingBatches.value.filter(id => id !== batchID);
        }
    });
};

const formatTime = (timeStr) => {
    const date = new Date(timeStr);
    return date.toLocaleString('en-PH', { 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit' 
    });
};
</script>

<template>
    <Head title="Procurement Queue" />
    <AuthenticatedLayout>
        <div class="max-w-7xl mx-auto p-6 py-10">
            
            <div class="mb-12 flex justify-between items-end">
                <div>
                    <h1 class="text-4xl font-black text-slate-900 uppercase tracking-tighter italic flex items-center gap-4">
                        <Layers class="size-10 text-blue-600" />
                        Procurement <span class="text-blue-600 font-light">Queue</span>
                    </h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-2 italic">
                        Verified Batch-Grouping System Active
                    </p>
                </div>
            </div>

            <div v-if="bundledRequests.length === 0" class="bg-white rounded-[3rem] border border-dashed border-slate-200 p-24 text-center">
                <ClipboardList class="size-20 mx-auto mb-6 text-slate-100" />
                <p class="font-black uppercase tracking-[0.2em] text-xs text-slate-300">No pending requests</p>
            </div>

            <div class="grid gap-10">
                <div v-for="bundle in bundledRequests" :key="bundle.batchID" 
                    class="bg-white rounded-[2.5rem] border border-slate-100 shadow-2xl overflow-hidden transition-all hover:border-blue-400">
                    
                    <div class="bg-slate-950 px-10 py-5 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-5">
                            <span :class="bundle.urgency === 'High' ? 'bg-rose-600' : 'bg-blue-600'" 
                                  class="px-4 py-1 rounded-full text-white text-[10px] font-black uppercase tracking-widest shadow-lg">
                                {{ bundle.urgency }} Priority
                            </span>
                            <div class="flex items-center gap-2 text-slate-500 font-mono text-xs font-bold">
                                <Hash class="size-3 text-blue-500" />
                                {{ bundle.batchID }}
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <span class="text-[10px] font-black text-slate-600 uppercase italic">{{ formatTime(bundle.time) }}</span>
                            <span class="text-slate-800 font-black text-[10px] uppercase tracking-widest">{{ bundle.requested_by }}</span>
                        </div>
                    </div>

                    <div class="p-10">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                            <div v-for="item in bundle.items" :key="item.id" 
                                class="flex justify-between items-center p-6 bg-slate-50 rounded-[2rem] border border-slate-100 hover:bg-white hover:border-blue-200 transition-all group">
                                <div class="flex items-center gap-4">
                                    <Package class="size-5 text-slate-300 group-hover:text-blue-600 transition-colors" />
                                    <div>
                                        <p class="font-black text-slate-900 uppercase text-xs">{{ item.material_name }}</p>
                                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">{{ item.req_number }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-xl font-black text-slate-900 leading-none">{{ item.required_qty }}</p>
                                    <p class="text-[9px] font-black text-blue-600 uppercase">{{ item.unit }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-8 border-t border-slate-100">
                            <div class="flex items-center gap-3 text-[10px] font-black text-slate-400 uppercase tracking-widest italic">
                                <CheckCircle2 class="size-4 text-emerald-500" v-if="bundle.items.length > 1" />
                                Total Items: {{ bundle.items.length }}
                            </div>

                            <button @click="handleSendAction(bundle.batchID)" 
                                :disabled="processingBatches.includes(bundle.batchID)"
                                class="px-12 py-5 bg-slate-950 text-white rounded-2xl text-[11px] font-black uppercase tracking-[0.2em] hover:bg-blue-600 transition-all shadow-xl active:scale-95 disabled:opacity-50 flex items-center gap-3">
                                <span v-if="processingBatches.includes(bundle.batchID)" class="size-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <Send v-else class="size-4" />
                                {{ processingBatches.includes(bundle.batchID) ? 'Forwarding Batch...' : 'Approve & Dispatch Batch' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
* { font-style: normal !important; }
</style>