<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import { Plus, Search, Pencil, Trash2, UserCheck, UserX, ScrollText, UserPlus, Eye, EyeOff } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem, Paginator } from "@/types";
import { swalConfirmDelete, swalSuccess } from "@/composables/useSwal";

interface PDS {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    name_extension?: string;
    email: string;
    phone_number?: string;
    city_municipality_name?: string;
    province_name?: string;
    user?: { id: number; email: string };
}

interface Props {
    pdsRecords: Paginator<PDS>;
    filters: { search: string };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "PDS Management", href: "/super-admin/pds" },
];

const page = usePage<{ flash?: { success?: string } }>();
onMounted(() => { if (page.props.flash?.success) swalSuccess(page.props.flash.success); });
watch(() => page.props.flash?.success, (v) => { if (v) swalSuccess(v); });

const search = ref(props.filters.search ?? "");
let searchTimer: ReturnType<typeof setTimeout>;

const doSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get("/super-admin/pds", { search: search.value }, { preserveState: true, replace: true });
    }, 350);
};

const fullName = (p: PDS) =>
    [p.last_name + ",", p.first_name, p.middle_name, p.name_extension].filter(Boolean).join(" ");

const handleDelete = async (id: number, name: string) => {
    const result = await swalConfirmDelete(name);
    if (result.isConfirmed) router.delete(`/super-admin/pds/${id}`, { preserveScroll: true });
};

// ── Generate Account Modal ──────────────────────────────────────────────────
const roles = [
    { value: 'super_admin',      label: 'Super Admin' },
    { value: 'coop_admin',       label: 'Coop Admin' },
    { value: 'chairperson',      label: 'Chairperson' },
    { value: 'general_manager',  label: 'General Manager' },
    { value: 'officer',          label: 'Officer' },
    { value: 'committee_member', label: 'Committee Member' },
    { value: 'member',           label: 'Member' },
];

const showModal    = ref(false);
const showPassword = ref(false);
const showConfirm  = ref(false);
const activePds    = ref<PDS | null>(null);

const accountForm = useForm({
    email:                 '',
    password:              '',
    password_confirmation: '',
    role:                  'member',
});

const openModal = (pds: PDS) => {
    activePds.value = pds;
    accountForm.reset();
    accountForm.email = pds.email ?? '';
    accountForm.role  = 'member';
    showPassword.value = false;
    showConfirm.value  = false;
    showModal.value    = true;
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
    accountForm.post(`/super-admin/pds/${activePds.value.id}/create-user`, {
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

            <!-- Page Header -->
            <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><ScrollText class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Personal Data Sheets</h1>
                            <p class="text-sm text-blue-200">{{ pdsRecords.total }} record{{ pdsRecords.total === 1 ? '' : 's' }} on file</p>
                        </div>
                    </div>
                    <Link href="/super-admin/pds/create">
                        <Button class="bg-white text-blue-700 hover:bg-blue-50 gap-2 font-semibold shadow">
                            <Plus class="w-4 h-4" /> New PDS
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Search + Stats bar -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input
                        v-model="search"
                        @input="doSearch"
                        placeholder="Search by name or email…"
                        class="h-9 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-4 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                </div>
                <span class="text-xs text-slate-400 font-medium">
                    {{ pdsRecords.total }} record{{ pdsRecords.total === 1 ? '' : 's' }}
                </span>
            </div>

            <!-- Table Card -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-800">
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-300">Account</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="!pdsRecords.data.length">
                            <td colspan="5" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center gap-2 text-slate-400">
                                    <Search class="w-8 h-8 opacity-40" />
                                    <p class="text-sm font-medium">No PDS records found.</p>
                                    <p class="text-xs">Try adjusting your search or create a new record.</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="pds in pdsRecords.data" :key="pds.id"
                            class="hover:bg-blue-50/30 transition-colors group">
                            <!-- Name with avatar -->
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs font-bold shrink-0 uppercase">
                                        {{ pds.first_name?.[0] ?? '' }}{{ pds.last_name?.[0] ?? '' }}
                                    </div>
                                    <Link :href="`/super-admin/pds/${pds.id}`"
                                        class="font-medium text-slate-800 hover:text-indigo-600 transition-colors">
                                        {{ fullName(pds) }}
                                    </Link>
                                </div>
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
                                    <Link :href="`/super-admin/pds/${pds.id}`">
                                        <button class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-blue-700 hover:bg-blue-50 transition-colors">
                                            <ScrollText class="w-3.5 h-3.5" /> View
                                        </button>
                                    </Link>
                                    <Link :href="`/super-admin/pds/${pds.id}/edit`">
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
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pdsRecords.last_page > 1" class="flex items-center justify-between">
                <p class="text-xs text-slate-400">
                    Showing {{ pdsRecords.from }}–{{ pdsRecords.to }} of {{ pdsRecords.total }}
                </p>
                <div class="flex items-center gap-1">
                    <Link v-for="link in pdsRecords.links" :key="link.label"
                        :href="link.url ?? '#'"
                        :class="[
                            'px-3 py-1.5 text-xs rounded-md border font-medium transition-colors',
                            link.active
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                : link.url
                                    ? 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50 hover:border-slate-300'
                                    : 'bg-slate-50 text-slate-300 border-slate-200 cursor-not-allowed pointer-events-none'
                        ]"
                        v-html="link.label"
                    />
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
                        <div class="flex items-center gap-3 rounded-t-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 text-white">
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
                                    <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
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
