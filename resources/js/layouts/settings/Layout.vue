<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, Palette, ShieldCheck, UserRound } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
        icon: UserRound,
    },
    {
        title: 'Password',
        href: editPassword(),
        icon: KeyRound,
    },
    {
        title: 'Two-factor auth',
        href: show(),
        icon: ShieldCheck,
    },
    {
        title: 'Appearance',
        href: editAppearance(),
        icon: Palette,
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-4 py-6 md:px-6 lg:py-8">
        <div class="mb-6 rounded-2xl border border-border/70 bg-card/70 p-5 shadow-[0_16px_38px_-24px_rgba(10,30,45,0.45)] backdrop-blur-sm md:p-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
            <aside class="rounded-2xl border border-border/70 bg-card/70 p-2 shadow-[0_14px_34px_-26px_rgba(10,30,45,0.45)] backdrop-blur-sm lg:sticky lg:top-20 lg:h-fit">
                <nav class="flex flex-col gap-1" aria-label="Settings">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-primary text-primary-foreground shadow-[0_8px_20px_-14px_hsl(var(--primary))]'
                                : 'text-muted-foreground hover:bg-muted/70 hover:text-foreground'
                        "
                    >
                        <span
                            class="flex h-8 w-8 items-center justify-center rounded-lg border transition-colors"
                            :class="
                                isCurrentOrParentUrl(item.href)
                                    ? 'border-primary-foreground/35 bg-primary-foreground/10'
                                    : 'border-border/70 bg-background/80 group-hover:border-border'
                            "
                        >
                            <component v-if="item.icon" :is="item.icon" class="h-4 w-4" />
                        </span>
                        <span>{{ item.title }}</span>
                    </Link>
                </nav>
            </aside>

            <Separator class="lg:hidden" />

            <div class="rounded-2xl border border-border/70 bg-card/75 p-5 shadow-[0_20px_42px_-28px_rgba(10,30,45,0.5)] backdrop-blur-sm md:p-7">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
