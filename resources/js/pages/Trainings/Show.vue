<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    ClipboardList,
    GraduationCap,
    Pencil,
    Paperclip,
    Search,
    Target,
    Users,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { useCreateBack } from '@/composables/useCreateBack';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string | null;
    region?: string | null;
    classification?: string | null;
    participants_count?: number;
    participants?: Participant[];
}

interface Participant {
    id: number;
    member_id: number | null;
    full_name: string;
    first_name?: string | null;
    last_name?: string | null;
    outcome?: string | null;
    certificate_no?: string | null;
    certificate_date?: string | null;
    remarks?: string | null;
}

interface ParticipantGroup {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string | null;
    region?: string | null;
    classification?: string | null;
    participants_count: number;
    participants: Participant[];
}

interface Training {
    id: number;
    coop_id: number;
    title: string;
    date_conducted: string | null;
    facilitator: string | null;
    skills_targeted: string | null;
    venue: string | null;
    target_group: string;
    target_group_labels?: string[];
    no_of_participants: number | null;
    follow_up_needed: boolean;
    follow_up_date: string | null;
    follow_up_remarks: string | null;
    status: string;
    cooperative: Cooperative | null;
}

interface Props {
    training: Training;
    cooperatives: Cooperative[];
    participantsByCooperative: ParticipantGroup[];
    participantCount: number;
    total_participants?: number;
    linkedTrainingCount: number;
    attachments: Array<Record<string, unknown>>;
}

const props = defineProps<Props>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canEdit = computed(() => permissions.value.includes('update training-&-capacity'));
const isPanelsLoading = ref(true);
const globalSearch = ref('');
const memberSearch = ref('');
const selectedCooperativeId = ref<number | null>(null);
const currentUrl = computed(() => page.url || '');
const { goBack } = useCreateBack({ fallbackHref: '/trainings' });

const goEdit = () => {
    const editHref = currentUrl.value
        ? `/trainings/${props.training.id}/edit?return_to=${encodeURIComponent(currentUrl.value)}`
        : `/trainings/${props.training.id}/edit`;
    router.visit(editHref);
};

const openReport = () => {
    window.open(`/trainings/${props.training.id}/report`, '_blank', 'noopener,noreferrer');
};

const formatDate = (value: string | null) => {
    if (!value) return '—';

    return new Date(value).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const textOrDash = (value: string | number | null | undefined) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
};

const targetGroupDisplay = computed(() => {
    const labels = props.training.target_group_labels || [];

    if (labels.length) {
        return labels.join(', ');
    }

    return textOrDash(props.training.target_group);
});

const normalize = (value: string | null | undefined) => (value || '').toLowerCase();

const formatMemberName = (participant: Participant) => {
    const last = (participant.last_name || '').trim();
    const first = (participant.first_name || '').trim();

    if (!last && !first) {
        return participant.full_name || 'Unknown member';
    }

    if (!last) {
        return first;
    }

    return first ? `${last}, ${first}` : last;
};

const sortedParticipants = (participants: Participant[]) => {
    return [...participants].sort((a, b) => {
        const lastCompare = normalize(a.last_name).localeCompare(normalize(b.last_name));

        if (lastCompare !== 0) {
            return lastCompare;
        }

        return normalize(a.first_name).localeCompare(normalize(b.first_name));
    });
};

const participantSearchText = (participant: Participant) => {
    return `${formatMemberName(participant)} ${participant.full_name || ''}`.toLowerCase();
};

const getInitials = (participant: Participant) => {
    const lastInitial = (participant.last_name || '').trim().charAt(0);
    const firstInitial = (participant.first_name || '').trim().charAt(0);
    const combined = `${lastInitial}${firstInitial}`.toUpperCase();

    if (combined) {
        return combined;
    }

    const fallback = (participant.full_name || '').trim().replace(/\s+/g, '');
    return fallback ? fallback.slice(0, 2).toUpperCase() : '--';
};

const statusDotClass = (status: string | null | undefined) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'active') {
        return 'bg-emerald-500';
    }

    if (normalized === 'inactive') {
        return 'bg-red-500';
    }

    if (normalized === 'pending') {
        return 'bg-amber-500';
    }

    return 'bg-slate-400';
};

const cooperativeStatusBadgeClass = (status: string | null | undefined) => {
    const normalized = (status || '').toLowerCase();

    if (normalized === 'active') {
        return 'border-emerald-200 bg-emerald-100 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-900/20 dark:text-emerald-400';
    }

    if (normalized === 'inactive') {
        return 'border-red-200 bg-red-100 text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400';
    }

    if (normalized === 'pending') {
        return 'border-amber-200 bg-amber-100 text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-400';
    }

    return 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-900/40 dark:bg-slate-900/20 dark:text-slate-300';
};

const totalParticipants = computed(() => {
    return props.total_participants
        ?? props.participantCount
        ?? props.cooperatives.reduce((sum, cooperative) => sum + Number(cooperative.participants_count || cooperative.participants?.length || 0), 0);
});

const preparedCooperatives = computed(() => {
    const term = globalSearch.value.trim().toLowerCase();

    return props.cooperatives
        .map((cooperative) => {
            const participants = sortedParticipants(cooperative.participants || []);
            const cooperativeNameMatches = term ? normalize(cooperative.name).includes(term) : false;
            const matchingMemberCount = term
                ? participants.filter((participant) => participantSearchText(participant).includes(term)).length
                : 0;

            const visible = !term || cooperativeNameMatches || matchingMemberCount > 0;

            return {
                ...cooperative,
                participants,
                cooperativeNameMatches,
                matchingMemberCount,
                visible,
            };
        })
        .filter((cooperative) => cooperative.visible);
});

const selectedCooperative = computed(() => {
    return preparedCooperatives.value.find((cooperative) => cooperative.id === selectedCooperativeId.value)
        || preparedCooperatives.value[0]
        || null;
});

const globallyFilteredMembers = computed(() => {
    const cooperative = selectedCooperative.value;

    if (!cooperative) {
        return [] as Participant[];
    }

    const term = globalSearch.value.trim().toLowerCase();
    const participants = cooperative.participants;

    if (!term) {
        return participants;
    }

    const matchedMembers = participants.filter((participant) => participantSearchText(participant).includes(term));
    if (matchedMembers.length > 0) {
        return matchedMembers;
    }

    if (cooperative.cooperativeNameMatches) {
        return participants;
    }

    return [];
});

const visibleMembers = computed(() => {
    const term = memberSearch.value.trim().toLowerCase();

    if (!term) {
        return globallyFilteredMembers.value;
    }

    return globallyFilteredMembers.value.filter((participant) => participantSearchText(participant).includes(term));
});

const selectedCooperativePosition = computed(() => {
    if (!selectedCooperative.value) {
        return 0;
    }

    return preparedCooperatives.value.findIndex((cooperative) => cooperative.id === selectedCooperative.value?.id) + 1;
});

const canGoPreviousCooperative = computed(() => selectedCooperativePosition.value > 1);
const canGoNextCooperative = computed(() => selectedCooperativePosition.value > 0 && selectedCooperativePosition.value < preparedCooperatives.value.length);

const selectCooperative = (id: number) => {
    selectedCooperativeId.value = id;
    memberSearch.value = '';
};

const goToPreviousCooperative = () => {
    if (!canGoPreviousCooperative.value) {
        return;
    }

    const previous = preparedCooperatives.value[selectedCooperativePosition.value - 2];
    if (previous) {
        selectCooperative(previous.id);
    }
};

const goToNextCooperative = () => {
    if (!canGoNextCooperative.value) {
        return;
    }

    const next = preparedCooperatives.value[selectedCooperativePosition.value];
    if (next) {
        selectCooperative(next.id);
    }
};

const onCooperativeListKeydown = (event: KeyboardEvent) => {
    if (!preparedCooperatives.value.length || !selectedCooperative.value) {
        return;
    }

    const currentIndex = selectedCooperativePosition.value - 1;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        const target = preparedCooperatives.value[Math.min(currentIndex + 1, preparedCooperatives.value.length - 1)];
        if (target) {
            selectCooperative(target.id);
        }
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        const target = preparedCooperatives.value[Math.max(currentIndex - 1, 0)];
        if (target) {
            selectCooperative(target.id);
        }
    }
};

watch(preparedCooperatives, (cooperatives) => {
    if (!cooperatives.length) {
        selectedCooperativeId.value = null;
        return;
    }

    const exists = cooperatives.some((cooperative) => cooperative.id === selectedCooperativeId.value);
    if (!exists) {
        selectedCooperativeId.value = cooperatives[0].id;
    }
}, { immediate: true });

onMounted(() => {
    requestAnimationFrame(() => {
        isPanelsLoading.value = false;
    });
});

const statusBadgeClass = (status: string) => {
    const normalized = status.toLowerCase();

    if (normalized === 'completed') {
        return 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300';
    }

    if (normalized === 'planned' || normalized === 'follow-up pending') {
        return 'border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300';
    }

    if (normalized === 'cancelled') {
        return 'border-red-200 bg-red-100 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-300';
    }

    if (normalized === 'archived') {
        return 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900/30 dark:text-slate-300';
    }

    return 'border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300';
};

const selectedCooperativeLabel = computed(() => {
    if (props.cooperatives.length === 0) {
        return 'No cooperatives linked';
    }

    if (props.linkedTrainingCount > 1) {
        return `${props.cooperatives.length} cooperatives participating across ${props.linkedTrainingCount} linked training records`;
    }

    return props.training.cooperative?.name || props.cooperatives[0]?.name || 'No cooperative linked';
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <GraduationCap class="h-5 w-5" />
                            </div>
                            <div class="flex-1 space-y-2">
                                <Badge variant="outline">Training &amp; Capacity Building</Badge>
                                <div class="flex flex-wrap items-center gap-3">
                                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
                                        {{ training.title }}
                                    </h1>
                                    <Badge :class="statusBadgeClass(training.status)">
                                        {{ training.status }}
                                    </Badge>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    {{ selectedCooperativeLabel }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="h-8 gap-1 px-2 text-xs transition-colors duration-150 border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100 hover:border-violet-300 dark:border-violet-700 dark:bg-violet-950 dark:text-violet-300 dark:hover:bg-violet-900 dark:hover:border-violet-600"
                                type="button"
                                @click="openReport"
                            >
                                <ClipboardList class="h-3.5 w-3.5" />
                                Report
                            </Button>
                            <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                                <ArrowLeft class="h-4 w-4" />
                                Back
                            </Button>
                            <Button v-if="canEdit" variant="outline" size="sm" class="gap-2" type="button" @click="goEdit">
                                <Pencil class="h-4 w-4" />
                                Edit
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="flex items-center gap-2 text-xl">
                        <Target class="h-5 w-5" />
                        Basic Information
                    </CardTitle>
                    <CardDescription>Review the core training details and delivery information.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5 pt-0">
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Date Conducted</dt>
                            <dd class="mt-1 flex items-center gap-2 text-sm font-medium text-foreground">
                                <CalendarDays class="h-4 w-4 text-muted-foreground" />
                                {{ formatDate(training.date_conducted) }}
                            </dd>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Venue</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ textOrDash(training.venue) }}</dd>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Facilitator</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ textOrDash(training.facilitator) }}</dd>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Target Group</dt>
                            <dd class="mt-1 text-sm font-medium text-foreground">{{ targetGroupDisplay }}</dd>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4 sm:col-span-2">
                            <dt class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Skills Targeted</dt>
                            <dd class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ textOrDash(training.skills_targeted) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-3 pb-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Building2 class="h-5 w-5" />
                            Cooperatives &amp; Participants
                        </CardTitle>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-400">
                                <Building2 class="h-3.5 w-3.5" />
                                {{ preparedCooperatives.length }} Cooperatives
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-green-200 bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-400">
                                <Users class="h-3.5 w-3.5" />
                                {{ totalParticipants }} Participants
                            </span>
                        </div>
                    </div>

                    <p class="text-sm text-muted-foreground">
                        {{ preparedCooperatives.length }} cooperatives · {{ totalParticipants }} total participants
                    </p>

                    <div class="relative">
                        <Search class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                        <Input
                            v-model="globalSearch"
                            class="h-9 pl-9 text-sm"
                            placeholder="Search cooperatives or members..."
                        />
                    </div>
                </CardHeader>

                <CardContent class="pt-0">
                    <div class="overflow-hidden rounded-xl border border-border bg-card">
                        <div class="grid h-110 grid-cols-1 overflow-x-hidden lg:grid-cols-[320px_minmax(0,1fr)] xl:grid-cols-[360px_minmax(0,1fr)]">
                            <div class="border-b border-border lg:border-b-0 lg:border-r">
                                <div
                                    tabindex="0"
                                    class="max-h-110 overflow-y-auto overflow-x-hidden p-2 outline-none"
                                    @keydown="onCooperativeListKeydown"
                                >
                                    <template v-if="isPanelsLoading">
                                        <div v-for="index in 4" :key="`coop-skeleton-${index}`" class="border-b border-border p-3">
                                            <div class="h-4 w-3/4 animate-pulse rounded bg-muted" />
                                            <div class="mt-2 h-3 w-1/2 animate-pulse rounded bg-muted" />
                                        </div>
                                    </template>

                                    <template v-else-if="preparedCooperatives.length">
                                        <button
                                            v-for="cooperative in preparedCooperatives"
                                            :key="cooperative.id"
                                            type="button"
                                            class="w-full rounded-lg border-2 px-3.5 py-3 text-left transition-all duration-150 cursor-pointer hover:shadow-sm"
                                            :class="cooperative.id === selectedCooperative?.id
                                                ? 'border-primary bg-primary/15 shadow-sm hover:shadow-md hover:bg-primary/20'
                                                : 'border-border/60 bg-white hover:border-primary/50 hover:bg-primary/5 dark:bg-muted/30 dark:border-border dark:hover:border-primary/40 dark:hover:bg-primary/10'"
                                            @click="selectCooperative(cooperative.id)"
                                        >
                                            <div class="flex items-center gap-2">
                                                <Building2 class="h-4 w-4 shrink-0 text-muted-foreground" />
                                                <p class="min-w-0 flex-1 truncate text-sm" :class="cooperative.id === selectedCooperative?.id ? 'font-semibold text-foreground' : 'font-semibold text-foreground'">
                                                    {{ cooperative.name }}
                                                </p>
                                                <span class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900/20 dark:text-green-400">
                                                    {{ cooperative.participants_count || cooperative.participants.length || 0 }}
                                                    <span class="ml-0.5">👥</span>
                                                </span>
                                            </div>

                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <span class="rounded bg-blue-50 px-1.5 py-0.5 text-xs text-blue-600 dark:bg-blue-900/20 dark:text-blue-300">
                                                    {{ cooperative.region || 'Region not specified' }}
                                                </span>
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs text-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
                                                    {{ cooperative.classification || 'No class' }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 text-xs text-muted-foreground">
                                                    <span class="h-2 w-2 rounded-full" :class="statusDotClass(cooperative.status)" />
                                                    {{ cooperative.status || 'Unknown' }}
                                                </span>
                                            </div>

                                            <p
                                                v-if="globalSearch.trim() && cooperative.matchingMemberCount > 0"
                                                class="mt-1 text-xs text-amber-600 dark:text-amber-400"
                                            >
                                                {{ cooperative.matchingMemberCount }} matching members
                                            </p>
                                        </button>
                                    </template>

                                    <div
                                        v-else
                                        class="flex flex-col items-center justify-center px-4 py-10 text-center text-muted-foreground"
                                    >
                                        <Building2 class="mb-2 h-8 w-8 opacity-40" />
                                        <p class="text-xs">No cooperatives found</p>
                                    </div>
                                </div>
                            </div>

                            <div class="shadow-sm">
                                <div class="max-h-110 overflow-y-auto">
                                    <div class="sticky top-0 z-10 border-b bg-background px-4 py-3">
                                        <template v-if="selectedCooperative">
                                            <div class="flex flex-wrap items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="flex items-center gap-2 text-sm font-semibold text-foreground">
                                                        <Building2 class="h-4 w-4" />
                                                        <span class="truncate">{{ selectedCooperative.name }}</span>
                                                    </p>
                                                </div>
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <Badge variant="outline" class="text-xs">
                                                        {{ selectedCooperative.classification || 'No classification' }}
                                                    </Badge>
                                                    <span :class="['rounded-full border px-2 py-0.5 text-xs font-medium', cooperativeStatusBadgeClass(selectedCooperative.status)]">
                                                        {{ selectedCooperative.status || 'Unknown' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <p class="mt-1 text-xs text-muted-foreground">
                                                Region:
                                                <span class="rounded bg-blue-50 px-1.5 py-0.5 text-blue-600 dark:bg-blue-900/20 dark:text-blue-300">
                                                    {{ selectedCooperative.region || 'Not specified' }}
                                                </span>
                                                · Members: {{ globallyFilteredMembers.length }} participants
                                            </p>

                                            <div class="relative mt-2">
                                                <Search class="pointer-events-none absolute left-3 top-2 h-3.5 w-3.5 text-muted-foreground" />
                                                <Input
                                                    v-model="memberSearch"
                                                    class="h-8 pl-8 text-sm"
                                                    placeholder="Search members in this cooperative..."
                                                />
                                            </div>
                                            <p class="mt-1 text-xs text-muted-foreground">
                                                Showing {{ visibleMembers.length }} of {{ globallyFilteredMembers.length }} members
                                            </p>
                                        </template>

                                        <template v-else>
                                            <p class="text-sm text-muted-foreground">Select a cooperative to view participants.</p>
                                        </template>
                                    </div>

                                    <template v-if="isPanelsLoading">
                                        <div class="space-y-2 px-4 py-3">
                                            <div v-for="index in 6" :key="`member-skeleton-${index}`" class="h-10 animate-pulse rounded bg-muted" />
                                        </div>
                                    </template>

                                    <template v-else-if="selectedCooperative">
                                        <div v-if="visibleMembers.length" class="divide-y divide-border">
                                            <div
                                                v-for="(participant, index) in visibleMembers"
                                                :key="participant.id"
                                                class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors hover:bg-primary/5"
                                                :class="index % 2 === 0 ? 'bg-white dark:bg-background' : 'bg-muted/20 dark:bg-muted/10'"
                                            >
                                                <span class="w-6 shrink-0 text-right font-mono text-xs text-muted-foreground">
                                                    {{ index + 1 }}
                                                </span>
                                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ getInitials(participant) }}
                                                </span>
                                                <p class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">
                                                    {{ formatMemberName(participant) }}
                                                </p>
                                                <span class="shrink-0 font-mono text-xs text-muted-foreground">
                                                    {{ participant.member_code || '—' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div
                                            v-else-if="memberSearch.trim()"
                                            class="px-4 py-10 text-center text-sm text-muted-foreground"
                                        >
                                            No members found matching "{{ memberSearch.trim() }}"
                                        </div>

                                        <div
                                            v-else
                                            class="flex flex-col items-center px-4 py-12 text-center text-muted-foreground"
                                        >
                                            <Users class="mx-auto mb-3 h-10 w-10 opacity-30" />
                                            <p class="text-sm font-medium">No participants from this cooperative</p>
                                            <p class="mt-1 text-xs">No members were recorded for this training</p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 border-t bg-muted/20 px-4 py-3">
                            <p class="text-xs text-muted-foreground">
                                <template v-if="selectedCooperative">
                                    Showing {{ selectedCooperative.name }} — {{ visibleMembers.length }} of {{ totalParticipants }} participants
                                </template>
                                <template v-else>
                                    No cooperative selected
                                </template>
                            </p>

                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 gap-1 text-xs"
                                    :disabled="!canGoPreviousCooperative"
                                    @click="goToPreviousCooperative"
                                >
                                    <ChevronLeft class="h-3.5 w-3.5" />
                                    Prev Cooperative
                                </Button>
                                <span class="text-xs text-muted-foreground">
                                    {{ selectedCooperativePosition }} of {{ preparedCooperatives.length }}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    class="h-7 gap-1 text-xs"
                                    :disabled="!canGoNextCooperative"
                                    @click="goToNextCooperative"
                                >
                                    Next Cooperative
                                    <ChevronRight class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 xl:grid-cols-2">
                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Budget &amp; Beneficiaries</CardTitle>
                        <CardDescription>Summary values for the linked training records.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-xl border border-border bg-muted/20 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Beneficiaries</p>
                                <p class="mt-1 text-lg font-semibold text-foreground">{{ participantCount }}</p>
                                <p class="text-sm text-muted-foreground">Participants recorded</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/20 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Linked Records</p>
                                <p class="mt-1 text-lg font-semibold text-foreground">{{ linkedTrainingCount }}</p>
                                <p class="text-sm text-muted-foreground">Training rows grouped together</p>
                            </div>
                            <div class="rounded-xl border border-border bg-muted/20 p-4 sm:col-span-2">
                                <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Budget</p>
                                <p class="mt-1 text-sm font-medium text-foreground">Not tracked in this module yet.</p>
                                <p class="text-sm text-muted-foreground">No dedicated training budget field exists in the current data model.</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Paperclip class="h-5 w-5" />
                            Attachments / Supporting Documents
                        </CardTitle>
                        <CardDescription>Uploaded files can be listed here when the module starts storing them.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div v-if="attachments.length === 0" class="rounded-xl border border-dashed border-border bg-muted/10 px-6 py-10 text-center text-sm text-muted-foreground">
                            <Paperclip class="mx-auto mb-2 h-6 w-6" />
                            No attachments are stored for this training record.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="(attachment, index) in attachments"
                                :key="index"
                                class="rounded-xl border border-border bg-card p-4"
                            >
                                <p class="font-medium text-foreground">Supporting document {{ index + 1 }}</p>
                                <p class="text-sm text-muted-foreground">{{ attachment }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="space-y-1 pb-4">
                    <CardTitle class="text-xl">Additional Notes / Remarks</CardTitle>
                    <CardDescription>Follow-up instructions and closing remarks for the training.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4 pt-0">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Follow-up Needed</p>
                            <p class="mt-1 text-sm font-medium text-foreground">{{ training.follow_up_needed ? 'Yes' : 'No' }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Follow-up Date</p>
                            <p class="mt-1 text-sm font-medium text-foreground">{{ formatDate(training.follow_up_date) }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/20 p-4 md:col-span-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Follow-up Remarks</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ textOrDash(training.follow_up_remarks) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
