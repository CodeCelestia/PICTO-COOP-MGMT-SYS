<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { User, Mail, Lock } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';
</script>

<template>
    <AuthSplitCard>
        <Head title="Register - Coop System">
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
            <link
                href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap"
                rel="stylesheet"
            />
        </Head>

        <!-- Welcome Header -->
        <div class="mb-8 text-white">
            <div class="mb-2 flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-sky-300/20 bg-sky-500/10 text-sky-100">
                    <User class="h-6 w-6" />
                </div>
            </div>
            <h2 class="font-display text-2xl font-semibold">Create an account</h2>
            <p class="mt-1 text-sm text-slate-300/90">
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
                    <Label for="name" class="text-sm font-medium text-slate-200">
                        Full Name
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <User class="h-5 w-5 text-slate-400" />
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
                            class="h-12 border-sky-200/20 bg-slate-900/50 pl-10 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        />
                    </div>
                    <InputError :message="errors.name" class="text-red-400" />
                </div>

                <!-- Email Field -->
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium text-slate-200">
                        Email Address
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Mail class="h-5 w-5 text-slate-400" />
                        </div>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
                            placeholder="you@example.com"
                            class="h-12 border-sky-200/20 bg-slate-900/50 pl-10 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        />
                    </div>
                    <InputError :message="errors.email" class="text-red-400" />
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <Label for="password" class="text-sm font-medium text-slate-200">
                        Password
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Lock class="h-5 w-5 text-slate-400" />
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="••••••••"
                            class="h-12 border-sky-200/20 bg-slate-900/50 pl-10 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        />
                    </div>
                    <InputError :message="errors.password" class="text-red-400" />
                </div>

                <!-- Confirm Password Field -->
                <div class="space-y-2">
                    <Label for="password_confirmation" class="text-sm font-medium text-slate-200">
                        Confirm Password
                    </Label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <Lock class="h-5 w-5 text-slate-400" />
                        </div>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="••••••••"
                            class="h-12 border-sky-200/20 bg-slate-900/50 pl-10 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        />
                    </div>
                    <InputError :message="errors.password_confirmation" class="text-red-400" />
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="mt-2 h-12 w-full bg-[#0e7ea0] text-base font-medium text-white hover:bg-[#1294bc]"
                    tabindex="5"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner v-if="processing" class="mr-2" />
                    Create Account
                </Button>
            </div>

            <!-- Login Link -->
            <div class="text-center text-sm text-slate-300/90">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="font-medium text-sky-300 hover:text-sky-200 hover:underline"
                    :tabindex="6"
                >
                    Sign in here
                </TextLink>
            </div>
        </Form>
    </AuthSplitCard>
</template>

<style scoped>
.font-display {
    font-family:
        'Sora',
        'IBM Plex Sans',
        sans-serif;
    letter-spacing: -0.025em;
}
</style>
