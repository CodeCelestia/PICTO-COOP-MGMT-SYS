<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { UserPlus, ArrowLeft, Eye, EyeOff } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalSuccess, swalError } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { storeUser as pdsStoreUser } from '@/routes/super-admin/pds';

interface PDS {
    id: number;
    first_name: string;
    middle_name?: string;
    last_name: string;
    email: string;
}

interface Props {
    pds: PDS;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'PDS Management', href: '/super-admin/pds' },
    { title: `${props.pds.last_name}, ${props.pds.first_name}`, href: `/super-admin/pds/${props.pds.id}` },
    { title: 'Create Account', href: '' },
];

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const fullName = `${props.pds.first_name}${props.pds.middle_name ? ' ' + props.pds.middle_name : ''} ${props.pds.last_name}`;

const submit = () => {
    form.post(pdsStoreUser(props.pds.id).url, {
        onSuccess: () => swalSuccess('Account Created!', `Login credentials have been set for ${fullName}.`),
        onError: () => swalError('Validation Error', 'Please check the fields.'),
    });
};
</script>

<template>
    <Head title="Create User Account" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-lg mx-auto w-full">

            <!-- Header -->
            <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><UserPlus class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Create User Account</h1>
                            <p class="text-sm text-blue-200">Set login credentials for {{ fullName }}</p>
                        </div>
                    </div>
                    <Link :href="`/super-admin/pds/${pds.id}`">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button>
                    </Link>
                </div>
            </div>

            <!-- PDS info card -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 flex items-center gap-4">
                <div class="h-12 w-12 rounded-xl bg-blue-600 text-white flex items-center justify-center text-lg font-black uppercase shrink-0">
                    {{ pds.first_name?.[0] ?? '' }}{{ pds.last_name?.[0] ?? '' }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ fullName }}</p>
                    <p class="text-xs text-slate-500">{{ pds.email }}</p>
                </div>
                <span class="ml-auto inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-50 text-amber-700 ring-1 ring-amber-200">No Account Yet</span>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-5">Set Password</p>

                <div class="space-y-4">
                    <!-- Email (readonly) -->
                    <div class="space-y-1.5">
                        <Label class="text-sm font-semibold text-slate-700">Email Address</Label>
                        <Input :value="pds.email" disabled class="bg-slate-50 text-slate-500" />
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <Label class="text-sm font-semibold text-slate-700">Password <span class="text-red-500">*</span></Label>
                        <div class="relative">
                            <Input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                minlength="8"
                                placeholder="Minimum 8 characters"
                                :class="{ 'border-red-400': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</p>
                        <p v-else class="text-xs text-slate-400">Must be at least 8 characters</p>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <Label class="text-sm font-semibold text-slate-700">Confirm Password <span class="text-red-500">*</span></Label>
                        <div class="relative">
                            <Input
                                v-model="form.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                required
                                placeholder="Re-enter password"
                                :class="{ 'border-red-400': form.errors.password_confirmation }"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                            >
                                <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="text-xs text-red-600">{{ form.errors.password_confirmation }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-100 mt-6 pt-4">
                    <Link :href="`/super-admin/pds/${pds.id}`">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white gap-2 shadow-sm">
                        <UserPlus class="h-4 w-4" />
                        {{ form.processing ? 'Creating...' : 'Create Account' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
