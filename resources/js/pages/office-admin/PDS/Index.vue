<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { Plus, Search, Pencil, Trash2, FileText, Building2, UserCheck, UserPlus, Eye, EyeOff, ScrollText } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Paginator } from '@/types';
import { swalConfirmDelete, swalSuccess } from '@/composables/useSwal';

interface PDS {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    email: string;
    phone_number?: string;
    city_municipality_name?: string;
    province_name?: string;
    user?: { id: number; email: string };
}

interface Office {
    id: number;
    name: string;
    code: string;
}

interface Props {
    pdsRecords: Paginator<PDS>;
    systemRoles: Record<string, string>;
    office: Office;
}

const props = defineProps<Props>();

const page = usePage<{ flash?: { success?: string } }>();
onMounted(() => { if (page.props.flash?.success) swalSuccess(page.props.flash.success); });
watch(() => page.props.flash?.success, (v) => { if (v) swalSuccess(v); });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/office-admin/dashboard' },
    { title: 'PDS Management', href: '/office-admin/pds' },
];

const searchQuery = ref('');

const handleSearch = () => {
    router.get('/office-admin/pds', { search: searchQuery.value }, { preserveState: true });
};

const handleDelete = async (id: number, name: string) => {
    const result = await swalConfirmDelete(name);
    if (result.isConfirmed) {
        router.delete(`/office-admin/pds/${id}`, { preserveScroll: true });
    }
};

const fullName = (pds: PDS) => {
    return `${pds.first_name} ${pds.middle_name || ''} ${pds.last_name}`.replace(/\s+/g, ' ').trim();
};

// ── Generate Account Modal ──────────────────────────────────────────────────
const showModal = ref(false);
const showPassword = ref(false);
const showConfirm = ref(false);
const activePds = ref<PDS | null>(null);

const accountForm = useForm({
    email: '',
    password: '',
    password_confirmation: '',
    role: 'member',
});

const openModal = (pds: PDS) => {
    activePds.value = pds;
    accountForm.reset();
    accountForm.email = pds.email ?? '';
    accountForm.role = 'member';
    showPassword.value = false;
    showConfirm.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    activePds.value = null;
    accountForm.reset();
    accountForm.clearErrors();
};

const submitAccount = () => {
    if (!activePds.value) return;
    const name = fullName(activePds.value);
    accountForm.post(`/office-admin/pds/${activePds.value.id}/create-user`, {
        onSuccess: () => {
            closeModal();
            swalSuccess(`Account created for ${name}!`);
        },
    });
};
</script>

<template>
    <Head title="PDS Management" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6">
            
            <!-- Header -->
            <div class="rounded-2xl bg-linear-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <FileText class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">PDS Management</h1>
                            <p class="text-sm text-blue-200">{{ office.name }} - {{ pdsRecords.total }} member records</p>
                        </div>
                    </div>
                    <Link :href="`/office-admin/pds/create`">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <Plus class="h-4 w-4" /> Add New PDS
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Office Info Banner -->
            <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-4 py-3">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-indigo-500 p-1 text-white mt-0.5">
                        <Building2 class="h-4 w-4" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-indigo-900">
                            Managing PDS records for {{ office.name }}
                        </p>
                        <p class="text-xs text-indigo-700 mt-1">
                            All PDS records created here will be automatically assigned to your office ({{ office.code }}).
                        </p>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="searchQuery"
                        @keyup.enter="handleSearch"
                        type="text"
                        placeholder="Search by name or email..."
                        class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-10 pr-4 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                    />
                </div>
                <Button @click="handleSearch" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                    Search
                </Button>
            </div>

            <!-- PDS Table -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full min-w-160">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Account Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="pds in pdsRecords.data" :key="pds.id" class="hover:bg-indigo-50/20 transition-colors">
                            <td class="px-4 py-3">
                                <Link :href="`/office-admin/pds/${pds.id}`"
                                    class="font-medium text-slate-800 hover:text-indigo-600 transition-colors">
                                    {{ fullName(pds) }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ pds.email }}</td>
                            <td class="px-4 py-3 text-slate-400 text-xs">
                                {{ [pds.city_municipality_name, pds.province_name].filter(Boolean).join(", ") || "—" }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="pds.user"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">
                                    <UserCheck class="w-3 h-3" /> Linked
                                </span>
                                <button v-else
                                    @click="openModal(pds)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-200 hover:bg-amber-100 hover:ring-amber-300 transition-colors cursor-pointer">
                                    <UserPlus class="w-3 h-3" /> Generate Account
                                </button>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <Link :href="`/office-admin/pds/${pds.id}`">
                                        <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                                            <ScrollText class="w-3.5 h-3.5" /> View
                                        </button>
                                    </Link>
                                    <Link :href="`/office-admin/pds/${pds.id}/edit`">
                                        <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-indigo-700 hover:bg-indigo-50 transition-colors">
                                            <Pencil class="w-3.5 h-3.5" /> Edit
                                        </button>
                                    </Link>
                                    <button @click="handleDelete(pds.id, fullName(pds))"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-red-600 hover:bg-red-50 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" /> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!pdsRecords.data.length">
                            <td colspan="5" class="px-4 py-16 text-center text-sm text-slate-500">
                                No PDS records found. Click "Add New PDS" to create one.
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="pdsRecords.last_page > 1" class="border-t border-slate-200 px-4 py-3 flex items-center justify-between">
                    <div class="text-sm text-slate-500">
                        Showing {{ pdsRecords.from }} to {{ pdsRecords.to }} of {{ pdsRecords.total }} records
                    </div>
                    <div class="flex gap-1">
                        <Link v-for="link in pdsRecords.links" :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1 text-sm rounded border',
                                link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : link.url
                                    ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                                    : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Generate Account Modal ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

                    <!-- Panel -->
                    <div class="relative z-10 w-full max-w-md rounded-2xl bg-white shadow-2xl">
                        <!-- Header -->
                        <div class="flex items-center gap-3 rounded-t-2xl bg-linear-to-r from-blue-600 to-indigo-600 px-6 py-4 text-white">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/20">
                                <UserPlus class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-xs text-blue-200">Creating account for</p>
                                <h2 class="text-base font-bold">{{ activePds ? fullName(activePds) : '' }}</h2>
                            </div>
                            <button @click="closeModal" class="ml-auto rounded-lg p-1.5 hover:bg-white/20 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>

                        <!-- Form -->
                        <form @submit.prevent="submitAccount" class="space-y-4 p-6">
                            <!-- Email -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600">Email Address</label>
                                <input
                                    v-model="accountForm.email"
                                    type="email"
                                    placeholder="e.g. juan@example.com"
                                    class="w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    :class="accountForm.errors.email ? 'border-red-400' : 'border-slate-200'"
                                />
                                <p v-if="accountForm.errors.email" class="mt-1 text-xs text-red-500">{{ accountForm.errors.email }}</p>
                            </div>

                            <!-- Password -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600">Password</label>
                                <div class="relative">
                                    <input
                                        v-model="accountForm.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        placeholder="Min. 8 characters"
                                        class="w-full rounded-lg border px-3 py-2 pr-10 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                        :class="accountForm.errors.password ? 'border-red-400' : 'border-slate-200'"
                                    />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <Eye v-if="!showPassword" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="accountForm.errors.password" class="mt-1 text-xs text-red-500">{{ accountForm.errors.password }}</p>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600">Confirm Password</label>
                                <div class="relative">
                                    <input
                                        v-model="accountForm.password_confirmation"
                                        :type="showConfirm ? 'text' : 'password'"
                                        placeholder="Repeat password"
                                        class="w-full rounded-lg border px-3 py-2 pr-10 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                        :class="accountForm.errors.password_confirmation ? 'border-red-400' : 'border-slate-200'"
                                    />
                                    <button type="button" @click="showConfirm = !showConfirm"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                        <Eye v-if="!showConfirm" class="h-4 w-4" />
                                        <EyeOff v-else class="h-4 w-4" />
                                    </button>
                                </div>
                                <p v-if="accountForm.errors.password_confirmation" class="mt-1 text-xs text-red-500">{{ accountForm.errors.password_confirmation }}</p>
                            </div>

                            <!-- Role -->
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold text-slate-600">Position / Role</label>
                                <select
                                    v-model="accountForm.role"
                                    class="w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    :class="accountForm.errors.role ? 'border-red-400' : 'border-slate-200'"
                                >
                                    <option v-for="(label, value) in systemRoles" :key="value" :value="value">{{ label }}</option>
                                </select>
                                <p v-if="accountForm.errors.role" class="mt-1 text-xs text-red-500">{{ accountForm.errors.role }}</p>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                                <button type="button" @click="closeModal"
                                    class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    :disabled="accountForm.processing"
                                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">
                                    <UserPlus class="h-4 w-4" />
                                    {{ accountForm.processing ? 'Creating…' : 'Create Account' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
