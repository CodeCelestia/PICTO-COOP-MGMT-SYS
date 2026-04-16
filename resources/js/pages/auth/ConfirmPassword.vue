<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { store } from '@/routes/password/confirm';

const goBack = () => {
    if (typeof window !== 'undefined') {
        window.history.back();
    }
};
</script>

<template>
    <AuthSplitCard
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password - Coop System">
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
                    @click="goBack"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>
            <h2 class="font-display text-2xl font-semibold">Confirm your password</h2>
            <p class="mt-1 text-sm text-slate-300/90">
                This is a secure area of the application. Please confirm your password before continuing.
            </p>
        </div>

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label for="password" class="text-slate-200">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        class="mt-1 block h-12 w-full border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400 focus:border-sky-400 focus:ring-sky-400"
                        required
                        autocomplete="current-password"
                        autofocus
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="h-12 w-full bg-[#0e7ea0] text-white hover:bg-[#1294bc]"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" />
                        Confirm password
                    </Button>
                </div>
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
