<template>
    <Head title="Inquiries - ECO Module" />
    <AuthenticatedLayout>
        <div class="min-h-screen bg-[#F8FAFC] dark:bg-slate-950 text-slate-900 dark:text-slate-50">
            <div class="max-w-[1400px] mx-auto p-4 md:p-10 lg:p-16 space-y-12">
                
                <header class="flex flex-col md:flex-row md:items-end justify-between gap-8">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="h-[2px] w-12 bg-blue-600"></span>
                            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600">Workspace</span>
                        </div>
                        <h1 class="text-4xl md:text-5xl font-extralight tracking-tight">
                            Client <span class="font-black text-black dark:text-white">Inquiries</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 text-sm md:text-base max-w-md leading-relaxed">
                            Monitor and manage incoming requests with precision and speed.
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <button @click="refreshData" 
                            class="group p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-blue-500 transition-all duration-300 shadow-sm hover:shadow-blue-500/10">
                            <RefreshCw class="h-5 w-5 text-slate-400 group-hover:text-blue-600 group-hover:rotate-180 transition-all duration-500" />
                        </button>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div v-for="(stat, index) in stats" :key="index" 
                        class="relative bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden group hover:shadow-xl transition-all duration-500">
                        <div :class="`absolute top-0 left-0 w-full h-1 opacity-20 ${stat.colorClass}`"></div>
                        
                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-4">{{ stat.label }}</p>
                                <p class="text-4xl font-black tracking-tighter">{{ stat.value }}</p>
                            </div>
                            <div :class="`p-3 rounded-2xl bg-opacity-10 ${stat.colorClass} ${stat.textColor}`">
                                <component :is="stat.icon" class="h-6 w-6" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-2xl shadow-slate-200/50 dark:shadow-none overflow-hidden">
                    
                    <div class="p-6 md:p-10 flex flex-col lg:flex-row gap-6 items-center">
                        <div class="relative w-full group">
                            <Search class="absolute left-5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-300 group-focus-within:text-blue-600 transition-colors" />
                            <input v-model="searchTerm" type="text" placeholder="Search by company or product..."
                                class="w-full pl-12 pr-6 py-4 rounded-2xl border-slate-100 dark:border-slate-800 dark:bg-slate-950 text-sm focus:ring-4 focus:ring-blue-500/5 focus:border-blue-500 transition-all placeholder:text-slate-300">
                        </div>
                        
                        <div class="flex w-full lg:w-auto gap-4">
                            <select v-model="statusFilter"
                                class="w-full lg:w-48 px-5 py-4 rounded-2xl border-slate-100 dark:border-slate-800 dark:bg-slate-950 text-xs font-bold uppercase tracking-widest text-slate-600 focus:ring-4 focus:ring-blue-500/5 border-r-8 border-r-transparent transition-all cursor-pointer">
                                <option value="all text-black">Filter: All</option>
                                <option value="open">Pending</option>
                                <option value="quotation_sent">Sent</option>
                                <option value="converted">Finalized</option>
                            </select>
                        </div>
                    </div>

                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-y border-slate-50 dark:border-slate-800">
                                    <th class="px-10 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-left">Company Details</th>
                                    <th class="px-10 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-left">Message Snippet</th>
                                    <th class="px-10 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-left">Status</th>
                                    <th class="px-10 py-6 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 text-left">Last Activity</th>
                                    <th class="px-10 py-6 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                                <tr v-for="inquiry in filteredInquiries" :key="inquiry.id" 
                                    class="group hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-all">
                                    <td class="px-10 py-8">
                                        <div class="flex items-center gap-5">
                                            <div class="relative h-12 w-12 rounded-2xl flex items-center justify-center bg-black text-white dark:bg-white dark:text-black shadow-lg shadow-black/5 transition-transform group-hover:scale-110">
                                                <Layers v-if="isBulkInquiry(inquiry)" class="h-5 w-5" />
                                                <Building2 v-else class="h-5 w-5" />
                                            </div>
                                            <div>
                                                <p class="text-sm font-black text-black dark:text-white">{{ inquiry.client?.company_name }}</p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span v-if="!isBulkInquiry(inquiry)" class="text-[11px] font-medium text-slate-400">
                                                        {{ inquiry.product?.name }}
                                                    </span>
                                                    <span v-else class="flex items-center gap-1.5 px-2 py-0.5 bg-yellow-400 text-black text-[9px] font-black uppercase rounded-md">
                                                        <span class="h-1 w-1 rounded-full bg-black"></span>
                                                        {{ getBulkProductCount(inquiry.initial_message) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-10 py-8">
                                        <p class="text-xs text-slate-500 max-w-xs line-clamp-1 italic font-serif">"{{ inquiry.initial_message || 'Empty inquiry content...' }}"</p>
                                    </td>
                                    <td class="px-10 py-8">
                                        <span :class="statusBadge(inquiry.status)" 
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-tighter">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            {{ inquiry.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-10 py-8 text-xs font-bold text-slate-400 tracking-tight">
                                        {{ formatDate(inquiry.last_message_at) }}
                                    </td>
                                    <td class="px-10 py-8 text-right">
                                        <Link :href="route('eco.inquiry.show', inquiry.id)"
                                            class="inline-flex items-center justify-center h-10 w-10 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-black dark:text-white hover:bg-black hover:text-white dark:hover:bg-white dark:hover:text-black transition-all group/btn">
                                            <ArrowRight class="h-4 w-4 group-hover/btn:translate-x-1 transition-transform" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
                        <div v-for="inquiry in filteredInquiries" :key="inquiry.id" class="p-6 space-y-6">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 rounded-2xl bg-slate-950 flex items-center justify-center text-white">
                                        <Building2 class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-black">{{ inquiry.client?.company_name }}</p>
                                        <p class="text-[10px] text-slate-400 uppercase tracking-widest font-bold">{{ formatDate(inquiry.last_message_at) }}</p>
                                    </div>
                                </div>
                                <span :class="statusBadge(inquiry.status)" class="px-3 py-1 rounded-full text-[9px] font-black uppercase">
                                    {{ inquiry.status }}
                                </span>
                            </div>
                            
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <p class="text-xs text-slate-500 leading-relaxed italic">"{{ inquiry.initial_message }}"</p>
                            </div>

                            <Link :href="route('eco.inquiry.show', inquiry.id)"
                                class="w-full flex items-center justify-center gap-3 py-4 bg-black dark:bg-white dark:text-black text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-black/10 active:scale-[0.98] transition-all">
                                Open Inquiry
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>

                    <div v-if="filteredInquiries.length === 0" class="py-32 text-center animate-in fade-in zoom-in duration-700">
                        <div class="inline-flex items-center justify-center h-24 w-24 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-200 mb-6">
                            <MessageSquare class="h-10 w-10" />
                        </div>
                        <h3 class="text-xl font-light text-slate-900 dark:text-white">Nothing to show</h3>
                        <p class="text-sm text-slate-400 mt-2 italic font-serif">Adjust your search or filter to find inquiries.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    MessageSquare, RefreshCw, Search, Building2, 
    ArrowRight, Layers, Clock, Send, CheckCircle2 
} from 'lucide-vue-next';

const props = defineProps({
    inquiries: {
        type: Array,
        default: () => []
    }
});

const searchTerm = ref('');
const statusFilter = ref('all');

// Stats configuration with icon support
const stats = computed(() => [
    { 
        label: 'In Queue', 
        value: openCount.value, 
        colorClass: 'bg-blue-600', 
        textColor: 'text-blue-600',
        icon: Clock 
    },
    { 
        label: 'Active Proposals', 
        value: quotationSentCount.value, 
        colorClass: 'bg-amber-400', 
        textColor: 'text-amber-500',
        icon: Send 
    },
    { 
        label: 'Closed Deals', 
        value: convertedCount.value, 
        colorClass: 'bg-slate-950', 
        textColor: 'text-slate-900 dark:text-white',
        icon: CheckCircle2 
    }
]);

const filteredInquiries = computed(() => {
    let list = props.inquiries;
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        list = list.filter(i =>
            i.client?.company_name?.toLowerCase().includes(term) ||
            i.product?.name?.toLowerCase().includes(term)
        );
    }
    if (statusFilter.value !== 'all') {
        list = list.filter(i => i.status === statusFilter.value);
    }
    return list;
});

const openCount = computed(() => props.inquiries.filter(i => i.status === 'open').length);
const quotationSentCount = computed(() => props.inquiries.filter(i => i.status === 'quotation_sent').length);
const convertedCount = computed(() => props.inquiries.filter(i => i.status === 'converted').length);

const statusBadge = (status) => {
    const map = {
        open: 'bg-blue-50 text-blue-600',
        quotation_sent: 'bg-amber-50 text-amber-600',
        converted: 'bg-black text-white dark:bg-white dark:text-black',
        abandoned: 'bg-slate-100 text-slate-400'
    };
    return map[status] || 'bg-slate-100 text-slate-500';
};

const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { 
        month: 'short', 
        day: 'numeric',
        year: 'numeric'
    });
};

const isBulkInquiry = (inquiry) => inquiry.product?.name === 'Bulk Inquiry' || !inquiry.product;

const getBulkProductCount = (message) => {
    if (!message) return 'Bulk';
    const match = message.match(/BULK PRODUCT INQUIRY\s*[—-]\s*(\d+)\s*Product/i);
    return match ? `${match[1]} Products` : 'Multi-Item';
};

const refreshData = () => {
    router.reload({ 
        only: ['inquiries'],
        onStart: () => {}, // Add loading states if needed
    });
};
</script>

<style scoped>
/* Smooth transitions for hover effects */
.transition-all {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom Line Clamping */
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Custom shadow for white cards on gray background */
.shadow-sm {
    shadow-color: rgba(15, 23, 42, 0.05);
}

/* Hide scrollbar but allow scrolling */
.overflow-x-auto {
    scrollbar-width: none;
}
.overflow-x-auto::-webkit-scrollbar {
    display: none;
}
</style>