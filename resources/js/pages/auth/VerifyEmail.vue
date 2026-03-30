<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { MailCheck } from 'lucide-vue-next';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthSplitCard from '@/layouts/auth/AuthSplitCard.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthSplitCard
        title="Verify email"
        description="Please verify your email address by clicking on the link we just emailed to you."
    >
        <Head title="Email verification - Coop System">
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
                    <MailCheck class="h-5 w-5" />
                </div>
            </div>
            <h2 class="font-display text-2xl font-semibold">Verify email</h2>
            <p class="mt-1 text-sm text-slate-300/90">
                Please verify your email address by clicking on the link we just emailed to you.
            </p>
        </div>

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 rounded-lg border border-green-500/30 bg-green-500/10 p-3 text-center text-sm font-medium text-green-300"
        >
            A new verification link has been sent to the email address you
            provided during registration.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" class="h-12 bg-[#0e7ea0] text-white hover:bg-[#1294bc]">
                <Spinner v-if="processing" />
                Resend verification email
            </Button>

            <TextLink
                :href="logout()"
                method="post"
                as="button"
                class="mx-auto block text-sm font-medium text-sky-300 hover:text-sky-200"
            >
                Log out
            </TextLink>
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
