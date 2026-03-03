<script setup lang="ts">
import { Head, router, useForm } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ArrowLeft, UserPlus } from "lucide-vue-next";

interface CreateUserProps {
    systemRoles: string[];
}

const props = defineProps<CreateUserProps>();

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
});

const submit = () => {
    form.post("/super-admin/users", {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Create User" />

    <div class="min-h-screen bg-linear-to-br from-slate-50 via-indigo-50/30 to-slate-100 p-6">
        <div class="mx-auto max-w-3xl space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Create User</h1>
                    <p class="text-sm text-slate-500 mt-1">Add a new user to the system</p>
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
                        <UserPlus class="w-5 h-5 text-indigo-600" />
                        User Information
                    </CardTitle>
                    <CardDescription>Enter the details for the new user account</CardDescription>
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

                        <!-- Password -->
                        <div class="space-y-2">
                            <Label for="password">Password <span class="text-red-500">*</span></Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                :class="{ 'border-red-500': form.errors.password }"
                                required
                            />
                            <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                            <p class="text-xs text-slate-500">Minimum 8 characters</p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="space-y-2">
                            <Label for="password_confirmation">Confirm Password <span class="text-red-500">*</span></Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                required
                            />
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
                                <UserPlus class="w-4 h-4 mr-2" />
                                {{ form.processing ? "Creating..." : "Create User" }}
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
