<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    CheckSquare,
    Loader2,
    Save,
    Search,
    Square,
    UserCheck,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Activity {
    id: number;
    title: string;
}

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    gender?: string | null;
    date_joined?: string | null;
}

interface Props {
    activity: Activity;
    cooperative: Cooperative;
    allMembers: Member[];
    selectedMemberIds: number[];
    isCooperativeContext?: boolean;
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Activities & Projects', href: '/activities' },
    { title: props.activity.title, href: `/activities/${props.activity.id}/cooperatives-participating` },
    { title: 'Participants', href: `/cooperatives/${props.cooperative.id}/activities/${props.activity.id}/participants` },
]);

const routeCooperativeId = computed<number | null>(() => {
    const path = (page.url || '').split('?')[0];
    const segments = path.split('/').filter(Boolean);

    const nestedIndex = segments.indexOf('cooperatives');
    if (nestedIndex !== -1 && segments[nestedIndex + 1]) {
        const parsed = Number(segments[nestedIndex + 1]);
        return Number.isNaN(parsed) ? null : parsed;
    }

    return null;
});

const cooperativeId = computed(() => routeCooperativeId.value ?? props.cooperative?.id ?? null);
const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext ?? cooperativeId.value));

const search = ref('');
const genderFilter = ref('all');
const selectedIds = ref<number[]>([...(props.selectedMemberIds || [])]);
const isSaving = ref(false);

const availablePanelRef = ref<HTMLElement | null>(null);
const selectedPanelRef = ref<HTMLElement | null>(null);
const showAvailableFade = ref(false);
const showSelectedFade = ref(false);

const sortByLastName = (a: Member, b: Member) => {
    const lastCompare = (a.last_name || '').localeCompare(b.last_name || '', 'en', { sensitivity: 'base' });
    if (lastCompare !== 0) {
        return lastCompare;
    }

    return (a.first_name || '').localeCompare(b.first_name || '', 'en', { sensitivity: 'base' });
};

const sortedMembers = computed(() => [...(props.allMembers || [])].sort(sortByLastName));
const totalMembers = computed(() => sortedMembers.value.length);

const unselectedMembers = computed(() => {
    const selectedSet = new Set(selectedIds.value);

    return sortedMembers.value.filter((member) => !selectedSet.has(member.id));
});

const selectedMembers = computed(() => {
    const selectedSet = new Set(selectedIds.value);
    return sortedMembers.value.filter((member) => selectedSet.has(member.id));
});

const filteredUnselectedMembers = computed(() => {
    let members = unselectedMembers.value;
    const gender = genderFilter.value.trim().toLowerCase();
    const keyword = search.value.trim().toLowerCase();

    if (gender !== 'all') {
        members = members.filter((member) => (member.gender || '').toLowerCase() === gender);
    }

    if (keyword) {
        members = members.filter((member) => {
            const display = `${member.last_name || ''}, ${member.first_name || ''}`.toLowerCase();
            return display.includes(keyword);
        });
    }

    return members;
});

const updateFadeState = (panel: HTMLElement | null, target: { value: boolean }) => {
    if (!panel) {
        target.value = false;
        return;
    }

    target.value = panel.scrollHeight - panel.scrollTop - panel.clientHeight > 4;
};

const updateAvailableFade = () => updateFadeState(availablePanelRef.value, showAvailableFade);
const updateSelectedFade = () => updateFadeState(selectedPanelRef.value, showSelectedFade);

watch([unselectedMembers, selectedMembers], async () => {
    await nextTick();
    updateAvailableFade();
    updateSelectedFade();
}, { deep: true });

onMounted(async () => {
    await nextTick();
    updateAvailableFade();
    updateSelectedFade();
});

// helper: displayName intentionally removed in favour of explicit fields in template
const formatDateJoined = (dateString?: string | null) => {
    if (!dateString) return '—';

    // Normalize to YYYY-MM-DD by taking the date part only to avoid timezone shifts
    const datePart = String(dateString).substring(0, 10);
    const parts = datePart.split('-').map((p) => Number(p));
    if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return '—';

    const [year, month, day] = parts;
    // Construct local date to prevent UTC timezone offset issues
    const d = new Date(year, (month || 1) - 1, day || 1);
    if (Number.isNaN(d.getTime())) return '—';

    return d.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// genderLabel intentionally removed; templates use direct member.gender or fallback '—'

const initials = (member: Member) => {
    const last = (member.last_name || '').trim().charAt(0).toUpperCase();
    const first = (member.first_name || '').trim().charAt(0).toUpperCase();
    return `${last}${first}` || 'NA';
};

const selectMember = (memberId: number) => {
    if (selectedIds.value.includes(memberId)) {
        return;
    }

    selectedIds.value = [...selectedIds.value, memberId];
};

const removeMember = (memberId: number) => {
    selectedIds.value = selectedIds.value.filter((id) => id !== memberId);
};

const toggleSelected = (memberId: number) => {
    if (selectedIds.value.includes(memberId)) {
        removeMember(memberId);
        return;
    }

    selectMember(memberId);
};

const selectAll = () => {
    selectedIds.value = sortedMembers.value.map((member) => member.id);
};

const deselectAll = () => {
    selectedIds.value = [];
};

const inertiaBack = () => {
    try {
        if (window.history.length > 1) {
            window.history.back();
            return;
        }
    } catch {
        // ignore and fallback
    }

    // Fallback: navigate to cooperative page
    router.get(`/cooperatives/${props.cooperative.id}`);
};

const handleBack = () => {
    inertiaBack();
};

const handleCancel = () => {
    inertiaBack();
};

const saveParticipants = () => {
    isSaving.value = true;

    router.post(
        `/cooperatives/${props.cooperative.id}/activities/${props.activity.id}/participants`,
        { member_ids: selectedIds.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isSaving.value = false;
            },
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Participants saved',
                    text: 'Participants have been updated successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                }).then(() => {
                    inertiaBack();
                });
            },
            onError: (errors: any) => {
                Swal.fire({
                    icon: 'error',
                    title: 'Save failed',
                    text: 'Could not save participants. Please try again.',
                });
                console.error('Save failed:', errors);
            },
        }
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-2">
                        <h1 class="text-xl font-semibold text-foreground">
                            {{ activity.title }} - Participants
                        </h1>
                        <Badge class="w-fit border-border bg-muted text-foreground dark:border-border dark:bg-muted dark:text-foreground">
                            <Building2 class="mr-1.5 h-3.5 w-3.5" />
                            {{ cooperative.name }}
                        </Badge>
                        <p class="text-sm text-muted-foreground">
                            Manage members of {{ cooperative.name }} who are participating in this activity.
                        </p>
                    </div>

                    <Button variant="outline" class="h-9 gap-2 self-start" @click="handleBack">
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="border-b px-4 py-4">
                    <h2 class="text-base font-semibold text-foreground">Participants</h2>
                    <p class="text-sm text-muted-foreground">Select members who attended this activity.</p>
                </div>

                <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center">
                    <Button type="button" size="sm" variant="outline" class="gap-2" @click="selectAll">
                        <CheckSquare class="h-4 w-4" />
                        Select All
                    </Button>
                    <Button type="button" size="sm" variant="outline" class="gap-2" @click="deselectAll">
                        <Square class="h-4 w-4" />
                        Deselect All
                    </Button>
                    <div class="w-40 shrink-0">
                        <Select v-model="genderFilter">
                            <SelectTrigger class="h-8 w-full">
                                <SelectValue placeholder="All Genders" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Genders</SelectItem>
                                <SelectItem value="Male">Male</SelectItem>
                                <SelectItem value="Female">Female</SelectItem>
                                <SelectItem value="Other">Other</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="relative flex-1 min-w-[220px]">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search members..." class="h-8 pl-9 text-sm" />
                    </div>
                </div>

                <div v-if="isCooperativeContext" class="grid grid-cols-1 divide-y md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="min-w-0">
                        <div class="flex items-center justify-between border-b bg-muted/30 p-3">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm font-semibold text-foreground">Available Members</span>
                            </div>
                            <Badge variant="secondary">{{ filteredUnselectedMembers.length }} members</Badge>
                        </div>

                        <div class="relative">
                            <div
                                ref="availablePanelRef"
                                class="min-h-[280px] max-h-[420px] overflow-y-auto"
                                @scroll="updateAvailableFade"
                            >
                                <template v-if="filteredUnselectedMembers.length">
                                    <!-- Headers (sticky inside scroll area) -->
                                    <div class="sticky top-0 z-10 grid grid-cols-[2fr_1fr_1fr] bg-muted/80 dark:bg-muted/60 border-b px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                                        <span class="">Member Name</span>
                                        <span class="text-center">Gender</span>
                                        <span class="text-center">Date Joined</span>
                                    </div>

                                    <div v-for="(member, index) in filteredUnselectedMembers" :key="`available-${member.id}`" @click="selectMember(member.id)"
                                        :class="[
                                            'grid grid-cols-[2fr_1fr_1fr] items-center px-3 py-2.5 border-b last:border-0 cursor-pointer transition-colors duration-150',
                                            index % 2 === 0
                                                ? 'bg-background dark:bg-background'
                                                : 'bg-muted/30 dark:bg-muted/20',
                                            'hover:bg-blue-50/60 dark:hover:bg-blue-900/10'
                                        ]">

                                        <!-- Column 1: Checkbox + Avatar + Name -->
                                        <div class="flex items-center gap-2 min-w-0">
                                            <Checkbox :model-value="false" @click.stop @update:model-value="() => toggleSelected(member.id)" class="h-4 w-4 shrink-0 pointer-events-none" />
                                            <div class="h-7 w-7 rounded-full shrink-0 bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300 flex items-center justify-center text-xs font-semibold">
                                                {{ initials(member) }}
                                            </div>
                                            <span class="text-sm font-medium truncate">{{ member.last_name }}, {{ member.first_name }}</span>
                                        </div>

                                        <!-- Column 2: Gender -->
                                        <div class="text-center">
                                            <span class="text-xs text-muted-foreground">{{ member.gender ?? '—' }}</span>
                                        </div>

                                        <!-- Column 3: Date Joined -->
                                        <div class="text-center">
                                            <span class="text-xs text-muted-foreground">{{ formatDateJoined(member.date_joined) }}</span>
                                        </div>
                                    </div>
                                </template>

                                <div v-else class="flex flex-col items-center py-8 text-muted-foreground">
                                    <CheckCircle2 class="mb-2 h-8 w-8 text-green-400" />
                                    <p class="text-sm font-medium">All members selected</p>
                                    <p class="text-xs">Use Deselect All to start over</p>
                                </div>
                            </div>

                            <div
                                v-if="showAvailableFade"
                                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-card to-transparent"
                            />
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center justify-between border-b bg-muted/30 p-3 dark:bg-muted/20">
                            <div class="flex items-center gap-2">
                                <UserCheck class="h-4 w-4 text-foreground/60 dark:text-foreground/40" />
                                <span class="text-sm font-semibold text-foreground">Selected Participants</span>
                            </div>
                            <Badge class="border-border bg-muted text-foreground dark:border-border dark:bg-muted dark:text-foreground">
                                {{ selectedMembers.length }} selected
                            </Badge>
                        </div>

                        <div class="relative">
                            <div
                                ref="selectedPanelRef"
                                class="min-h-[280px] max-h-[420px] overflow-y-auto"
                                @scroll="updateSelectedFade"
                            >
                                <template v-if="selectedMembers.length">
                                    <!-- Headers (sticky inside scroll area) -->
                                    <div class="sticky top-0 z-10 grid grid-cols-[2fr_1fr_1fr_32px] bg-muted/80 dark:bg-muted/60 border-b px-3 py-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                                        <span class="">Member Name</span>
                                        <span class="text-center">Gender</span>
                                        <span class="text-center">Date Joined</span>
                                        <span class="" />
                                    </div>

                                    <div v-for="(member, index) in selectedMembers" :key="`selected-${member.id}`" @click="removeMember(member.id)"
                                        :class="[
                                            'grid grid-cols-[2fr_1fr_1fr_32px] items-center px-3 py-2.5 border-b last:border-0 cursor-pointer transition-colors duration-150',
                                            index % 2 === 0
                                                ? 'bg-blue-50/40 dark:bg-blue-900/5'
                                                : 'bg-blue-50/70 dark:bg-blue-900/10',
                                            'hover:bg-blue-100/60 dark:hover:bg-blue-900/20'
                                        ]">

                                        <!-- Column 1: Checkbox + Avatar + Name -->
                                        <div class="flex items-center gap-2 min-w-0">
                                            <Checkbox :model-value="true" @click.stop @update:model-value="() => toggleSelected(member.id)" class="h-4 w-4 shrink-0 pointer-events-none" />
                                            <div class="h-7 w-7 rounded-full shrink-0 bg-muted text-xs font-semibold text-foreground dark:bg-muted dark:text-foreground flex items-center justify-center">
                                                {{ initials(member) }}
                                            </div>
                                            <span class="text-sm font-medium truncate">{{ member.last_name }}, {{ member.first_name }}</span>
                                        </div>

                                        <!-- Column 2: Gender -->
                                        <div class="text-center">
                                            <span class="text-xs text-muted-foreground">{{ member.gender ?? '—' }}</span>
                                        </div>

                                        <!-- Column 3: Date Joined -->
                                        <div class="text-center">
                                            <span class="text-xs text-muted-foreground">{{ formatDateJoined(member.date_joined) }}</span>
                                        </div>

                                        <!-- Column 4: Remove Button -->
                                        <div class="flex items-center justify-center">
                                            <TooltipProvider :delay-duration="120">
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <button
                                                            type="button"
                                                            class="rounded p-1 text-muted-foreground transition-colors hover:text-red-500"
                                                            @click.stop="removeMember(member.id)"
                                                        >
                                                            <X class="h-4 w-4" />
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Remove from selection</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                </template>

                                <div v-else class="flex flex-col items-center py-8 text-muted-foreground">
                                    <UserPlus class="mb-2 h-8 w-8 text-blue-300" />
                                    <p class="text-sm font-medium">No participants selected yet</p>
                                    <p class="text-xs">Click members on the left to add them</p>
                                </div>
                            </div>

                            <div
                                v-if="showSelectedFade"
                                class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-card to-transparent"
                            />
                        </div>
                    </div>
                </div>

                <div class="border-t bg-muted/20 px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ selectedMembers.length }}</span>
                        of
                        <span class="font-semibold text-foreground">{{ totalMembers }}</span>
                        active members from
                        <span class="font-medium text-foreground dark:text-foreground">{{ cooperative.name }}</span>
                        selected to participate
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <Button variant="outline" @click="handleCancel">Cancel</Button>
                <Button :disabled="isSaving" @click="saveParticipants">
                    <Loader2 v-if="isSaving" class="mr-2 h-4 w-4 animate-spin" />
                    <Save v-else class="mr-2 h-4 w-4" />
                    Save Participants
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
