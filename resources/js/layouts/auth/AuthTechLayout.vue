<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AuthTechBackground from '@/components/auth/AuthTechBackground.vue';
import { home } from '@/routes';

const page = usePage();
const appName = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="relative min-h-svh overflow-hidden bg-slate-950 text-slate-100">
        <AuthTechBackground />

        <div
            class="absolute inset-0 bg-[radial-gradient(80%_70%_at_10%_20%,rgba(59,130,246,0.24),transparent_60%),radial-gradient(60%_50%_at_90%_80%,rgba(14,165,233,0.18),transparent_70%)]"
        />
        <div
            class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-blue-950/75 to-slate-900/95"
        />

        <div class="relative z-10 flex min-h-svh items-center justify-center p-6 md:p-10">
            <div
                class="grid w-full max-w-6xl gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center"
            >
                <div class="hidden max-w-xl space-y-6 lg:block">
                    <Link
                        :href="home()"
                        class="inline-flex items-center gap-3 rounded-lg border border-blue-300/25 bg-slate-900/40 px-4 py-2 text-sm font-medium text-blue-100 transition hover:border-blue-300/45 hover:bg-slate-900/60"
                    >
                        <AppLogoIcon class="size-6 fill-current text-blue-300" />
                        {{ appName }}
                    </Link>

                    <div class="space-y-3">
                        <p
                            class="inline-flex items-center rounded-full border border-sky-300/30 bg-blue-500/10 px-3 py-1 text-xs font-medium tracking-wide text-blue-200"
                        >
                            Cooperative Management Information System
                        </p>
                        <h2 class="text-3xl font-semibold leading-tight text-white xl:text-4xl">
                            Secure access for modern cooperative operations.
                        </h2>
                        <p class="max-w-lg text-sm leading-relaxed text-slate-300">
                            Role-based authentication with dynamic access control, built for scalable multi-cooperative administration.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs text-slate-300">
                        <div class="rounded-lg border border-blue-300/20 bg-slate-900/35 p-3">
                            Dynamic role filtering
                        </div>
                        <div class="rounded-lg border border-blue-300/20 bg-slate-900/35 p-3">
                            Secure session control
                        </div>
                        <div class="rounded-lg border border-blue-300/20 bg-slate-900/35 p-3">
                            Multi-tenant ready
                        </div>
                        <div class="rounded-lg border border-blue-300/20 bg-slate-900/35 p-3">
                            Super admin first flow
                        </div>
                    </div>
                </div>

                <div
                    class="mx-auto w-full max-w-md rounded-2xl border border-blue-300/20 bg-slate-900/80 p-6 shadow-2xl shadow-blue-950/60 backdrop-blur md:p-8"
                >
                    <div class="mb-6 space-y-2">
                        <h1 class="text-xl font-semibold text-white md:text-2xl">
                            {{ title }}
                        </h1>
                        <p class="text-sm text-slate-300" v-if="description">
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
