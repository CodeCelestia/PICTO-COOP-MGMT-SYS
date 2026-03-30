<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthSplitCard
        title="Forgot password"
        description="Enter your email to receive a password reset link"
    >
        <Head title="Forgot password - Coop System">
            <link rel="preconnect" href="https://fonts.googleapis.com" />
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
            <link
                href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap"
                rel="stylesheet"
            />
        </Head>

        <div class="mb-8 text-white">
            <div class="mb-2 flex items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-sky-300/20 bg-sky-500/10 text-sky-100">
                    <Mail class="h-5 w-5" />
                </div>
            </div>
            <h2 class="font-display text-2xl font-semibold">Forgot password</h2>
            <p class="mt-1 text-sm text-slate-300/90">
                Enter your email to receive a password reset link
            </p>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 p-3 text-center text-sm font-medium text-green-300"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="email.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email" class="text-slate-200">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                        class="h-12 border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button
                        class="h-12 w-full bg-[#0e7ea0] text-white hover:bg-[#1294bc]"
                        :disabled="processing"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="processing" />
                        Email password reset link
                    </Button>
                </div>
            </Form>

            <div class="space-x-1 text-center text-sm text-slate-300/90">
                <span>Or, return to</span>
                <TextLink :href="login()" class="font-medium text-sky-300 hover:text-sky-200">log in</TextLink>
            </div>
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
