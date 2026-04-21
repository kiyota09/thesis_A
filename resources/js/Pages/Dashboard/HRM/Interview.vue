<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Calendar, Clock, Video, MapPin, XCircle, CheckCircle,
    PlayCircle, User, Mail, Phone, Briefcase, CalendarDays,
    AlertTriangle, X, UserCheck, ArrowRight, ChevronRight,
    Building, FileText, Globe, ChevronLeft, Heart, BookOpen,
    Factory, CreditCard, Phone as PhoneIcon, MapPin as MapPinIcon,
    User as UserIcon, Award, ShieldCheck, Eye, Pencil, Save,
    Loader2, Trash2, Upload, Image as ImageIcon
} from 'lucide-vue-next';

const props = defineProps({
    applicants: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) }
});

const canEdit = computed(() => props.permissions?.interview === 'edit');

// ─── Toast ────────────────────────────────────────────────────────────────────
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');
const triggerToast = (msg, type = 'success') => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 4000);
};
const page = usePage();
if (page.props.flash?.message) triggerToast(page.props.flash.message);

// ─── Modal states ─────────────────────────────────────────────────────────────
const isScheduleModalOpen = ref(false);
const isPassModalOpen = ref(false);
const isFailModalOpen = ref(false);
const selectedApplicant = ref(null);

// Tracks which applicant IDs are currently in "Interview Now" mode
const interviewingNowIds = ref(new Set());

// Fail flow step: 'choose' | 'confirm_fail' | 'pass_to_other'
const failStep = ref('choose');
const failReason = ref('');
const otherModule = ref('');

// ─── Detail side panel ────────────────────────────────────────────────────────
const detailPanelOpen = ref(false);
const detailPanelApplicant = ref(null);
const detailPanelTab = ref('personal'); // 'personal', 'education', 'employment', 'family'

// ─── Schedule form ────────────────────────────────────────────────────────────
const scheduleForm = ref({
    scheduled_at: '', interview_type: '', duration: 45,
    interviewer: '', location: '', notes: ''
});

// ─── Module options ───────────────────────────────────────────────────────────
const modules = [
    { value: 'HRM', label: 'Human Resource', color: '#2563eb' },
    { value: 'ECO', label: 'E-Commerce', color: '#0891b2' },
    { value: 'CRM', label: 'Customer Relationship', color: '#9333ea' },
    { value: 'SCM', label: 'Supply Chain', color: '#16a34a' },
    { value: 'MAN', label: 'Manufacturing', color: '#059669' },
    { value: 'PROJ', label: 'Project Management', color: '#d97706' },
    { value: 'FIN', label: 'Finance', color: '#dc2626' },
    { value: 'LOG', label: 'Logistics', color: '#ea580c' },
    { value: 'IT', label: 'Information Technology', color: '#7c3aed' },
];

// ─── Computed ─────────────────────────────────────────────────────────────────
const todaysInterviews = computed(() => {
    const today = new Date();
    return props.applicants
        .filter(a => {
            if (!a.scheduled_at) return false;
            return new Date(a.scheduled_at).toDateString() === today.toDateString();
        })
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials = (name) =>
    name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';

const formatDateTime = (date) =>
    date ? new Date(date).toLocaleString('en-US', {
        month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    }) : 'N/A';

const formatTime = (date) =>
    date ? new Date(date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

const formatDateFull = (date) =>
    date ? new Date(date).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    }) : 'N/A';

const isInterviewingNow = (applicant) => interviewingNowIds.value.has(applicant.id);
const hasSchedule = (applicant) => !!applicant.scheduled_at;

const getStatusInfo = (applicant) => {
    if (isInterviewingNow(applicant))
        return { label: 'In Progress', color: 'text-amber-700 bg-amber-50 border-amber-200', dot: 'bg-amber-500' };
    if (hasSchedule(applicant))
        return { label: 'Scheduled', color: 'text-blue-700 bg-blue-50 border-blue-200', dot: 'bg-blue-500' };
    return { label: 'Pending', color: 'text-slate-600 bg-slate-100 border-slate-200', dot: 'bg-slate-400' };
};

const getInterviewTypeLabel = (type) => {
    const map = {
        phone: 'Phone Screen', video: 'Video Call',
        onsite: 'On-site', technical: 'Technical', behavioral: 'Behavioral'
    };
    return map[type?.toLowerCase()] || type || 'Interview';
};

const getInterviewTypeIcon = (type) => {
    const t = type?.toLowerCase();
    if (t === 'phone') return Phone;
    if (t === 'video') return Video;
    if (t === 'onsite') return MapPin;
    return Calendar;
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const startInterviewNow = (applicant, event) => {
    event.stopPropagation();
    const next = new Set(interviewingNowIds.value);
    next.add(applicant.id);
    interviewingNowIds.value = next;
};

const openScheduleModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission to schedule interviews.', 'error'); return; }
    selectedApplicant.value = applicant;
    scheduleForm.value = {
        scheduled_at: applicant.scheduled_at
            ? new Date(applicant.scheduled_at).toISOString().slice(0, 16) : '',
        interview_type: applicant.interview_type || '',
        duration: applicant.duration || 45,
        interviewer: applicant.interviewer || '',
        location: applicant.location || '',
        notes: applicant.notes || ''
    };
    isScheduleModalOpen.value = true;
};

const openPassModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedApplicant.value = applicant;
    isPassModalOpen.value = true;
};

const openFailModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedApplicant.value = applicant;
    failReason.value = '';
    otherModule.value = '';
    failStep.value = 'choose';
    isFailModalOpen.value = true;
};

const openDetailPanel = (applicant) => {
    detailPanelApplicant.value = applicant;
    detailPanelOpen.value = true;
    detailPanelTab.value = 'personal';
};
const closeDetailPanel = () => {
    detailPanelOpen.value = false;
    setTimeout(() => { detailPanelApplicant.value = null; }, 350);
};

const closeModals = () => {
    isScheduleModalOpen.value = false;
    isPassModalOpen.value = false;
    isFailModalOpen.value = false;
    selectedApplicant.value = null;
    failStep.value = 'choose';
    failReason.value = '';
    otherModule.value = '';
    scheduleForm.value = {
        scheduled_at: '', interview_type: '', duration: 45,
        interviewer: '', location: '', notes: ''
    };
};

// ─── API ──────────────────────────────────────────────────────────────────────
const scheduleInterview = () => {
    if (!scheduleForm.value.scheduled_at || !scheduleForm.value.interview_type) {
        triggerToast('Please fill in all required fields.', 'error'); return;
    }
    router.post(route('hrm.interview.schedule', selectedApplicant.value.id), scheduleForm.value, {
        preserveScroll: true,
        onSuccess: () => { triggerToast('Interview scheduled successfully.'); closeModals(); },
        onError: (e) => triggerToast(Object.values(e)[0] || 'Failed to schedule.', 'error')
    });
};

const passApplicant = () => {
    router.post(route('hrm.interview.pass', selectedApplicant.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => { triggerToast(`${selectedApplicant.value.name} passed the interview!`); closeModals(); },
        onError: (e) => triggerToast(Object.values(e)[0] || 'Failed.', 'error')
    });
};

const failApplicant = () => {
    if (!failReason.value.trim()) { triggerToast('Please provide a reason for failure.', 'error'); return; }
    router.post(route('hrm.interview.fail', selectedApplicant.value.id), { reason: failReason.value }, {
        preserveScroll: true,
        onSuccess: () => { triggerToast(`${selectedApplicant.value.name} has been failed.`); closeModals(); },
        onError: (e) => triggerToast(Object.values(e)[0] || 'Failed.', 'error')
    });
};

const passToOtherModule = () => {
    if (!otherModule.value) { triggerToast('Please select a module.', 'error'); return; }
    router.post(route('hrm.interview.pass-to-other', selectedApplicant.value.id),
        { module: otherModule.value }, {
            preserveScroll: true,
            onSuccess: () => { triggerToast(`Applicant forwarded to ${otherModule.value} module.`); closeModals(); },
            onError: (e) => triggerToast(Object.values(e)[0] || 'Failed to pass to other module.', 'error')
        });
};

// ─── Image preview modal ──────────────────────────────────────────────────────
const imagePreview = ref(null);
const openImagePreview = (url, title) => {
    imagePreview.value = { url, title };
};
const closeImagePreview = () => {
    imagePreview.value = null;
};

// ─── Helper to format notice period ──────────────────────────────────────────
const formatNoticePeriod = (period) => {
    if (!period) return '—';
    const map = {
        immediate: 'Immediate',
        '15_days': '15 Days',
        '30_days': '30 Days',
        '60_days': '60 Days'
    };
    return map[period] || period;
};
</script>

<template>
    <Head title="Interview Management" />
    <AuthenticatedLayout>

        <!-- ╔══════════════════ TOAST ══════════════════╗ -->
        <Transition name="toast">
            <div v-if="showToast"
                class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl border max-w-xs w-full mx-4"
                :class="toastType === 'error'
                    ? 'bg-rose-600 border-rose-500 text-white'
                    : 'bg-slate-900 border-slate-700 text-white'">
                <CheckCircle v-if="toastType !== 'error'" class="h-4 w-4 text-emerald-400 shrink-0" />
                <XCircle v-else class="h-4 w-4 text-rose-300 shrink-0" />
                <p class="text-xs font-semibold tracking-wide flex-1">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 pb-24">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-8">

                <!-- ╔══════════════════ HEADER ══════════════════╗ -->
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-indigo-500 uppercase tracking-[0.15em] mb-1">
                                Human Resource
                            </p>
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Interviews
                            </h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ props.applicants.length }} candidate{{ props.applicants.length !== 1 ? 's' : '' }} assigned
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 mt-1">
                            <span v-if="canEdit"
                                class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full uppercase tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                Full Access
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full uppercase tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 inline-block"></span>
                                View Only
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ╔══════════════════ TODAY'S INTERVIEWS ══════════════════╗ -->
                <div v-if="todaysInterviews.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-5 w-1 rounded-full bg-indigo-500"></div>
                        <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest">
                            Today's Interviews
                        </h2>
                        <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded-full">
                            {{ todaysInterviews.length }}
                        </span>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm">
                        <div class="divide-y divide-slate-50 dark:divide-slate-700/60">
                            <div v-for="(applicant, idx) in todaysInterviews" :key="'today-' + applicant.id"
                                class="flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors cursor-pointer"
                                @click="openDetailPanel(applicant)">
                                <!-- Time -->
                                <div class="text-center shrink-0 w-12">
                                    <p class="text-xs font-black text-indigo-600">{{ formatTime(applicant.scheduled_at) }}</p>
                                    <div class="h-px w-6 bg-slate-200 mx-auto mt-1"></div>
                                </div>
                                <!-- Avatar -->
                                <div class="shrink-0">
                                    <img v-if="applicant.profile_photo"
                                        :src="applicant.profile_photo" :alt="applicant.name"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow" />
                                    <div v-else
                                        class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-black shadow">
                                        {{ getInitials(applicant.name) }}
                                    </div>
                                </div>
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ applicant.name }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ applicant.position_applied }}</p>
                                </div>
                                <!-- Type badge -->
                                <div class="shrink-0">
                                    <span class="text-[10px] font-bold text-slate-600 bg-slate-100 dark:bg-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-lg capitalize">
                                        {{ getInterviewTypeLabel(applicant.interview_type) }}
                                    </span>
                                </div>
                                <!-- Interview Now button (if canEdit and not already in progress) -->
                                <div class="shrink-0" v-if="canEdit">
                                    <button
                                        v-if="!isInterviewingNow(applicant)"
                                        @click="startInterviewNow(applicant, $event)"
                                        class="interview-now-btn flex items-center gap-1.5 text-[10px] font-black text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1.5 rounded-lg uppercase tracking-wide transition-all active:scale-95">
                                        <PlayCircle class="h-3 w-3" /> Now
                                    </button>
                                    <span v-else
                                        class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg">
                                        In Progress
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ╔══════════════════ APPLICANTS LIST ══════════════════╗ -->
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-5 w-1 rounded-full bg-slate-400"></div>
                        <h2 class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest">
                            All Candidates
                        </h2>
                    </div>

                    <!-- Empty state -->
                    <div v-if="props.applicants.length === 0"
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-12 text-center shadow-sm">
                        <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-700 mb-4">
                            <Calendar class="h-8 w-8 text-slate-400" />
                        </div>
                        <h3 class="text-base font-bold text-slate-700 dark:text-slate-300">No candidates yet</h3>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs mx-auto">
                            Candidates accepted by HR and assigned to your department will appear here.
                        </p>
                    </div>

                    <!-- Applicant cards -->
                    <div v-else class="space-y-3">
                        <div v-for="applicant in props.applicants" :key="applicant.id"
                            class="applicant-card bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden cursor-pointer transition-all hover:shadow-md hover:-translate-y-0.5 active:scale-[0.99]"
                            @click="openDetailPanel(applicant)">

                            <!-- Card top -->
                            <div class="p-4">
                                <div class="flex items-start gap-3.5">
                                    <!-- Photo / Avatar -->
                                    <div class="shrink-0 relative">
                                        <img v-if="applicant.profile_photo"
                                            :src="applicant.profile_photo" :alt="applicant.name"
                                            class="h-14 w-14 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-slate-600 shadow" />
                                        <div v-else
                                            class="h-14 w-14 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-base font-black shadow">
                                            {{ getInitials(applicant.name) }}
                                        </div>
                                        <!-- Online dot for today's interviews -->
                                        <span v-if="todaysInterviews.find(a => a.id === applicant.id)"
                                            class="absolute -top-1 -right-1 h-3.5 w-3.5 rounded-full bg-emerald-400 border-2 border-white dark:border-slate-800"></span>
                                    </div>

                                    <!-- Name & info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">
                                                    {{ applicant.name }}
                                                </h3>
                                                <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">
                                                    {{ applicant.email }}
                                                </p>
                                                <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5 truncate">
                                                    {{ applicant.position_applied }}
                                                </p>
                                            </div>
                                            <!-- Status badge -->
                                            <div class="shrink-0">
                                                <span :class="['inline-flex items-center gap-1 text-[10px] font-bold border px-2 py-0.5 rounded-full uppercase tracking-wide', getStatusInfo(applicant).color]">
                                                    <span :class="['h-1.5 w-1.5 rounded-full', getStatusInfo(applicant).dot]"></span>
                                                    {{ getStatusInfo(applicant).label }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Scheduled time (compact) -->
                                        <div v-if="applicant.scheduled_at"
                                            class="flex items-center gap-1.5 mt-2">
                                            <Calendar class="h-3 w-3 text-indigo-400" />
                                            <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                                {{ formatDateTime(applicant.scheduled_at) }}
                                            </span>
                                            <span v-if="applicant.interview_type"
                                                class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded-md ml-1 capitalize">
                                                {{ getInterviewTypeLabel(applicant.interview_type) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action buttons (only for canEdit) -->
                            <div v-if="canEdit" class="px-4 pb-4" @click.stop>

                                <!-- STATE 1: No schedule yet → only Schedule -->
                                <div v-if="!hasSchedule(applicant) && !isInterviewingNow(applicant)"
                                    class="flex gap-2">
                                    <button @click="openScheduleModal(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-indigo-200">
                                        <CalendarDays class="h-3.5 w-3.5" /> Set Schedule
                                    </button>
                                </div>

                                <!-- STATE 2: Scheduled, not interviewing yet → Reschedule + Interview Now -->
                                <div v-else-if="hasSchedule(applicant) && !isInterviewingNow(applicant)"
                                    class="flex gap-2">
                                    <button @click="openScheduleModal(applicant, $event)"
                                        class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                        <Calendar class="h-3.5 w-3.5" /> Reschedule
                                    </button>
                                    <button @click="startInterviewNow(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-indigo-200">
                                        <PlayCircle class="h-3.5 w-3.5" /> Interview Now
                                    </button>
                                </div>

                                <!-- STATE 3: Interviewing now → Pass + Fail -->
                                <div v-else-if="isInterviewingNow(applicant)"
                                    class="flex gap-2">
                                    <button @click="openPassModal(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-emerald-200">
                                        <CheckCircle class="h-3.5 w-3.5" /> Pass
                                    </button>
                                    <button @click="openFailModal(applicant, $event)"
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-rose-200">
                                        <XCircle class="h-3.5 w-3.5" /> Fail
                                    </button>
                                </div>
                            </div>

                            <!-- View-only message -->
                            <div v-else class="px-4 pb-4">
                                <p class="text-[11px] text-center text-slate-400 italic">
                                    View only · Contact HR to manage this interview
                                </p>
                            </div>

                            <!-- Tap hint -->
                            <div class="flex items-center justify-end gap-1 px-4 pb-2.5">
                                <p class="text-[10px] text-slate-300 dark:text-slate-600">Tap to view profile</p>
                                <ChevronRight class="h-3 w-3 text-slate-300 dark:text-slate-600" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ╔══════════════════ DETAIL SIDE PANEL (Full Applicant Details) ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="panel-backdrop">
                <div v-if="detailPanelOpen"
                    class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm"
                    @click="closeDetailPanel">
                </div>
            </Transition>

            <Transition name="panel-slide">
                <div v-if="detailPanelOpen"
                    class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col overflow-hidden">

                    <!-- Panel header with photo -->
                    <div class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-5 pt-10 pb-6">
                        <div class="absolute inset-0 opacity-10"
                            style="background-image: radial-gradient(circle at 70% 50%, white 1px, transparent 1px); background-size: 20px 20px;">
                        </div>
                        <button @click="closeDetailPanel"
                            class="absolute top-4 right-4 h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                            <X class="h-4 w-4 text-white" />
                        </button>
                        <div class="flex items-center gap-4">
                            <img v-if="detailPanelApplicant?.profile_photo"
                                :src="detailPanelApplicant.profile_photo"
                                :alt="detailPanelApplicant?.name"
                                class="h-20 w-20 rounded-2xl object-cover ring-4 ring-white/30 shadow-xl" />
                            <div v-else
                                class="h-20 w-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-black ring-4 ring-white/20 shadow-xl">
                                {{ getInitials(detailPanelApplicant?.name) }}
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-white">{{ detailPanelApplicant?.name }}</h2>
                                <p class="text-indigo-200 text-sm font-medium">{{ detailPanelApplicant?.position_applied }}</p>
                                <div class="mt-2">
                                    <span :class="[
                                        'inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-widest border',
                                        detailPanelApplicant && getStatusInfo(detailPanelApplicant).color
                                    ]">
                                        <span :class="['h-1.5 w-1.5 rounded-full', detailPanelApplicant && getStatusInfo(detailPanelApplicant).dot]"></span>
                                        {{ detailPanelApplicant && getStatusInfo(detailPanelApplicant).label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex gap-1 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 pt-3">
                        <button @click="detailPanelTab = 'personal'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'personal'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            Personal
                        </button>
                        <button @click="detailPanelTab = 'contact'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'contact'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            Contact
                        </button>
                        <button @click="detailPanelTab = 'ids'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'ids'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            IDs
                        </button>
                        <button @click="detailPanelTab = 'education'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'education'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            Education
                        </button>
                        <button @click="detailPanelTab = 'employment'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'employment'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            Employment
                        </button>
                        <button @click="detailPanelTab = 'family'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'family'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700'
                            ]">
                            Family
                        </button>
                    </div>

                    <!-- Panel body (scrollable) -->
                    <div class="flex-1 overflow-y-auto p-5 space-y-5">

                        <!-- PERSONAL TAB -->
                        <div v-if="detailPanelTab === 'personal'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <UserIcon class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Basic Information</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><p class="text-[10px] text-slate-400">Full Name</p><p class="text-sm font-medium">{{ detailPanelApplicant?.first_name }} {{ detailPanelApplicant?.middle_name }} {{ detailPanelApplicant?.last_name }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Date of Birth</p><p class="text-sm">{{ formatDateFull(detailPanelApplicant?.date_of_birth) }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Place of Birth</p><p class="text-sm">{{ detailPanelApplicant?.place_of_birth || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Age</p><p class="text-sm">{{ detailPanelApplicant?.age || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Sex</p><p class="text-sm capitalize">{{ detailPanelApplicant?.sex || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Civil Status</p><p class="text-sm capitalize">{{ detailPanelApplicant?.civil_status || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Citizenship</p><p class="text-sm">{{ detailPanelApplicant?.citizenship || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Religion</p><p class="text-sm">{{ detailPanelApplicant?.religion || '—' }}</p></div>
                                    <div><p class="text-[10px] text-slate-400">Weight / Height</p><p class="text-sm">{{ detailPanelApplicant?.weight }} kg / {{ detailPanelApplicant?.height }} cm</p></div>
                                    <div><p class="text-[10px] text-slate-400">Languages</p><p class="text-sm">{{ detailPanelApplicant?.languages || '—' }}</p></div>
                                    <div class="col-span-2"><p class="text-[10px] text-slate-400">Special Skills</p><p class="text-sm">{{ detailPanelApplicant?.special_skills || '—' }}</p></div>
                                    <div class="col-span-2"><p class="text-[10px] text-slate-400">Machine Operation</p><p class="text-sm">{{ detailPanelApplicant?.machine_operation || '—' }}</p></div>
                                </div>
                            </div>
                        </div>

                        <!-- CONTACT TAB -->
                        <div v-if="detailPanelTab === 'contact'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <PhoneIcon class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Contact & Address</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Email</p><p class="text-sm">{{ detailPanelApplicant?.email }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Phone</p><p class="text-sm">{{ detailPanelApplicant?.phone }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Address</p><p class="text-sm">{{ detailPanelApplicant?.street_address }}, {{ detailPanelApplicant?.city }}, {{ detailPanelApplicant?.state_province }} {{ detailPanelApplicant?.postal_zip_code }}</p></div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <AlertTriangle class="h-4 w-4 text-rose-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Emergency Contact</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Name</p><p class="text-sm">{{ detailPanelApplicant?.emergency_name || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Relationship</p><p class="text-sm">{{ detailPanelApplicant?.emergency_relationship || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Phone</p><p class="text-sm">{{ detailPanelApplicant?.emergency_phone || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Address</p><p class="text-sm">{{ detailPanelApplicant?.emergency_address || '—' }}</p></div>
                            </div>
                        </div>

                        <!-- GOVERNMENT IDs TAB -->
                        <div v-if="detailPanelTab === 'ids'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <CreditCard class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Government IDs</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">SSS Number</p><p class="text-sm">{{ detailPanelApplicant?.sss_number || '—' }}</p>
                                    <div v-if="detailPanelApplicant?.sss_file_url" class="mt-2">
                                        <button @click="openImagePreview(detailPanelApplicant.sss_file_url, 'SSS ID')"
                                            class="text-xs text-indigo-600 underline">View SSS ID</button>
                                    </div>
                                </div>
                                <div><p class="text-[10px] text-slate-400">PhilHealth Number</p><p class="text-sm">{{ detailPanelApplicant?.philhealth_number || '—' }}</p>
                                    <div v-if="detailPanelApplicant?.philhealth_file_url" class="mt-2">
                                        <button @click="openImagePreview(detailPanelApplicant.philhealth_file_url, 'PhilHealth ID')"
                                            class="text-xs text-indigo-600 underline">View PhilHealth ID</button>
                                    </div>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Pag-IBIG Number</p><p class="text-sm">{{ detailPanelApplicant?.pagibig_number || '—' }}</p>
                                    <div v-if="detailPanelApplicant?.pagibig_file_url" class="mt-2">
                                        <button @click="openImagePreview(detailPanelApplicant.pagibig_file_url, 'Pag-IBIG ID')"
                                            class="text-xs text-indigo-600 underline">View Pag-IBIG ID</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EDUCATION TAB -->
                        <div v-if="detailPanelTab === 'education'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <BookOpen class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Educational Background</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Elementary</p><p class="text-sm">{{ detailPanelApplicant?.elementary_school || '—' }} {{ detailPanelApplicant?.elementary_year ? `(${detailPanelApplicant.elementary_year})` : '' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">High School</p><p class="text-sm">{{ detailPanelApplicant?.high_school || '—' }} {{ detailPanelApplicant?.high_year ? `(${detailPanelApplicant.high_year})` : '' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">College</p><p class="text-sm">{{ detailPanelApplicant?.college || '—' }} {{ detailPanelApplicant?.college_year ? `(${detailPanelApplicant.college_year})` : '' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Vocational</p><p class="text-sm">{{ detailPanelApplicant?.vocational || '—' }} {{ detailPanelApplicant?.vocational_year ? `(${detailPanelApplicant.vocational_year})` : '' }}</p></div>
                            </div>
                        </div>

                        <!-- EMPLOYMENT TAB -->
                        <div v-if="detailPanelTab === 'employment'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <Briefcase class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Employment History</p>
                                </div>
                                <div v-if="detailPanelApplicant?.employment_records && detailPanelApplicant.employment_records.length">
                                    <div v-for="(rec, idx) in detailPanelApplicant.employment_records" :key="idx" class="mb-3 pb-2 border-b border-slate-200 last:border-0">
                                        <p class="text-sm font-bold">{{ rec.company }}</p>
                                        <p class="text-xs">Years: {{ rec.years }} | Salary: {{ rec.salary }} | Position: {{ rec.position }}</p>
                                        <p class="text-xs text-slate-500">Reason: {{ rec.reason }}</p>
                                    </div>
                                </div>
                                <div v-else-if="detailPanelApplicant?.previous_employment_company">
                                    <p><span class="text-[10px] text-slate-400">Company:</span> {{ detailPanelApplicant.previous_employment_company }}</p>
                                    <p><span class="text-[10px] text-slate-400">When:</span> {{ detailPanelApplicant.previous_employment_when }}</p>
                                    <p><span class="text-[10px] text-slate-400">Position:</span> {{ detailPanelApplicant.previous_employment_position }}</p>
                                    <p><span class="text-[10px] text-slate-400">Department:</span> {{ detailPanelApplicant.previous_employment_department }}</p>
                                </div>
                                <div v-else class="text-sm text-slate-500 italic">No employment history provided.</div>
                            </div>
                        </div>

                        <!-- FAMILY TAB -->
                        <div v-if="detailPanelTab === 'family'" class="space-y-4">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <Heart class="h-4 w-4 text-rose-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Family Background</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Father</p><p class="text-sm">{{ detailPanelApplicant?.father_name || '—' }}<br/>{{ detailPanelApplicant?.father_address || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Mother</p><p class="text-sm">{{ detailPanelApplicant?.mother_name || '—' }}<br/>{{ detailPanelApplicant?.mother_address || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Spouse</p><p class="text-sm">{{ detailPanelApplicant?.spouse_name || '—' }} ({{ detailPanelApplicant?.spouse_occupation || '—' }})<br/>{{ detailPanelApplicant?.spouse_address || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Number of Children</p><p class="text-sm">{{ detailPanelApplicant?.number_of_children || 0 }}</p></div>
                                <div v-if="detailPanelApplicant?.children && detailPanelApplicant.children.length">
                                    <p class="text-[10px] text-slate-400">Children</p>
                                    <div v-for="(child, i) in detailPanelApplicant.children" :key="i" class="text-sm">- {{ typeof child === 'object' ? child.name : child }}</div>
                                </div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3">
                                <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                                    <Globe class="h-4 w-4 text-indigo-500" />
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider">Referral & Relations</p>
                                </div>
                                <div><p class="text-[10px] text-slate-400">Referred By</p><p class="text-sm">{{ detailPanelApplicant?.referred_by || '—' }}<br/>{{ detailPanelApplicant?.referred_by_address || '—' }}</p></div>
                                <div><p class="text-[10px] text-slate-400">Related Employees</p><p class="text-sm">{{ detailPanelApplicant?.related_employees || '—' }}</p></div>
                            </div>
                        </div>

                        <!-- Quick actions inside panel (only for canEdit) -->
                        <div v-if="canEdit" class="space-y-2 pt-2" @click.stop>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Quick Actions</p>
                            <button v-if="!hasSchedule(detailPanelApplicant)"
                                @click="openScheduleModal(detailPanelApplicant, $event)"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                <CalendarDays class="h-4 w-4" /> Set Schedule
                            </button>
                            <button v-if="hasSchedule(detailPanelApplicant) && !isInterviewingNow(detailPanelApplicant)"
                                @click="startInterviewNow(detailPanelApplicant, $event)"
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                <PlayCircle class="h-4 w-4" /> Interview Now
                            </button>
                            <div v-if="isInterviewingNow(detailPanelApplicant)" class="grid grid-cols-2 gap-2">
                                <button @click="openPassModal(detailPanelApplicant, $event)"
                                    class="flex items-center justify-center gap-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                    <CheckCircle class="h-4 w-4" /> Pass
                                </button>
                                <button @click="openFailModal(detailPanelApplicant, $event)"
                                    class="flex items-center justify-center gap-2 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95">
                                    <XCircle class="h-4 w-4" /> Fail
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ IMAGE PREVIEW MODAL ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="imagePreview" class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                    @click.self="closeImagePreview">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ imagePreview.title }}</h3>
                            <button @click="closeImagePreview" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition">
                                <X class="w-5 h-5 text-slate-600 dark:text-slate-400" />
                            </button>
                        </div>
                        <div class="p-4 flex justify-center bg-slate-100 dark:bg-slate-900">
                            <img :src="imagePreview.url" :alt="imagePreview.title" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ SCHEDULE MODAL (unchanged) ══════════════════╗ -->
        <!-- (Kept as in original – no changes needed) -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isScheduleModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white dark:bg-slate-800 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
                        <div class="bg-gradient-to-r from-indigo-600 to-violet-600 p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2 class="text-base font-black text-white uppercase tracking-wider">Set Schedule</h2>
                                    <p class="text-indigo-200 text-xs mt-0.5">{{ selectedApplicant?.name }}</p>
                                </div>
                                <button @click="closeModals"
                                    class="h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                    <X class="h-4 w-4 text-white" />
                                </button>
                            </div>
                        </div>
                        <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="form-label">Date & Time *</label>
                                <input type="datetime-local" v-model="scheduleForm.scheduled_at"
                                    class="form-input" required />
                            </div>
                            <div>
                                <label class="form-label">Interview Type *</label>
                                <select v-model="scheduleForm.interview_type" class="form-input">
                                    <option value="">Select type</option>
                                    <option value="phone">Phone Screen</option>
                                    <option value="technical">Technical Interview</option>
                                    <option value="behavioral">Behavioral Interview</option>
                                    <option value="onsite">On-site Interview</option>
                                    <option value="video">Video Conference</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">Duration (min)</label>
                                    <select v-model="scheduleForm.duration" class="form-input">
                                        <option value="15">15 min</option>
                                        <option value="30">30 min</option>
                                        <option value="45">45 min</option>
                                        <option value="60">60 min</option>
                                        <option value="90">90 min</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label">Location</label>
                                    <input type="text" v-model="scheduleForm.location"
                                        class="form-input" placeholder="Office / Link" />
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Interviewer(s)</label>
                                <input type="text" v-model="scheduleForm.interviewer"
                                    class="form-input" placeholder="Name(s) of interviewer" />
                            </div>
                            <div>
                                <label class="form-label">Notes</label>
                                <textarea v-model="scheduleForm.notes" rows="2"
                                    class="form-input resize-none" placeholder="Additional instructions..."></textarea>
                            </div>
                        </div>
                        <div class="flex gap-3 px-5 pb-5">
                            <button @click="closeModals"
                                class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors">
                                Cancel
                            </button>
                            <button @click="scheduleInterview"
                                class="flex-1 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-indigo-200 transition-all active:scale-95">
                                Confirm Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ PASS MODAL (unchanged) ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isPassModalOpen"
                    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-8 text-center">
                            <div class="inline-flex h-16 w-16 rounded-2xl bg-white/20 items-center justify-center mb-4 shadow-inner">
                                <CheckCircle class="h-8 w-8 text-white" />
                            </div>
                            <h2 class="text-lg font-black text-white uppercase tracking-wider">Pass Candidate</h2>
                            <p class="text-emerald-100 text-xs mt-1">This will convert them into a trainee</p>
                        </div>
                        <div class="p-6 text-center">
                            <p class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed">
                                Are you sure you want to pass
                                <strong class="text-slate-900 dark:text-white">{{ selectedApplicant?.name }}</strong>?
                                They will become a trainee in your department.
                            </p>
                            <div class="flex gap-3 mt-6">
                                <button @click="closeModals"
                                    class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                                <button @click="passApplicant"
                                    class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-emerald-200 transition-all active:scale-95">
                                    Confirm Pass
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ FAIL MODAL (unchanged) ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="isFailModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals">
                    <div class="bg-white dark:bg-slate-800 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden">

                        <div class="bg-gradient-to-br from-rose-600 to-red-700 p-5">
                            <div class="flex items-center gap-3">
                                <button v-if="failStep !== 'choose'" @click="failStep = 'choose'"
                                    class="h-7 w-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                    <ChevronLeft class="h-4 w-4 text-white" />
                                </button>
                                <div class="flex-1">
                                    <h2 class="text-sm font-black text-white uppercase tracking-wider">
                                        {{ failStep === 'choose' ? 'Fail Candidate' :
                                           failStep === 'confirm_fail' ? 'Confirm Failure' : 'Pass to Module' }}
                                    </h2>
                                    <p class="text-rose-200 text-xs mt-0.5">{{ selectedApplicant?.name }}</p>
                                </div>
                                <button @click="closeModals"
                                    class="h-7 w-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                    <X class="h-4 w-4 text-white" />
                                </button>
                            </div>
                        </div>

                        <div v-if="failStep === 'choose'" class="p-5 space-y-3">
                            <p class="text-xs text-slate-500 dark:text-slate-400 text-center pb-1">
                                What would you like to do with this candidate?
                            </p>
                            <button @click="failStep = 'confirm_fail'"
                                class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-rose-100 hover:border-rose-300 hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/20 text-left transition-all group">
                                <div class="h-10 w-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0 group-hover:bg-rose-200 transition-colors">
                                    <Ban class="h-5 w-5 text-rose-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">Fail Candidate</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Permanently reject with a reason</p>
                                </div>
                                <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-rose-400 transition-colors" />
                            </button>
                            <button @click="failStep = 'pass_to_other'"
                                class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-violet-100 hover:border-violet-300 hover:bg-violet-50 dark:border-violet-900/40 dark:hover:bg-violet-900/20 text-left transition-all group">
                                <div class="h-10 w-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0 group-hover:bg-violet-200 transition-colors">
                                    <Layers class="h-5 w-5 text-violet-600" />
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">Pass to Another Module</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Forward for interview in other department</p>
                                </div>
                                <ChevronRight class="h-4 w-4 text-slate-300 group-hover:text-violet-400 transition-colors" />
                            </button>
                            <button @click="closeModals"
                                class="w-full py-3 text-slate-400 text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                                Cancel
                            </button>
                        </div>

                        <div v-else-if="failStep === 'confirm_fail'" class="p-5 space-y-4">
                            <div>
                                <label class="form-label">Reason for Failure *</label>
                                <textarea v-model="failReason" rows="4"
                                    class="form-input resize-none focus:ring-rose-500"
                                    placeholder="e.g., Insufficient technical skills, poor communication..."></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button @click="failStep = 'choose'"
                                    class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors">
                                    Back
                                </button>
                                <button @click="failApplicant" :disabled="!failReason.trim()"
                                    class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-rose-200 transition-all active:scale-95">
                                    Confirm Fail
                                </button>
                            </div>
                        </div>

                        <div v-else-if="failStep === 'pass_to_other'" class="p-5 space-y-4">
                            <div>
                                <label class="form-label">Select Department Module *</label>
                                <div class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto pr-1">
                                    <label v-for="mod in modules" :key="mod.value"
                                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="otherModule === mod.value
                                            ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20'
                                            : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600'">
                                        <input type="radio" v-model="otherModule" :value="mod.value" class="sr-only" />
                                        <div class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0"
                                            :style="{ backgroundColor: mod.color + '20', color: mod.color }">
                                            <Building class="h-3.5 w-3.5" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex-1">
                                            {{ mod.label }}
                                        </span>
                                        <span v-if="otherModule === mod.value"
                                            class="h-5 w-5 rounded-full bg-violet-500 flex items-center justify-center">
                                            <CheckCircle class="h-3 w-3 text-white" />
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button @click="failStep = 'choose'"
                                    class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors">
                                    Back
                                </button>
                                <button @click="passToOtherModule" :disabled="!otherModule"
                                    class="flex-1 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-violet-200 transition-all active:scale-95">
                                    Forward
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&display=swap');

* { font-family: 'DM Sans', sans-serif; }

/* ── Form ────────────────────────────────────────── */
.form-label {
    display: block;
    font-size: 10px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: 6px;
}
.form-input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    background: #f8fafc;
    border: none;
    box-shadow: 0 0 0 1.5px #e2e8f0;
    font-size: 13px;
    font-weight: 500;
    color: #1e293b;
    transition: box-shadow 0.15s;
    outline: none;
}
.form-input:focus {
    box-shadow: 0 0 0 2px #6366f1;
}
@media (prefers-color-scheme: dark) {
    .form-input {
        background: #1e293b;
        color: #f1f5f9;
        box-shadow: 0 0 0 1.5px #334155;
    }
}

/* ── Toast ─────────────────────────────────────────── */
.toast-enter-active, .toast-leave-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(-16px); }

/* ── Modal ─────────────────────────────────────────── */
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div, .modal-leave-to > div { transform: translateY(24px) scale(0.97); }

/* ── Panel slide ───────────────────────────────────── */
.panel-slide-enter-active { transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1); }
.panel-slide-leave-active { transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1); }
.panel-slide-enter-from  { transform: translateX(100%); }
.panel-slide-leave-to    { transform: translateX(100%); }

.panel-backdrop-enter-active { transition: opacity 0.3s ease; }
.panel-backdrop-leave-active { transition: opacity 0.3s ease; }
.panel-backdrop-enter-from, .panel-backdrop-leave-to { opacity: 0; }

/* ── Card hover ─────────────────────────────────────── */
.applicant-card {
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

/* ── Scrollbar ──────────────────────────────────────── */
::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 999px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

/* ── Interview Now button pulse ─────────────────────── */
.interview-now-btn {
    animation: pulse-indigo 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse-indigo {
    0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
    50%       { box-shadow: 0 0 0 6px rgba(99, 102, 241, 0); }
}
</style>