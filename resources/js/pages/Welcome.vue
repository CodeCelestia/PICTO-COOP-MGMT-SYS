<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthTechBackground from '@/components/auth/AuthTechBackground.vue';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome" />

    <div class="relative min-h-svh overflow-hidden bg-slate-950 text-slate-100">
        <AuthTechBackground />

        <div
            class="absolute inset-0 bg-[radial-gradient(80%_70%_at_10%_20%,rgba(59,130,246,0.24),transparent_60%),radial-gradient(60%_50%_at_90%_80%,rgba(14,165,233,0.18),transparent_70%)]"
        />
        <div
            class="absolute inset-0 bg-gradient-to-br from-slate-950/95 via-blue-950/75 to-slate-900/95"
        />

        <div class="relative z-10 mx-auto flex min-h-svh w-full max-w-6xl flex-col px-6 py-8 md:px-10">
            <header class="mb-12 flex items-center justify-between">
                <div class="inline-flex items-center gap-2 rounded-md border border-blue-300/25 bg-slate-900/50 px-3 py-1.5 text-sm font-medium text-blue-100">
                    COOP Management System
                </div>

                <nav class="flex items-center gap-3 text-sm">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-md border border-blue-300/40 bg-blue-600/90 px-4 py-2 font-medium text-white transition hover:bg-blue-500"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-md border border-blue-300/30 bg-slate-900/50 px-4 py-2 font-medium text-blue-100 transition hover:border-blue-200/60 hover:bg-slate-900/70"
                        >
                            Log in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="register()"
                            class="rounded-md border border-blue-300/40 bg-blue-600/90 px-4 py-2 font-medium text-white transition hover:bg-blue-500"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="grid flex-1 items-center gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="space-y-6">
                    <p class="inline-flex items-center rounded-full border border-sky-300/30 bg-blue-500/10 px-3 py-1 text-xs font-medium tracking-wide text-blue-200">
                        Multi-tenant RBAC Platform
                    </p>

                    <h1 class="max-w-2xl text-3xl font-semibold leading-tight text-white md:text-5xl">
                        Cooperative Management Information System
                    </h1>

                    <p class="max-w-xl text-sm leading-relaxed text-slate-300 md:text-base">
                        A secure and dynamic platform for role-based cooperative management, starting with Super Admin controls and scalable module architecture.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="$page.props.auth.user ? dashboard() : login()"
                            class="rounded-md border border-blue-300/40 bg-blue-600/90 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-blue-500"
                        >
                            {{ $page.props.auth.user ? 'Go to Dashboard' : 'Start with Login' }}
                        </Link>
                        <Link
                            v-if="!$page.props.auth.user && canRegister"
                            :href="register()"
                            class="rounded-md border border-blue-300/30 bg-slate-900/50 px-5 py-2.5 text-sm font-medium text-blue-100 transition hover:border-blue-200/60 hover:bg-slate-900/70"
                        >
                            Create Account
                        </Link>
                    </div>
                </section>

                <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                    <article class="rounded-xl border border-blue-300/20 bg-slate-900/55 p-4 backdrop-blur">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-200">
                            Access Model
                        </p>
                        <p class="mt-2 text-sm text-slate-200">
                            Super Admin, COOP Admin, Chairperson, GM, Officers, Committee, and Member roles.
                        </p>
                    </article>

                    <article class="rounded-xl border border-blue-300/20 bg-slate-900/55 p-4 backdrop-blur">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-200">
                            Core Focus
                        </p>
                        <p class="mt-2 text-sm text-slate-200">
                            Secure authentication, dynamic permissions, activity logging, and clean module growth.
                        </p>
                    </article>

                    <article class="rounded-xl border border-blue-300/20 bg-slate-900/55 p-4 backdrop-blur">
                        <p class="text-xs font-medium uppercase tracking-wide text-blue-200">
                            Data Scope
                        </p>
                        <p class="mt-2 text-sm text-slate-200">
                            Cooperative profiles, members, officers, projects, financials, and trainings.
                        </p>
                    </article>
                </section>
            </main>
        </div>
    </div>
</template>
