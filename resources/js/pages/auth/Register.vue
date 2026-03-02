<script setup lang="ts">
import { computed, ref } from 'vue';
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/auth/AuthTechLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const password = ref('');

const passwordScore = computed(() => {
    let score = 0;

    if (password.value.length >= 8) {
        score += 1;
    }

    if (/[A-Z]/.test(password.value) && /[a-z]/.test(password.value)) {
        score += 1;
    }

    if (/\d/.test(password.value)) {
        score += 1;
    }

    if (/[^\w\s]/.test(password.value)) {
        score += 1;
    }

    return score;
});

const strengthLabel = computed(() => {
    if (!password.value.length) {
        return 'Enter a password';
    }

    if (passwordScore.value <= 1) {
        return 'Weak password';
    }

    if (passwordScore.value <= 2) {
        return 'Fair password';
    }

    if (passwordScore.value === 3) {
        return 'Strong password';
    }

    return 'Very strong password';
});
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Transition
            appear
            enter-active-class="duration-500 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
        >
            <Form
                v-bind="store.form()"
                :reset-on-success="['password', 'password_confirmation']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-6"
            >
                <div class="grid gap-6">
                    <div class="grid gap-2">
                        <Label for="name" class="text-slate-200">Name</Label>
                        <Input
                            id="name"
                            type="text"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="name"
                            name="name"
                            placeholder="Full name"
                            :aria-invalid="Boolean(errors.name)"
                            :class="[
                                'border-slate-700/80 bg-slate-950/40 text-slate-100 placeholder:text-slate-400',
                                errors.name ? 'animate-in fade-in-0 zoom-in-95 duration-200' : '',
                            ]"
                        />
                        <InputError :message="errors.name" class="text-red-300" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email" class="text-slate-200">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            :tabindex="2"
                            autocomplete="email"
                            name="email"
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
                        <Label for="password" class="text-slate-200">Password</Label>
                        <Input
                            id="password"
                            v-model="password"
                            type="password"
                            required
                            :tabindex="3"
                            autocomplete="new-password"
                            name="password"
                            placeholder="Password"
                            :aria-invalid="Boolean(errors.password)"
                            :class="[
                                'border-slate-700/80 bg-slate-950/40 text-slate-100 placeholder:text-slate-400',
                                errors.password ? 'animate-in fade-in-0 zoom-in-95 duration-200' : '',
                            ]"
                        />
                        <div class="space-y-2">
                            <div class="grid grid-cols-4 gap-1">
                                <span
                                    v-for="index in 4"
                                    :key="`meter-${index}`"
                                    class="h-1.5 rounded-full transition-all duration-300"
                                    :class="index <= passwordScore ? 'bg-blue-400' : 'bg-slate-700'"
                                />
                            </div>
                            <p class="text-xs text-slate-300 transition">
                                {{ strengthLabel }}
                            </p>
                        </div>
                        <InputError :message="errors.password" class="text-red-300" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation" class="text-slate-200">Confirm password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            required
                            :tabindex="4"
                            autocomplete="new-password"
                            name="password_confirmation"
                            placeholder="Confirm password"
                            :aria-invalid="Boolean(errors.password_confirmation)"
                            :class="[
                                'border-slate-700/80 bg-slate-950/40 text-slate-100 placeholder:text-slate-400',
                                errors.password_confirmation ? 'animate-in fade-in-0 zoom-in-95 duration-200' : '',
                            ]"
                        />
                        <InputError
                            :message="errors.password_confirmation"
                            class="text-red-300"
                        />
                    </div>

                    <Button
                        type="submit"
                        class="mt-2 w-full bg-blue-600 text-white transition hover:bg-blue-500"
                        tabindex="5"
                        :disabled="processing"
                        data-test="register-user-button"
                    >
                        <Spinner v-if="processing" />
                        Create account
                    </Button>
                </div>

                <div class="text-center text-sm text-slate-300">
                    Already have an account?
                    <TextLink
                        :href="login()"
                        class="text-blue-300 transition hover:text-blue-200"
                        :tabindex="6"
                    >
                        Log in
                    </TextLink>
                </div>
            </Form>
        </Transition>
    </AuthBase>
</template>
