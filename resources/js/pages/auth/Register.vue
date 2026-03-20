<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
import { User, Mail, Lock } from 'lucide-vue-next';
</script>

<template>
    <AuthSplitCard>
        <Head title="Register - Coop System" />

        <!-- Welcome Header -->
        <div class="mb-8 text-white">
            <div class="mb-2 flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                    <User class="h-6 w-6" />
                </div>
            </div>
            <h2 class="text-2xl font-bold">Create an account</h2>
            <p class="mt-1 text-sm text-gray-400">
                Enter your details to get started
            </p>
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="space-y-5">
                <!-- Name Field -->
                <div class="space-y-2">
                    <Label for="name" class="text-sm font-medium text-gray-300">
                        Full Name
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <User class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            name="name"
                            placeholder="Juan Dela Cruz"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.name" class="text-red-400" />
                </div>

                <!-- Email Field -->
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium text-gray-300">
                        Email Address
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Mail class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
                            placeholder="you@example.com"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.email" class="text-red-400" />
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium text-gray-300">
                        Password
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Lock class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="••••••••"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.password" class="text-red-400" />
                </div>

                <!-- Confirm Password Field -->
                <div class="space-y-2">
                    <Label for="password_confirmation" class="text-sm font-medium text-gray-300">
                        Confirm Password
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Lock class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="••••••••"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.password_confirmation" class="text-red-400" />
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="mt-2 h-12 w-full bg-blue-600 text-base font-medium hover:bg-blue-700"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    Create Account
                </Button>
            </div>

            <!-- Login Link -->
            <div class="text-center text-sm text-gray-400">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="font-medium text-blue-400 hover:text-blue-300 hover:underline"
                    :tabindex="6"
                >
                    Sign in here
                </TextLink>
            </div>
        </Form>
    </AuthSplitCard>
</template>
