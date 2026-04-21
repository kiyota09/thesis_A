<script setup>
import { ref, computed } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    Calendar,
    Clock,
    Video,
    MapPin,
    XCircle,
    CheckCircle,
    PlayCircle,
    User,
    Mail,
    Phone,
    Briefcase,
    CalendarDays,
    AlertTriangle,
    X,
    UserCheck,
    ArrowRight,
    ChevronRight,
    Building,
    FileText,
    Globe,
    ChevronLeft,
    Heart,
    BookOpen,
    Factory,
    CreditCard,
    Phone as PhoneIcon,
    MapPin as MapPinIcon,
    User as UserIcon,
    Award,
    ShieldCheck,
    Eye,
    Pencil,
    Save,
    Loader2,
    Trash2,
    Upload,
    Image as ImageIcon,
    Ban,
    Layers,
    AlertCircle,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    applicants: { type: Array, default: () => [] },
    permissions: { type: Object, default: () => ({}) },
});

const canEdit = computed(() => props.permissions?.interview === "edit");

// ─── Toast System ─────────────────────────────────────────────────────────────
const showToast = ref(false);
const toastMessage = ref("");
const toastType = ref("success");
const triggerToast = (msg, type = "success") => {
    toastMessage.value = msg;
    toastType.value = type;
    showToast.value = true;
    setTimeout(() => {
        showToast.value = false;
    }, 4000);
};
const page = usePage();
if (page.props.flash?.message) triggerToast(page.props.flash.message);

// ─── Confirmation Modal System ─────────────────────────────────────────────────
const confirmModal = ref({
    show: false,
    type: "warning",
    title: "",
    message: "",
    confirmText: "Confirm",
    cancelText: "Cancel",
    onConfirm: null,
    isLoading: false,
});

const showConfirm = (options) => {
    confirmModal.value = {
        show: true,
        type: options.type || "warning",
        title: options.title || "Are you sure?",
        message: options.message || "This action cannot be undone.",
        confirmText: options.confirmText || "Confirm",
        cancelText: options.cancelText || "Cancel",
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
            console.error("Confirmation action failed:", error);
        }
    }
    hideConfirm();
};

// ─── Modal states ─────────────────────────────────────────────────────────────
const isScheduleModalOpen = ref(false);
const isPassModalOpen = ref(false);
const isFailModalOpen = ref(false);
const selectedApplicant = ref(null);

// Tracks which applicant IDs are currently in "Interview Now" mode
const interviewingNowIds = ref(new Set());

// Fail flow step: 'choose' | 'confirm_fail' | 'pass_to_other'
const failStep = ref("choose");
const failReason = ref("");
const otherModule = ref("");

// ─── Detail side panel ────────────────────────────────────────────────────────
const detailPanelOpen = ref(false);
const detailPanelApplicant = ref(null);
const detailPanelTab = ref("personal");

// ─── Schedule form with validation errors ──────────────────────────────────────
const scheduleForm = ref({
    scheduled_at: "",
    interview_type: "",
    duration: 45,
    interviewer: "",
    location: "",
    notes: "",
});

const scheduleErrors = ref({});
const isSubmittingSchedule = ref(false);

// ─── Validation helpers ───────────────────────────────────────────────────────
const validateScheduleForm = () => {
    const errors = {};
    const now = new Date();
    now.setMinutes(now.getMinutes() - 5); // Allow 5 min buffer

    if (!scheduleForm.value.scheduled_at) {
        errors.scheduled_at = "Date and time is required";
    } else {
        const selectedDate = new Date(scheduleForm.value.scheduled_at);
        if (selectedDate < now) {
            errors.scheduled_at = "Interview cannot be scheduled in the past";
        }
    }

    if (!scheduleForm.value.interview_type) {
        errors.interview_type = "Interview type is required";
    }

    if (!scheduleForm.value.interviewer) {
        errors.interviewer = "Interviewer name is required";
    }

    if (!scheduleForm.value.location) {
        errors.location = "Location is required";
    }

    scheduleErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const clearScheduleErrors = () => {
    scheduleErrors.value = {};
};

// ─── Module options ───────────────────────────────────────────────────────────
const modules = [
    { value: "HRM", label: "Human Resource", color: "#2563eb" },
    { value: "ECO", label: "E-Commerce", color: "#0891b2" },
    { value: "CRM", label: "Customer Relationship", color: "#9333ea" },
    { value: "SCM", label: "Supply Chain", color: "#16a34a" },
    { value: "MAN", label: "Manufacturing", color: "#059669" },
    { value: "PROJ", label: "Project Management", color: "#d97706" },
    { value: "FIN", label: "Finance", color: "#dc2626" },
    { value: "LOG", label: "Logistics", color: "#ea580c" },
    { value: "IT", label: "Information Technology", color: "#7c3aed" },
];

// ─── Computed ─────────────────────────────────────────────────────────────────
const todaysInterviews = computed(() => {
    const today = new Date();
    return props.applicants
        .filter((a) => {
            if (!a.scheduled_at) return false;
            return (
                new Date(a.scheduled_at).toDateString() === today.toDateString()
            );
        })
        .sort((a, b) => new Date(a.scheduled_at) - new Date(b.scheduled_at));
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const getInitials = (name) =>
    name
        ? name
              .split(" ")
              .map((n) => n[0])
              .join("")
              .toUpperCase()
              .slice(0, 2)
        : "?";

const formatDateTime = (date) =>
    date
        ? new Date(date).toLocaleString("en-US", {
              month: "short",
              day: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          })
        : "N/A";

const formatTime = (date) =>
    date
        ? new Date(date).toLocaleTimeString([], {
              hour: "2-digit",
              minute: "2-digit",
          })
        : "";

const formatDateFull = (date) =>
    date
        ? new Date(date).toLocaleDateString("en-US", {
              year: "numeric",
              month: "long",
              day: "numeric",
          })
        : "N/A";

const isInterviewingNow = (applicant) =>
    interviewingNowIds.value.has(applicant.id);
const hasSchedule = (applicant) => !!applicant.scheduled_at;

const getStatusInfo = (applicant) => {
    if (isInterviewingNow(applicant))
        return {
            label: "In Progress",
            color: "text-amber-700 bg-amber-50 border-amber-200",
            dot: "bg-amber-500",
        };
    if (hasSchedule(applicant))
        return {
            label: "Scheduled",
            color: "text-blue-700 bg-blue-50 border-blue-200",
            dot: "bg-blue-500",
        };
    return {
        label: "Pending",
        color: "text-slate-600 bg-slate-100 border-slate-200",
        dot: "bg-slate-400",
    };
};

const getInterviewTypeLabel = (type) => {
    const map = {
        phone: "Phone Screen",
        video: "Video Call",
        onsite: "On-site",
        technical: "Technical",
        behavioral: "Behavioral",
    };
    return map[type?.toLowerCase()] || type || "Interview";
};

const getInterviewTypeIcon = (type) => {
    const t = type?.toLowerCase();
    if (t === "phone") return Phone;
    if (t === "video") return Video;
    if (t === "onsite") return MapPin;
    return Calendar;
};

// ─── Actions ──────────────────────────────────────────────────────────────────
const startInterviewNow = (applicant, event) => {
    event.stopPropagation();
    const next = new Set(interviewingNowIds.value);
    next.add(applicant.id);
    interviewingNowIds.value = next;
    triggerToast(`Interview started with ${applicant.name}`, "success");
};

const openScheduleModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) {
        triggerToast("No permission to schedule interviews.", "error");
        return;
    }
    selectedApplicant.value = applicant;
    scheduleForm.value = {
        scheduled_at: applicant.scheduled_at
            ? new Date(applicant.scheduled_at).toISOString().slice(0, 16)
            : "",
        interview_type: applicant.interview_type || "",
        duration: applicant.duration || 45,
        interviewer: applicant.interviewer || "",
        location: applicant.location || "",
        notes: applicant.notes || "",
    };
    clearScheduleErrors();
    isScheduleModalOpen.value = true;
};

const openPassModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) {
        triggerToast("No permission.", "error");
        return;
    }
    selectedApplicant.value = applicant;
    isPassModalOpen.value = true;
};

const openFailModal = (applicant, event) => {
    event?.stopPropagation();
    if (!canEdit.value) {
        triggerToast("No permission.", "error");
        return;
    }
    selectedApplicant.value = applicant;
    failReason.value = "";
    otherModule.value = "";
    failStep.value = "choose";
    isFailModalOpen.value = true;
};

const openDetailPanel = (applicant) => {
    detailPanelApplicant.value = applicant;
    detailPanelOpen.value = true;
    detailPanelTab.value = "personal";
};
const closeDetailPanel = () => {
    detailPanelOpen.value = false;
    setTimeout(() => {
        detailPanelApplicant.value = null;
    }, 350);
};

const closeModals = () => {
    isScheduleModalOpen.value = false;
    isPassModalOpen.value = false;
    isFailModalOpen.value = false;
    selectedApplicant.value = null;
    failStep.value = "choose";
    failReason.value = "";
    otherModule.value = "";
    scheduleForm.value = {
        scheduled_at: "",
        interview_type: "",
        duration: 45,
        interviewer: "",
        location: "",
        notes: "",
    };
    clearScheduleErrors();
    isSubmittingSchedule.value = false;
};

// ─── API ──────────────────────────────────────────────────────────────────────
const scheduleInterview = () => {
    if (!validateScheduleForm()) {
        triggerToast("Please fix the errors in the form.", "error");
        return;
    }

    showConfirm({
        type: "info",
        title: "Confirm Schedule",
        message: `Schedule interview for ${selectedApplicant.value.name} on ${formatDateTime(scheduleForm.value.scheduled_at)}?`,
        confirmText: "Schedule Interview",
        onConfirm: () => {
            isSubmittingSchedule.value = true;
            router.post(
                route("hrm.interview.schedule", selectedApplicant.value.id),
                scheduleForm.value,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        triggerToast(
                            "Interview scheduled successfully.",
                            "success",
                        );
                        closeModals();
                        isSubmittingSchedule.value = false;
                    },
                    onError: (e) => {
                        triggerToast(
                            Object.values(e)[0] || "Failed to schedule.",
                            "error",
                        );
                        isSubmittingSchedule.value = false;
                    },
                },
            );
        },
    });
};

const passApplicant = () => {
    showConfirm({
        type: "success",
        title: "Pass Candidate",
        message: `Are you sure you want to pass ${selectedApplicant.value.name}? They will become a trainee in your department.`,
        confirmText: "Pass Candidate",
        onConfirm: () => {
            router.post(
                route("hrm.interview.pass", selectedApplicant.value.id),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        triggerToast(
                            `${selectedApplicant.value.name} passed the interview!`,
                            "success",
                        );
                        closeModals();
                    },
                    onError: (e) =>
                        triggerToast(Object.values(e)[0] || "Failed.", "error"),
                },
            );
        },
    });
};

const failApplicant = () => {
    if (!failReason.value.trim()) {
        triggerToast("Please provide a reason for failure.", "error");
        return;
    }

    showConfirm({
        type: "danger",
        title: "Fail Candidate",
        message: `Are you sure you want to fail ${selectedApplicant.value.name}?`,
        confirmText: "Confirm Failure",
        onConfirm: () => {
            router.post(
                route("hrm.interview.fail", selectedApplicant.value.id),
                { reason: failReason.value },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        triggerToast(
                            `${selectedApplicant.value.name} has been failed.`,
                            "success",
                        );
                        closeModals();
                    },
                    onError: (e) =>
                        triggerToast(Object.values(e)[0] || "Failed.", "error"),
                },
            );
        },
    });
};

const passToOtherModule = () => {
    if (!otherModule.value) {
        triggerToast("Please select a module.", "error");
        return;
    }

    const moduleName =
        modules.find((m) => m.value === otherModule.value)?.label ||
        otherModule.value;

    showConfirm({
        type: "info",
        title: "Forward to Module",
        message: `Forward ${selectedApplicant.value.name} to ${moduleName} module for interview?`,
        confirmText: "Forward",
        onConfirm: () => {
            router.post(
                route(
                    "hrm.interview.pass-to-other",
                    selectedApplicant.value.id,
                ),
                { module: otherModule.value },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        triggerToast(
                            `Applicant forwarded to ${otherModule.value} module.`,
                            "success",
                        );
                        closeModals();
                    },
                    onError: (e) =>
                        triggerToast(
                            Object.values(e)[0] ||
                                "Failed to pass to other module.",
                            "error",
                        ),
                },
            );
        },
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
    if (!period) return "—";
    const map = {
        immediate: "Immediate",
        "15_days": "15 Days",
        "30_days": "30 Days",
        "60_days": "60 Days",
    };
    return map[period] || period;
};

// Toast colors
const toastColors = (type) => {
    switch (type) {
        case "success":
            return "bg-emerald-600 border-emerald-500";
        case "error":
            return "bg-rose-600 border-rose-500";
        case "warning":
            return "bg-amber-600 border-amber-500";
        default:
            return "bg-slate-900 border-slate-700";
    }
};

const confirmColors = (type) => {
    switch (type) {
        case "danger":
            return {
                bg: "bg-red-600 hover:bg-red-700",
                icon: "bg-red-100 text-red-600",
            };
        case "warning":
            return {
                bg: "bg-amber-500 hover:bg-amber-600",
                icon: "bg-amber-100 text-amber-600",
            };
        case "success":
            return {
                bg: "bg-emerald-600 hover:bg-emerald-700",
                icon: "bg-emerald-100 text-emerald-600",
            };
        default:
            return {
                bg: "bg-blue-600 hover:bg-blue-700",
                icon: "bg-blue-100 text-blue-600",
            };
    }
};
</script>

<template>
    <Head title="Interview Management" />
    <AuthenticatedLayout>
        <!-- ╔══════════════════ TOAST ══════════════════╗ -->
        <Transition name="toast">
            <div
                v-if="showToast"
                class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-2xl border max-w-xs w-full mx-4"
                :class="toastColors(toastType)"
            >
                <CheckCircle
                    v-if="toastType === 'success'"
                    class="h-4 w-4 text-white shrink-0"
                />
                <XCircle
                    v-else-if="toastType === 'error'"
                    class="h-4 w-4 text-white shrink-0"
                />
                <AlertTriangle v-else class="h-4 w-4 text-white shrink-0" />
                <p
                    class="text-xs font-semibold tracking-wide flex-1 text-white"
                >
                    {{ toastMessage }}
                </p>
            </div>
        </Transition>

        <!-- ╔══════════════════ GLOBAL CONFIRMATION MODAL ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="confirmModal.show"
                    class="fixed inset-0 z-[200] flex items-end sm:items-center justify-center p-0 sm:p-4"
                    @click.self="hideConfirm"
                >
                    <div
                        class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    ></div>
                    <Transition name="modal-content">
                        <div
                            v-if="confirmModal.show"
                            class="relative bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-3xl shadow-2xl overflow-hidden"
                        >
                            <div
                                class="flex justify-center pt-3 pb-1 sm:hidden"
                            >
                                <div
                                    class="w-10 h-1 bg-slate-200 rounded-full"
                                ></div>
                            </div>

                            <div class="p-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        :class="[
                                            'w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0',
                                            confirmColors(confirmModal.type)
                                                .icon,
                                        ]"
                                    >
                                        <AlertTriangle
                                            v-if="
                                                confirmModal.type ===
                                                    'warning' ||
                                                confirmModal.type === 'danger'
                                            "
                                            class="w-6 h-6"
                                        />
                                        <Info v-else class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-lg font-semibold text-slate-900"
                                        >
                                            {{ confirmModal.title }}
                                        </h3>
                                        <p class="text-sm text-slate-500 mt-1">
                                            {{ confirmModal.message }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end"
                            >
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
                                    :class="[
                                        'w-full sm:w-auto px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all disabled:opacity-50 inline-flex items-center justify-center gap-2',
                                        confirmColors(confirmModal.type).bg,
                                    ]"
                                >
                                    <span
                                        v-if="confirmModal.isLoading"
                                        class="inline-flex items-center gap-2"
                                    >
                                        <svg
                                            class="animate-spin h-4 w-4"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                class="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                stroke-width="4"
                                                fill="none"
                                            ></circle>
                                            <path
                                                class="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            ></path>
                                        </svg>
                                        Processing...
                                    </span>
                                    <span v-else>{{
                                        confirmModal.confirmText
                                    }}</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>

        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 pb-24">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 space-y-8">
                <!-- ╔══════════════════ HEADER ══════════════════╗ -->
                <div>
                    <div class="flex items-start justify-between">
                        <div>
                            <p
                                class="text-[11px] font-bold text-indigo-500 uppercase tracking-[0.15em] mb-1"
                            >
                                Human Resource
                            </p>
                            <h1
                                class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"
                            >
                                Interviews
                            </h1>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400 mt-0.5"
                            >
                                {{ props.applicants.length }} candidate{{
                                    props.applicants.length !== 1 ? "s" : ""
                                }}
                                assigned
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-1.5 mt-1">
                            <span
                                v-if="canEdit"
                                class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full uppercase tracking-wide"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500 inline-block"
                                ></span>
                                Full Access
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1.5 text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full uppercase tracking-wide"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-amber-400 inline-block"
                                ></span>
                                View Only
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ╔══════════════════ TODAY'S INTERVIEWS ══════════════════╗ -->
                <div v-if="todaysInterviews.length > 0">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="h-5 w-1 rounded-full bg-indigo-500"></div>
                        <h2
                            class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest"
                        >
                            Today's Interviews
                        </h2>
                        <span
                            class="text-[10px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-200 px-1.5 py-0.5 rounded-full"
                        >
                            {{ todaysInterviews.length }}
                        </span>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 overflow-hidden shadow-sm"
                    >
                        <div
                            class="divide-y divide-slate-50 dark:divide-slate-700/60"
                        >
                            <div
                                v-for="(applicant, idx) in todaysInterviews"
                                :key="'today-' + applicant.id"
                                class="flex items-center gap-4 px-4 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors cursor-pointer"
                                @click="openDetailPanel(applicant)"
                            >
                                <div class="text-center shrink-0 w-12">
                                    <p
                                        class="text-xs font-black text-indigo-600"
                                    >
                                        {{ formatTime(applicant.scheduled_at) }}
                                    </p>
                                    <div
                                        class="h-px w-6 bg-slate-200 mx-auto mt-1"
                                    ></div>
                                </div>
                                <div class="shrink-0">
                                    <img
                                        v-if="applicant.profile_photo"
                                        :src="applicant.profile_photo"
                                        :alt="applicant.name"
                                        class="h-9 w-9 rounded-full object-cover ring-2 ring-white shadow"
                                    />
                                    <div
                                        v-else
                                        class="h-9 w-9 rounded-full bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-xs font-black shadow"
                                    >
                                        {{ getInitials(applicant.name) }}
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-sm font-bold text-slate-900 dark:text-white truncate"
                                    >
                                        {{ applicant.name }}
                                    </p>
                                    <p
                                        class="text-[11px] text-slate-500 truncate"
                                    >
                                        {{ applicant.position_applied }}
                                    </p>
                                </div>
                                <div class="shrink-0">
                                    <span
                                        class="text-[10px] font-bold text-slate-600 bg-slate-100 dark:bg-slate-700 dark:text-slate-300 px-2 py-0.5 rounded-lg capitalize"
                                    >
                                        {{
                                            getInterviewTypeLabel(
                                                applicant.interview_type,
                                            )
                                        }}
                                    </span>
                                </div>
                                <div class="shrink-0" v-if="canEdit">
                                    <button
                                        v-if="!isInterviewingNow(applicant)"
                                        @click="
                                            startInterviewNow(applicant, $event)
                                        "
                                        class="interview-now-btn flex items-center gap-1.5 text-[10px] font-black text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1.5 rounded-lg uppercase tracking-wide transition-all active:scale-95"
                                    >
                                        <PlayCircle class="h-3 w-3" /> Now
                                    </button>
                                    <span
                                        v-else
                                        class="text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-1 rounded-lg"
                                    >
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
                        <h2
                            class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-widest"
                        >
                            All Candidates
                        </h2>
                    </div>

                    <div
                        v-if="props.applicants.length === 0"
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 p-12 text-center shadow-sm"
                    >
                        <div
                            class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-700 mb-4"
                        >
                            <Calendar class="h-8 w-8 text-slate-400" />
                        </div>
                        <h3
                            class="text-base font-bold text-slate-700 dark:text-slate-300"
                        >
                            No candidates yet
                        </h3>
                        <p class="text-sm text-slate-400 mt-1 max-w-xs mx-auto">
                            Candidates accepted by HR and assigned to your
                            department will appear here.
                        </p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="applicant in props.applicants"
                            :key="applicant.id"
                            class="applicant-card bg-white dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden cursor-pointer transition-all hover:shadow-md hover:-translate-y-0.5 active:scale-[0.99]"
                            @click="openDetailPanel(applicant)"
                        >
                            <div class="p-4">
                                <div class="flex items-start gap-3.5">
                                    <div class="shrink-0 relative">
                                        <img
                                            v-if="applicant.profile_photo"
                                            :src="applicant.profile_photo"
                                            :alt="applicant.name"
                                            class="h-14 w-14 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-slate-600 shadow"
                                        />
                                        <div
                                            v-else
                                            class="h-14 w-14 rounded-xl bg-gradient-to-br from-indigo-400 to-violet-500 flex items-center justify-center text-white text-base font-black shadow"
                                        >
                                            {{ getInitials(applicant.name) }}
                                        </div>
                                        <span
                                            v-if="
                                                todaysInterviews.find(
                                                    (a) =>
                                                        a.id === applicant.id,
                                                )
                                            "
                                            class="absolute -top-1 -right-1 h-3.5 w-3.5 rounded-full bg-emerald-400 border-2 border-white dark:border-slate-800"
                                        ></span>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <div class="min-w-0">
                                                <h3
                                                    class="text-sm font-bold text-slate-900 dark:text-white truncate"
                                                >
                                                    {{ applicant.name }}
                                                </h3>
                                                <p
                                                    class="text-[11px] text-slate-500 dark:text-slate-400 truncate"
                                                >
                                                    {{ applicant.email }}
                                                </p>
                                                <p
                                                    class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-0.5 truncate"
                                                >
                                                    {{
                                                        applicant.position_applied
                                                    }}
                                                </p>
                                            </div>
                                            <div class="shrink-0">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1 text-[10px] font-bold border px-2 py-0.5 rounded-full uppercase tracking-wide',
                                                        getStatusInfo(applicant)
                                                            .color,
                                                    ]"
                                                >
                                                    <span
                                                        :class="[
                                                            'h-1.5 w-1.5 rounded-full',
                                                            getStatusInfo(
                                                                applicant,
                                                            ).dot,
                                                        ]"
                                                    ></span>
                                                    {{
                                                        getStatusInfo(applicant)
                                                            .label
                                                    }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            v-if="applicant.scheduled_at"
                                            class="flex items-center gap-1.5 mt-2"
                                        >
                                            <Calendar
                                                class="h-3 w-3 text-indigo-400"
                                            />
                                            <span
                                                class="text-[11px] text-slate-500 dark:text-slate-400"
                                            >
                                                {{
                                                    formatDateTime(
                                                        applicant.scheduled_at,
                                                    )
                                                }}
                                            </span>
                                            <span
                                                v-if="applicant.interview_type"
                                                class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-700 px-1.5 py-0.5 rounded-md ml-1 capitalize"
                                            >
                                                {{
                                                    getInterviewTypeLabel(
                                                        applicant.interview_type,
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="canEdit" class="px-4 pb-4" @click.stop>
                                <div
                                    v-if="
                                        !hasSchedule(applicant) &&
                                        !isInterviewingNow(applicant)
                                    "
                                    class="flex gap-2"
                                >
                                    <button
                                        @click="
                                            openScheduleModal(applicant, $event)
                                        "
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-indigo-200"
                                    >
                                        <CalendarDays class="h-3.5 w-3.5" /> Set
                                        Schedule
                                    </button>
                                </div>

                                <div
                                    v-else-if="
                                        hasSchedule(applicant) &&
                                        !isInterviewingNow(applicant)
                                    "
                                    class="flex gap-2"
                                >
                                    <button
                                        @click="
                                            openScheduleModal(applicant, $event)
                                        "
                                        class="flex items-center justify-center gap-1.5 py-2.5 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95"
                                    >
                                        <Calendar class="h-3.5 w-3.5" />
                                        Reschedule
                                    </button>
                                    <button
                                        @click="
                                            startInterviewNow(applicant, $event)
                                        "
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-indigo-200"
                                    >
                                        <PlayCircle class="h-3.5 w-3.5" />
                                        Interview Now
                                    </button>
                                </div>

                                <div
                                    v-else-if="isInterviewingNow(applicant)"
                                    class="flex gap-2"
                                >
                                    <button
                                        @click="
                                            openPassModal(applicant, $event)
                                        "
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-emerald-200"
                                    >
                                        <CheckCircle class="h-3.5 w-3.5" /> Pass
                                    </button>
                                    <button
                                        @click="
                                            openFailModal(applicant, $event)
                                        "
                                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95 shadow-sm shadow-rose-200"
                                    >
                                        <XCircle class="h-3.5 w-3.5" /> Fail
                                    </button>
                                </div>
                            </div>

                            <div v-else class="px-4 pb-4">
                                <p
                                    class="text-[11px] text-center text-slate-400 italic"
                                >
                                    View only · Contact HR to manage this
                                    interview
                                </p>
                            </div>

                            <div
                                class="flex items-center justify-end gap-1 px-4 pb-2.5"
                            >
                                <p
                                    class="text-[10px] text-slate-300 dark:text-slate-600"
                                >
                                    Tap to view profile
                                </p>
                                <ChevronRight
                                    class="h-3 w-3 text-slate-300 dark:text-slate-600"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ╔══════════════════ DETAIL SIDE PANEL ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="panel-backdrop">
                <div
                    v-if="detailPanelOpen"
                    class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm"
                    @click="closeDetailPanel"
                ></div>
            </Transition>

            <Transition name="panel-slide">
                <div
                    v-if="detailPanelOpen"
                    class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white dark:bg-slate-900 shadow-2xl flex flex-col overflow-hidden"
                >
                    <div
                        class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-violet-600 to-purple-700 px-5 pt-10 pb-6"
                    >
                        <div
                            class="absolute inset-0 opacity-10"
                            style="
                                background-image: radial-gradient(
                                    circle at 70% 50%,
                                    white 1px,
                                    transparent 1px
                                );
                                background-size: 20px 20px;
                            "
                        ></div>
                        <button
                            @click="closeDetailPanel"
                            class="absolute top-4 right-4 h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                        >
                            <X class="h-4 w-4 text-white" />
                        </button>
                        <div class="flex items-center gap-4">
                            <img
                                v-if="detailPanelApplicant?.profile_photo"
                                :src="detailPanelApplicant.profile_photo"
                                :alt="detailPanelApplicant?.name"
                                class="h-20 w-20 rounded-2xl object-cover ring-4 ring-white/30 shadow-xl"
                            />
                            <div
                                v-else
                                class="h-20 w-20 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-white text-2xl font-black ring-4 ring-white/20 shadow-xl"
                            >
                                {{ getInitials(detailPanelApplicant?.name) }}
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-white">
                                    {{ detailPanelApplicant?.name }}
                                </h2>
                                <p class="text-indigo-200 text-sm font-medium">
                                    {{ detailPanelApplicant?.position_applied }}
                                </p>
                                <div class="mt-2">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-widest border',
                                            detailPanelApplicant &&
                                                getStatusInfo(
                                                    detailPanelApplicant,
                                                ).color,
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                detailPanelApplicant &&
                                                    getStatusInfo(
                                                        detailPanelApplicant,
                                                    ).dot,
                                            ]"
                                        ></span>
                                        {{
                                            detailPanelApplicant &&
                                            getStatusInfo(detailPanelApplicant)
                                                .label
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex gap-1 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700 px-4 pt-3"
                    >
                        <button
                            @click="detailPanelTab = 'personal'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'personal'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            Personal
                        </button>
                        <button
                            @click="detailPanelTab = 'contact'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'contact'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            Contact
                        </button>
                        <button
                            @click="detailPanelTab = 'ids'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'ids'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            IDs
                        </button>
                        <button
                            @click="detailPanelTab = 'education'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'education'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            Education
                        </button>
                        <button
                            @click="detailPanelTab = 'employment'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'employment'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            Employment
                        </button>
                        <button
                            @click="detailPanelTab = 'family'"
                            :class="[
                                'px-4 py-2 text-xs font-bold rounded-t-lg transition-all',
                                detailPanelTab === 'family'
                                    ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400'
                                    : 'text-slate-500 hover:text-slate-700',
                            ]"
                        >
                            Family
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-5 space-y-5">
                        <!-- PERSONAL TAB -->
                        <div
                            v-if="detailPanelTab === 'personal'"
                            class="space-y-4"
                        >
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <UserIcon class="h-4 w-4 text-indigo-500" />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Basic Information
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Full Name
                                        </p>
                                        <p class="text-sm font-medium">
                                            {{
                                                detailPanelApplicant?.first_name
                                            }}
                                            {{
                                                detailPanelApplicant?.middle_name
                                            }}
                                            {{
                                                detailPanelApplicant?.last_name
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Date of Birth
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                formatDateFull(
                                                    detailPanelApplicant?.date_of_birth,
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Place of Birth
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.place_of_birth ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Age
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.age || "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Sex
                                        </p>
                                        <p class="text-sm capitalize">
                                            {{
                                                detailPanelApplicant?.sex || "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Civil Status
                                        </p>
                                        <p class="text-sm capitalize">
                                            {{
                                                detailPanelApplicant?.civil_status ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Citizenship
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.citizenship ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Religion
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.religion ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Weight / Height
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.weight
                                            }}
                                            kg /
                                            {{
                                                detailPanelApplicant?.height
                                            }}
                                            cm
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] text-slate-400">
                                            Languages
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.languages ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-[10px] text-slate-400">
                                            Special Skills
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.special_skills ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                    <div class="col-span-2">
                                        <p class="text-[10px] text-slate-400">
                                            Machine Operation
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                detailPanelApplicant?.machine_operation ||
                                                "—"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other tabs remain unchanged -->
                        <!-- CONTACT TAB -->
                        <div
                            v-if="detailPanelTab === 'contact'"
                            class="space-y-4"
                        >
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <PhoneIcon
                                        class="h-4 w-4 text-indigo-500"
                                    />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Contact & Address
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Email
                                    </p>
                                    <p class="text-sm">
                                        {{ detailPanelApplicant?.email }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Phone
                                    </p>
                                    <p class="text-sm">
                                        {{ detailPanelApplicant?.phone }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Address
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.street_address
                                        }}, {{ detailPanelApplicant?.city }},
                                        {{
                                            detailPanelApplicant?.state_province
                                        }}
                                        {{
                                            detailPanelApplicant?.postal_zip_code
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <AlertTriangle
                                        class="h-4 w-4 text-rose-500"
                                    />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Emergency Contact
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Name
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.emergency_name ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Relationship
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.emergency_relationship ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Phone
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.emergency_phone ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Address
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.emergency_address ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- GOVERNMENT IDs TAB -->
                        <div v-if="detailPanelTab === 'ids'" class="space-y-4">
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <CreditCard
                                        class="h-4 w-4 text-indigo-500"
                                    />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Government IDs
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        SSS Number
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.sss_number ||
                                            "—"
                                        }}
                                    </p>
                                    <div
                                        v-if="
                                            detailPanelApplicant?.sss_file_url
                                        "
                                        class="mt-2"
                                    >
                                        <button
                                            @click="
                                                openImagePreview(
                                                    detailPanelApplicant.sss_file_url,
                                                    'SSS ID',
                                                )
                                            "
                                            class="text-xs text-indigo-600 underline"
                                        >
                                            View SSS ID
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        PhilHealth Number
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.philhealth_number ||
                                            "—"
                                        }}
                                    </p>
                                    <div
                                        v-if="
                                            detailPanelApplicant?.philhealth_file_url
                                        "
                                        class="mt-2"
                                    >
                                        <button
                                            @click="
                                                openImagePreview(
                                                    detailPanelApplicant.philhealth_file_url,
                                                    'PhilHealth ID',
                                                )
                                            "
                                            class="text-xs text-indigo-600 underline"
                                        >
                                            View PhilHealth ID
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Pag-IBIG Number
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.pagibig_number ||
                                            "—"
                                        }}
                                    </p>
                                    <div
                                        v-if="
                                            detailPanelApplicant?.pagibig_file_url
                                        "
                                        class="mt-2"
                                    >
                                        <button
                                            @click="
                                                openImagePreview(
                                                    detailPanelApplicant.pagibig_file_url,
                                                    'Pag-IBIG ID',
                                                )
                                            "
                                            class="text-xs text-indigo-600 underline"
                                        >
                                            View Pag-IBIG ID
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EDUCATION TAB -->
                        <div
                            v-if="detailPanelTab === 'education'"
                            class="space-y-4"
                        >
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <BookOpen class="h-4 w-4 text-indigo-500" />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Educational Background
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Elementary
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.elementary_school ||
                                            "—"
                                        }}
                                        {{
                                            detailPanelApplicant?.elementary_year
                                                ? `(${detailPanelApplicant.elementary_year})`
                                                : ""
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        High School
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.high_school ||
                                            "—"
                                        }}
                                        {{
                                            detailPanelApplicant?.high_year
                                                ? `(${detailPanelApplicant.high_year})`
                                                : ""
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        College
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.college || "—"
                                        }}
                                        {{
                                            detailPanelApplicant?.college_year
                                                ? `(${detailPanelApplicant.college_year})`
                                                : ""
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Vocational
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.vocational ||
                                            "—"
                                        }}
                                        {{
                                            detailPanelApplicant?.vocational_year
                                                ? `(${detailPanelApplicant.vocational_year})`
                                                : ""
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- EMPLOYMENT TAB -->
                        <div
                            v-if="detailPanelTab === 'employment'"
                            class="space-y-4"
                        >
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <Briefcase
                                        class="h-4 w-4 text-indigo-500"
                                    />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Employment History
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        detailPanelApplicant?.employment_records &&
                                        detailPanelApplicant.employment_records
                                            .length
                                    "
                                >
                                    <div
                                        v-for="(
                                            rec, idx
                                        ) in detailPanelApplicant.employment_records"
                                        :key="idx"
                                        class="mb-3 pb-2 border-b border-slate-200 last:border-0"
                                    >
                                        <p class="text-sm font-bold">
                                            {{ rec.company }}
                                        </p>
                                        <p class="text-xs">
                                            Years: {{ rec.years }} | Salary:
                                            {{ rec.salary }} | Position:
                                            {{ rec.position }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Reason: {{ rec.reason }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-else-if="
                                        detailPanelApplicant?.previous_employment_company
                                    "
                                >
                                    <p>
                                        <span class="text-[10px] text-slate-400"
                                            >Company:</span
                                        >
                                        {{
                                            detailPanelApplicant.previous_employment_company
                                        }}
                                    </p>
                                    <p>
                                        <span class="text-[10px] text-slate-400"
                                            >When:</span
                                        >
                                        {{
                                            detailPanelApplicant.previous_employment_when
                                        }}
                                    </p>
                                    <p>
                                        <span class="text-[10px] text-slate-400"
                                            >Position:</span
                                        >
                                        {{
                                            detailPanelApplicant.previous_employment_position
                                        }}
                                    </p>
                                    <p>
                                        <span class="text-[10px] text-slate-400"
                                            >Department:</span
                                        >
                                        {{
                                            detailPanelApplicant.previous_employment_department
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-else
                                    class="text-sm text-slate-500 italic"
                                >
                                    No employment history provided.
                                </div>
                            </div>
                        </div>

                        <!-- FAMILY TAB -->
                        <div
                            v-if="detailPanelTab === 'family'"
                            class="space-y-4"
                        >
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <Heart class="h-4 w-4 text-rose-500" />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Family Background
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Father
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.father_name ||
                                            "—"
                                        }}<br />{{
                                            detailPanelApplicant?.father_address ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Mother
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.mother_name ||
                                            "—"
                                        }}<br />{{
                                            detailPanelApplicant?.mother_address ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Spouse
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.spouse_name ||
                                            "—"
                                        }}
                                        ({{
                                            detailPanelApplicant?.spouse_occupation ||
                                            "—"
                                        }})<br />{{
                                            detailPanelApplicant?.spouse_address ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Number of Children
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.number_of_children ||
                                            0
                                        }}
                                    </p>
                                </div>
                                <div
                                    v-if="
                                        detailPanelApplicant?.children &&
                                        detailPanelApplicant.children.length
                                    "
                                >
                                    <p class="text-[10px] text-slate-400">
                                        Children
                                    </p>
                                    <div
                                        v-for="(
                                            child, i
                                        ) in detailPanelApplicant.children"
                                        :key="i"
                                        class="text-sm"
                                    >
                                        -
                                        {{
                                            typeof child === "object"
                                                ? child.name
                                                : child
                                        }}
                                    </div>
                                </div>
                            </div>
                            <div
                                class="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-3"
                            >
                                <div
                                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2"
                                >
                                    <Globe class="h-4 w-4 text-indigo-500" />
                                    <p
                                        class="text-xs font-black text-slate-700 dark:text-slate-200 uppercase tracking-wider"
                                    >
                                        Referral & Relations
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Referred By
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.referred_by ||
                                            "—"
                                        }}<br />{{
                                            detailPanelApplicant?.referred_by_address ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        Related Employees
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            detailPanelApplicant?.related_employees ||
                                            "—"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="canEdit" class="space-y-2 pt-2" @click.stop>
                            <p
                                class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2"
                            >
                                Quick Actions
                            </p>
                            <button
                                v-if="!hasSchedule(detailPanelApplicant)"
                                @click="
                                    openScheduleModal(
                                        detailPanelApplicant,
                                        $event,
                                    )
                                "
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95"
                            >
                                <CalendarDays class="h-4 w-4" /> Set Schedule
                            </button>
                            <button
                                v-if="
                                    hasSchedule(detailPanelApplicant) &&
                                    !isInterviewingNow(detailPanelApplicant)
                                "
                                @click="
                                    startInterviewNow(
                                        detailPanelApplicant,
                                        $event,
                                    )
                                "
                                class="w-full flex items-center justify-center gap-2 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95"
                            >
                                <PlayCircle class="h-4 w-4" /> Interview Now
                            </button>
                            <div
                                v-if="isInterviewingNow(detailPanelApplicant)"
                                class="grid grid-cols-2 gap-2"
                            >
                                <button
                                    @click="
                                        openPassModal(
                                            detailPanelApplicant,
                                            $event,
                                        )
                                    "
                                    class="flex items-center justify-center gap-2 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95"
                                >
                                    <CheckCircle class="h-4 w-4" /> Pass
                                </button>
                                <button
                                    @click="
                                        openFailModal(
                                            detailPanelApplicant,
                                            $event,
                                        )
                                    "
                                    class="flex items-center justify-center gap-2 py-3 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold tracking-wide transition-all active:scale-95"
                                >
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
                <div
                    v-if="imagePreview"
                    class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                    @click.self="closeImagePreview"
                >
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden"
                    >
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700"
                        >
                            <h3
                                class="text-lg font-bold text-slate-900 dark:text-white"
                            >
                                {{ imagePreview.title }}
                            </h3>
                            <button
                                @click="closeImagePreview"
                                class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition"
                            >
                                <X
                                    class="w-5 h-5 text-slate-600 dark:text-slate-400"
                                />
                            </button>
                        </div>
                        <div
                            class="p-4 flex justify-center bg-slate-100 dark:bg-slate-900"
                        >
                            <img
                                :src="imagePreview.url"
                                :alt="imagePreview.title"
                                class="max-w-full max-h-[70vh] object-contain rounded-lg"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ SCHEDULE MODAL (FIXED UI) ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="isScheduleModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals"
                >
                    <div
                        class="bg-white dark:bg-slate-800 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] sm:max-h-[85vh] overflow-hidden flex flex-col"
                    >
                        <div class="flex justify-center pt-3 pb-1 sm:hidden">
                            <div
                                class="w-10 h-1 bg-slate-200 dark:bg-slate-600 rounded-full"
                            ></div>
                        </div>

                        <!-- Header -->
                        <div
                            class="bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-4 flex-shrink-0"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center"
                                    >
                                        <CalendarDays
                                            class="h-5 w-5 text-white"
                                        />
                                    </div>
                                    <div>
                                        <h2
                                            class="text-base font-black text-white uppercase tracking-wider"
                                        >
                                            Schedule Interview
                                        </h2>
                                        <p
                                            class="text-indigo-200 text-xs mt-0.5"
                                        >
                                            {{ selectedApplicant?.name }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="closeModals"
                                    class="h-8 w-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                                >
                                    <X class="h-4 w-4 text-white" />
                                </button>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto px-5 py-5 space-y-4">
                            <!-- Info banner -->
                            <div
                                class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-xl p-3 flex items-start gap-3"
                            >
                                <Info
                                    class="h-4 w-4 text-indigo-500 dark:text-indigo-400 shrink-0 mt-0.5"
                                />
                                <p
                                    class="text-xs text-indigo-700 dark:text-indigo-300"
                                >
                                    Schedule an interview for
                                    <strong>{{
                                        selectedApplicant?.name
                                    }}</strong
                                    >. All fields marked with
                                    <span class="text-rose-500">*</span> are
                                    required.
                                </p>
                            </div>

                            <!-- Date & Time -->
                            <div>
                                <label class="form-label">
                                    Date & Time
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    v-model="scheduleForm.scheduled_at"
                                    :class="[
                                        'form-input',
                                        scheduleErrors.scheduled_at
                                            ? 'border-rose-300 bg-rose-50/30 dark:border-rose-700 dark:bg-rose-900/20'
                                            : '',
                                    ]"
                                    @input="clearScheduleErrors"
                                />
                                <p
                                    v-if="scheduleErrors.scheduled_at"
                                    class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                                >
                                    <AlertCircle class="h-3 w-3" />
                                    {{ scheduleErrors.scheduled_at }}
                                </p>
                            </div>

                            <!-- Interview Type & Duration -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="form-label">
                                        Interview Type
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="scheduleForm.interview_type"
                                        :class="[
                                            'form-input',
                                            scheduleErrors.interview_type
                                                ? 'border-rose-300 bg-rose-50/30 dark:border-rose-700 dark:bg-rose-900/20'
                                                : '',
                                        ]"
                                        @change="clearScheduleErrors"
                                    >
                                        <option value="">Select type</option>
                                        <option value="phone">
                                            📞 Phone Screen
                                        </option>
                                        <option value="technical">
                                            💻 Technical Interview
                                        </option>
                                        <option value="behavioral">
                                            🧠 Behavioral Interview
                                        </option>
                                        <option value="onsite">
                                            🏢 On-site Interview
                                        </option>
                                        <option value="video">
                                            🎥 Video Conference
                                        </option>
                                    </select>
                                    <p
                                        v-if="scheduleErrors.interview_type"
                                        class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                                    >
                                        <AlertCircle class="h-3 w-3" />
                                        {{ scheduleErrors.interview_type }}
                                    </p>
                                </div>
                                <div>
                                    <label class="form-label">Duration</label>
                                    <select
                                        v-model="scheduleForm.duration"
                                        class="form-input"
                                    >
                                        <option :value="15">15 minutes</option>
                                        <option :value="30">30 minutes</option>
                                        <option :value="45">45 minutes</option>
                                        <option :value="60">60 minutes</option>
                                        <option :value="90">90 minutes</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Interviewer -->
                            <div>
                                <label class="form-label">
                                    Interviewer(s)
                                    <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    v-model="scheduleForm.interviewer"
                                    :class="[
                                        'form-input',
                                        scheduleErrors.interviewer
                                            ? 'border-rose-300 bg-rose-50/30 dark:border-rose-700 dark:bg-rose-900/20'
                                            : '',
                                    ]"
                                    placeholder="e.g. John Doe, Jane Smith"
                                    @input="clearScheduleErrors"
                                />
                                <p
                                    v-if="scheduleErrors.interviewer"
                                    class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                                >
                                    <AlertCircle class="h-3 w-3" />
                                    {{ scheduleErrors.interviewer }}
                                </p>
                            </div>

                            <!-- Location -->
                            <div>
                                <label class="form-label">
                                    Location
                                    <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <MapPin
                                        class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400"
                                    />
                                    <input
                                        type="text"
                                        v-model="scheduleForm.location"
                                        :class="[
                                            'form-input pl-10',
                                            scheduleErrors.location
                                                ? 'border-rose-300 bg-rose-50/30 dark:border-rose-700 dark:bg-rose-900/20'
                                                : '',
                                        ]"
                                        placeholder="Office address or video call link"
                                        @input="clearScheduleErrors"
                                    />
                                </div>
                                <p
                                    v-if="scheduleErrors.location"
                                    class="text-xs text-rose-500 mt-1 flex items-center gap-1"
                                >
                                    <AlertCircle class="h-3 w-3" />
                                    {{ scheduleErrors.location }}
                                </p>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label class="form-label"
                                    >Additional Notes</label
                                >
                                <textarea
                                    v-model="scheduleForm.notes"
                                    rows="3"
                                    class="form-input resize-none"
                                    placeholder="Any preparation instructions or additional details..."
                                ></textarea>
                            </div>

                            <!-- Schedule Preview -->
                            <div
                                v-if="
                                    scheduleForm.scheduled_at &&
                                    scheduleForm.interview_type &&
                                    scheduleForm.location
                                "
                                class="bg-slate-50 dark:bg-slate-700/30 rounded-xl p-4 border border-slate-200 dark:border-slate-600"
                            >
                                <p
                                    class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2"
                                >
                                    Schedule Preview
                                </p>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2">
                                        <Calendar
                                            class="h-4 w-4 text-indigo-500"
                                        />
                                        <span
                                            class="text-sm text-slate-700 dark:text-slate-300"
                                            >{{
                                                formatDateTime(
                                                    scheduleForm.scheduled_at,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Video
                                            class="h-4 w-4 text-indigo-500"
                                        />
                                        <span
                                            class="text-sm text-slate-700 dark:text-slate-300 capitalize"
                                            >{{
                                                getInterviewTypeLabel(
                                                    scheduleForm.interview_type,
                                                )
                                            }}</span
                                        >
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <MapPin
                                            class="h-4 w-4 text-indigo-500"
                                        />
                                        <span
                                            class="text-sm text-slate-700 dark:text-slate-300"
                                            >{{ scheduleForm.location }}</span
                                        >
                                    </div>
                                    <div
                                        v-if="scheduleForm.interviewer"
                                        class="flex items-center gap-2"
                                    >
                                        <User class="h-4 w-4 text-indigo-500" />
                                        <span
                                            class="text-sm text-slate-700 dark:text-slate-300"
                                            >{{
                                                scheduleForm.interviewer
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="px-5 py-4 border-t border-slate-200 dark:border-slate-700 flex-shrink-0 flex flex-col-reverse sm:flex-row gap-2 sm:justify-end"
                        >
                            <button
                                @click="closeModals"
                                class="w-full sm:w-auto px-5 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-all"
                            >
                                Cancel
                            </button>
                            <button
                                @click="scheduleInterview"
                                :disabled="isSubmittingSchedule"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-sm font-bold tracking-wide shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30 transition-all active:scale-95"
                            >
                                <Loader2
                                    v-if="isSubmittingSchedule"
                                    class="h-4 w-4 animate-spin"
                                />
                                <CalendarDays v-else class="h-4 w-4" />
                                {{
                                    isSubmittingSchedule
                                        ? "Scheduling..."
                                        : "Confirm Schedule"
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ PASS MODAL ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="isPassModalOpen"
                    class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals"
                >
                    <div
                        class="bg-white dark:bg-slate-800 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-br from-emerald-500 to-teal-600 p-8 text-center"
                        >
                            <div
                                class="inline-flex h-16 w-16 rounded-2xl bg-white/20 items-center justify-center mb-4 shadow-inner"
                            >
                                <CheckCircle class="h-8 w-8 text-white" />
                            </div>
                            <h2
                                class="text-lg font-black text-white uppercase tracking-wider"
                            >
                                Pass Candidate
                            </h2>
                            <p class="text-emerald-100 text-xs mt-1">
                                This will convert them into a trainee
                            </p>
                        </div>
                        <div class="p-6 text-center">
                            <p
                                class="text-slate-600 dark:text-slate-300 text-sm leading-relaxed"
                            >
                                Are you sure you want to pass
                                <strong
                                    class="text-slate-900 dark:text-white"
                                    >{{ selectedApplicant?.name }}</strong
                                >? They will become a trainee in your
                                department.
                            </p>
                            <div class="flex gap-3 mt-6">
                                <button
                                    @click="closeModals"
                                    class="flex-1 py-3 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="passApplicant"
                                    class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-emerald-200 dark:shadow-emerald-900/30 transition-all active:scale-95"
                                >
                                    Confirm Pass
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ╔══════════════════ FAIL MODAL ══════════════════╗ -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="isFailModalOpen"
                    class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm"
                    @click.self="closeModals"
                >
                    <div
                        class="bg-white dark:bg-slate-800 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden"
                    >
                        <div
                            class="bg-gradient-to-br from-rose-600 to-red-700 p-5"
                        >
                            <div class="flex items-center gap-3">
                                <button
                                    v-if="failStep !== 'choose'"
                                    @click="failStep = 'choose'"
                                    class="h-7 w-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                                >
                                    <ChevronLeft class="h-4 w-4 text-white" />
                                </button>
                                <div class="flex-1">
                                    <h2
                                        class="text-sm font-black text-white uppercase tracking-wider"
                                    >
                                        {{
                                            failStep === "choose"
                                                ? "Fail Candidate"
                                                : failStep === "confirm_fail"
                                                  ? "Confirm Failure"
                                                  : "Pass to Module"
                                        }}
                                    </h2>
                                    <p class="text-rose-200 text-xs mt-0.5">
                                        {{ selectedApplicant?.name }}
                                    </p>
                                </div>
                                <button
                                    @click="closeModals"
                                    class="h-7 w-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                                >
                                    <X class="h-4 w-4 text-white" />
                                </button>
                            </div>
                        </div>

                        <div v-if="failStep === 'choose'" class="p-5 space-y-3">
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400 text-center pb-1"
                            >
                                What would you like to do with this candidate?
                            </p>
                            <button
                                @click="failStep = 'confirm_fail'"
                                class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-rose-100 hover:border-rose-300 hover:bg-rose-50 dark:border-rose-900/40 dark:hover:bg-rose-900/20 text-left transition-all group"
                            >
                                <div
                                    class="h-10 w-10 rounded-xl bg-rose-100 dark:bg-rose-900/30 flex items-center justify-center shrink-0 group-hover:bg-rose-200 transition-colors"
                                >
                                    <Ban class="h-5 w-5 text-rose-600" />
                                </div>
                                <div class="flex-1">
                                    <p
                                        class="text-sm font-bold text-slate-900 dark:text-white"
                                    >
                                        Fail Candidate
                                    </p>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Permanently reject with a reason
                                    </p>
                                </div>
                                <ChevronRight
                                    class="h-4 w-4 text-slate-300 group-hover:text-rose-400 transition-colors"
                                />
                            </button>
                            <button
                                @click="failStep = 'pass_to_other'"
                                class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-violet-100 hover:border-violet-300 hover:bg-violet-50 dark:border-violet-900/40 dark:hover:bg-violet-900/20 text-left transition-all group"
                            >
                                <div
                                    class="h-10 w-10 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center shrink-0 group-hover:bg-violet-200 transition-colors"
                                >
                                    <Layers class="h-5 w-5 text-violet-600" />
                                </div>
                                <div class="flex-1">
                                    <p
                                        class="text-sm font-bold text-slate-900 dark:text-white"
                                    >
                                        Pass to Another Module
                                    </p>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        Forward for interview in other
                                        department
                                    </p>
                                </div>
                                <ChevronRight
                                    class="h-4 w-4 text-slate-300 group-hover:text-violet-400 transition-colors"
                                />
                            </button>
                            <button
                                @click="closeModals"
                                class="w-full py-3 text-slate-400 text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>

                        <div
                            v-else-if="failStep === 'confirm_fail'"
                            class="p-5 space-y-4"
                        >
                            <div>
                                <label class="form-label"
                                    >Reason for Failure
                                    <span class="text-rose-500">*</span></label
                                >
                                <textarea
                                    v-model="failReason"
                                    rows="4"
                                    class="form-input resize-none focus:ring-rose-500"
                                    placeholder="e.g., Insufficient technical skills, poor communication..."
                                ></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button
                                    @click="failStep = 'choose'"
                                    class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors"
                                >
                                    Back
                                </button>
                                <button
                                    @click="failApplicant"
                                    :disabled="!failReason.trim()"
                                    class="flex-1 py-3 bg-rose-600 hover:bg-rose-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-rose-200 transition-all active:scale-95"
                                >
                                    Confirm Fail
                                </button>
                            </div>
                        </div>

                        <div
                            v-else-if="failStep === 'pass_to_other'"
                            class="p-5 space-y-4"
                        >
                            <div>
                                <label class="form-label"
                                    >Select Department Module
                                    <span class="text-rose-500">*</span></label
                                >
                                <div
                                    class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto pr-1"
                                >
                                    <label
                                        v-for="mod in modules"
                                        :key="mod.value"
                                        class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all"
                                        :class="
                                            otherModule === mod.value
                                                ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20'
                                                : 'border-slate-100 dark:border-slate-700 hover:border-slate-200 dark:hover:border-slate-600'
                                        "
                                    >
                                        <input
                                            type="radio"
                                            v-model="otherModule"
                                            :value="mod.value"
                                            class="sr-only"
                                        />
                                        <div
                                            class="h-7 w-7 rounded-lg flex items-center justify-center shrink-0"
                                            :style="{
                                                backgroundColor:
                                                    mod.color + '20',
                                                color: mod.color,
                                            }"
                                        >
                                            <Building class="h-3.5 w-3.5" />
                                        </div>
                                        <span
                                            class="text-sm font-semibold text-slate-700 dark:text-slate-200 flex-1"
                                        >
                                            {{ mod.label }}
                                        </span>
                                        <span
                                            v-if="otherModule === mod.value"
                                            class="h-5 w-5 rounded-full bg-violet-500 flex items-center justify-center"
                                        >
                                            <CheckCircle
                                                class="h-3 w-3 text-white"
                                            />
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <button
                                    @click="failStep = 'choose'"
                                    class="flex-1 py-3 text-slate-500 font-bold text-xs uppercase tracking-wide rounded-xl hover:bg-slate-50 transition-colors"
                                >
                                    Back
                                </button>
                                <button
                                    @click="passToOtherModule"
                                    :disabled="!otherModule"
                                    class="flex-1 py-3 bg-violet-600 hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black uppercase tracking-wide shadow-lg shadow-violet-200 transition-all active:scale-95"
                                >
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
@import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800;900&display=swap");

* {
    font-family: "DM Sans", sans-serif;
}

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

.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(-50%) translateY(-16px);
}

.modal-enter-active,
.modal-leave-active {
    transition: all 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-content-enter-active {
    transition: all 0.2s ease-out;
}
.modal-content-leave-active {
    transition: all 0.15s ease-in;
}
.modal-content-enter-from {
    opacity: 0;
    transform: translateY(16px) scale(0.95);
}
.modal-content-leave-to {
    opacity: 0;
    transform: translateY(16px) scale(0.95);
}
@media (min-width: 640px) {
    .modal-content-enter-from,
    .modal-content-leave-to {
        transform: scale(0.95);
    }
}

.panel-slide-enter-active {
    transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
}
.panel-slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.panel-slide-enter-from {
    transform: translateX(100%);
}
.panel-slide-leave-to {
    transform: translateX(100%);
}

.panel-backdrop-enter-active {
    transition: opacity 0.3s ease;
}
.panel-backdrop-leave-active {
    transition: opacity 0.3s ease;
}
.panel-backdrop-enter-from,
.panel-backdrop-leave-to {
    opacity: 0;
}

.applicant-card {
    transition:
        transform 0.15s ease,
        box-shadow 0.15s ease;
}

::-webkit-scrollbar {
    width: 4px;
}
::-webkit-scrollbar-track {
    background: transparent;
}
::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 999px;
}
::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

.interview-now-btn {
    animation: pulse-indigo 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-indigo {
    0%,
    100% {
        box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
    }
    50% {
        box-shadow: 0 0 0 6px rgba(99, 102, 241, 0);
    }
}
</style>
