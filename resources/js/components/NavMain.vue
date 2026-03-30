<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import {
    BriefcaseBusiness,
    ChevronDown,
    CircleHelp,
    Coins,
    GraduationCap,
    LayoutDashboard,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

type SectionDefinition = {
    id: string;
    title: string;
    icon: unknown;
};

type SectionWithItems = SectionDefinition & {
    items: NavItem[];
};

const props = defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { state } = useSidebar();

const sectionOpenState = useStorage<Record<string, boolean>>(
    'sidebar_section_open_state',
    {
        platform: true,
        member: true,
        financial: true,
        activities: true,
        other: true,
    },
);

const sectionDefinitions: SectionDefinition[] = [
    {
        id: 'platform',
        title: 'Platform Management',
        icon: BriefcaseBusiness,
    },
    {
        id: 'member',
        title: 'Member Management',
        icon: Users,
    },
    {
        id: 'financial',
        title: 'Financial Management',
        icon: Coins,
    },
    {
        id: 'activities',
        title: 'Activities & Training',
        icon: GraduationCap,
    },
    {
        id: 'other',
        title: 'Other Modules',
        icon: CircleHelp,
    },
];

const isSidebarCollapsed = computed(() => state.value === 'collapsed');

const dashboardItem = computed<NavItem | undefined>(() => {
    return props.items.find((item) => toUrl(item.href) === '/dashboard');
});

const sectionedItems = computed<SectionWithItems[]>(() => {
    const buckets: Record<string, NavItem[]> = {
        platform: [],
        member: [],
        financial: [],
        activities: [],
        other: [],
    };

    props.items
        .filter((item) => toUrl(item.href) !== '/dashboard')
        .forEach((item) => {
            const url = toUrl(item.href);
            const title = item.title.toLowerCase();

            const isActivitySection =
                url.startsWith('/activities') ||
                url.startsWith('/activity-participants') ||
                url.startsWith('/trainings') ||
                url.startsWith('/training-participants') ||
                url.startsWith('/skill-inventories') ||
                url.startsWith('/member-portal/activities');

            const isFinancialSection =
                url.startsWith('/financial-records') ||
                url.startsWith('/external-supports') ||
                url.startsWith('/activity-funding-sources') ||
                title === 'loans' ||
                title === 'savings';

            const isMemberSection =
                url.startsWith('/members') ||
                url.startsWith('/officers') ||
                url.startsWith('/pds') ||
                url.startsWith('/member-portal/services') ||
                url === '/member-portal';

            const isPlatformSection =
                url.startsWith('/users') ||
                url.startsWith('/cooperatives') ||
                url.startsWith('/activity-logs') ||
                title === 'reports';

            if (isActivitySection) {
                buckets.activities.push(item);
                return;
            }

            if (isFinancialSection) {
                buckets.financial.push(item);
                return;
            }

            if (isMemberSection) {
                buckets.member.push(item);
                return;
            }

            if (isPlatformSection) {
                buckets.platform.push(item);
                return;
            }

            buckets.other.push(item);
        });

    return sectionDefinitions
        .map((section) => ({
            ...section,
            items: buckets[section.id] || [],
        }))
        .filter((section) => section.items.length > 0);
});

function getHrefKey(href: NavItem['href']) {
    return toUrl(href);
}

function isPlaceholderHref(href: NavItem['href']) {
    const url = toUrl(href);

    return !url || url === '#';
}

function isSectionExpanded(sectionId: string) {
    if (isSidebarCollapsed.value) return false;

    return sectionOpenState.value[sectionId] ?? true;
}

function toggleSection(sectionId: string) {
    if (isSidebarCollapsed.value) return;

    sectionOpenState.value = {
        ...sectionOpenState.value,
        [sectionId]: !isSectionExpanded(sectionId),
    };
}
</script>

<template>
    <SidebarGroup class="px-2 pb-1">
        <SidebarMenu>
            <SidebarMenuItem
                v-if="dashboardItem"
                :key="`dashboard:${getHrefKey(dashboardItem.href)}`"
            >
                <SidebarMenuButton
                    as-child
                    :tooltip="dashboardItem.title"
                    :is-active="isCurrentUrl(dashboardItem.href)"
                >
                    <Link
                        :href="dashboardItem.href"
                        prefetch
                        :preserve-state="false"
                        :preserve-scroll="false"
                    >
                        <component :is="dashboardItem.icon || LayoutDashboard" />
                        <span class="font-semibold">{{ dashboardItem.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup class="section-group px-2">
        <SidebarMenu class="section-list">
            <SidebarMenuItem
                v-for="section in sectionedItems"
                :key="`section:${section.id}`"
                class="section-item"
            >
                <SidebarMenuButton
                    type="button"
                    :tooltip="section.title"
                    class="section-trigger overflow-visible group-data-[collapsible=icon]:overflow-hidden font-semibold"
                    :aria-expanded="isSidebarCollapsed ? undefined : isSectionExpanded(section.id)"
                    :aria-controls="`sidebar-section-${section.id}`"
                    @click="toggleSection(section.id)"
                >
                    <component :is="section.icon" />
                    <span class="section-title font-semibold">{{ section.title }}</span>
                    <ChevronDown
                        v-if="!isSidebarCollapsed"
                        class="ml-auto shrink-0 transition-transform duration-200 group-data-[a11y-size=large]/sidebar-wrapper:size-5"
                        :class="isSectionExpanded(section.id) ? 'rotate-180' : ''"
                    />
                </SidebarMenuButton>

                <Transition name="section-collapse">
                    <div
                        v-if="isSectionExpanded(section.id)"
                        :id="`sidebar-section-${section.id}`"
                        class="overflow-hidden"
                    >
                        <SidebarMenu class="ml-2 mt-1 border-l border-sidebar-border/70 pl-2">
                            <SidebarMenuItem
                                v-for="item in section.items"
                                :key="`${section.id}:${item.title}:${getHrefKey(item.href)}`"
                            >
                                <SidebarMenuButton
                                    v-if="!isPlaceholderHref(item.href)"
                                    as-child
                                    :tooltip="item.title"
                                    :is-active="isCurrentUrl(item.href)"
                                >
                                    <Link
                                        :href="item.href"
                                        prefetch
                                        :preserve-state="false"
                                        :preserve-scroll="false"
                                    >
                                        <component :is="item.icon" />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>

                                <SidebarMenuButton
                                    v-else
                                    disabled
                                    :tooltip="item.title"
                                    class="cursor-not-allowed text-sidebar-foreground/50 hover:bg-transparent hover:text-sidebar-foreground/50"
                                >
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </div>
                </Transition>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup v-if="sectionedItems.length === 0" class="px-2 py-0">
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="`${item.title}:${getHrefKey(item.href)}`">
                <SidebarMenuButton
                    v-if="!isPlaceholderHref(item.href)"
                    as-child
                    :tooltip="item.title"
                    :is-active="isCurrentUrl(item.href)"
                >
                    <Link
                        :href="item.href"
                        prefetch
                        :preserve-state="false"
                        :preserve-scroll="false"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
                <SidebarMenuButton
                    v-else
                    disabled
                    :tooltip="item.title"
                    class="cursor-not-allowed text-sidebar-foreground/50 hover:bg-transparent hover:text-sidebar-foreground/50"
                >
                    <component :is="item.icon" />
                    <span>{{ item.title }}</span>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>

<style scoped>
.section-collapse-enter-active,
.section-collapse-leave-active {
    transition: max-height 0.24s ease, opacity 0.2s ease, transform 0.2s ease;
}

.section-collapse-enter-from,
.section-collapse-leave-to {
    max-height: 0;
    opacity: 0;
    transform: translateY(-4px);
}

.section-collapse-enter-to,
.section-collapse-leave-from {
    max-height: 640px;
    opacity: 1;
    transform: translateY(0);
}

.section-trigger {
    height: auto;
    min-height: var(--sidebar-menu-item-height);
    padding-top: 0.55rem;
    padding-bottom: 0.55rem;
    transition: padding 0.28s ease, min-height 0.28s ease;
}

.section-title {
    display: block;
    white-space: normal;
    line-height: 1.42;
    padding-bottom: 0.08em;
    transition: line-height 0.28s ease, padding-bottom 0.28s ease;
}

.section-group {
    padding-top: 1rem;
    transition: padding-top 0.28s ease;
}

.section-list {
    gap: 2rem;
    transition: gap 0.28s ease;
}

.section-item {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    transition: gap 0.28s ease;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-trigger {
    padding-top: 0.72rem;
    padding-bottom: 0.72rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-group {
    padding-top: 1.15rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-list {
    gap: 1.2rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-item {
    gap: 0.8rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-title {
    line-height: 1.5;
    padding-bottom: 0.1em;
}
</style>
