<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Eye, Pencil, Save, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    users: Array,
    pages: Array,
    permissions: Object, // current user's permissions for the CRM module (from controller)
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const isCEO = computed(() => currentUser.value?.role === 'CEO');
const isCRMmanager = computed(() => currentUser.value?.role === 'CRM' && currentUser.value?.position === 'manager');
const canEdit = computed(() => isCEO.value || isCRMmanager.value);

// Local state: for each user, store array of { page, permission }
const userPermissions = ref({});

// Initialize from props
const initPermissions = () => {
    props.users.forEach(user => {
        const perms = (user.permissions || []).map(p => ({
            page: p.page,
            permission: p.permission_level || 'edit'
        }));
        userPermissions.value[user.id] = perms;
    });
};
initPermissions();

// Helper to check if a page is enabled for a user
const isPageEnabled = (userId, pageKey) => {
    return userPermissions.value[userId]?.some(p => p.page === pageKey) || false;
};

// Helper to get permission level for a page
const getPagePermission = (userId, pageKey) => {
    const perm = userPermissions.value[userId]?.find(p => p.page === pageKey);
    return perm ? perm.permission : 'view';
};

// Toggle page enabled/disabled
const togglePage = (userId, pageKey) => {
    const perms = userPermissions.value[userId] || [];
    const existing = perms.find(p => p.page === pageKey);
    if (existing) {
        // Remove
        userPermissions.value[userId] = perms.filter(p => p.page !== pageKey);
    } else {
        // Add with default 'view'
        userPermissions.value[userId] = [...perms, { page: pageKey, permission: 'view' }];
    }
};

// Set permission level for a page
const setPagePermission = (userId, pageKey, level) => {
    const perms = userPermissions.value[userId] || [];
    const existing = perms.find(p => p.page === pageKey);
    if (existing) {
        existing.permission = level;
    } else {
        perms.push({ page: pageKey, permission: level });
    }
    userPermissions.value[userId] = [...perms];
};

// Save permissions for a user
const savePermissions = (userId) => {
    if (!canEdit.value) return;
    const pagesToSend = userPermissions.value[userId] || [];
    router.post(route('crm.access.update'), {
        user_id: userId,
        pages: pagesToSend
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // Optional: show toast
        },
        onError: (errors) => {
            console.error(errors);
        }
    });
};

const isOwnRow = (userId) => userId === currentUser.value?.id;
</script>

<template>
    <AuthenticatedLayout>
        <div class="p-6 max-w-7xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">CRM Access Control</h1>
                <p class="text-sm text-gray-500">Manage page permissions for CRM staff and managers.</p>
                <div v-if="!canEdit" class="mt-2 text-xs text-amber-600 bg-amber-50 inline-block px-2 py-0.5 rounded-full">
                    View only access
                </div>
                <div v-else class="mt-2 text-xs text-emerald-600 bg-emerald-50 inline-block px-2 py-0.5 rounded-full">
                    Full access (can manage permissions)
                </div>
            </div>

            <div v-if="users.length === 0" class="text-center py-12 text-gray-500">
                No CRM users found.
            </div>

            <div v-for="user in users" :key="user.id" class="bg-white dark:bg-zinc-900 rounded-2xl p-6 mb-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                <div class="flex flex-wrap justify-between items-start gap-4">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">{{ user.name }}</h2>
                        <p class="text-xs text-gray-500">
                            {{ user.role }} · {{ user.position }}
                            <span v-if="user.position === 'manager'" class="ml-2 text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">Manager</span>
                        </p>
                    </div>
                    <button 
                        v-if="canEdit && !isOwnRow(user.id)" 
                        @click="savePermissions(user.id)" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2"
                    >
                        <Save class="h-4 w-4" />
                        Save Permissions
                    </button>
                    <span v-else-if="isOwnRow(user.id) && !isCEO" class="text-xs text-gray-400 italic">(Your own permissions – ask a manager or CEO to change)</span>
                    <span v-else-if="!canEdit" class="text-xs text-gray-400 italic">(Read only)</span>
                </div>

                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="page in pages" :key="page" class="flex flex-col gap-2 border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0">
                        <div class="flex items-center gap-3">
                            <input 
                                type="checkbox" 
                                :checked="isPageEnabled(user.id, page)"
                                @change="togglePage(user.id, page)"
                                :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">
                                {{ page.replace('_', ' ') }}
                            </label>
                        </div>
                        <div v-if="isPageEnabled(user.id, page)" class="ml-7 flex gap-2">
                            <button
                                @click="setPagePermission(user.id, page, 'view')"
                                :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                :class="[
                                    'flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                    getPagePermission(user.id, page) === 'view'
                                        ? 'bg-amber-500 text-white'
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-amber-100'
                                ]"
                            >
                                <Eye class="w-3.5 h-3.5" /> View Only
                            </button>
                            <button
                                @click="setPagePermission(user.id, page, 'edit')"
                                :disabled="!canEdit || (isOwnRow(user.id) && !isCEO)"
                                :class="[
                                    'flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all',
                                    getPagePermission(user.id, page) === 'edit'
                                        ? 'bg-emerald-500 text-white'
                                        : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-emerald-100'
                                ]"
                            >
                                <Pencil class="w-3.5 h-3.5" /> Can Edit
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!canEdit && user.position === 'staff'" class="mt-3 text-xs text-amber-600">
                    ⚠️ Only CRM managers or the CEO can change permissions.
                </div>
                <div v-else-if="isOwnRow(user.id) && !isCEO && canEdit" class="mt-3 text-xs text-amber-600">
                    ⚠️ You cannot edit your own permissions. Ask another manager or the CEO.
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>