<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ArrowLeft, Save } from "lucide-vue-next";

interface User {
    id: number;
    name: string;
    email: string;
    roles: string[];
}

interface EditUserProps {
    user: User;
    systemRoles: string[];
}

const props = defineProps<EditUserProps>();

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: "",
    password_confirmation: "",
    role: props.user.roles[0] || "",
});

const submit = () => {
    form.patch(`/super-admin/users/${props.user.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Edit User - ${user.name}`" />

    <div class="min-h-screen bg-linear-to-br from-slate-50 via-indigo-50/30 to-slate-100 p-6">
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Edit User</h1>
                    <p class="text-sm text-slate-500 mt-1">Update user information and permissions</p>
                </div>
                <Button variant="outline" @click="router.visit('/super-admin/users')" class="flex items-center gap-2">
                    <ArrowLeft class="w-4 h-4" />
                    Back to Users
                </Button>
            </div>

            <!-- Form Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        User Details
                    </CardTitle>
                    <CardDescription>Modify user account information</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div class="space-y-2">
                            <Label for="name">Full Name <span class="text-red-500">*</span></Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Enter full name"
                                :class="{ 'border-red-500': form.errors.name }"
                                required
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div class="space-y-2">
                            <Label for="email">Email Address <span class="text-red-500">*</span></Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="user@example.com"
                                :class="{ 'border-red-500': form.errors.email }"
                                required
                            />
                            <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <!-- Password (Optional) -->
                        <div class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/30 p-4">
                            <div class="flex items-center gap-2">
                                <div class="h-5 w-5 rounded-full bg-amber-500 flex items-center justify-center text-white text-xs font-bold">i</div>
                                <p class="text-sm font-medium text-amber-800">Change Password (Optional)</p>
                            </div>
                            <p class="text-xs text-amber-700">Leave blank to keep the current password</p>

                            <div class="space-y-2">
                                <Label for="password">New Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="••••••••"
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation">Confirm New Password</Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>

                        <!-- Role -->
                        <div class="space-y-2">
                            <Label for="role">System Role <span class="text-red-500">*</span></Label>
                            <Select v-model="form.role" required>
                                <SelectTrigger :class="{ 'border-red-500': form.errors.role }">
                                    <SelectValue placeholder="Select a role" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="role in systemRoles" :key="role" :value="role">
                                        {{ role.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase()) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.role" class="text-sm text-red-600">{{ form.errors.role }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3 pt-4 border-t">
                            <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white">
                                <Save class="w-4 h-4 mr-2" />
                                {{ form.processing ? "Saving..." : "Save Changes" }}
                            </Button>
                            <Button type="button" variant="outline" @click="router.visit('/super-admin/users')" :disabled="form.processing">
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
