<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Check, CheckCircle2, GraduationCap, Lock, MinusCircle, Save, Search, UserCheck, UserPlus, Users, X } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import { useFormUx } from '@/composables/useFormUx';
import CooperativeMultiSelectDialog from '@/components/Cooperatives/CooperativeMultiSelectDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
// Explicit cancel navigation below (no history.back)
import AppLayout from '@/layouts/AppLayout.vue';
import { notifyError, notifySuccess } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string | null;
    region?: string | null;
    classification?: string | null;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    coop_id: number;
}

interface CooperativeMembersGroup {
    id: number;
    name: string;
    members: Member[];
}

interface MembersByCooperativesResponse {
    cooperatives: CooperativeMembersGroup[];
}

interface Props {
    cooperatives: Cooperative[];
    isCooperativeContext?: boolean;
    contextCooperativeId?: number | null;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateTraining = computed(() => permissions.value.includes('create training-&-capacity'));
const isCooperativeContext = computed(() => Boolean(props.isCooperativeContext && props.contextCooperativeId));
const lockedCooperativeId = computed(() => {
    if (!isCooperativeContext.value || !props.contextCooperativeId) return '';
    return String(props.contextCooperativeId);
});
const initialCooperativeIds = computed(() => {
    if (lockedCooperativeId.value) {
        return [lockedCooperativeId.value];
    }

    return [];
});

const form = useForm({
    coop_id: initialCooperativeIds.value[0] || '',
    coop_ids: [...initialCooperativeIds.value],
    member_ids: [] as number[],
    title: '',
    date_conducted: '',
    facilitator: '',
    skills_targeted: '',
    venue: '',
    target_group: 'All Members',
    no_of_participants: '',
    follow_up_needed: false,
    follow_up_date: '',
    follow_up_remarks: '',
    status: 'Planned',
});

// Initialize useFormUx for UX handling (dirty tracking, error classes, shake/scroll)
const { markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, showErrorShake } = useFormUx(form);

const targetGroups = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];
const statusOptions = ['Planned', 'Completed', 'Archived', 'Cancelled', 'Follow-Up Pending'];
const isCooperativeDialogOpen = ref(false);
const selectedCoopIds = ref<string[]>(form.coop_ids);
const trainingFormRef = ref<HTMLFormElement | null>(null);
const cooperativeSectionRef = ref<HTMLElement | null>(null);
// cancel() below will navigate to validated return_to / cooperative show / trainings index

const selectedCooperatives = computed(() => {
    const selectedSet = new Set(selectedCoopIds.value);
    return props.cooperatives.filter((coop) => selectedSet.has(String(coop.id)));
});
const lockedCooperative = computed(() => {
    if (!lockedCooperativeId.value) return null;
    return props.cooperatives.find((coop) => String(coop.id) === lockedCooperativeId.value) || null;
});

const selectedCooperativeSummary = computed(() => {
    const count = selectedCoopIds.value.length;

    if (count === 0) return 'No cooperatives selected';
    if (count === 1) return selectedCooperatives.value[0]?.name || '1 cooperative selected';

    return `${count} cooperatives selected`;
});

const syncSelectedCooperatives = (ids: string[]) => {
    if (lockedCooperativeId.value) {
        selectedCoopIds.value = [lockedCooperativeId.value];
        form.coop_ids = [lockedCooperativeId.value];
        form.coop_id = lockedCooperativeId.value;
        form.clearErrors('coop_id', 'coop_ids');
        return;
    }

    selectedCoopIds.value = [...new Set(ids)];
    form.coop_ids = [...selectedCoopIds.value];
    form.coop_id = selectedCoopIds.value[0] || '';
    form.clearErrors('coop_id', 'coop_ids');
};

const memberSearch = ref('');
const membersByCoop = ref<Record<number, CooperativeMembersGroup>>({});
const activeCoopId = ref<number | null>(null);
const isLoadingMembers = ref(false);
const fetchVersion = ref(0);

const sortMembers = (members: Member[]) => {
    return [...members].sort((a, b) => {
        const lastNameCompare = (a.last_name || '').localeCompare(b.last_name || '');
        if (lastNameCompare !== 0) return lastNameCompare;
        return (a.first_name || '').localeCompare(b.first_name || '');
    });
};

const formatMemberName = (member: Member) => {
    const lastName = (member.last_name || '').trim();
    const firstName = (member.first_name || '').trim();

    if (lastName && firstName) {
        return `${lastName}, ${firstName}`;
    }

    return lastName || firstName || 'Unnamed Member';
};

const getMemberInitials = (member: Member) => {
    const firstInitial = (member.first_name || '').trim().charAt(0);
    const lastInitial = (member.last_name || '').trim().charAt(0);
    const initials = `${firstInitial}${lastInitial}`.toUpperCase().trim();
    return initials || 'M';
};

const selectedCooperativeGroups = computed(() => {
    return selectedCooperatives.value.map((coop) => ({
        id: coop.id,
        name: coop.name,
        members: sortMembers(membersByCoop.value[coop.id]?.members || []),
    }));
});

const hasCooperativeSidebar = computed(() => selectedCooperativeGroups.value.length > 1);

const activeCooperativeGroup = computed(() => {
    if (!activeCoopId.value) return null;
    return selectedCooperativeGroups.value.find((group) => group.id === activeCoopId.value) || null;
});

const activeMembers = computed(() => activeCooperativeGroup.value?.members || []);

const selectedMemberSet = computed(() => new Set(form.member_ids));

const selectedMembersForCoop = computed(() => {
    return activeMembers.value.filter((member) => selectedMemberSet.value.has(member.id));
});

const unselectedMembersForCoop = computed(() => {
    return activeMembers.value.filter((member) => !selectedMemberSet.value.has(member.id));
});

const filteredAvailableMembers = computed(() => {
    const searchTerm = memberSearch.value.trim().toLowerCase();

    if (!searchTerm) return unselectedMembersForCoop.value;

    return unselectedMembersForCoop.value.filter((member) => {
        const formattedName = formatMemberName(member).toLowerCase();
        return formattedName.includes(searchTerm);
    });
});

const selectedCountByCoop = computed(() => {
    const counts: Record<number, number> = {};
    selectedCooperativeGroups.value.forEach((group) => {
        counts[group.id] = group.members.filter((member) => selectedMemberSet.value.has(member.id)).length;
    });
    return counts;
});

const selectedCoopCount = computed(() => {
    return selectedCooperativeGroups.value.filter((group) => (selectedCountByCoop.value[group.id] || 0) > 0).length;
});

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const returnToHref = computed(() => {
    const href = queryParams.value.get('return_to') || '';
    if (!href || !href.startsWith('/') || href.startsWith('//')) return '';
    return href;
});

const selectAllLabel = computed(() => {
    const searchTerm = memberSearch.value.trim();
    if (searchTerm) {
        return `Select Filtered (${filteredAvailableMembers.value.length})`;
    }

    return 'Select All';
});

const availableScrollRef = ref<HTMLElement | null>(null);
const selectedScrollRef = ref<HTMLElement | null>(null);
const hasAvailableOverflow = ref(false);
const hasSelectedOverflow = ref(false);

const updatePanelOverflow = () => {
    const availableEl = availableScrollRef.value;
    const selectedEl = selectedScrollRef.value;

    hasAvailableOverflow.value = Boolean(availableEl && availableEl.scrollHeight > availableEl.clientHeight + 1);
    hasSelectedOverflow.value = Boolean(selectedEl && selectedEl.scrollHeight > selectedEl.clientHeight + 1);
};

const isAllSelectedForCoop = (coopId: number) => {
    const group = selectedCooperativeGroups.value.find((item) => item.id === coopId);
    if (!group || group.members.length === 0) return false;
    return (selectedCountByCoop.value[coopId] || 0) === group.members.length;
};

const isPartialSelectedForCoop = (coopId: number) => {
    const group = selectedCooperativeGroups.value.find((item) => item.id === coopId);
    if (!group || group.members.length === 0) return false;
    const selectedCount = selectedCountByCoop.value[coopId] || 0;
    return selectedCount > 0 && selectedCount < group.members.length;
};


const addMemberSelection = (memberId: number) => {
    if (!form.member_ids.includes(memberId)) {
        form.member_ids.push(memberId);
    }
};

const removeMemberSelection = (memberId: number) => {
    form.member_ids = form.member_ids.filter((id) => id !== memberId);
};

const selectAllInCurrentCoop = () => {
    const source = memberSearch.value.trim() ? filteredAvailableMembers.value : unselectedMembersForCoop.value;
    form.member_ids = [...new Set([...form.member_ids, ...source.map((member) => member.id)])];
};

const deselectAllInCurrentCoop = () => {
    const idsToRemove = new Set(selectedMembersForCoop.value.map((member) => member.id));
    form.member_ids = form.member_ids.filter((id) => !idsToRemove.has(id));
};

const setActiveCooperative = (coopId: number) => {
    activeCoopId.value = coopId;
    memberSearch.value = '';
    nextTick(() => {
        availableScrollRef.value?.scrollTo({ top: 0 });
        selectedScrollRef.value?.scrollTo({ top: 0 });
        updatePanelOverflow();
    });
};

const fetchMembersForCooperatives = async (coopIds: string[]) => {
    const requestId = ++fetchVersion.value;

    isLoadingMembers.value = true;

    try {
        const params = new URLSearchParams();
        coopIds.forEach((id) => {
            params.append('cooperative_ids[]', String(Number(id)));
        });

        const response = await fetch(`/trainings/members/by-cooperatives?${params.toString()}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Failed to fetch members for selected cooperatives.');
        }

        const data = (await response.json()) as MembersByCooperativesResponse;

        if (requestId !== fetchVersion.value) {
            return;
        }

        const nextMembersByCoop: Record<number, CooperativeMembersGroup> = {};

        data.cooperatives.forEach((cooperative) => {
            nextMembersByCoop[cooperative.id] = {
                id: cooperative.id,
                name: cooperative.name,
                members: sortMembers(cooperative.members || []),
            };
        });

        membersByCoop.value = nextMembersByCoop;

        if (!activeCoopId.value || !coopIds.includes(String(activeCoopId.value))) {
            const firstCoopId = Number(coopIds[0]);
            activeCoopId.value = Number.isNaN(firstCoopId) ? null : firstCoopId;
        }

        await nextTick();
        updatePanelOverflow();
    } catch {
        if (requestId !== fetchVersion.value) {
            return;
        }

        membersByCoop.value = {};
        form.member_ids = [];
        notifyError('Unable to load members for selected cooperatives.');
    } finally {
        if (requestId === fetchVersion.value) {
            isLoadingMembers.value = false;
        }
    }
};

const openCooperativePicker = () => {
    if (lockedCooperativeId.value || !props.cooperatives.length) return;
    isCooperativeDialogOpen.value = true;
};

watch(lockedCooperativeId, (newValue) => {
    if (!newValue) return;

    selectedCoopIds.value = [newValue];
    form.coop_ids = [newValue];
    form.coop_id = newValue;
    form.clearErrors('coop_id', 'coop_ids');
}, { immediate: true });

watch(activeCoopId, () => {
    memberSearch.value = '';
    nextTick(() => {
        availableScrollRef.value?.scrollTo({ top: 0 });
        selectedScrollRef.value?.scrollTo({ top: 0 });
        updatePanelOverflow();
    });
});

watch(selectedCoopIds, async (newVal) => {
    form.coop_ids = [...newVal];
    form.coop_id = newVal[0] || '';

    if (newVal.length === 0) {
        membersByCoop.value = {};
        form.member_ids = [];
        activeCoopId.value = null;
        memberSearch.value = '';
        return;
    }

    if (!activeCoopId.value || !newVal.includes(String(activeCoopId.value))) {
        activeCoopId.value = Number(newVal[0]);
    }

    await fetchMembersForCooperatives(newVal);

    const allowedMemberIds = new Set(
        Object.values(membersByCoop.value).flatMap((group) => group.members.map((member) => member.id))
    );

    form.member_ids = form.member_ids.filter((memberId) => allowedMemberIds.has(memberId));
}, { deep: true, immediate: true });

const scrollToFirstInvalidField = async () => {
    await nextTick();

    const firstInvalid = trainingFormRef.value?.querySelector<HTMLElement>(':invalid');
    if (!firstInvalid) return;

    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    firstInvalid.focus();
};

const scrollToCooperativeSection = async () => {
    await nextTick();

    cooperativeSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    const picker = document.getElementById('coop_picker');
    picker?.focus();
};

const submit = async () => {
    if (!canCreateTraining.value) return;

    if (trainingFormRef.value && !trainingFormRef.value.reportValidity()) {
        await scrollToFirstInvalidField();
        return;
    }

    if (!selectedCoopIds.value.length) {
        form.setError('coop_ids', 'Please select at least one cooperative.');
        await scrollToCooperativeSection();
        return;
    }

    form.transform((data) => ({
        ...data,
        coop_id: selectedCoopIds.value[0] || '',
        coop_ids: [...selectedCoopIds.value],
        return_to: returnToHref.value,
    })).post('/trainings', {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
            notifySuccess('Training saved successfully.');
        },
        onError: async (errors) => {
            triggerErrorShake();
            if (errors.coop_ids || errors.coop_id) {
                await scrollToCooperativeSection();
            } else {
                await scrollToFirstError();
            }

            const firstError = Object.values(errors)[0];
            notifyError(firstError || 'Unable to save training. Please check the form and try again.');
        },
    });
};

const cancel = () => {
    const params = new URLSearchParams(page.url.split('?')[1] || '');
    const returnTo = params.get('return_to');

    const isValidReturnTo = (href: string | null) => {
        if (!href) return false;
        try {
            const url = new URL(href, window.location.origin);
            return url.origin === window.location.origin && url.pathname.startsWith('/');
        } catch (_e) {
            return false;
        }
    };

    if (isValidReturnTo(returnTo)) {
        router.get(returnTo as string);
        return;
    }

    if (isCooperativeContext) {
        const coopId = lockedCooperative.value?.id || (selectedCoopIds.value.length ? selectedCoopIds.value[0] : null);
        if (coopId) {
            router.get(`/cooperatives/${coopId}`);
            return;
        }
    }

    router.get('/trainings');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <GraduationCap class="h-5 w-5" />
                        </div>
                        <div class="flex-1">
                            <Badge variant="outline" class="mb-2">Training &amp; Capacity Building</Badge>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Training</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Record a training or capacity building session.</p>
                        </div>
                        <!-- Back removed per UX rules for Create pages -->
                    </div>
                </CardContent>
            </Card>

            <form ref="trainingFormRef" @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                <Card class="w-full min-h-87.5 border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <GraduationCap class="h-5 w-5" />
                            Basic Information
                        </CardTitle>
                        <CardDescription>Capture the training title, type, date, and delivery details.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="title">Title <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" required placeholder="Enter training title" :class="inputErrorClass('title')" @input="clearError('title')" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.title }}</p>
                            </div>
                            <div>
                                <Label for="target_group">Target Group <span class="text-red-500">*</span></Label>
                                <Select v-model="form.target_group" @update:modelValue="clearError('target_group')">
                                    <SelectTrigger id="target_group" :class="inputErrorClass('target_group')">
                                        <SelectValue placeholder="Select target group" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in targetGroups" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.target_group" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.target_group }}</p>
                            </div>
                            <div>
                                <Label for="date_conducted"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Date Conducted</Label>
                                <Input id="date_conducted" v-model="form.date_conducted" type="date" :class="inputErrorClass('date_conducted')" @input="clearError('date_conducted')" />
                                <p v-if="form.errors.date_conducted" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.date_conducted }}</p>
                            </div>
                            <div>
                                <Label for="venue"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Venue</Label>
                                <Input id="venue" v-model="form.venue" placeholder="Training venue" :class="inputErrorClass('venue')" @input="clearError('venue')" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.venue }}</p>
                            </div>
                            <div>
                                <Label for="facilitator"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Facilitator</Label>
                                <Input id="facilitator" v-model="form.facilitator" placeholder="Provider or facilitator" :class="inputErrorClass('facilitator')" @input="clearError('facilitator')" />
                                <p v-if="form.errors.facilitator" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.facilitator }}</p>
                            </div>
                            <div>
                                <Label for="status">Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.status" @update:modelValue="clearError('status')">
                                    <SelectTrigger id="status" :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">{{ option }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.status }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="skills_targeted"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Skills Targeted</Label>
                                <Textarea id="skills_targeted" v-model="form.skills_targeted" placeholder="Skills targeted by the training" :class="inputErrorClass('skills_targeted')" @input="clearError('skills_targeted')" />
                                <p v-if="form.errors.skills_targeted" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.skills_targeted }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Cooperative</CardTitle>
                        <CardDescription>Choose the cooperative or keep the locked context selection.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div ref="cooperativeSectionRef">
                            <Label for="coop_picker">Cooperatives</Label>
                            <Button
                                id="coop_picker"
                                type="button"
                                variant="outline"
                                :disabled="isCooperativeContext"
                                class="h-11 w-full items-center justify-between"
                                :class="[
                                    { 'border-red-500 focus-visible:ring-red-500': form.errors.coop_ids || form.errors.coop_id },
                                    isCooperativeContext ? 'cursor-not-allowed bg-muted opacity-60' : '',
                                ]"
                                @click="openCooperativePicker"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <Lock v-if="isCooperativeContext" class="h-4 w-4 shrink-0 text-muted-foreground" />
                                    <span class="truncate text-left">{{ isCooperativeContext ? (lockedCooperative?.name || selectedCooperativeSummary) : selectedCooperativeSummary }}</span>
                                </span>
                                <span class="ml-2 text-xs text-muted-foreground">{{ isCooperativeContext ? 'Locked' : (selectedCoopIds.length ? `${selectedCoopIds.length} selected` : 'Select') }}</span>
                            </Button>
                            <p v-if="isCooperativeContext" class="mt-1 text-xs text-muted-foreground">Cooperative is automatically set based on your current context.</p>
                            <div v-if="selectedCooperatives.length" class="mt-3 flex flex-wrap gap-1.5">
                                <Badge v-for="coop in selectedCooperatives.slice(0, 4)" :key="coop.id" variant="secondary" class="max-w-full truncate">{{ coop.name }}</Badge>
                                <Badge v-if="selectedCooperatives.length > 4" variant="outline">+{{ selectedCooperatives.length - 4 }} more</Badge>
                            </div>
                            <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">{{ form.errors.coop_id }}</p>
                            <p v-if="form.errors.coop_ids" class="mt-1 text-sm text-red-500">{{ form.errors.coop_ids }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="w-full min-h-87.5 border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Participants</CardTitle>
                        <CardDescription>Select the members who attended the training.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5 pt-0">
                        <div
                            v-if="selectedCoopIds.length === 0"
                            class="py-8 text-center text-sm text-muted-foreground"
                        >
                            <Users class="mx-auto mb-2 h-6 w-6" />
                            <p class="font-medium">No cooperative selected yet</p>
                            <p>Please select a cooperative above to view and add participants.</p>
                        </div>

                        <div v-else class="grid gap-4" :class="hasCooperativeSidebar ? 'md:grid-cols-[300px_minmax(0,1fr)] xl:grid-cols-[360px_minmax(0,1fr)]' : 'grid-cols-1'">
                            <div v-if="hasCooperativeSidebar" class="space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Cooperatives</p>
                                <div class="space-y-2 rounded-xl border border-border bg-card p-2.5">
                                    <button
                                        v-for="group in selectedCooperativeGroups"
                                        :key="group.id"
                                        type="button"
                                        class="flex w-full items-center justify-between rounded-lg border-2 px-3.5 py-3 text-left transition-all duration-150 cursor-pointer"
                                        :class="activeCoopId === group.id
                                            ? 'border-primary bg-primary/15 text-foreground shadow-sm hover:shadow-md hover:bg-primary/20'
                                            : 'border-border/60 bg-white text-foreground hover:border-primary/50 hover:bg-primary/5 hover:shadow-sm dark:bg-muted/30 dark:border-border dark:hover:border-primary/40 dark:hover:bg-primary/10'"
                                        @click="setActiveCooperative(group.id)"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <p class="font-semibold text-sm leading-5 wrap-break-word">{{ group.name }}</p>
                                            <p class="text-xs text-muted-foreground mt-0.5">
                                                {{ selectedCountByCoop[group.id] || 0 }} / {{ group.members.length }} selected
                                            </p>
                                        </div>
                                        <div class="ml-2 shrink-0 flex items-center justify-center">
                                            <CheckCircle2
                                                v-if="isAllSelectedForCoop(group.id)"
                                                class="h-5 w-5 text-emerald-600 dark:text-emerald-500"
                                            />
                                            <MinusCircle
                                                v-else-if="isPartialSelectedForCoop(group.id)"
                                                class="h-5 w-5 text-amber-600 dark:text-amber-500"
                                            />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div class="w-full space-y-3 rounded-xl border border-border bg-card p-3" :class="hasCooperativeSidebar ? '' : 'md:col-span-2'">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-foreground">
                                            {{ activeCooperativeGroup?.name || selectedCooperatives[0]?.name }}
                                        </h3>
                                        <p class="text-sm text-muted-foreground">{{ activeMembers.length }} members total</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <Button type="button" variant="outline" size="sm" class="h-8 gap-1.5 text-xs" @click="selectAllInCurrentCoop">
                                            <Check class="h-3.5 w-3.5" />
                                            {{ selectAllLabel }}
                                        </Button>
                                        <Button type="button" variant="outline" size="sm" class="h-8 gap-1.5 text-xs" @click="deselectAllInCurrentCoop">
                                            <X class="h-3.5 w-3.5" />
                                            Deselect All
                                        </Button>
                                    </div>
                                </div>

                                <div class="relative">
                                    <Search class="absolute left-3 top-2 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        v-model="memberSearch"
                                        placeholder="Search available members..."
                                        class="h-8 w-full pl-9 text-sm"
                                    />
                                </div>

                                <div class="grid gap-0 overflow-hidden rounded-xl border border-border bg-card md:grid-cols-2 md:divide-x">
                                    <div class="relative w-full">
                                        <div class="flex items-center justify-between border-b bg-muted/30 p-3">
                                            <div class="flex items-center gap-2">
                                                <Users class="h-4 w-4 text-muted-foreground" />
                                                <span class="text-sm font-semibold">Available Members</span>
                                            </div>
                                            <Badge variant="secondary">{{ unselectedMembersForCoop.length }} members</Badge>
                                        </div>
                                        <div ref="availableScrollRef" class="relative min-h-50 max-h-100 overflow-y-auto">
                                            <div v-if="isLoadingMembers" class="space-y-2 p-2">
                                                <div v-for="index in 8" :key="`available-skeleton-${index}`" class="h-10 animate-pulse rounded-md bg-muted/50" />
                                            </div>
                                            <div v-else-if="memberSearch.trim() && filteredAvailableMembers.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                                                No members found matching "{{ memberSearch.trim() }}"
                                            </div>
                                            <div v-else-if="unselectedMembersForCoop.length === 0" class="flex flex-col items-center py-8 text-muted-foreground">
                                                <CheckCircle2 class="mb-2 h-8 w-8 text-green-400" />
                                                <p class="text-sm font-medium">All members selected</p>
                                                <p class="text-xs">Use Deselect All to start over</p>
                                            </div>
                                            <div v-else>
                                                <div
                                                    v-for="(member, index) in filteredAvailableMembers"
                                                    :key="member.id"
                                                    role="button"
                                                    tabindex="0"
                                                    class="flex w-full cursor-pointer items-center gap-3 px-3 py-2 text-left transition-colors duration-150"
                                                    :class="[
                                                        index % 2 === 0 ? 'bg-white dark:bg-background' : 'bg-gray-50/80 dark:bg-muted/20',
                                                        'hover:bg-blue-50/60 dark:hover:bg-blue-900/10',
                                                    ]"
                                                    @click="addMemberSelection(member.id)"
                                                    @keydown.enter.prevent="addMemberSelection(member.id)"
                                                    @keydown.space.prevent="addMemberSelection(member.id)"
                                                >
                                                    <Checkbox
                                                        :model-value="false"
                                                        @update:model-value="() => addMemberSelection(member.id)"
                                                        class="h-4 w-4"
                                                    />
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ getMemberInitials(member) }}
                                                    </div>
                                                    <p class="min-w-0 truncate text-sm text-foreground">{{ formatMemberName(member) }}</p>
                                                </div>
                                            </div>
                                            <div v-if="hasAvailableOverflow" class="pointer-events-none absolute bottom-0 left-0 right-0 h-6 bg-linear-to-t from-background to-transparent" />
                                        </div>
                                    </div>

                                    <div class="relative w-full">
                                        <div class="flex items-center justify-between border-b bg-blue-50 p-3 dark:bg-blue-900/10">
                                            <div class="flex items-center gap-2">
                                                <UserCheck class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                <span class="text-sm font-semibold text-blue-700 dark:text-blue-300">Selected Members</span>
                                            </div>
                                            <Badge class="border-blue-200 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ selectedMembersForCoop.length }} selected</Badge>
                                        </div>
                                        <div ref="selectedScrollRef" class="relative min-h-50 max-h-100 overflow-y-auto">
                                            <div v-if="isLoadingMembers" class="space-y-2 p-2">
                                                <div v-for="index in 8" :key="`selected-skeleton-${index}`" class="h-10 animate-pulse rounded-md bg-muted/50" />
                                            </div>
                                            <div v-else-if="selectedMembersForCoop.length === 0" class="flex flex-col items-center py-8 text-muted-foreground">
                                                <UserPlus class="mb-2 h-8 w-8 text-blue-300" />
                                                <p class="text-sm font-medium">No members selected yet</p>
                                                <p class="text-xs">Click members on the left to add them</p>
                                            </div>
                                            <div v-else>
                                                <div
                                                    v-for="(member, index) in selectedMembersForCoop"
                                                    :key="member.id"
                                                    role="button"
                                                    tabindex="0"
                                                    class="flex w-full cursor-pointer items-center gap-3 px-3 py-2 text-left transition-colors duration-150"
                                                    :class="[
                                                        index % 2 === 0 ? 'bg-blue-50/40 dark:bg-blue-900/5' : 'bg-blue-50/70 dark:bg-blue-900/10',
                                                        'hover:bg-blue-100/60 dark:hover:bg-blue-900/20',
                                                    ]"
                                                    @click="removeMemberSelection(member.id)"
                                                    @keydown.enter.prevent="removeMemberSelection(member.id)"
                                                    @keydown.space.prevent="removeMemberSelection(member.id)"
                                                >
                                                    <Checkbox
                                                        :model-value="true"
                                                        @update:model-value="() => removeMemberSelection(member.id)"
                                                        class="h-4 w-4"
                                                    />
                                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                        {{ getMemberInitials(member) }}
                                                    </div>
                                                    <p class="min-w-0 truncate text-sm font-medium text-foreground">{{ formatMemberName(member) }}</p>
                                                    <button
                                                        type="button"
                                                        class="ml-auto rounded p-1 text-muted-foreground transition-colors hover:text-red-500"
                                                        title="Remove from selection"
                                                        @click.stop="removeMemberSelection(member.id)"
                                                    >
                                                        <X class="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                            <div v-if="hasSelectedOverflow" class="pointer-events-none absolute bottom-0 left-0 right-0 h-6 bg-linear-to-t from-background to-transparent" />
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t pt-3 text-sm font-medium text-muted-foreground">
                                    Total selected: {{ form.member_ids.length }} members across {{ selectedCoopCount }} cooperatives
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="group in selectedCooperativeGroups"
                                        :key="`summary-${group.id}`"
                                        type="button"
                                        class="cursor-pointer rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs text-blue-700 hover:bg-blue-100 dark:border-blue-900/30 dark:bg-blue-900/20 dark:text-blue-200 dark:hover:bg-blue-900/30"
                                        @click="setActiveCooperative(group.id)"
                                    >
                                        {{ group.name }}: {{ selectedCountByCoop[group.id] || 0 }} selected
                                    </button>
                                </div>

                                <p v-if="form.errors.member_ids" class="text-sm text-red-500">{{ form.errors.member_ids }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-xl">Additional Notes / Remarks</CardTitle>
                        <CardDescription>Keep follow-up notes and scheduling details together.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <Label for="no_of_participants"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Number of Participants</Label>
                                <Input id="no_of_participants" v-model="form.no_of_participants" type="number" min="0" placeholder="0" :class="inputErrorClass('no_of_participants')" @input="clearError('no_of_participants')" />
                                <p v-if="form.errors.no_of_participants" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.no_of_participants }}</p>
                            </div>
                            <div>
                                <Label for="follow_up_date"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Follow-Up Date</Label>
                                <Input id="follow_up_date" v-model="form.follow_up_date" type="date" :class="inputErrorClass('follow_up_date')" @input="clearError('follow_up_date')" />
                                <p v-if="form.errors.follow_up_date" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_date }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_remarks"><span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span> Follow-Up Remarks</Label>
                                <Input id="follow_up_remarks" v-model="form.follow_up_remarks" placeholder="Follow-up remarks or notes" :class="inputErrorClass('follow_up_remarks')" @input="clearError('follow_up_remarks')" />
                                <p v-if="form.errors.follow_up_remarks" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_remarks }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <Label for="follow_up_needed" class="flex items-center gap-2 text-sm text-foreground/80">
                                    <span class="text-xs text-muted-foreground font-normal">(Optional)</span>
                                    <Checkbox id="follow_up_needed" v-model:checked="form.follow_up_needed" @update:checked="clearError('follow_up_needed')" />
                                    <span>Follow-up needed</span>
                                </Label>
                                <p v-if="form.errors.follow_up_needed" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.follow_up_needed }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
                    <Button @click="cancel" type="button" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Cancel
                    </Button>
                    <Button v-if="canCreateTraining" type="submit" :disabled="form.processing" class="gap-2">
                        <Save class="h-4 w-4" />
                        Save Training
                    </Button>
                </div>
            </form>
        </div>

        <CooperativeMultiSelectDialog
            v-if="!isCooperativeContext"
            :open="isCooperativeDialogOpen"
            :cooperatives="cooperatives"
            :selected-ids="selectedCoopIds"
            title="Choose Cooperatives"
            description="Search and filter cooperatives, then choose one or more entries for this training record."
            confirm-label="Confirm"
            cancel-label="Cancel"
            @update:open="(value) => isCooperativeDialogOpen = value"
            @confirm="syncSelectedCooperatives"
        />
    </AppLayout>
</template>
