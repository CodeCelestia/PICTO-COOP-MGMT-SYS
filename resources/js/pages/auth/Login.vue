<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/auth/AuthTechLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <AuthBase
        title="Log in to your account"
        description="Enter your email and password below to log in"
    >
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Transition
            appear
            enter-active-class="duration-500 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
        >
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="email" class="text-slate-200">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="email@example.com"
                            :aria-invalid="Boolean(errors.email)"
                            :class="[
                                'border-slate-700/80 bg-slate-950/40 text-slate-100 placeholder:text-slate-400',
                                errors.email ? 'animate-in fade-in-0 zoom-in-95 duration-200' : '',
                            ]"
                        />
                        <InputError :message="errors.email" class="text-red-300" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label for="password" class="text-slate-200">Password</Label>
                            <TextLink
                                v-if="canResetPassword"
                                :href="request()"
                                class="text-sm text-blue-300 transition hover:text-blue-200"
                                :tabindex="5"
                            >
                                Forgot password?
                            </TextLink>
                        </div>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Password"
                            :aria-invalid="Boolean(errors.password)"
                            :class="[
                                'border-slate-700/80 bg-slate-950/40 text-slate-100 placeholder:text-slate-400',
                                errors.password ? 'animate-in fade-in-0 zoom-in-95 duration-200' : '',
                            ]"
                        />
                        <InputError :message="errors.password" class="text-red-300" />
                    </div>

                    <div class="flex items-center justify-between">
                        <Label for="remember" class="flex items-center space-x-3 text-slate-200">
                            <Checkbox id="remember" name="remember" :tabindex="3" />
                            <span>Remember me</span>
                        </Label>
                    </div>

                    <Button
                        type="submit"
                        class="mt-4 w-full bg-blue-600 text-white transition hover:bg-blue-500"
                        :tabindex="4"
                        :disabled="processing"
                        data-test="login-button"
                    >
                        <Spinner v-if="processing" />
                        Log in
                    </Button>
                </div>

                <div
                    class="text-center text-sm text-slate-300"
                    v-if="canRegister"
                >
                    Don't have an account?
                    <TextLink
                        :href="register()"
                        :tabindex="5"
                        class="text-blue-300 transition hover:text-blue-200"
                    >
                        Sign up
                    </TextLink>
                </div>
            </Form>
        </Transition>
    </AuthBase>
</template>
