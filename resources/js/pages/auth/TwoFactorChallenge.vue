<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            buttonText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication code',
        description:
            'Enter the authentication code provided by your authenticator application.',
        buttonText: 'login using a recovery code',
    };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};

const code = ref<string>('');

const goBackToLogin = () => {
    if (typeof window !== 'undefined' && window.history.length > 1) {
        window.history.back();
        return;
    }

    router.visit('/login');
};
</script>

<template>
    <AuthSplitCard
        :title="authConfigContent.title"
        :description="authConfigContent.description"
    >
        <Head title="Two-factor authentication - Coop System">
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
            <link
                href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap"
                rel="stylesheet"
            />
        </Head>

        <div class="mb-8 text-white">
            <div class="mb-2 flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-9 border-sky-300/35 bg-slate-950/40 px-3 text-sky-100 hover:bg-sky-500/15"
                    @click="goBackToLogin"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>
            <h2 class="font-display text-2xl font-semibold">{{ authConfigContent.title }}</h2>
            <p class="mt-1 text-sm text-slate-300/90">
                {{ authConfigContent.description }}
            </p>
        </div>

        <div class="space-y-6">
            <template v-if="!showRecoveryInput">
                <Form
                    v-bind="store.form()"
                    class="space-y-4"
                    reset-on-error
                    @error="code = ''"
                    #default="{ errors, processing, clearErrors }"
                >
                    <input type="hidden" name="code" :value="code" />
                    <div
                        class="flex flex-col items-center justify-center space-y-3 text-center"
                    >
                        <div class="flex w-full items-center justify-center">
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="processing"
                                autofocus
                                class="w-full justify-center"
                            >
                                <InputOTPGroup class="justify-center">
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                        class="h-11 w-11 border-sky-200/20 bg-slate-900/50 text-slate-100"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError :message="errors.code" />
                    </div>
                    <Button type="submit" class="h-12 w-full bg-[#0e7ea0] text-white hover:bg-[#1294bc]" :disabled="processing">
                        <Spinner v-if="processing" />
                        Continue
                    </Button>
                    <div class="text-center text-sm text-slate-300/90">
                        <span>or you can </span>
                        <button
                            type="button"
                            class="font-medium text-sky-300 underline underline-offset-4 transition-colors duration-300 hover:text-sky-200"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </Form>
            </template>

            <template v-else>
                <Form
                    v-bind="store.form()"
                    class="space-y-4"
                    reset-on-error
                    #default="{ errors, processing, clearErrors }"
                >
                    <Input
                        name="recovery_code"
                        type="text"
                        placeholder="Enter recovery code"
                        :autofocus="showRecoveryInput"
                        required
                        class="h-12 border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                    />
                    <InputError :message="errors.recovery_code" />
                    <Button type="submit" class="h-12 w-full bg-[#0e7ea0] text-white hover:bg-[#1294bc]" :disabled="processing">
                        <Spinner v-if="processing" />
                        Continue
                    </Button>

                    <div class="text-center text-sm text-slate-300/90">
                        <span>or you can </span>
                        <button
                            type="button"
                            class="font-medium text-sky-300 underline underline-offset-4 transition-colors duration-300 hover:text-sky-200"
                            @click="() => toggleRecoveryMode(clearErrors)"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </Form>
            </template>
        </div>
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
