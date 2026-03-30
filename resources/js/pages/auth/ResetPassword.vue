<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Lock } from 'lucide-vue-next';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { update } from '@/routes/password';

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <AuthSplitCard
        title="Reset password"
        description="Please enter your new password below"
    >
        <Head title="Reset password - Coop System">
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
                    <Lock class="h-5 w-5" />
                </div>
            </div>
            <h2 class="font-display text-2xl font-semibold">Reset password</h2>
            <p class="mt-1 text-sm text-slate-300/90">
                Please enter your new password below
            </p>
        </div>

        <Form
            v-bind="update.form()"
            :transform="(data) => ({ ...data, token, email })"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email" class="text-slate-200">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="inputEmail"
                        class="mt-1 block h-12 w-full border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400"
                        readonly
                    />
                    <InputError :message="errors.email" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password" class="text-slate-200">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block h-12 w-full border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        autofocus
                        placeholder="Password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block h-12 w-full border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        placeholder="Confirm password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <Button
                    type="submit"
                    class="mt-4 h-12 w-full bg-[#0e7ea0] text-white hover:bg-[#1294bc]"
                    :disabled="processing"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="processing" />
                    Reset password
                </Button>
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
