Users & Roles<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Shield } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalSuccess, swalError } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { store as officeRolesStore } from '@/routes/super-admin/office-roles';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/super-admin/dashboard' },
    { title: 'Office Roles', href: '/super-admin/office-roles' },
    { title: 'Create', href: '/super-admin/office-roles/create' },
];

const form = useForm({
    name: '',
    display_name: '',
    description: '',
});

const submit = () => {
    form.post(officeRolesStore().url, {
        onSuccess: () => swalSuccess('Role Created!', `"${form.display_name}" has been created successfully.`),
        onError: () => swalError('Validation Error', 'Please check the fields and try again.'),
    });
};
</script>

<template>
    <Head title="Create Office Role" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-3xl mx-auto w-full">

            <!-- Header -->
            <div class="rounded-2xl bg-linear-to-r from-purple-600 to-pink-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <Shield class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Create New Office Role</h1>
                            <p class="text-sm text-purple-200">Define a new role for office assignments</p>
                        </div>
                    </div>
                    <Link href="/super-admin/office-roles">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <ArrowLeft class="h-4 w-4" /> Back
                        </Button>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Role Information -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-purple-600 mb-4">Role Information</p>
                    
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">
                                Role Name (slug) <span class="text-red-500">*</span>
                            </label>
                            <Input 
                                v-model="form.name" 
                                required 
                                placeholder="e.g., assistant_manager" 
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <p class="text-xs text-slate-400">Lowercase, no spaces, use underscores or hyphens</p>
                            <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">
                                Display Name <span class="text-red-500">*</span>
                            </label>
                            <Input 
                                v-model="form.display_name" 
                                required 
                                placeholder="e.g., Assistant Manager" 
                                :class="{ 'border-red-400': form.errors.display_name }"
                            />
                            <p class="text-xs text-slate-400">Human-friendly name shown in the UI</p>
                            <span v-if="form.errors.display_name" class="text-xs text-red-600">{{ form.errors.display_name }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Description</label>
                            <textarea 
                                v-model="form.description" 
                                placeholder="Describe the responsibilities and permissions of this role..."
                                rows="3"
                                class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-red-400': form.errors.description }"
                            />
                            <span v-if="form.errors.description" class="text-xs text-red-600">{{ form.errors.description }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pb-6">
                    <Link href="/super-admin/office-roles">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="bg-purple-600 hover:bg-purple-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Creating…' : 'Create Role' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
