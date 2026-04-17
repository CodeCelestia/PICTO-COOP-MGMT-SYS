<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CircleHelp,
    House,
    LayoutDashboard,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
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

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const sectionDefinitions: SectionDefinition[] = [
    {
        id: 'management',
        title: 'Management',
        icon: Users,
    },
    {
        id: 'system',
        title: 'System',
        icon: CircleHelp,
    },
];

const topNavItems = computed<NavItem[]>(() => {
    const priorityOrder = ['/homepage', '/dashboard'];

    return priorityOrder
        .map((path) => props.items.find((item) => toUrl(item.href) === path))
        .filter((item): item is NavItem => Boolean(item));
});

const sectionedItems = computed<SectionWithItems[]>(() => {
    const buckets: Record<string, NavItem[]> = {
        management: [],
        system: [],
    };

    props.items
        .filter((item) => {
            const path = toUrl(item.href);
            return path !== '/dashboard' && path !== '/homepage';
        })
        .forEach((item) => {
            const url = toUrl(item.href);
            const title = item.title.toLowerCase();

            const isManagementSection =
                url.startsWith('/users') ||
                url.startsWith('/cooperatives') ||
                url.startsWith('/members') ||
                url.startsWith('/officers') ||
                url.startsWith('/pds') ||
                url.startsWith('/activities') ||
                url.startsWith('/trainings') ||
                url.startsWith('/member-portal/activities') ||
                url.startsWith('/finance') ||
                url.startsWith('/member-portal/services') ||
                url === '/member-portal';

            const isSystemSection =
                url.startsWith('/roles-permissions') ||
                url.startsWith('/activity-logs') ||
                url.startsWith('/display');

            if (isManagementSection) {
                buckets.management.push(item);
                return;
            }

            if (isSystemSection) {
                buckets.system.push(item);
                return;
            }

            buckets.management.push(item);
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

function isItemActive(item: NavItem) {
    const url = toUrl(item.href);

    if (url === '/finance') {
        return isCurrentOrParentUrl(item.href);
    }

    return isCurrentUrl(item.href);
}
</script>

<template>
    <SidebarGroup class="px-2 pb-1">
        <SidebarMenu>
            <SidebarMenuItem
                v-for="item in topNavItems"
                :key="`top-nav:${getHrefKey(item.href)}`"
            >
                <SidebarMenuButton
                    as-child
                    :tooltip="item.title"
                    :is-active="isItemActive(item)"
                >
                    <Link
                        :href="item.href"
                        prefetch
                        :preserve-state="false"
                        :preserve-scroll="false"
                    >
                        <component :is="item.icon || (toUrl(item.href) === '/homepage' ? House : LayoutDashboard)" />
                        <span class="font-semibold">{{ item.title }}</span>
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
                <SidebarGroupLabel class="section-label">
                    <component :is="section.icon" />
                    <span class="section-title">{{ section.title }}</span>
                </SidebarGroupLabel>

                <SidebarMenu class="ml-0 pl-0">
                    <SidebarMenuItem
                        v-for="item in section.items"
                        :key="`${section.id}:${item.title}:${getHrefKey(item.href)}`"
                    >
                        <SidebarMenuButton
                            v-if="!isPlaceholderHref(item.href)"
                            as-child
                            :tooltip="item.title"
                            :is-active="isItemActive(item)"
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
                    :is-active="isItemActive(item)"
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
.section-title {
    display: block;
    white-space: normal;
    line-height: 1.3;
    letter-spacing: 0.02em;
}

.section-label {
    gap: 0.55rem;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.16em;
    color: rgb(100 116 139 / 0.8);
}

.section-group {
    padding-top: 1rem;
}

.section-list {
    gap: 1.5rem;
}

.section-item {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-label {
    font-size: 0.78rem;
}

:deep(.group\/sidebar-wrapper[data-a11y-size='large']) .section-item {
    gap: 0.9rem;
}
</style>
