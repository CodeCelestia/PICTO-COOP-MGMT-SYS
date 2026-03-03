<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Building2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { swalSuccess, swalError } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';

interface Office {
    id: number;
    name: string;
    code: string;
    contact_email?: string;
    contact_phone?: string;
    chairperson?: string;
    general_manager?: string;
    key_services?: string;
}

interface Props {
    office: Office;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/office-admin/dashboard' },
    { title: 'Office Profile', href: '/office-admin/profile' },
    { title: 'Edit', href: '#' },
];

const form = useForm({
    contact_email: props.office.contact_email || '',
    contact_phone: props.office.contact_phone || '',
    chairperson: props.office.chairperson || '',
    general_manager: props.office.general_manager || '',
    key_services: props.office.key_services || '',
});

const submit = () => {
    form.patch('/office-admin/profile', {
        onSuccess: () => swalSuccess('Profile Updated!', 'Office profile has been updated successfully.'),
        onError: () => swalError('Validation Error', 'Please check the fields and try again.'),
    });
};
</script>

<template>
    <Head title="Edit Office Profile" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-3xl mx-auto w-full">

            <!-- Header -->
            <div class="rounded-2xl bg-linear-to-r from-indigo-600 to-purple-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">Edit Office Profile</h1>
                            <p class="text-sm text-indigo-200">Update {{ office.name }} information</p>
                        </div>
                    </div>
                    <Link href="/office-admin/profile">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2">
                            <ArrowLeft class="h-4 w-4" /> Back
                        </Button>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- Office Information -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Office Information</p>
                    
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="contact_email">Contact Email</Label>
                            <Input 
                                id="contact_email"
                                v-model="form.contact_email" 
                                type="email"
                                placeholder="office@example.com" 
                                :class="{ 'border-red-400': form.errors.contact_email }"
                            />
                            <span v-if="form.errors.contact_email" class="text-xs text-red-600">{{ form.errors.contact_email }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="contact_phone">Contact Phone</Label>
                            <Input 
                                id="contact_phone"
                                v-model="form.contact_phone" 
                                type="text"
                                placeholder="+63 XXX XXX XXXX" 
                                :class="{ 'border-red-400': form.errors.contact_phone }"
                            />
                            <span v-if="form.errors.contact_phone" class="text-xs text-red-600">{{ form.errors.contact_phone }}</span>
                        </div>
                    </div>
                </div>

                <!-- Leadership Information -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Leadership</p>
                    
                    <div class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="chairperson">Chairperson</Label>
                            <Input 
                                id="chairperson"
                                v-model="form.chairperson" 
                                type="text"
                                placeholder="Chairperson name" 
                                :class="{ 'border-red-400': form.errors.chairperson }"
                            />
                            <span v-if="form.errors.chairperson" class="text-xs text-red-600">{{ form.errors.chairperson }}</span>
                        </div>

                        <div class="space-y-1.5">
                            <Label for="general_manager">General Manager</Label>
                            <Input 
                                id="general_manager"
                                v-model="form.general_manager" 
                                type="text"
                                placeholder="General Manager name" 
                                :class="{ 'border-red-400': form.errors.general_manager }"
                            />
                            <span v-if="form.errors.general_manager" class="text-xs text-red-600">{{ form.errors.general_manager }}</span>
                        </div>
                    </div>
                </div>

                <!-- Services -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">Services</p>
                    
                    <div class="space-y-1.5">
                        <Label for="key_services">Key Services</Label>
                        <textarea 
                            id="key_services"
                            v-model="form.key_services" 
                            placeholder="Describe the key services provided by the office..."
                            rows="4"
                            class="flex min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :class="{ 'border-red-400': form.errors.key_services }"
                        />
                        <span v-if="form.errors.key_services" class="text-xs text-red-600">{{ form.errors.key_services }}</span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pb-6">
                    <Link href="/office-admin/profile">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                    <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
