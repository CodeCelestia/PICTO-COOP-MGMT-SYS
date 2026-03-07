<script setup lang="ts">
import { useForm, Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

const form = useForm({
    name: '',
    code: '',
    address: '',
    allow_self_registration: false,
    admin_name: '',
    admin_email: '',
    admin_password: '',
    admin_password_confirmation: '',
});

function submit() {
    form.post('/sdn-admin/offices');
}
</script>

<template>
    <AppLayout>
        <Head title="Create Office" />

        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-bold">Create Office</h1>
                <p class="text-muted-foreground text-sm">A new office admin account will be created automatically.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Office Details -->
                <Card>
                    <CardHeader>
                        <CardTitle>Office Details</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="name">Office Name <span class="text-red-500">*</span></Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. PICTO Davao Branch" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="code">Office Code <span class="text-red-500">*</span></Label>
                            <Input id="code" v-model="form.code" placeholder="e.g. PICTO-DAV" />
                            <InputError :message="form.errors.code" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="form.address" placeholder="Full office address" />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="allow_self_registration"
                                type="checkbox"
                                v-model="form.allow_self_registration"
                                class="h-4 w-4 rounded border-gray-300"
                            />
                            <Label for="allow_self_registration" class="cursor-pointer">
                                Allow members to self-register under this office
                            </Label>
                        </div>
                    </CardContent>
                </Card>

                <!-- Office Admin Account -->
                <Card>
                    <CardHeader>
                        <CardTitle>Office Admin Account</CardTitle>
                        <CardDescription>These credentials will be used by the office admin to log in.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label for="admin_name">Admin Full Name <span class="text-red-500">*</span></Label>
                            <Input id="admin_name" v-model="form.admin_name" placeholder="e.g. Juan Dela Cruz" />
                            <InputError :message="form.errors.admin_name" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="admin_email">Admin Email <span class="text-red-500">*</span></Label>
                            <Input id="admin_email" type="email" v-model="form.admin_email" placeholder="admin@office.coop" />
                            <InputError :message="form.errors.admin_email" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="admin_password">Password <span class="text-red-500">*</span></Label>
                            <Input id="admin_password" type="password" v-model="form.admin_password" placeholder="Min. 8 characters" />
                            <InputError :message="form.errors.admin_password" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="admin_password_confirmation">Confirm Password <span class="text-red-500">*</span></Label>
                            <Input id="admin_password_confirmation" type="password" v-model="form.admin_password_confirmation" placeholder="Repeat password" />
                        </div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="router.visit('/sdn-admin/offices')">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Creating...' : 'Create Office' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
