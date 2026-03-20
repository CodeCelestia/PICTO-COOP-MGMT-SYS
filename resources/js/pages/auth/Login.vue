<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import { Mail, Lock } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthSplitCard>
        <Head title="Sign In - Coop System" />

        <!-- Welcome Header -->
        <div class="mb-8 text-white">
            <div class="mb-2 flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                    <svg 
                        xmlns="http://www.w3.org/2000/svg" 
                        class="h-6 w-6" 
                        fill="none" 
                        viewBox="0 0 24 24" 
                        stroke="currentColor"
                    >
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            stroke-width="2" 
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" 
                        />
                    </svg>
                </div>
            </div>
            <h2 class="text-2xl font-bold">Welcome back</h2>
            <p class="mt-1 text-sm text-gray-400">
                Sign in to your account to continue
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg bg-green-500/10 border border-green-500/20 p-3 text-center text-sm text-green-400"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="space-y-5">
                <!-- Email Field -->
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium text-gray-300">
                        Email address
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Mail class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="you@example.com"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.email" class="text-red-400" />
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium text-gray-300">
                            Password
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-sm text-blue-400 hover:text-blue-300 hover:underline"
                            :tabindex="5"
                        >
                            Forgot password?
                        </TextLink>
                    </div>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Lock class="h-5 w-5 text-gray-500" />
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="h-12 border-gray-700 bg-[#0f1419] pl-10 text-white placeholder:text-gray-500 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                    <InputError :message="errors.password" class="text-red-400" />
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <Label for="remember" class="flex items-center space-x-2 text-sm text-gray-300">
                        <Checkbox 
                            id="remember" 
                            name="remember" 
                            :tabindex="3"
                            class="border-gray-700 bg-[#0f1419] data-[state=checked]:bg-blue-600 data-[state=checked]:border-blue-600"
                        />
                        <span>Keep me signed in</span>
                    </Label>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="mt-2 h-12 w-full bg-blue-600 text-base font-medium hover:bg-blue-700"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    Sign In
                </Button>
            </div>

            <!-- Register Link -->
            <div
                class="text-center text-sm text-gray-400"
                v-if="canRegister"
            >
                Don't have an account?
                <TextLink 
                    :href="register()" 
                    :tabindex="5" 
                    class="font-medium text-blue-400 hover:text-blue-300 hover:underline"
                >
                    Create an account
                </TextLink>
            </div>
        </Form>
    </AuthSplitCard>
</template>
