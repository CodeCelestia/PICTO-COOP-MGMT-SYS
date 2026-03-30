<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BarChart3,
    Building2,
    CircuitBoard,
    Handshake,
    LockKeyhole,
    Quote,
    Workflow,
    Zap,
} from 'lucide-vue-next';
import {
    onBeforeUnmount,
    onBeforeUpdate,
    onMounted,
    ref
    
    
} from 'vue';
import type {Component, ComponentPublicInstance} from 'vue';
import LandingPage from '@/components/landing/LandingPage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

interface FeatureItem {
    title: string;
    description: string;
    metric: string;
    icon: Component;
}

interface BenefitItem {
    title: string;
    description: string;
}

interface TestimonialItem {
    quote: string;
    name: string;
    role: string;
}

const featureItems: FeatureItem[] = [
    {
        title: 'Unified Cooperative Profiles',
        description:
            'Manage registrations, officers, committees, and members in one connected data system with role-based controls.',
        metric: '100% profile traceability',
        icon: Building2,
    },
    {
        title: 'Operational Intelligence',
        description:
            'Monitor programs, financial records, and project outcomes through live dashboards designed for local governance decisions.',
        metric: 'Real-time analytics stream',
        icon: BarChart3,
    },
    {
        title: 'Secure Collaboration Layers',
        description:
            'Separate permissions for provincial admins, coop admins, officers, and members to keep workflows safe and accountable.',
        metric: 'Multi-role access architecture',
        icon: LockKeyhole,
    },
    {
        title: 'Process Automation Engine',
        description:
            'Reduce repetitive work with integrated forms, historical logging, and structured data flows tailored for cooperative reporting.',
        metric: 'Faster operational cycles',
        icon: Workflow,
    },
];

const benefitItems: BenefitItem[] = [
    {
        title: 'Stronger Institutional Trust',
        description:
            'A transparent source of truth improves confidence between officers, members, and government stakeholders.',
    },
    {
        title: 'Faster Program Delivery',
        description:
            'Digital workflows shorten processing times and help teams focus on high-impact cooperative development.',
    },
    {
        title: 'Reliable Compliance Readiness',
        description:
            'Audit trails and standardized data structures make reporting simpler, cleaner, and easier to verify.',
    },
];

const testimonialItems: TestimonialItem[] = [
    {
        quote: 'Coop System gave our provincial team one consistent operational view. We now resolve data requests in minutes instead of days.',
        name: 'Maria Dela Cruz',
        role: 'Provincial Cooperative Officer',
    },
    {
        quote: 'The role-based setup is exactly what we needed. Officers collaborate faster while sensitive records stay properly controlled.',
        name: 'Engr. Paolo Reyes',
        role: 'Cooperative Administrator',
    },
    {
        quote: 'Our members can finally track services and participation clearly. It strengthened transparency across the entire organization.',
        name: 'Lorna Villanueva',
        role: 'Committee Member',
    },
];

const revealTargets = ref<HTMLElement[]>([]);
let revealObserver: IntersectionObserver | null = null;

const registerRevealTarget = (
    target: Element | ComponentPublicInstance | null,
) => {
    const element =
        target instanceof HTMLElement
            ? target
            : target && '$el' in target && target.$el instanceof HTMLElement
              ? target.$el
              : null;

    if (element) {
        revealTargets.value.push(element);
    }
};

onBeforeUpdate(() => {
    revealTargets.value = [];
});

onMounted(() => {
    revealObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealObserver?.unobserve(entry.target);
                }
            });
        },
        {
            threshold: 0.2,
            rootMargin: '0px 0px -8% 0px',
        },
    );

    revealTargets.value.forEach((element, index) => {
        element.style.setProperty('--reveal-delay', `${Math.min(index * 65, 280)}ms`);
        revealObserver?.observe(element);
    });
});

onBeforeUnmount(() => {
    revealObserver?.disconnect();
    revealObserver = null;
});
</script>

<template>
    <Head title="Coop System Landing">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link
            href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div class="landing-root relative min-h-screen overflow-x-clip scroll-smooth bg-[#020910] text-slate-100">
        <LandingPage />

        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(15,52,71,0.35),transparent_60%)]" />

        <header class="sticky top-0 z-40 border-b border-sky-300/15 bg-slate-950/45 backdrop-blur-sm">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <a href="#top" class="group flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-sky-300/30 bg-slate-950/80 text-sky-200 transition-colors group-hover:border-sky-200/70 group-hover:text-sky-100">
                        <CircuitBoard class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="font-display text-base font-semibold tracking-wide text-sky-100 sm:text-lg">Coop System</p>
                        <p class="text-sm text-slate-300/85">Cooperative Management Platform</p>
                    </div>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <Button
                        v-if="$page.props.auth.user"
                        as-child
                        class="bg-[#0e7ea0] font-semibold text-white hover:bg-[#1294bc]"
                    >
                        <Link :href="dashboard()">Go to Dashboard</Link>
                    </Button>

                    <template v-else>
                        <Button
                            as-child
                            variant="outline"
                            class="border-sky-200/40 bg-slate-950/40 font-semibold text-sky-100 hover:bg-sky-500/15"
                        >
                            <Link :href="login()">Login</Link>
                        </Button>

                        <Button
                            as-child
                            class="bg-[#0e7ea0] font-semibold text-white shadow-[0_0_0_1px_rgba(126,221,255,0.28)] hover:bg-[#1294bc]"
                        >
                            <Link :href="canRegister ? register() : login()">Sign Up</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </header>

        <main class="relative z-10">
            <section id="top" class="mx-auto grid w-full max-w-7xl gap-8 px-4 pb-14 pt-14 sm:px-6 lg:grid-cols-12 lg:px-8 lg:pb-20 lg:pt-20">
                <div
                    :ref="registerRevealTarget"
                    class="reveal-item space-y-7 rounded-2xl border border-sky-300/15 bg-slate-950/45 p-6 shadow-[0_0_0_1px_rgba(148,210,236,0.06),0_24px_70px_-30px_rgba(13,90,123,0.65)] backdrop-blur-sm lg:col-span-7 lg:p-8"
                >
                    <Badge variant="outline" class="border-sky-300/40 bg-sky-500/10 px-3 py-1 text-sky-100">
                        Built for connected cooperative operations
                    </Badge>

                    <div class="space-y-4">
                        <h1 class="font-display max-w-2xl text-3xl font-semibold leading-tight text-white sm:text-4xl lg:text-5xl">
                            Industrial-grade digital operations for modern cooperatives.
                        </h1>
                        <p class="max-w-xl text-base leading-relaxed text-slate-200/90 sm:text-lg">
                            From membership to activities, financial records, and governance workflows, Coop System centralizes your operations with secure, collaborative intelligence.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            as-child
                            size="lg"
                            class="h-11 bg-[#f48235] text-slate-950 shadow-[0_10px_30px_-18px_rgba(244,130,53,0.9)] hover:bg-[#ffa45f]"
                        >
                            <Link :href="$page.props.auth.user ? dashboard() : login()">
                                Launch Platform
                                <ArrowRight class="ml-1 h-4 w-4" />
                            </Link>
                        </Button>

                        <Button
                            as-child
                            size="lg"
                            variant="outline"
                            class="h-11 border-sky-300/35 bg-slate-950/40 text-sky-100 hover:bg-sky-500/15"
                        >
                            <a href="#features">Explore capabilities</a>
                        </Button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="font-display text-2xl font-semibold text-sky-100">24/7</p>
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-300/85">Operational visibility</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="font-display text-2xl font-semibold text-sky-100">Role-safe</p>
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-300/85">Team collaboration</p>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                            <p class="font-display text-2xl font-semibold text-sky-100">Audit-ready</p>
                            <p class="text-xs uppercase tracking-[0.15em] text-slate-300/85">Data history logs</p>
                        </div>
                    </div>
                </div>

                <Card
                    :ref="registerRevealTarget"
                    class="reveal-item border-sky-300/20 bg-linear-to-br from-slate-950/80 via-slate-900/70 to-[#08364a]/75 backdrop-blur-sm lg:col-span-5"
                >
                    <CardHeader class="space-y-3">
                        <Badge variant="secondary" class="w-fit bg-sky-200/15 text-sky-100">Platform Snapshot</Badge>
                        <CardTitle class="font-display text-2xl text-white">Built for growth, governance, and trust</CardTitle>
                        <CardDescription class="text-slate-200/90">
                            Cooperative teams use one integrated platform to manage records, monitor outcomes, and deliver member services with confidence.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="rounded-xl border border-sky-200/20 bg-[#031722]/65 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sky-100">
                                <Handshake class="h-4 w-4" />
                                <p class="font-medium">Collaboration by design</p>
                            </div>
                            <p class="text-sm text-slate-200/85">
                                Shared workflows align provincial administrators, officers, and members in one reliable operating model.
                            </p>
                        </div>
                        <div class="rounded-xl border border-sky-200/20 bg-[#031722]/65 p-4">
                            <div class="mb-2 flex items-center gap-2 text-sky-100">
                                <Zap class="h-4 w-4" />
                                <p class="font-medium">Operational acceleration</p>
                            </div>
                            <p class="text-sm text-slate-200/85">
                                Automated records and structured forms reduce manual follow-up so your team can focus on impactful programs.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section id="features" class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-20">
                <div :ref="registerRevealTarget" class="reveal-item mb-8 max-w-3xl space-y-3">
                    <Badge variant="outline" class="border-[#f48235]/50 bg-[#f48235]/10 text-[#ffd4b2]">Core Features</Badge>
                    <h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">A platform engineered for cooperative ecosystems</h2>
                    <p class="text-slate-300/90">
                        Every module is designed to connect teams, preserve accountability, and keep mission-critical information available when decisions need to be made.
                    </p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <Card
                        v-for="feature in featureItems"
                        :key="feature.title"
                        :ref="registerRevealTarget"
                        class="reveal-item border-sky-300/15 bg-slate-950/60 backdrop-blur-sm"
                    >
                        <CardHeader class="space-y-3">
                            <div class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-sky-300/30 bg-sky-500/10 text-sky-100">
                                <component :is="feature.icon" class="h-5 w-5" />
                            </div>
                            <CardTitle class="font-display text-xl text-white">{{ feature.title }}</CardTitle>
                            <CardDescription class="text-slate-300/90">{{ feature.description }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm font-medium text-[#ffd4b2]">{{ feature.metric }}</p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section id="benefits" class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-20">
                <div :ref="registerRevealTarget" class="reveal-item mb-8 max-w-3xl space-y-3">
                    <Badge variant="outline" class="border-sky-300/40 bg-sky-500/10 text-sky-100">Why Coop System</Badge>
                    <h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">Built to support sustainable growth and transparent governance</h2>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <Card
                        v-for="benefit in benefitItems"
                        :key="benefit.title"
                        :ref="registerRevealTarget"
                        class="reveal-item border-slate-200/10 bg-slate-950/55"
                    >
                        <CardHeader class="space-y-2">
                            <CardTitle class="font-display text-xl text-white">{{ benefit.title }}</CardTitle>
                            <CardDescription class="text-slate-300/90">{{ benefit.description }}</CardDescription>
                        </CardHeader>
                    </Card>
                </div>
            </section>

            <section id="testimonials" class="mx-auto w-full max-w-7xl px-4 pb-16 sm:px-6 lg:px-8 lg:pb-20">
                <div :ref="registerRevealTarget" class="reveal-item mb-8 max-w-3xl space-y-3">
                    <Badge variant="outline" class="border-[#f48235]/50 bg-[#f48235]/10 text-[#ffd4b2]">Testimonials</Badge>
                    <h2 class="font-display text-2xl font-semibold text-white sm:text-3xl">Trusted by cooperative leaders and operations teams</h2>
                </div>

                <div class="grid gap-5 lg:grid-cols-3">
                    <Card
                        v-for="entry in testimonialItems"
                        :key="entry.name"
                        :ref="registerRevealTarget"
                        class="reveal-item border-slate-200/10 bg-slate-950/60"
                    >
                        <CardHeader>
                            <Quote class="h-5 w-5 text-sky-100" />
                            <CardDescription class="mt-3 text-base leading-relaxed text-slate-200/95">{{ entry.quote }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <p class="font-medium text-white">{{ entry.name }}</p>
                            <p class="text-sm text-slate-300/90">{{ entry.role }}</p>
                        </CardContent>
                    </Card>
                </div>
            </section>

            <section class="mx-auto w-full max-w-7xl px-4 pb-20 sm:px-6 lg:px-8 lg:pb-24">
                <Card
                    :ref="registerRevealTarget"
                    class="reveal-item border-sky-300/25 bg-linear-to-br from-[#062537]/85 via-[#041a27]/90 to-[#111727]/90"
                >
                    <CardHeader class="space-y-3">
                        <Badge variant="secondary" class="w-fit bg-sky-200/15 text-sky-100">Get Access</Badge>
                        <CardTitle class="font-display text-2xl text-white sm:text-3xl">Start your cooperative digital transformation</CardTitle>
                        <CardDescription class="text-slate-200/90">
                            Submit your details and our team will guide your onboarding to Coop System.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="org-name" class="text-slate-100">Organization name</Label>
                                <Input
                                    id="org-name"
                                    placeholder="Enter cooperative or agency name"
                                    class="border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="contact-email" class="text-slate-100">Work email</Label>
                                <Input
                                    id="contact-email"
                                    type="email"
                                    placeholder="name@coop.gov.ph"
                                    class="border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400"
                                />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="goals" class="text-slate-100">Primary goals</Label>
                            <Textarea
                                id="goals"
                                placeholder="Share your current challenges, priority modules, and target rollout timeline."
                                class="min-h-28 border-sky-200/20 bg-slate-900/50 text-slate-100 placeholder:text-slate-400"
                            />
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <Button
                                as-child
                                size="lg"
                                class="bg-[#f48235] text-slate-950 hover:bg-[#ffa45f]"
                            >
                                <Link :href="canRegister ? register() : login()">Request Sign Up Access</Link>
                            </Button>
                            <Button
                                as-child
                                variant="outline"
                                size="lg"
                                class="border-sky-200/30 bg-transparent text-sky-100 hover:bg-sky-500/15"
                            >
                                <Link :href="login()">Already have an account</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </main>

        <footer class="relative z-10 border-t border-sky-300/15 bg-[#020910]/90">
            <div class="mx-auto flex w-full max-w-7xl flex-col items-start justify-between gap-3 px-4 py-6 text-sm text-slate-300 sm:px-6 md:flex-row lg:px-8">
                <p>&copy; 2026 Provincial ICT Office. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.landing-root {
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

.reveal-item {
    opacity: 0;
    transform: translateY(26px) scale(0.985);
    transition:
        transform 720ms cubic-bezier(0.2, 1, 0.3, 1),
        opacity 720ms cubic-bezier(0.2, 1, 0.3, 1);
    transition-delay: var(--reveal-delay, 0ms);
}

.reveal-item.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}

@media (prefers-reduced-motion: reduce) {
    .reveal-item {
        opacity: 1;
        transform: none;
        transition: none;
    }
}
</style>
