<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ChevronDown,
    CircleHelp,
    House,
    LayoutDashboard,
    Plus,
    Users,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubItem,
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

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { isMobile, state } = useSidebar();

const CREATE_DROPDOWN_STORAGE_KEY = 'sidebar-create-dropdown-open';
const CREATE_CHILD_PATH_PREFIXES = ['/activities', '/trainings', '/finance'];

const isCreateOpen = ref(false);
const isCreateFlyoutOpen = ref(false);

const isCreateChildItem = (item: NavItem) => {
    const url = toUrl(item.href);

    return CREATE_CHILD_PATH_PREFIXES.some((prefix) => url === prefix || url.startsWith(`${prefix}/`));
};

const createChildItems = computed<NavItem[]>(() => props.items.filter((item) => isCreateChildItem(item)));

const hasActiveCreateChild = computed(() => createChildItems.value.some((item) => isItemActive(item)));

const readStoredCreateState = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.localStorage.getItem(CREATE_DROPDOWN_STORAGE_KEY) === '1';
};

const persistCreateState = (isOpen: boolean) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(CREATE_DROPDOWN_STORAGE_KEY, isOpen ? '1' : '0');
};

if (typeof window !== 'undefined') {
    isCreateOpen.value = readStoredCreateState();
}

watch(
    hasActiveCreateChild,
    (active) => {
        if (active) {
            isCreateOpen.value = true;
        }
    },
    { immediate: true },
);

watch(isCreateOpen, (isOpen) => {
    persistCreateState(isOpen);
});

const toggleCreateMenu = () => {
    if (!createChildItems.value.length) {
        return;
    }

    isCreateOpen.value = !isCreateOpen.value;
};

const handleCreateButtonClick = () => {
    if (state.value === 'collapsed') {
        return;
    }

    toggleCreateMenu();
};

const openCreateFlyout = () => {
    if (state.value !== 'collapsed' || isMobile.value || !createChildItems.value.length) {
        return;
    }

    isCreateFlyoutOpen.value = true;
};

const closeCreateFlyout = () => {
    if (state.value !== 'collapsed' || isMobile.value) {
        return;
    }

    isCreateFlyoutOpen.value = false;
};

const shouldRenderCreateMenuAtIndex = (items: NavItem[], index: number) => {
    const item = items[index];

    if (!item || !isCreateChildItem(item)) {
        return false;
    }

    return !items.slice(0, index).some((current) => isCreateChildItem(current));
};

const shouldSkipCreateChildAtIndex = (items: NavItem[], index: number) => {
    const item = items[index];

    if (!item || !isCreateChildItem(item)) {
        return false;
    }

    return items.slice(0, index).some((current) => isCreateChildItem(current));
};

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

            const isManagementSection =
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
                url.startsWith('/users') ||
                url.startsWith('/roles-permissions') ||
                url.startsWith('/activity-logs') ||
                url.startsWith('/display') ||
                url.startsWith('/recycle-bin');

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

    const userManagementItems = buckets.system.filter((item) => toUrl(item.href).startsWith('/users'));

    if (userManagementItems.length > 0) {
        buckets.system = buckets.system
            .filter((item) => !toUrl(item.href).startsWith('/users'))
            .concat(userManagementItems);
    }

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
        <SidebarMenu class="space-y-1">
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

                <SidebarMenu class="ml-0 space-y-1 pl-0">
                    <SidebarMenuItem
                        v-for="(item, index) in section.items"
                        :key="`${section.id}:${item.title}:${getHrefKey(item.href)}`"
                    >
                        <template v-if="section.id === 'management' && shouldRenderCreateMenuAtIndex(section.items, index)">
                            <DropdownMenu v-model:open="isCreateFlyoutOpen">
                                <DropdownMenuTrigger as-child>
                                    <SidebarMenuButton
                                        size="lg"
                                        :tooltip="state === 'collapsed' ? undefined : 'Create'"
                                        :is-active="false"
                                        class="[&>svg]:size-5"
                                        @click="handleCreateButtonClick"
                                        @mouseenter="openCreateFlyout"
                                        @mouseleave="closeCreateFlyout"
                                    >
                                        <Plus />
                                        <span class="group-data-[collapsible=icon]:hidden">Create</span>
                                        <ChevronDown
                                            class="ml-auto transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                                            :class="isCreateOpen ? 'rotate-180' : ''"
                                        />
                                    </SidebarMenuButton>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent
                                    v-if="state === 'collapsed' && !isMobile && createChildItems.length"
                                    side="right"
                                    align="start"
                                    :side-offset="10"
                                    class="w-72"
                                    @mouseenter="openCreateFlyout"
                                    @mouseleave="closeCreateFlyout"
                                >
                                    <DropdownMenuLabel class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-foreground">
                                        Create
                                    </DropdownMenuLabel>

                                    <DropdownMenuItem
                                        v-for="createItem in createChildItems"
                                        :key="`create-flyout:${getHrefKey(createItem.href)}`"
                                        class="text-base [&_svg]:size-5"
                                        as-child
                                    >
                                        <Link
                                            :href="createItem.href"
                                            prefetch
                                            :preserve-state="false"
                                            :preserve-scroll="false"
                                            class="flex items-center gap-2"
                                        >
                                            <component :is="createItem.icon" />
                                            <span>{{ createItem.title }}</span>
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <transition name="create-dropdown">
                                <SidebarMenuSub v-if="isCreateOpen && state !== 'collapsed' && createChildItems.length" class="mt-1 gap-3">
                                    <SidebarMenuSubItem
                                        v-for="createItem in createChildItems"
                                        :key="`create-sub:${getHrefKey(createItem.href)}`"
                                    >
                                        <SidebarMenuButton as-child :is-active="isItemActive(createItem)" class="h-8 text-base [&>svg]:size-5">
                                            <Link
                                                :href="createItem.href"
                                                prefetch
                                                :preserve-state="false"
                                                :preserve-scroll="false"
                                            >
                                                <component :is="createItem.icon" />
                                                <span>{{ createItem.title }}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </transition>
                        </template>

                        <template v-else-if="section.id === 'management' && shouldSkipCreateChildAtIndex(section.items, index)" />

                        <template v-else>
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
                        </template>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>

    <SidebarGroup v-if="sectionedItems.length === 0" class="px-2 py-0">
        <SidebarMenu class="space-y-1">
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

.create-dropdown-enter-active,
.create-dropdown-leave-active {
    overflow: hidden;
    transition: opacity 0.22s ease, transform 0.22s ease, max-height 0.22s ease;
}

.create-dropdown-enter-from,
.create-dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
    max-height: 0;
}

.create-dropdown-enter-to,
.create-dropdown-leave-from {
    opacity: 1;
    transform: translateY(0);
    max-height: 320px;
}
</style>
