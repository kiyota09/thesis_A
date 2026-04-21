<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Plus, Calendar, X, CheckCircle2, AlertCircle, AlertTriangle,
    MessageSquare, Video, MapPin, FileText, Upload, ChevronDown, ChevronUp,
    ArrowUpRight, Search, MoveRight, Check, XCircle, UserCheck, Building2,
    Trash2, Info, Bell
} from 'lucide-vue-next';

const props = defineProps({
    leads: Array,
    permissions: { type: Object, default: () => ({}) },
    flash: { type: Object, default: () => ({}) }
});

const canEdit = computed(() => props.permissions?.leads === 'edit');

// ─────────────────────────────────────────────────
// Toast Notification System
// ─────────────────────────────────────────────────
const toast = ref({
    show: false,
    type: 'success', // success, error, warning, info
    title: '',
    message: '',
});

const showToast = (type, title, message) => {
    toast.value = {
        show: true,
        type,
        title,
        message,
    };
    setTimeout(() => {
        toast.value.show = false;
    }, 5000);
};

const hideToast = () => {
    toast.value.show = false;
};

// ─────────────────────────────────────────────────
// Confirmation Modal System
// ─────────────────────────────────────────────────
const confirmModal = ref({
    show: false,
    type: 'warning', // warning, danger, info
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    onConfirm: null,
    isLoading: false,
});

const showConfirm = (options) => {
    confirmModal.value = {
        show: true,
        type: options.type || 'warning',
        title: options.title || 'Are you sure?',
        message: options.message || 'This action cannot be undone.',
        confirmText: options.confirmText || 'Confirm',
        cancelText: options.cancelText || 'Cancel',
        onConfirm: options.onConfirm,
        isLoading: false,
    };
};

const hideConfirm = () => {
    confirmModal.value.show = false;
    confirmModal.value.onConfirm = null;
    confirmModal.value.isLoading = false;
};

const handleConfirm = async () => {
    if (confirmModal.value.onConfirm) {
        confirmModal.value.isLoading = true;
        try {
            await confirmModal.value.onConfirm();
        } catch (error) {
            console.error('Confirmation action failed:', error);
        }
    }
    hideConfirm();
};

// ─────────────────────────────────────────────────
// Tab State & Lead Grouping
// ─────────────────────────────────────────────────
const activeTab = ref('Inquiry');
const searchQuery = ref('');

const stages = [
    { status: 'Inquiry',      label: 'New Inquiry',   icon: MessageSquare },
    { status: 'Negotiation',  label: 'Negotiation',   icon: Video },
    { status: 'Approval Sent',label: 'Approval Sent', icon: FileText },
    { status: 'Closed-Won',   label: 'Closed-Won',    icon: CheckCircle2 },
];

const leadsByStatus = computed(() => {
    const groups = {};
    stages.forEach(s => groups[s.status] = []);
    (props.leads || []).forEach(lead => {
        if (groups[lead.status] !== undefined) groups[lead.status].push(lead);
    });
    for (const status in groups) {
        groups[status].sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
    }
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        for (const status in groups) {
            groups[status] = groups[status].filter(lead =>
                lead.company_name.toLowerCase().includes(query) ||
                lead.contact_person.toLowerCase().includes(query) ||
                lead.email.toLowerCase().includes(query)
            );
        }
    }
    return groups;
});

const currentLeads = computed(() => leadsByStatus.value[activeTab.value] || []);

// ─────────────────────────────────────────────────
// Modal & Form State
// ─────────────────────────────────────────────────
const showCreateModal           = ref(false);
const showClientConversionModal = ref(false);
const showNoteModal              = ref(false);
const showInterviewModal        = ref(false);
const showFinalizeModal         = ref(false);
const currentLead                = ref(null);
const pendingMoveNextStage      = ref(null);

const finalizeFile   = ref(null);
const finalizeAction = ref(null);
const rejectReason   = ref('');
const isUploading    = ref(false);

// Validation errors state
const formErrors = ref({});
const conversionErrors = ref({});

const form = useForm({
    company_name: '', contact_person: '', email: '', phone: '',
});

const conversionForm = useForm({
    lead_id: null, company_name: '', contact_person: '', email: '', phone: '',
    business_type: 'wholesaler', tin_number: '', company_address: '', password: 'password123',
});

const noteForm      = useForm({ note: '' });
const interviewForm = useForm({ scheduled_at: '', location: '', notes: '' });
const rejectForm    = useForm({ reject_reason: '' });

// ─────────────────────────────────────────────────
// Validation Helpers
// ─────────────────────────────────────────────────
const validatePhone = (phone) => {
    // Validates that the input consists of exactly 11 digits
    const phoneRegex = /^\d{11}$/;
    return phoneRegex.test(phone);
};

const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
};

const validateForm = () => {
    const errors = {};
    
    if (!form.company_name.trim()) {
        errors.company_name = 'Company name is required';
    } else if (form.company_name.length < 2) {
        errors.company_name = 'Company name must be at least 2 characters';
    }
    
    if (!form.contact_person.trim()) {
        errors.contact_person = 'Contact person is required';
    }
    
    if (!form.email.trim()) {
        errors.email = 'Email is required';
    } else if (!validateEmail(form.email)) {
        errors.email = 'Please enter a valid email address';
    }
    
    if (!form.phone.trim()) {
        errors.phone = 'Phone number is required';
    } else if (!validatePhone(form.phone)) {
        errors.phone = 'Phone number must be exactly 11 digits (e.g., 09123456789)';
    }
    
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const validateConversionForm = () => {
    const errors = {};
    
    if (!conversionForm.tin_number.trim()) {
        errors.tin_number = 'TIN number is required';
    }
    
    if (!conversionForm.company_address.trim()) {
        errors.company_address = 'Company address is required';
    }
    
    conversionErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// ─────────────────────────────────────────────────
// Stage Movement
// ─────────────────────────────────────────────────
const getNextStage = (currentStatus) => {
    const order = ['Inquiry', 'Negotiation', 'Approval Sent', 'Closed-Won'];
    const idx = order.indexOf(currentStatus);
    return (idx === -1 || idx === order.length - 1) ? null : order[idx + 1];
};

const openMoveConfirm = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to move leads.');
        return;
    }
    currentLead.value = lead;
    pendingMoveNextStage.value = getNextStage(lead.status);
    
    showConfirm({
        type: 'info',
        title: 'Confirm Stage Change',
        message: `Move "${lead.company_name}" from ${lead.status} to ${pendingMoveNextStage.value}?`,
        confirmText: 'Move Lead',
        onConfirm: () => confirmMove()
    });
};

const confirmMove = () => {
    if (!currentLead.value || !pendingMoveNextStage.value) return;
    
    router.patch(route('crm.lead.status', currentLead.value.id), { 
        status: pendingMoveNextStage.value 
    }, {
        preserveScroll: true,
        onSuccess: () => {
            currentLead.value = null;
            pendingMoveNextStage.value = null;
            showToast('success', 'Success', 'Lead moved successfully.');
            router.reload({ only: ['leads'] });
        },
        onError: (errors) => {
            showToast('error', 'Error', Object.values(errors).join(', '));
        }
    });
};

// ─────────────────────────────────────────────────
// Delete Lead
// ─────────────────────────────────────────────────
const openDeleteConfirm = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to delete leads.');
        return;
    }
    currentLead.value = lead;
    
    showConfirm({
        type: 'danger',
        title: 'Delete Lead',
        message: `Are you sure you want to permanently delete "${lead.company_name}"? This action cannot be undone.`,
        confirmText: 'Delete Permanently',
        onConfirm: () => confirmDelete()
    });
};

const confirmDelete = () => {
    if (!currentLead.value) return;
    
    router.delete(route('crm.lead.destroy', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            const leadName = currentLead.value.company_name;
            currentLead.value = null;
            showToast('success', 'Success', `Lead "${leadName}" has been deleted.`);
            router.reload({ only: ['leads'] });
        },
        onError: (errors) => {
            showToast('error', 'Error', Object.values(errors).join(', '));
        }
    });
};

// ─────────────────────────────────────────────────
// Finalization
// ─────────────────────────────────────────────────
const openFinalizeModal = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to finalize leads.');
        return;
    }
    currentLead.value = lead;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
    showFinalizeModal.value = true;
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) { 
            showToast('error', 'File Too Large', 'File size must be less than 5MB.');
            return; 
        }
        finalizeFile.value = file;
    }
};

const uploadFinalizeFile = () => {
    if (!finalizeFile.value) return;
    isUploading.value = true;
    const formData = new FormData();
    formData.append('file', finalizeFile.value);
    router.post(route('crm.lead.upload-file', currentLead.value.id), formData, {
        preserveScroll: true,
        onSuccess: () => { 
            finalizeFile.value = null; 
            isUploading.value = false;
            showToast('success', 'Success', 'File uploaded successfully.');
            router.reload({ only: ['leads'] }); 
        },
        onError: (errors) => { 
            isUploading.value = false; 
            showToast('error', 'Upload Failed', Object.values(errors).join(', '));
        }
    });
};

const submitFinalize = () => {
    if (!finalizeAction.value) {
        showToast('warning', 'Action Required', 'Please select a decision (Accept or Reject).');
        return;
    }
    
    if (finalizeAction.value === 'accept') {
        showConfirm({
            type: 'success',
            title: 'Accept Lead',
            message: `Accept "${currentLead.value.company_name}" and move to Closed-Won?`,
            confirmText: 'Accept Lead',
            onConfirm: () => {
                router.post(route('crm.lead.accept', currentLead.value.id), {}, {
                    preserveScroll: true,
                    onSuccess: () => { 
                        showFinalizeModal.value = false; 
                        currentLead.value = null;
                        showToast('success', 'Success', 'Lead accepted and moved to Closed-Won.');
                        router.reload({ only: ['leads'] }); 
                    },
                    onError: (errors) => {
                        showToast('error', 'Error', Object.values(errors).join(', '));
                    }
                });
            }
        });
    } else if (finalizeAction.value === 'reject') {
        if (!rejectReason.value.trim()) { 
            showToast('warning', 'Reason Required', 'Please provide a reason for rejection.');
            return; 
        }
        
        showConfirm({
            type: 'danger',
            title: 'Reject Lead',
            message: `Reject "${currentLead.value.company_name}"?`,
            confirmText: 'Reject Lead',
            onConfirm: () => {
                rejectForm.reject_reason = rejectReason.value;
                rejectForm.post(route('crm.lead.reject', currentLead.value.id), {
                    preserveScroll: true,
                    onSuccess: () => { 
                        showFinalizeModal.value = false; 
                        currentLead.value = null; 
                        rejectForm.reset();
                        showToast('success', 'Success', 'Lead rejected and marked as Lost.');
                        router.reload({ only: ['leads'] }); 
                    },
                    onError: (errors) => {
                        showToast('error', 'Error', Object.values(errors).join(', '));
                    }
                });
            }
        });
    }
};

// ─────────────────────────────────────────────────
// Other Modals
// ─────────────────────────────────────────────────
const openNoteModal = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to add notes.');
        return;
    }
    currentLead.value = lead;
    noteForm.note = '';
    showNoteModal.value = true;
};

const openInterviewModal = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to schedule interviews.');
        return;
    }
    currentLead.value = lead;
    interviewForm.reset();
    showInterviewModal.value = true;
};

const closeAllModals = () => {
    showNoteModal.value = false;
    showInterviewModal.value = false;
    showFinalizeModal.value = false;
    currentLead.value = null;
    finalizeFile.value = null;
    finalizeAction.value = null;
    rejectReason.value = '';
};

const addNote = () => {
    if (!noteForm.note.trim()) {
        showToast('warning', 'Note Required', 'Please enter a note.');
        return;
    }
    
    noteForm.post(route('crm.lead.add-note', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => { 
            closeAllModals(); 
            noteForm.reset();
            showToast('success', 'Success', 'Note added successfully.');
            router.reload({ only: ['leads'] }); 
        },
        onError: (errors) => {
            showToast('error', 'Error', Object.values(errors).join(', '));
        }
    });
};

const scheduleInterview = () => {
    if (!interviewForm.scheduled_at) {
        showToast('warning', 'Date Required', 'Please select a date and time for the interview.');
        return;
    }
    
    const selectedDate = new Date(interviewForm.scheduled_at);
    const now = new Date();
    
    if (selectedDate < now) {
        showConfirm({
            type: 'warning',
            title: 'Past Date Selected',
            message: 'The selected date is in the past. Are you sure you want to schedule this interview?',
            confirmText: 'Schedule Anyway',
            onConfirm: () => submitInterview()
        });
    } else {
        submitInterview();
    }
};

const submitInterview = () => {
    interviewForm.post(route('crm.lead.schedule-interview', currentLead.value.id), {
        preserveScroll: true,
        onSuccess: () => { 
            closeAllModals(); 
            interviewForm.reset();
            showToast('success', 'Success', 'Interview scheduled successfully.');
            router.reload({ only: ['leads'] }); 
        },
        onError: (errors) => {
            showToast('error', 'Error', Object.values(errors).join(', '));
        }
    });
};

const openConversionModal = (lead) => {
    if (!canEdit.value) {
        showToast('error', 'Permission Denied', 'You do not have permission to convert leads.');
        return;
    }
    conversionForm.lead_id        = lead.id;
    conversionForm.company_name   = lead.company_name;
    conversionForm.contact_person = lead.contact_person;
    conversionForm.email          = lead.email;
    conversionForm.phone          = lead.phone;
    conversionForm.tin_number      = '';
    conversionForm.company_address = '';
    conversionErrors.value = {};
    showClientConversionModal.value = true;
};

const submitConversion = () => {
    if (!validateConversionForm()) {
        showToast('error', 'Validation Error', 'Please fill in all required fields.');
        return;
    }
    
    showConfirm({
        type: 'info',
        title: 'Convert to Client',
        message: `Convert "${conversionForm.company_name}" to a business client?`,
        confirmText: 'Convert',
        onConfirm: () => {
            conversionForm.post(route('crm.lead.convert'), {
                preserveScroll: true,
                onSuccess: () => { 
                    showClientConversionModal.value = false; 
                    conversionForm.reset();
                    showToast('success', 'Success', 'Lead converted to client successfully.');
                    router.reload({ only: ['leads'] }); 
                },
                onError: (errors) => {
                    showToast('error', 'Conversion Failed', Object.values(errors).join(', '));
                }
            });
        }
    });
};

const submit = () => {
    if (!validateForm()) {
        showToast('error', 'Validation Error', 'Please correct the errors in the form.');
        return;
    }
    
    form.post(route('crm.lead.store'), {
        onSuccess: () => { 
            showCreateModal.value = false; 
            form.reset();
            formErrors.value = {};
            showToast('success', 'Success', 'New lead created successfully.');
        },
        onError: (errors) => {
            if (errors.email) {
                showToast('error', 'Duplicate Email', errors.email);
            } else {
                showToast('error', 'Error', Object.values(errors).join(', '));
            }
        }
    });
};

// ─────────────────────────────────────────────────
// UI Helpers
// ─────────────────────────────────────────────────
const formatDateTime = (date) => new Date(date).toLocaleString();

const showNotes      = ref({});
const showInterviews = ref({});
const toggleNotes      = (id) => { showNotes.value[id]      = !showNotes.value[id]; };
const toggleInterviews = (id) => { showInterviews.value[id] = !showInterviews.value[id]; };

// Stage accent colors
const stageAccent = (status) => ({
    'Inquiry':       { bg: 'bg-blue-600',   light: 'bg-blue-50',   text: 'text-blue-700' },
    'Negotiation':   { bg: 'bg-amber-400',  light: 'bg-amber-50',  text: 'text-amber-700' },
    'Approval Sent': { bg: 'bg-slate-700',  light: 'bg-slate-50',  text: 'text-slate-700' },
    'Closed-Won':    { bg: 'bg-blue-600',   light: 'bg-blue-50',   text: 'text-blue-700' },
}[status] || { bg: 'bg-slate-400', light: 'bg-slate-50', text: 'text-slate-600' });

// Toast icon mapping
const toastIcon = (type) => {
    switch(type) {
        case 'success': return CheckCircle2;
        case 'error': return XCircle;
        case 'warning': return AlertTriangle;
        default: return Info;
    }
};

const toastColors = (type) => {
    switch(type) {
        case 'success': return 'bg-emerald-50 border-emerald-200 text-emerald-800';
        case 'error': return 'bg-red-50 border-red-200 text-red-800';
        case 'warning': return 'bg-amber-50 border-amber-200 text-amber-800';
        default: return 'bg-blue-50 border-blue-200 text-blue-800';
    }
};

const confirmColors = (type) => {
    switch(type) {
        case 'danger': return { bg: 'bg-red-600 hover:bg-red-700', icon: 'bg-red-100 text-red-600' };
        case 'warning': return { bg: 'bg-amber-500 hover:bg-amber-600', icon: 'bg-amber-100 text-amber-600' };
        case 'success': return { bg: 'bg-emerald-600 hover:bg-emerald-700', icon: 'bg-emerald-100 text-emerald-600' };
        default: return { bg: 'bg-blue-600 hover:bg-blue-700', icon: 'bg-blue-100 text-blue-600' };
    }
};
</script>

<template>
    <AuthenticatedLayout title="Lead & Deal Workspace">
        <div class="min-h-screen bg-white">

            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 translate-x-4"
                >
                    <div
                        v-if="toast.show"
                        :class="['fixed top-20 right-4 z-[200] max-w-md w-full rounded-xl border shadow-lg p-4', toastColors(toast.type)]"
                    >
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <component :is="toastIcon(toast.type)" class="w-5 h-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold">{{ toast.title }}</p>
                                <p class="text-sm mt-0.5 opacity-90">{{ toast.message }}</p>
                            </div>
                            <button @click="hideToast" class="flex-shrink-0 opacity-60 hover:opacity-100 transition">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </Transition>
            </Teleport>

            <Teleport to="body">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0"
                    enter-to-class="opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="confirmModal.show" class="fixed inset-0 z-[180] flex items-end sm:items-center justify-center p-0 sm:p-4">
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="hideConfirm"></div>
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="translate-y-4 sm:scale-95 opacity-0"
                            enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                        >
                            <div v-if="confirmModal.show" class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl overflow-hidden">
                                <div class="flex justify-center pt-3 pb-1 sm:hidden">
                                    <div class="w-10 h-1 bg-slate-200 rounded-full"></div>
                                </div>
                                
                                <div class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div :class="['w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0', confirmColors(confirmModal.type).icon]">
                                            <AlertTriangle v-if="confirmModal.type === 'warning' || confirmModal.type === 'danger'" class="w-6 h-6" />
                                            <Info v-else class="w-6 h-6" />
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-slate-900">{{ confirmModal.title }}</h3>
                                            <p class="text-sm text-slate-500 mt-1">{{ confirmModal.message }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                    <button 
                                        @click="hideConfirm" 
                                        :disabled="confirmModal.isLoading"
                                        class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all disabled:opacity-50"
                                    >
                                        {{ confirmModal.cancelText }}
                                    </button>
                                    <button 
                                        @click="handleConfirm" 
                                        :disabled="confirmModal.isLoading"
                                        :class="['w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all disabled:opacity-50 inline-flex items-center justify-center gap-2', confirmColors(confirmModal.type).bg]"
                                    >
                                        <span v-if="confirmModal.isLoading" class="inline-flex items-center gap-2">
                                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Processing...
                                        </span>
                                        <span v-else>{{ confirmModal.confirmText }}</span>
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>

            <div class="border-b border-slate-100 bg-white sticky top-0 z-10">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center flex-shrink-0">
                                <ArrowUpRight class="w-4 h-4 text-white" />
                            </div>
                            <div>
                                <h1 class="text-base sm:text-lg font-semibold text-slate-900 leading-tight">Lead Pipeline</h1>
                                <p class="text-xs text-slate-400 hidden sm:block">Manage leads in a structured queue — oldest first</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span v-if="!canEdit && permissions.leads === 'view'"
                                class="text-xs font-medium text-amber-700 bg-amber-50 ring-1 ring-amber-200 px-2.5 py-1 rounded-full">
                                View only
                            </span>
                            <span v-else-if="canEdit"
                                class="text-xs font-medium text-blue-700 bg-blue-50 ring-1 ring-blue-200 px-2.5 py-1 rounded-full hidden sm:inline-flex">
                                Full access
                            </span>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search leads..."
                                    class="pl-8 pr-3 py-2 border border-slate-200 rounded-xl text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 w-40 sm:w-52 transition-all"
                                />
                            </div>
                            <button
                                v-if="canEdit"
                                @click="showCreateModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors active:scale-95"
                            >
                                <Plus class="w-3.5 h-3.5" /> New Deal
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-5">

                <div class="overflow-x-auto no-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                    <div class="flex gap-1.5 min-w-max">
                        <button
                            v-for="stage in stages"
                            :key="stage.status"
                            @click="activeTab = stage.status"
                            :class="[
                                'flex items-center gap-2 px-4 py-2 rounded-full text-xs font-semibold transition-all duration-150 whitespace-nowrap',
                                activeTab === stage.status
                                    ? 'bg-blue-600 text-white shadow-sm'
                                    : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100 bg-white border border-slate-200'
                            ]"
                        >
                            <component :is="stage.icon" class="w-3.5 h-3.5" />
                            {{ stage.label }}
                            <span :class="[
                                'px-1.5 py-0.5 rounded-full text-[10px] font-bold',
                                activeTab === stage.status ? 'bg-white/25 text-white' : 'bg-slate-100 text-slate-500'
                            ]">
                                {{ leadsByStatus[stage.status]?.length || 0 }}
                            </span>
                        </button>
                    </div>
                </div>

                <div v-if="currentLeads.length === 0" class="py-20 text-center">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
                        <AlertCircle class="w-7 h-7 text-slate-300" />
                    </div>
                    <p class="text-sm font-medium text-slate-500">No leads in this stage</p>
                    <p class="text-xs text-slate-400 mt-1">Create a new deal to start the pipeline.</p>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="lead in currentLeads"
                        :key="lead.id"
                        class="bg-white border border-slate-100 rounded-2xl hover:border-slate-200 hover:shadow-sm transition-all duration-200 overflow-hidden"
                    >
                        <div class="flex">
                            <div :class="stageAccent(lead.status).bg" class="w-1 flex-shrink-0 rounded-l-2xl"></div>

                            <div class="flex-1 p-4 sm:p-5">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div :class="[stageAccent(lead.status).light, stageAccent(lead.status).text]"
                                            class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 text-sm font-bold">
                                            {{ lead.company_name?.charAt(0)?.toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-slate-900 leading-tight">{{ lead.company_name }}</h3>
                                            <p class="text-xs text-slate-400 mt-0.5 truncate">{{ lead.contact_person }} · {{ lead.email }}</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 flex items-center gap-2">
                                        <button
                                            v-if="canEdit"
                                            @click="openDeleteConfirm(lead)"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl text-xs font-medium transition-colors"
                                            title="Delete Lead"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            v-if="canEdit && (lead.status === 'Inquiry' || lead.status === 'Negotiation')"
                                            @click="openMoveConfirm(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors active:scale-95"
                                        >
                                            <MoveRight class="w-3.5 h-3.5" /> Move to {{ getNextStage(lead.status) }}
                                        </button>
                                        <button
                                            v-if="canEdit && lead.status === 'Approval Sent'"
                                            @click="openFinalizeModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-400 hover:bg-amber-500 text-white rounded-xl text-xs font-semibold transition-colors active:scale-95"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" /> Finalize
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 mt-3">
                                    <span class="text-xs text-slate-500">{{ lead.phone }}</span>
                                    <span class="text-xs text-slate-300 ml-auto"></span>
                                </div>

                                <div v-if="lead.status === 'Inquiry'" class="mt-3 pt-3 border-t border-slate-50 space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="canEdit"
                                            @click="openNoteModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl text-xs font-medium transition-colors"
                                        >
                                            <MessageSquare class="w-3.5 h-3.5" /> Add Note
                                        </button>
                                    </div>
                                    <div v-if="lead.notes && lead.notes.length">
                                        <button @click="toggleNotes(lead.id)" class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                            <ChevronDown v-if="!showNotes[lead.id]" class="w-3.5 h-3.5" />
                                            <ChevronUp v-else class="w-3.5 h-3.5" />
                                            {{ lead.notes.length }} note{{ lead.notes.length !== 1 ? 's' : '' }}
                                        </button>
                                        <div v-if="showNotes[lead.id]" class="mt-2 space-y-2 pl-3 border-l-2 border-slate-100">
                                            <div v-for="note in lead.notes" :key="note.id" class="bg-slate-50 rounded-xl p-3">
                                                <p class="text-xs text-slate-700">{{ note.note }}</p>
                                                <p class="text-[10px] text-slate-400 mt-1">{{ note.user?.name }} · {{ new Date(note.created_at).toLocaleString() }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="lead.status === 'Negotiation'" class="mt-3 pt-3 border-t border-slate-50 space-y-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-if="canEdit"
                                            @click="openInterviewModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 text-slate-600 border border-slate-200 rounded-xl text-xs font-medium transition-colors"
                                        >
                                            <Video class="w-3.5 h-3.5" /> Schedule Interview
                                        </button>
                                    </div>
                                    <div v-if="lead.interviews && lead.interviews.length">
                                        <button @click="toggleInterviews(lead.id)" class="flex items-center gap-1 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                            <ChevronDown v-if="!showInterviews[lead.id]" class="w-3.5 h-3.5" />
                                            <ChevronUp v-else class="w-3.5 h-3.5" />
                                            {{ lead.interviews.length }} interview{{ lead.interviews.length !== 1 ? 's' : '' }}
                                        </button>
                                        <div v-if="showInterviews[lead.id]" class="mt-2 space-y-2 pl-3 border-l-2 border-slate-100">
                                            <div v-for="iv in lead.interviews" :key="iv.id" class="bg-slate-50 rounded-xl p-3 space-y-1">
                                                <div class="flex items-center gap-1.5 text-xs text-slate-700">
                                                    <Calendar class="w-3 h-3 text-slate-400" /> {{ formatDateTime(iv.scheduled_at) }}
                                                </div>
                                                <div v-if="iv.location" class="flex items-center gap-1.5 text-xs text-slate-500">
                                                    <MapPin class="w-3 h-3 text-slate-400" /> {{ iv.location }}
                                                </div>
                                                <p v-if="iv.notes" class="text-xs text-slate-500 italic">{{ iv.notes }}</p>
                                                <p class="text-[10px] text-slate-400">{{ iv.user?.name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="lead.status === 'Approval Sent' && lead.approval_files?.length" class="mt-3 pt-3 border-t border-slate-50">
                                    <span class="inline-flex items-center gap-1.5 text-xs text-slate-500">
                                        <FileText class="w-3.5 h-3.5 text-slate-400" />
                                        {{ lead.approval_files.length }} file{{ lead.approval_files.length !== 1 ? 's' : '' }} attached
                                    </span>
                                </div>

                                <div v-if="lead.status === 'Closed-Won'" class="mt-3 pt-3 border-t border-slate-50">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-700">
                                            <CheckCircle2 class="w-3.5 h-3.5" /> Qualified
                                        </span>
                                        <button
                                            v-if="canEdit"
                                            @click="openConversionModal(lead)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold transition-colors"
                                        >
                                            <UserCheck class="w-3.5 h-3.5" /> Create Client Account
                                        </button>
                                    </div>
                                </div>

                                <p class="text-[10px] text-slate-300 mt-3 text-right">
                                    Created {{ new Date(lead.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">

            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showCreateModal = false"></div>
                    <Transition name="modal-content">
                        <div v-if="showCreateModal" class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl max-h-[92vh] flex flex-col overflow-hidden">
                            <div class="flex justify-center pt-3 pb-1 sm:hidden"><div class="w-10 h-1 bg-slate-200 rounded-full"></div></div>
                            
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center">
                                        <Plus class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">New Lead</h3>
                                        <p class="text-xs text-slate-400">Fill in the lead details</p>
                                    </div>
                                </div>
                                <button @click="showCreateModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            
                            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-1.5">Company Name <span class="text-red-400">*</span></label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        placeholder="Company name"
                                        :class="['w-full border rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition', formErrors.company_name ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                    />
                                    <p v-if="formErrors.company_name" class="text-xs text-red-500 mt-1">{{ formErrors.company_name }}</p>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-1.5">Contact Person <span class="text-red-400">*</span></label>
                                    <input
                                        v-model="form.contact_person"
                                        type="text"
                                        placeholder="Full name"
                                        :class="['w-full border rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition', formErrors.contact_person ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                    />
                                    <p v-if="formErrors.contact_person" class="text-xs text-red-500 mt-1">{{ formErrors.contact_person }}</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700 block mb-1.5">Email <span class="text-red-400">*</span></label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            placeholder="email@company.com"
                                            :class="['w-full border rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition', formErrors.email ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                        />
                                        <p v-if="formErrors.email" class="text-xs text-red-500 mt-1">{{ formErrors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700 block mb-1.5">Phone <span class="text-red-400">*</span></label>
                                        <input
                                            v-model="form.phone"
                                            type="text"
                                            placeholder="09123456789"
                                            :class="['w-full border rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition', formErrors.phone ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                        />
                                        <p v-if="formErrors.phone" class="text-xs text-red-500 mt-1">{{ formErrors.phone }}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-5 py-4 border-t border-slate-100 flex-shrink-0 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="showCreateModal = false" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                                <button
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all"
                                >
                                    <Plus class="w-4 h-4" />
                                    {{ form.processing ? 'Creating…' : 'Create Lead' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition name="modal">
                <div v-if="showNoteModal" class="fixed inset-0 z-[130] flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeAllModals"></div>
                    <Transition name="modal-content">
                        <div v-if="showNoteModal" class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl overflow-hidden flex flex-col">
                            <div class="flex justify-center pt-3 pb-1 sm:hidden"><div class="w-10 h-1 bg-slate-200 rounded-full"></div></div>
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center">
                                        <MessageSquare class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Add Note</h3>
                                        <p class="text-xs text-slate-400">{{ currentLead?.company_name }}</p>
                                    </div>
                                </div>
                                <button @click="closeAllModals" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="px-5 py-5">
                                <label class="text-xs font-semibold text-slate-700 block mb-2">Note <span class="text-red-400">*</span></label>
                                <textarea
                                    v-model="noteForm.note"
                                    rows="4"
                                    placeholder="Write your notes here..."
                                    class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                                ></textarea>
                            </div>
                            <div class="px-5 py-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="closeAllModals" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                                <button @click="addNote" :disabled="noteForm.processing || !noteForm.note.trim()"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400 text-white rounded-xl text-sm font-semibold transition-all">
                                    <MessageSquare class="w-4 h-4" /> {{ noteForm.processing ? 'Saving…' : 'Save Note' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition name="modal">
                <div v-if="showInterviewModal" class="fixed inset-0 z-[130] flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeAllModals"></div>
                    <Transition name="modal-content">
                        <div v-if="showInterviewModal" class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl overflow-hidden flex flex-col">
                            <div class="flex justify-center pt-3 pb-1 sm:hidden"><div class="w-10 h-1 bg-slate-200 rounded-full"></div></div>
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-amber-400 flex items-center justify-center">
                                        <Video class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Schedule Interview</h3>
                                        <p class="text-xs text-slate-400">{{ currentLead?.company_name }}</p>
                                    </div>
                                </div>
                                <button @click="closeAllModals" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="px-5 py-5 space-y-3">
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">Date & Time <span class="text-red-400">*</span></label>
                                    <input
                                        type="datetime-local"
                                        v-model="interviewForm.scheduled_at"
                                        required
                                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">Location <span class="text-slate-300 font-normal">(optional)</span></label>
                                    <input
                                        type="text"
                                        v-model="interviewForm.location"
                                        placeholder="e.g. Zoom, Office, etc."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                    />
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">Notes <span class="text-slate-300 font-normal">(optional)</span></label>
                                    <textarea
                                        v-model="interviewForm.notes"
                                        rows="3"
                                        placeholder="Additional context..."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="px-5 py-4 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="closeAllModals" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                                <button @click="scheduleInterview" :disabled="interviewForm.processing || !interviewForm.scheduled_at"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-slate-200 disabled:text-slate-400 text-white rounded-xl text-sm font-semibold transition-all">
                                    <Calendar class="w-4 h-4" /> {{ interviewForm.processing ? 'Scheduling…' : 'Schedule' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition name="modal">
                <div v-if="showFinalizeModal" class="fixed inset-0 z-[140] flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeAllModals"></div>
                    <Transition name="modal-content">
                        <div v-if="showFinalizeModal" class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl shadow-2xl max-h-[92vh] flex flex-col overflow-hidden">
                            <div class="flex justify-center pt-3 pb-1 sm:hidden"><div class="w-10 h-1 bg-slate-200 rounded-full"></div></div>
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-amber-400 flex items-center justify-center">
                                        <FileText class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Finalize Lead</h3>
                                        <p class="text-xs text-slate-400">{{ currentLead?.company_name }}</p>
                                    </div>
                                </div>
                                <button @click="closeAllModals" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm text-slate-600 leading-relaxed">
                                    Complete the approval process for <span class="font-semibold text-slate-900">{{ currentLead?.company_name }}</span>.
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">
                                        Upload Approval File <span class="text-slate-300 font-normal">(optional, max 5MB)</span>
                                    </label>
                                    <div class="border border-dashed border-slate-200 rounded-xl p-4 space-y-3">
                                        <input
                                            type="file"
                                            @change="handleFileChange"
                                            accept="image/*,application/pdf"
                                            class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                        />
                                        <button
                                            v-if="finalizeFile"
                                            @click="uploadFinalizeFile"
                                            :disabled="isUploading"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-xs font-semibold transition-all"
                                        >
                                            <Upload class="w-3.5 h-3.5" />
                                            {{ isUploading ? 'Uploading…' : 'Upload File' }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">Decision <span class="text-red-400">*</span></label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            @click="finalizeAction = 'accept'"
                                            :class="[
                                                'flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all border-2',
                                                finalizeAction === 'accept'
                                                    ? 'bg-blue-600 border-blue-600 text-white shadow-sm'
                                                    : 'bg-white border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-600'
                                            ]"
                                        >
                                            <Check class="w-4 h-4" /> Accept
                                        </button>
                                        <button
                                            @click="finalizeAction = 'reject'"
                                            :class="[
                                                'flex items-center justify-center gap-2 py-3 rounded-xl text-sm font-semibold transition-all border-2',
                                                finalizeAction === 'reject'
                                                    ? 'bg-slate-800 border-slate-800 text-white shadow-sm'
                                                    : 'bg-white border-slate-200 text-slate-600 hover:border-slate-400 hover:text-slate-800'
                                            ]"
                                        >
                                            <XCircle class="w-4 h-4" /> Reject
                                        </button>
                                    </div>
                                </div>

                                <div v-if="finalizeAction === 'reject'">
                                    <label class="text-xs font-semibold text-slate-700 block mb-2">Reason for Rejection <span class="text-red-400">*</span></label>
                                    <textarea
                                        v-model="rejectReason"
                                        rows="3"
                                        placeholder="Please provide a reason..."
                                        class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-transparent transition resize-none"
                                    ></textarea>
                                </div>
                            </div>
                            <div class="px-5 py-4 border-t border-slate-100 flex-shrink-0 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="closeAllModals" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                                <button
                                    @click="submitFinalize"
                                    :disabled="!finalizeAction || (finalizeAction === 'reject' && !rejectReason.trim())"
                                    :class="[
                                        'w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold transition-all disabled:opacity-40 disabled:cursor-not-allowed',
                                        finalizeAction === 'reject' ? 'bg-slate-800 hover:bg-slate-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white'
                                    ]"
                                >
                                    <CheckCircle2 class="w-4 h-4" />
                                    {{ finalizeAction === 'accept' ? 'Confirm & Move to Closed-Won' : finalizeAction === 'reject' ? 'Confirm & Archive' : 'Confirm' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

            <Transition name="modal">
                <div v-if="showClientConversionModal" class="fixed inset-0 z-[130] flex items-end sm:items-center justify-center p-0 sm:p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showClientConversionModal = false"></div>
                    <Transition name="modal-content">
                        <div v-if="showClientConversionModal" class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-3xl shadow-2xl max-h-[92vh] flex flex-col overflow-hidden">
                            <div class="flex justify-center pt-3 pb-1 sm:hidden"><div class="w-10 h-1 bg-slate-200 rounded-full"></div></div>
                            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 flex-shrink-0">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center">
                                        <UserCheck class="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">Promote to Business Client</h3>
                                        <p class="text-xs text-slate-400">{{ conversionForm.company_name }}</p>
                                    </div>
                                </div>
                                <button @click="showClientConversionModal = false" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 transition-all">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-800">
                                    Finalizing the partnership for <span class="font-semibold">{{ conversionForm.company_name }}</span>. Fill in the official business details below.
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700 block mb-1.5">Business Type</label>
                                        <select
                                            v-model="conversionForm.business_type"
                                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                                        >
                                            <option value="wholesaler">Wholesaler</option>
                                            <option value="retailer">Retailer</option>
                                            <option value="manufacturer">Manufacturer</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs font-semibold text-slate-700 block mb-1.5">TIN Number <span class="text-red-400">*</span></label>
                                        <input
                                            v-model="conversionForm.tin_number"
                                            type="text"
                                            placeholder="000-000-000"
                                            :class="['w-full border rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition', conversionErrors.tin_number ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                        />
                                        <p v-if="conversionErrors.tin_number" class="text-xs text-red-500 mt-1">{{ conversionErrors.tin_number }}</p>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-semibold text-slate-700 block mb-1.5">Official Company Address <span class="text-red-400">*</span></label>
                                    <textarea
                                        v-model="conversionForm.company_address"
                                        rows="3"
                                        placeholder="Complete business address"
                                        :class="['w-full border rounded-xl px-4 py-3 text-sm text-slate-800 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none', conversionErrors.company_address ? 'border-red-300 bg-red-50/30' : 'border-slate-200']"
                                    ></textarea>
                                    <p v-if="conversionErrors.company_address" class="text-xs text-red-500 mt-1">{{ conversionErrors.company_address }}</p>
                                </div>
                            </div>
                            <div class="px-5 py-4 border-t border-slate-100 flex-shrink-0 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end">
                                <button @click="showClientConversionModal = false" class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">Cancel</button>
                                <button
                                    @click="submitConversion"
                                    :disabled="conversionForm.processing"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-sm font-semibold transition-all"
                                >
                                    <Building2 class="w-4 h-4" />
                                    {{ conversionForm.processing ? 'Converting…' : 'Finalize Client' }}
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>

        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }

.modal-content-enter-active { transition: all 0.2s ease-out; }
.modal-content-leave-active { transition: all 0.15s ease-in; }
.modal-content-enter-from { opacity: 0; transform: translateY(16px) scale(0.95); }
.modal-content-leave-to { opacity: 0; transform: translateY(16px) scale(0.95); }
@media (min-width: 640px) {
    .modal-content-enter-from, .modal-content-leave-to { transform: scale(0.95); }
}
</style>