<template>
    <Head title="Logistics Trainee Development" />

    <AuthenticatedLayout>
        <!-- Toast Notification -->
        <Transition name="toast">
            <div v-if="showToast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl border max-w-xs w-full mx-4"
                :class="toastType === 'error' ? 'bg-rose-600 border-rose-500 text-white' : 'bg-slate-900 border-slate-700 text-white'">
                <CheckCircle v-if="toastType !== 'error'" class="h-4 w-4 text-emerald-400 shrink-0" />
                <XCircle v-else class="h-4 w-4 text-rose-300 shrink-0" />
                <p class="text-xs font-semibold tracking-wide flex-1">{{ toastMessage }}</p>
            </div>
        </Transition>

        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 pb-24">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-8">

                <!-- Header -->
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-indigo-500 uppercase tracking-[0.15em] mb-1">
                                Logistics Department
                            </p>
                            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                Logistics Trainees
                            </h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ trainees.length }} trainee{{ trainees.length !== 1 ? 's' : '' }} in development
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 mt-1">
                            <span v-if="canEdit" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full uppercase tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"></span> Full Access
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full uppercase tracking-wide">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-400 inline-block"></span> View Only
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Trainees Table -->
                <div v-if="trainees.length === 0" class="bg-white dark:bg-slate-800 rounded-[2rem] border border-slate-100 dark:border-slate-700 p-12 text-center">
                    <div class="inline-flex p-6 bg-slate-100 dark:bg-slate-700 rounded-full mb-4">
                        <GraduationCap class="h-10 w-10 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-600 dark:text-slate-400">No trainees yet</h3>
                    <p class="text-sm text-slate-500 mt-2">When applicants pass the interview, they will appear here as trainees.</p>
                </div>

                <div v-else class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-700">
                                <tr>
                                    <th class="text-left px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Trainee</th>
                                    <th class="text-left px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Join Date</th>
                                    <th class="text-left px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Grade</th>
                                    <th class="text-left px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="text-right px-5 py-4 text-[10px] font-black text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr v-for="trainee in trainees" :key="trainee.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-sm font-black shadow-sm">
                                                {{ getInitials(trainee.name) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-white">{{ trainee.name }}</p>
                                                <p class="text-[11px] text-slate-500">{{ trainee.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-slate-600 dark:text-slate-300 text-xs">{{ formatDate(trainee.join_date) }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 bg-slate-200 dark:bg-slate-700 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full transition-all duration-500"
                                                    :class="getGradeColor(trainee.grade_percentage)"
                                                    :style="{ width: `${trainee.grade_percentage}%` }"></div>
                                            </div>
                                            <span class="text-xs font-bold" :class="getGradeTextColor(trainee.grade_percentage)">
                                                {{ trainee.grade_percentage }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span v-if="trainee.grade_percentage >= 80" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Ready for promotion
                                        </span>
                                        <span v-else-if="trainee.grade_percentage > 0" class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span> In progress
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Not graded
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div v-if="canEdit" class="flex items-center justify-end gap-2">
                                            <button @click="openGradeModal(trainee)"
                                                class="p-2 rounded-lg text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition"
                                                title="Grade trainee">
                                                <Edit3 class="h-4 w-4" />
                                            </button>
                                            <button v-if="trainee.grade_percentage >= 80"
                                                @click="openPassModal(trainee)"
                                                class="p-2 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 transition"
                                                title="Pass to staff">
                                                <UserCheck class="h-4 w-4" />
                                            </button>
                                            <button @click="openFailModal(trainee)"
                                                class="p-2 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition"
                                                title="Fail trainee">
                                                <UserMinus class="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">Read only</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Modal -->
        <div v-if="isGradeModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="bg-indigo-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Grade Trainee</h2>
                    <p class="text-indigo-200 text-xs mt-1">{{ selectedTrainee?.name }}</p>
                </div>
                <div class="p-6 space-y-5">
                    <div class="space-y-3">
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Skills & Performance (1-5)</label>
                            <input type="number" v-model.number="gradeForm.skills_performance" min="1" max="5" step="1"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Behaviour & Attitude (1-5)</label>
                            <input type="number" v-model.number="gradeForm.behaviour" min="1" max="5" step="1"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Technicals & Logistics Knowledge (1-5)</label>
                            <input type="number" v-model.number="gradeForm.technicals" min="1" max="5" step="1"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Safety Awareness (1-5)</label>
                            <input type="number" v-model.number="gradeForm.safety_awareness" min="1" max="5" step="1"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-1.5">Productivity & Efficiency (1-5)</label>
                            <input type="number" v-model.number="gradeForm.productivity" min="1" max="5" step="1"
                                class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900 rounded-xl p-3 text-center">
                        <p class="text-[10px] text-slate-400 uppercase tracking-wider">Total Percentage</p>
                        <p class="text-2xl font-black" :class="computedGradePercentage >= 80 ? 'text-emerald-600' : 'text-amber-600'">
                            {{ computedGradePercentage }}%
                        </p>
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="submitGrade" class="flex-1 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-indigo-700 transition-all">Save Grade</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pass Modal -->
        <div v-if="isPassModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-sm overflow-hidden text-center">
                <div class="bg-emerald-600 p-8 text-white">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                        <UserCheck class="h-8 w-8" />
                    </div>
                    <h2 class="text-xl font-black uppercase">Promote to Staff</h2>
                </div>
                <div class="p-6">
                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        Are you sure you want to promote <strong>{{ selectedTrainee?.name }}</strong> to Logistics Staff?
                        They will receive default dashboard access.
                    </p>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="passTrainee" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-emerald-700 transition-all">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fail Modal -->
        <div v-if="isFailModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md" @click.self="closeModals">
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-rose-600 p-6 text-white">
                    <h2 class="text-xl font-black uppercase">Fail Trainee</h2>
                    <p class="text-rose-200 text-xs mt-1">Provide reason for failure</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest block mb-2">Reason *</label>
                        <textarea v-model="failReason" rows="3" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-900 border-none ring-1 ring-slate-200 dark:ring-slate-700 focus:ring-2 focus:ring-rose-500" placeholder="e.g., Failed to meet performance targets..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button @click="closeModals" class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase">Cancel</button>
                        <button @click="failTrainee" :disabled="!failReason.trim()" class="flex-1 py-3 bg-rose-600 text-white rounded-xl text-xs font-black uppercase shadow-lg hover:bg-rose-700 transition-all disabled:opacity-50">Confirm Fail</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    GraduationCap, CheckCircle, XCircle, Edit3, UserCheck, UserMinus,
    Calendar, Mail, Phone, X
} from 'lucide-vue-next';

const props = defineProps({
    trainees: {
        type: Array,
        default: () => []
    },
    permissions: {
        type: Object,
        default: () => ({})
    }
});

const canEdit = computed(() => props.permissions?.trainee === 'edit');

// Toast
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
if (page.props.flash?.error) triggerToast(page.props.flash.error, 'error');

// Modal states
const isGradeModalOpen = ref(false);
const isPassModalOpen = ref(false);
const isFailModalOpen = ref(false);
const selectedTrainee = ref(null);
const failReason = ref('');

// Grade form
const gradeForm = ref({
    skills_performance: 3,
    behaviour: 3,
    technicals: 3,
    safety_awareness: 3,
    productivity: 3
});

const computedGradePercentage = computed(() => {
    const total = gradeForm.value.skills_performance +
                  gradeForm.value.behaviour +
                  gradeForm.value.technicals +
                  gradeForm.value.safety_awareness +
                  gradeForm.value.productivity;
    return (total / 25) * 100;
});

// Helpers
const getInitials = (name) => name ? name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';
const formatDate = (date) => date ? new Date(date).toLocaleDateString() : 'N/A';

const getGradeColor = (percentage) => {
    if (percentage >= 80) return 'bg-emerald-500';
    if (percentage >= 60) return 'bg-amber-500';
    return 'bg-rose-500';
};

const getGradeTextColor = (percentage) => {
    if (percentage >= 80) return 'text-emerald-600 dark:text-emerald-400';
    if (percentage >= 60) return 'text-amber-600 dark:text-amber-400';
    return 'text-rose-600 dark:text-rose-400';
};

// Open modals
const openGradeModal = (trainee) => {
    if (!canEdit.value) { triggerToast('No permission to grade trainees.', 'error'); return; }
    selectedTrainee.value = trainee;
    // Load existing grade if available (via props, but for simplicity we can assume grade data is embedded)
    gradeForm.value = {
        skills_performance: trainee.skills_performance || 3,
        behaviour: trainee.behaviour || 3,
        technicals: trainee.technicals || 3,
        safety_awareness: trainee.safety_awareness || 3,
        productivity: trainee.productivity || 3
    };
    isGradeModalOpen.value = true;
};

const openPassModal = (trainee) => {
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedTrainee.value = trainee;
    isPassModalOpen.value = true;
};

const openFailModal = (trainee) => {
    if (!canEdit.value) { triggerToast('No permission.', 'error'); return; }
    selectedTrainee.value = trainee;
    failReason.value = '';
    isFailModalOpen.value = true;
};

const closeModals = () => {
    isGradeModalOpen.value = false;
    isPassModalOpen.value = false;
    isFailModalOpen.value = false;
    selectedTrainee.value = null;
    gradeForm.value = { skills_performance: 3, behaviour: 3, technicals: 3, safety_awareness: 3, productivity: 3 };
    failReason.value = '';
};

// API calls
const submitGrade = () => {
    router.post(route('logistics.trainee.grade', selectedTrainee.value.id), gradeForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast('Grade saved successfully.');
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to save grade.', 'error');
        }
    });
};

const passTrainee = () => {
    router.post(route('logistics.trainee.pass', selectedTrainee.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedTrainee.value.name} promoted to staff.`);
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to promote trainee.', 'error');
        }
    });
};

const failTrainee = () => {
    if (!failReason.value.trim()) {
        triggerToast('Please provide a reason for failure.', 'error');
        return;
    }
    router.post(route('logistics.trainee.fail', selectedTrainee.value.id), {
        reason: failReason.value
    }, {
        preserveScroll: true,
        onSuccess: () => {
            triggerToast(`${selectedTrainee.value.name} marked as failed.`);
            closeModals();
        },
        onError: (errors) => {
            triggerToast(Object.values(errors)[0] || 'Failed to process.', 'error');
        }
    });
};
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateX(-50%) translateY(-16px); }
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.95); }
</style>