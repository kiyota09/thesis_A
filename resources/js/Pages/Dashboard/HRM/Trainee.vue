<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Award, Star, CheckCircle, XCircle, User, Bell,
    UserCheck, UserMinus, AlertTriangle, TrendingUp
} from 'lucide-vue-next';

const props = defineProps({
    trainees: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) }
});

const canEdit = computed(() => props.permissions?.trainee === 'edit');

// ─── Local list so we can remove passed trainees reactively ──────────────────
const localTrainees = ref([...props.trainees]);

// ─── Toast ────────────────────────────────────────────────────────────────────
const toast = ref({ show: false, message: '', type: 'success' });
const triggerToast = (msg, type = 'success') => {
    toast.value = { show: true, message: msg, type };
    setTimeout(() => { toast.value.show = false; }, 4500);
};

const page = usePage();
if (page.props.flash?.message) triggerToast(page.props.flash.message, 'success');
if (page.props.flash?.error)   triggerToast(page.props.flash.error, 'error');

// ─── Modal states ─────────────────────────────────────────────────────────────
const isGradeModalOpen   = ref(false);
const isSweetAlertOpen   = ref(false);   // SweetAlert-style pass confirmation
const isPassSuccessOpen  = ref(false);   // SweetAlert-style pass success
const isFailModalOpen    = ref(false);
const selectedTrainee    = ref(null);
const failReason         = ref('');
const isSubmitting       = ref(false);

// ─── Grade form ───────────────────────────────────────────────────────────────
const gradeForm = ref({
    skills_performance: 0,
    behaviour: 0,
    technicals: 0,
    safety_awareness: 0,
    productivity: 0
});

const criteria = [
    { id: 'skills_performance', label: 'Skills Performance' },
    { id: 'behaviour',          label: 'Behaviour' },
    { id: 'technicals',         label: 'Technicals' },
    { id: 'safety_awareness',   label: 'Safety Awareness' },
    { id: 'productivity',       label: 'Productivity' }
];

const gradeTotal  = computed(() => {
    const sum = criteria.reduce((acc, c) => acc + (gradeForm.value[c.id] || 0), 0);
    return (sum / 25) * 100;
});
const isPassing = computed(() => gradeTotal.value >= 80);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials  = name => name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';
const formatDate   = d => d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
const gradeColor   = pct => pct >= 80 ? 'text-emerald-600' : pct >= 60 ? 'text-blue-600' : pct >= 40 ? 'text-amber-500' : 'text-red-500';
const barColor     = pct => pct >= 80 ? 'bg-emerald-500' : pct >= 60 ? 'bg-blue-500' : pct >= 40 ? 'bg-amber-500' : 'bg-red-500';
const hasGrade     = t => t.trainee_grade && t.trainee_grade.total_percentage > 0;
const isApproved   = t => hasGrade(t) && t.trainee_grade.total_percentage >= 80;

// ─── Open modals ──────────────────────────────────────────────────────────────
const openGradeModal = (trainee) => {
    if (!canEdit.value) { triggerToast('You do not have permission to grade trainees.', 'error'); return; }
    selectedTrainee.value = trainee;
    gradeForm.value = trainee.trainee_grade ? {
        skills_performance: trainee.trainee_grade.skills_performance || 0,
        behaviour:          trainee.trainee_grade.behaviour          || 0,
        technicals:         trainee.trainee_grade.technicals         || 0,
        safety_awareness:   trainee.trainee_grade.safety_awareness   || 0,
        productivity:       trainee.trainee_grade.productivity       || 0
    } : { skills_performance: 0, behaviour: 0, technicals: 0, safety_awareness: 0, productivity: 0 };
    isGradeModalOpen.value = true;
};

const openPassSweetAlert = (trainee) => {
    if (!canEdit.value) { triggerToast('You do not have permission to approve trainees.', 'error'); return; }
    selectedTrainee.value = trainee;
    isSweetAlertOpen.value = true;
};

const openFailModal = (trainee) => {
    if (!canEdit.value) { triggerToast('You do not have permission to fail trainees.', 'error'); return; }
    selectedTrainee.value = trainee;
    failReason.value = '';
    isFailModalOpen.value = true;
};

const closeModals = () => {
    isGradeModalOpen.value  = false;
    isSweetAlertOpen.value  = false;
    isPassSuccessOpen.value = false;
    isFailModalOpen.value   = false;
    selectedTrainee.value   = null;
    failReason.value        = '';
    isSubmitting.value      = false;
    gradeForm.value = { skills_performance: 0, behaviour: 0, technicals: 0, safety_awareness: 0, productivity: 0 };
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const submitGrade = () => {
    if (criteria.some(c => gradeForm.value[c.id] === 0)) {
        triggerToast('Please rate all criteria before submitting.', 'error');
        return;
    }
    isSubmitting.value = true;
    router.post(route('hrm.trainee.grade', selectedTrainee.value.id), gradeForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            // Update local trainee grade so card updates instantly
            const idx = localTrainees.value.findIndex(t => t.id === selectedTrainee.value.id);
            if (idx !== -1) {
                localTrainees.value[idx].trainee_grade = {
                    ...gradeForm.value,
                    total_percentage: gradeTotal.value
                };
            }
            triggerToast('Grade saved successfully!', 'success');
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to save grade.', 'error');
            isSubmitting.value = false;
        }
    });
};

const confirmPassTrainee = () => {
    isSweetAlertOpen.value = false;
    isSubmitting.value = true;
    router.post(route('hrm.trainee.pass', selectedTrainee.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Remove trainee from local list – they move to Onboarding
            localTrainees.value = localTrainees.value.filter(t => t.id !== selectedTrainee.value.id);
            isSubmitting.value = false;
            isPassSuccessOpen.value = true;
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to approve trainee.', 'error');
            isSubmitting.value = false;
        }
    });
};

const failTrainee = () => {
    if (!failReason.value.trim()) { triggerToast('Please provide a reason for failure.', 'error'); return; }
    isSubmitting.value = true;
    router.post(route('hrm.trainee.fail', selectedTrainee.value.id), { reason: failReason.value }, {
        preserveScroll: true,
        onSuccess: () => {
            localTrainees.value = localTrainees.value.filter(t => t.id !== selectedTrainee.value.id);
            triggerToast('Trainee has been archived.', 'success');
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to process.', 'error');
            isSubmitting.value = false;
        }
    });
};
</script>

<template>
    <Head title="Trainee Management" />
    <AuthenticatedLayout>

        <!-- ═══ TOAST ═══════════════════════════════════════════════════════════ -->
        <Transition name="toast">
            <div v-if="toast.show"
                class="fixed top-4 right-4 z-[200] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-xl border max-w-xs sm:max-w-sm"
                :class="toast.type === 'success'
                    ? 'bg-white border-emerald-200 text-slate-800'
                    : 'bg-white border-red-200 text-slate-800'">
                <div class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center"
                    :class="toast.type === 'success' ? 'bg-emerald-100' : 'bg-red-100'">
                    <CheckCircle v-if="toast.type === 'success'" class="w-4 h-4 text-emerald-600" />
                    <XCircle v-else class="w-4 h-4 text-red-500" />
                </div>
                <p class="text-sm font-semibold leading-tight">{{ toast.message }}</p>
            </div>
        </Transition>

        <!-- ═══ PAGE CONTENT ═════════════════════════════════════════════════════ -->
        <div class="min-h-screen bg-gray-50/60 px-4 py-6 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">HRM · Trainee Module</p>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-none">
                            Trainee <span class="text-blue-600">Development</span>
                        </h1>
                        <p class="text-slate-500 text-sm mt-1.5 font-medium">
                            Grade and manage your department's trainees.
                        </p>
                    </div>
                    <!-- Access badge -->
                    <span v-if="canEdit"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200 self-start sm:self-auto">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Full Edit Access
                    </span>
                    <span v-else
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 self-start sm:self-auto">
                        View Only
                    </span>
                </div>

                <!-- Stats bar -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
                            <User class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 leading-none">{{ localTrainees.length }}</p>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Active Trainees</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                            <Star class="w-5 h-5 text-amber-500" />
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 leading-none">
                                {{ localTrainees.filter(t => hasGrade(t)).length }}
                            </p>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Graded</p>
                        </div>
                    </div>
                    <div class="col-span-2 sm:col-span-1 bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
                            <TrendingUp class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900 leading-none">
                                {{ localTrainees.filter(t => isApproved(t)).length }}
                            </p>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Ready for HR</p>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-if="localTrainees.length === 0"
                    class="bg-white rounded-3xl border border-slate-100 shadow-sm p-12 sm:p-16 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <User class="w-8 h-8 text-slate-400" />
                    </div>
                    <h3 class="text-base font-bold text-slate-700">No active trainees</h3>
                    <p class="text-sm text-slate-400 mt-1.5 max-w-xs mx-auto">
                        When applicants pass the interview for your department, they will appear here.
                    </p>
                </div>

                <!-- ─── Trainee Cards ─────────────────────────────────────────── -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
                    <div v-for="trainee in localTrainees" :key="trainee.id"
                        class="bg-white rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200 overflow-hidden flex flex-col">

                        <!-- Card top accent -->
                        <div class="h-1 w-full"
                            :class="isApproved(trainee) ? 'bg-gradient-to-r from-blue-500 to-blue-400'
                                  : hasGrade(trainee)   ? 'bg-gradient-to-r from-amber-400 to-yellow-400'
                                  : 'bg-slate-100'">
                        </div>

                        <div class="p-5 flex flex-col flex-1">
                            <!-- Avatar + info -->
                            <div class="flex items-start gap-3.5">
                                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-base font-black shrink-0 border border-blue-100">
                                    {{ getInitials(trainee.name) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-bold text-slate-900 text-sm leading-tight truncate">{{ trainee.name }}</h3>
                                    <p class="text-xs text-slate-400 truncate mt-0.5">{{ trainee.email }}</p>
                                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-wide">
                                            {{ trainee.role }}
                                        </span>
                                        <span class="text-[10px] text-slate-400">
                                            Joined {{ formatDate(trainee.join_date) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Grade section -->
                            <div class="mt-4 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Overall Grade</p>
                                    <p class="text-sm font-black" :class="gradeColor(trainee.trainee_grade?.total_percentage || 0)">
                                        {{ (trainee.trainee_grade?.total_percentage || 0).toFixed(1) }}%
                                    </p>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700"
                                        :class="barColor(trainee.trainee_grade?.total_percentage || 0)"
                                        :style="{ width: `${trainee.trainee_grade?.total_percentage || 0}%` }">
                                    </div>
                                </div>
                                <p v-if="!hasGrade(trainee)" class="text-[10px] text-slate-400 mt-1.5 font-medium">
                                    Not yet graded
                                </p>
                                <p v-else-if="isApproved(trainee)" class="text-[10px] text-emerald-600 mt-1.5 font-bold flex items-center gap-1">
                                    <CheckCircle class="w-3 h-3" /> Ready for HR conversion
                                </p>
                                <p v-else class="text-[10px] text-amber-600 mt-1.5 font-medium">
                                    Needs 80% to qualify
                                </p>
                            </div>

                            <!-- Grading criteria breakdown (if graded) -->
                            <div v-if="hasGrade(trainee)" class="mt-3 grid grid-cols-5 gap-1">
                                <div v-for="c in criteria" :key="c.id" class="text-center">
                                    <div class="flex justify-center gap-0.5 mb-0.5">
                                        <span v-for="s in 5" :key="s"
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="trainee.trainee_grade?.[c.id] >= s ? 'bg-amber-400' : 'bg-slate-200'">
                                        </span>
                                    </div>
                                    <p class="text-[9px] text-slate-400 leading-tight font-medium">{{ c.label.split(' ')[0] }}</p>
                                </div>
                            </div>

                            <!-- Spacer -->
                            <div class="flex-1"></div>

                            <!-- Action buttons -->
                            <div v-if="canEdit" class="mt-4 space-y-2">
                                <!-- Grade button always visible -->
                                <button @click="openGradeModal(trainee)"
                                    class="w-full flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold transition-all duration-150 border"
                                    :class="hasGrade(trainee)
                                        ? 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'
                                        : 'bg-amber-400 text-white border-amber-400 hover:bg-amber-500 shadow-sm shadow-amber-200'">
                                    <Star class="w-3.5 h-3.5" />
                                    {{ hasGrade(trainee) ? 'Update Grade' : 'Grade Trainee' }}
                                </button>

                                <!-- Pass (only if grade >= 80) or Fail -->
                                <div v-if="hasGrade(trainee)" class="flex gap-2">
                                    <button v-if="isApproved(trainee)" @click="openPassSweetAlert(trainee)"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold bg-blue-600 text-white border border-blue-600 hover:bg-blue-700 transition-all shadow-sm shadow-blue-200">
                                        <Bell class="w-3.5 h-3.5" /> Notify HR
                                    </button>
                                    <button @click="openFailModal(trainee)"
                                        class="flex-1 flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold bg-white text-red-600 border border-red-200 hover:bg-red-50 transition-all">
                                        <UserMinus class="w-3.5 h-3.5" /> Fail
                                    </button>
                                </div>
                            </div>

                            <!-- View-only note -->
                            <p v-else class="mt-4 text-center text-[10px] text-slate-400 italic">
                                View only – contact your manager to grade
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ GRADE MODAL ══════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
            <div v-if="isGradeModalOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="closeModals">
                <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden max-h-[92vh] flex flex-col">

                    <!-- Modal header -->
                    <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                        <div>
                            <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">Grading</p>
                            <h2 class="text-lg font-black text-slate-900 leading-tight mt-0.5">Coursework Evaluation</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ selectedTrainee?.name }}</p>
                        </div>
                        <button @click="closeModals"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-500 font-bold text-lg leading-none">
                            &times;
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="overflow-y-auto px-6 py-5 space-y-5 flex-1">
                        <div v-for="criterion in criteria" :key="criterion.id">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-slate-600">{{ criterion.label }}</p>
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                    :class="gradeForm[criterion.id] > 0
                                        ? 'bg-amber-50 text-amber-600'
                                        : 'bg-slate-100 text-slate-400'">
                                    {{ gradeForm[criterion.id] > 0 ? `${gradeForm[criterion.id]}/5` : 'Not rated' }}
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <button v-for="star in 5" :key="star" type="button"
                                    @click="gradeForm[criterion.id] = star"
                                    class="focus:outline-none transition-transform active:scale-110 hover:scale-105">
                                    <Star :class="[
                                        'w-7 h-7 transition-colors',
                                        gradeForm[criterion.id] >= star
                                            ? 'text-amber-400 fill-amber-400'
                                            : 'text-slate-200 hover:text-amber-200'
                                    ]" />
                                </button>
                            </div>
                        </div>

                        <!-- Grade result -->
                        <div v-if="gradeTotal > 0"
                            class="rounded-xl border-2 p-4 transition-all"
                            :class="isPassing ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Score</p>
                                    <p class="text-3xl font-black mt-0.5"
                                        :class="isPassing ? 'text-emerald-600' : 'text-amber-600'">
                                        {{ gradeTotal.toFixed(1) }}%
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold"
                                    :class="isPassing ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                    <CheckCircle v-if="isPassing" class="w-4 h-4" />
                                    <XCircle v-else class="w-4 h-4" />
                                    {{ isPassing ? 'Qualifies for HR' : 'Below 80% threshold' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal footer -->
                    <div class="px-6 py-4 border-t border-slate-100 flex gap-3 shrink-0">
                        <button @click="closeModals"
                            class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border border-slate-200">
                            Cancel
                        </button>
                        <button @click="submitGrade" :disabled="!gradeTotal || isSubmitting"
                            class="flex-1 py-3 rounded-xl text-sm font-bold text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="isSubmitting ? 'bg-amber-400' : 'bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-200'">
                            {{ isSubmitting ? 'Saving…' : 'Save Grades' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ═══ SWEETALERT — PASS CONFIRMATION ══════════════════════════════════ -->
        <Transition name="sweet">
            <div v-if="isSweetAlertOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm text-center overflow-hidden sweet-bounce">

                    <!-- Icon area -->
                    <div class="pt-10 pb-6 px-8">
                        <div class="w-20 h-20 rounded-full bg-blue-50 border-4 border-blue-100 flex items-center justify-center mx-auto mb-6 sweet-icon">
                            <Bell class="w-9 h-9 text-blue-600" />
                        </div>
                        <h2 class="text-xl font-black text-slate-900 mb-2">Notify HR?</h2>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            You're about to notify HR to officially convert
                            <strong class="text-slate-800 font-bold">{{ selectedTrainee?.name }}</strong>
                            from <em>Trainee</em> to <em>Staff</em>.
                        </p>
                        <div class="mt-4 flex items-center justify-center gap-2 text-xs font-bold text-blue-700 bg-blue-50 rounded-xl px-4 py-2.5 border border-blue-100">
                            <TrendingUp class="w-3.5 h-3.5 shrink-0" />
                            Grade: {{ selectedTrainee?.trainee_grade?.total_percentage?.toFixed(1) }}% · Qualified ✓
                        </div>
                        <p class="text-[11px] text-slate-400 mt-3">
                            This trainee will be removed from your list and moved to Onboarding.
                        </p>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-slate-100"></div>

                    <!-- Buttons -->
                    <div class="flex">
                        <button @click="closeModals"
                            class="flex-1 py-4 text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border-r border-slate-100">
                            Cancel
                        </button>
                        <button @click="confirmPassTrainee" :disabled="isSubmitting"
                            class="flex-1 py-4 text-sm font-black text-blue-600 hover:bg-blue-50 transition-colors disabled:opacity-60">
                            {{ isSubmitting ? 'Processing…' : 'Yes, Notify HR' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ═══ SWEETALERT — PASS SUCCESS ════════════════════════════════════════ -->
        <Transition name="sweet">
            <div v-if="isPassSuccessOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm text-center overflow-hidden sweet-bounce">

                    <!-- Animated success circle -->
                    <div class="pt-10 pb-6 px-8">
                        <div class="w-20 h-20 rounded-full bg-emerald-50 border-4 border-emerald-200 flex items-center justify-center mx-auto mb-6 success-pop">
                            <CheckCircle class="w-9 h-9 text-emerald-500 fill-emerald-100" />
                        </div>
                        <h2 class="text-xl font-black text-slate-900 mb-2">HR Notified!</h2>
                        <p class="text-sm text-slate-500 leading-relaxed">
                            <strong class="text-slate-800 font-bold">{{ selectedTrainee?.name }}</strong> has been forwarded to
                            <strong class="text-blue-600">Onboarding</strong>. HR will complete the conversion to Staff.
                        </p>
                        <div class="mt-4 flex items-center justify-center gap-2 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-xl px-4 py-2.5 border border-emerald-100">
                            <CheckCircle class="w-3.5 h-3.5 shrink-0" />
                            Removed from your trainee list
                        </div>
                    </div>

                    <div class="border-t border-slate-100"></div>
                    <button @click="closeModals"
                        class="w-full py-4 text-sm font-black text-blue-600 hover:bg-blue-50 transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </Transition>

        <!-- ═══ FAIL MODAL ════════════════════════════════════════════════════════ -->
        <Transition name="modal-fade">
            <div v-if="isFailModalOpen"
                class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/40 backdrop-blur-sm"
                @click.self="closeModals">
                <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-hidden">

                    <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Action Required</p>
                            <h2 class="text-lg font-black text-slate-900 mt-0.5">Archive Trainee</h2>
                            <p class="text-xs text-slate-400 mt-0.5">{{ selectedTrainee?.name }}</p>
                        </div>
                        <button @click="closeModals"
                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors text-slate-500 font-bold text-lg leading-none">
                            &times;
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        <div class="flex items-start gap-3 p-3.5 bg-red-50 rounded-xl border border-red-100 text-xs text-red-700 font-medium">
                            <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5" />
                            This trainee will be marked inactive and removed from active lists. This action cannot be undone.
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">
                                Reason for failure <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="failReason" rows="3"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 focus:border-red-300 focus:ring-2 focus:ring-red-100 focus:outline-none text-sm text-slate-700 resize-none transition-all placeholder:text-slate-400"
                                placeholder="e.g., Failed to meet performance standards during training period…">
                            </textarea>
                        </div>

                        <div class="flex gap-3 pt-1">
                            <button @click="closeModals"
                                class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-50 transition-colors border border-slate-200">
                                Cancel
                            </button>
                            <button @click="failTrainee" :disabled="!failReason.trim() || isSubmitting"
                                class="flex-1 py-3 rounded-xl text-sm font-bold text-white bg-red-500 hover:bg-red-600 transition-all shadow-sm shadow-red-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isSubmitting ? 'Processing…' : 'Confirm & Archive' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

    </AuthenticatedLayout>
</template>

<style scoped>
/* Toast */
.toast-enter-active, .toast-leave-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(24px) scale(0.95); }

/* Regular modal */
.modal-fade-enter-active, .modal-fade-leave-active { transition: all 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.modal-fade-enter-from .bg-white, .modal-fade-leave-to .bg-white { transform: translateY(20px); }

/* SweetAlert */
.sweet-enter-active, .sweet-leave-active { transition: all 0.3s ease; }
.sweet-enter-from, .sweet-leave-to { opacity: 0; }

.sweet-bounce {
    animation: sweetBounce 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes sweetBounce {
    from { transform: scale(0.7); opacity: 0; }
    to   { transform: scale(1);   opacity: 1; }
}

.sweet-icon {
    animation: iconPulse 0.5s ease 0.15s both;
}
@keyframes iconPulse {
    from { transform: scale(0.5); opacity: 0; }
    60%  { transform: scale(1.15); }
    to   { transform: scale(1); opacity: 1; }
}

.success-pop {
    animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
}
@keyframes successPop {
    from { transform: scale(0.3) rotate(-15deg); opacity: 0; }
    to   { transform: scale(1) rotate(0deg); opacity: 1; }
}
</style>