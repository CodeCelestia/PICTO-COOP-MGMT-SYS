<script setup lang="ts">
import { CircuitBoard, LockKeyhole, Workflow } from 'lucide-vue-next';
import LandingPage from '@/components/landing/LandingPage.vue';

defineProps<{
    title?: string;
    titleMeta?: string;
    titleDate?: string;
    titleTime?: string;
    description?: string;
    showProjectLogo?: boolean;
}>();
</script>

<template>
    <div class="landing-auth relative flex min-h-screen items-center justify-center overflow-hidden bg-[#020910] p-4 md:p-6 lg:p-8">
        <LandingPage />
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(15,52,71,0.35),transparent_60%)]" />

        <div class="relative z-10 w-full max-w-6xl">
            <div class="grid gap-4 lg:grid-cols-12 lg:gap-6">
                <div class="hidden rounded-2xl border border-sky-300/20 bg-slate-950/55 p-8 text-slate-100 shadow-[0_0_0_1px_rgba(148,210,236,0.08),0_24px_70px_-30px_rgba(13,90,123,0.65)] backdrop-blur-sm lg:col-span-5 lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <template v-if="showProjectLogo">
                            <div class="mb-6 flex items-center gap-4 xl:gap-5">
                                <div class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-sky-300/30 bg-slate-950/75">
                                    <img
                                        src="/SDN_Logo.png"
                                        alt="SDN Logo"
                                        class="h-20 w-20 object-contain"
                                    />
                                </div>
                                <h1 class="font-display text-2xl font-semibold leading-tight text-white xl:text-3xl">
                                    {{ title || 'Coop System Access Portal' }}
                                </h1>
                            </div>

                            <div class="rounded-2xl border border-sky-300/20 bg-slate-950/45 p-5">
                                <p v-if="titleDate" class="text-base font-medium text-slate-200/95">
                                    {{ titleDate }}
                                </p>
                                <p
                                    v-if="titleTime"
                                    class="font-display mt-2 text-4xl font-semibold leading-none text-sky-100 xl:text-5xl"
                                    aria-live="polite"
                                >
                                    {{ titleTime }}
                                </p>
                                <p
                                    v-else-if="titleMeta"
                                    class="mt-2 text-sm font-medium text-slate-200/95"
                                    aria-live="polite"
                                >
                                    {{ titleMeta }}
                                </p>
                            </div>

                            <p class="mt-5 text-sm leading-relaxed text-slate-300/90">
                                {{
                                    description ||
                                    'Securely manage cooperative records and workflows through one connected digital platform.'
                                }}
                            </p>
                        </template>

                        <template v-else>
                            <div class="mb-6 inline-flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl border border-sky-300/30 bg-slate-950/75 text-sky-200">
                                <CircuitBoard class="h-6 w-6" />
                            </div>
                            <h1 class="font-display text-3xl font-semibold leading-tight text-white">
                                {{ title || 'Coop System Access Portal' }}
                            </h1>
                            <p v-if="titleMeta" class="mt-3 text-sm font-medium text-slate-200/95" aria-live="polite">
                                {{ titleMeta }}
                            </p>
                            <p class="mt-3 text-sm leading-relaxed text-slate-300/90">
                                {{
                                    description ||
                                    'Securely manage cooperative records and workflows through one connected digital platform.'
                                }}
                            </p>
                        </template>
                    </div>

                    <div class="space-y-3 text-sm text-slate-300/90">
                        <div class="flex items-center gap-2">
                            <LockKeyhole class="h-4 w-4 text-sky-200" />
                            <p>Protected, role-based system access</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Workflow class="h-4 w-4 text-sky-200" />
                            <p>Seamless operations across cooperative teams</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-sky-300/20 bg-slate-950/55 shadow-[0_0_0_1px_rgba(148,210,236,0.08),0_24px_70px_-30px_rgba(13,90,123,0.65)] backdrop-blur-sm lg:col-span-7">
                    <div class="mx-auto w-full max-w-md px-6 py-8 md:px-10 md:py-10">
                        <slot />
                    </div>

                    <div class="border-t border-sky-300/15 px-6 py-4 text-center md:px-10">
                        <p class="text-xs text-slate-400">
                            © 2026 Provincial ICT Office. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.landing-auth {
    font-family:
        'IBM Plex Sans',
        'Segoe UI',
        sans-serif;
}

.font-display {
    font-family:
        'Sora',
        'IBM Plex Sans',
        sans-serif;
    letter-spacing: -0.025em;
}
</style>
