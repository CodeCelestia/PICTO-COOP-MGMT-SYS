<script setup lang="ts">
import { Check, CheckSquare, Loader2, Search, Users, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
    role_names: string[];
    member_code?: string | null;
    gender?: string | null;
    date_joined?: string | null;
    status?: string | null;
    first_name?: string | null;
    last_name?: string | null;
}

interface Props {
    open: boolean;
    members: MemberOption[];
    cooperativeId?: string | number | null;
    selectedMemberId?: string | number | null;
    selectedMemberIds?: Array<string | number>;
    multiSelect?: boolean;
    cooperativeName?: string | null;
    title?: string;
    description?: string;
    loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    cooperativeId: null,
    selectedMemberId: null,
    selectedMemberIds: () => [],
    multiSelect: false,
    cooperativeName: null,
    title: 'Select Member',
    description: 'Search and choose a member to assign as officer.',
    loading: false,
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'select', value: MemberOption): void;
    (e: 'confirm', value: MemberOption[]): void;
}>();

const searchQuery = ref('');
const genderFilter = ref('all');
const statusFilter = ref('all');
const draftSelectedIds = ref<string[]>([]);

const normalizeId = (id: string | number) => String(id);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        searchQuery.value = '';
        genderFilter.value = 'all';
        statusFilter.value = 'all';
        draftSelectedIds.value = props.multiSelect
            ? props.selectedMemberIds.map(normalizeId)
            : [normalizeId(props.selectedMemberId ?? '')].filter(Boolean);
    }
);

const sortedMembers = computed(() => [...props.members].sort((a, b) => a.name.localeCompare(b.name)));

const filteredMembers = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();
    const cooperativeId = props.cooperativeId == null || props.cooperativeId === '' ? null : String(props.cooperativeId);

    return sortedMembers.value.filter((member) => {
        if (cooperativeId && String(member.coop_id) !== cooperativeId) {
            return false;
        }

        const matchesSearch = query === ''
            || member.name.toLowerCase().includes(query)
            || String(member.id).includes(query)
            || (member.member_code || '').toLowerCase().includes(query)
            || (member.first_name || '').toLowerCase().includes(query)
            || (member.last_name || '').toLowerCase().includes(query)
            || member.role_names.some((roleName) => roleName.toLowerCase().includes(query));

        if (!matchesSearch) {
            return false;
        }

        const matchesGender = genderFilter.value === 'all' || (member.gender || '') === genderFilter.value;
        if (!matchesGender) {
            return false;
        }

        const matchesStatus = statusFilter.value === 'all' || (member.status || 'Active') === statusFilter.value;
        return matchesStatus;
    });
});

const selectedMemberIdNormalized = computed(() => String(props.selectedMemberId ?? ''));
const selectedCount = computed(() => draftSelectedIds.value.length);
const selectedCountLabel = computed(() => `${selectedCount.value} selected`);
const allVisibleSelected = computed(() => filteredMembers.value.length > 0 && filteredMembers.value.every((member) => draftSelectedIds.value.includes(normalizeId(member.id))));
const someVisibleSelected = computed(() => filteredMembers.value.some((member) => draftSelectedIds.value.includes(normalizeId(member.id))));

const closeDialog = () => {
    emit('update:open', false);
};

const getInitials = (name?: string | null) => {
    if (!name) return '--';

    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '--';
    if (parts.length === 1) return (parts[0][0] || '-').toUpperCase();
    return ((parts[0][0] || '-') + (parts[1][0] || '-')).toUpperCase();
};

const formatDateJoined = (dateJoined?: string | null) => {
    if (!dateJoined) {
        return '—';
    }

    const parsedDate = new Date(dateJoined);
    if (Number.isNaN(parsedDate.getTime())) {
        return '—';
    }

    return parsedDate.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isSelected = (memberId: string | number) => draftSelectedIds.value.includes(normalizeId(memberId));

const toggleMember = (member: MemberOption) => {
    if (!props.multiSelect) {
        emit('select', member);
        emit('update:open', false);
        return;
    }

    const id = normalizeId(member.id);
    if (draftSelectedIds.value.includes(id)) {
        draftSelectedIds.value = draftSelectedIds.value.filter((selectedId) => selectedId !== id);
        return;
    }

    draftSelectedIds.value = [...draftSelectedIds.value, id];
};

const toggleAllVisible = (checked: boolean | 'indeterminate') => {
    if (!props.multiSelect) {
        return;
    }

    const visibleIds = filteredMembers.value.map((member) => normalizeId(member.id));
    const visibleSet = new Set(visibleIds);

    if (checked === true) {
        draftSelectedIds.value = Array.from(new Set([...draftSelectedIds.value, ...visibleIds]));
        return;
    }

    draftSelectedIds.value = draftSelectedIds.value.filter((id) => !visibleSet.has(id));
};

const confirmSelection = () => {
    if (props.multiSelect) {
        const selectedMembers = sortedMembers.value.filter((member) => draftSelectedIds.value.includes(normalizeId(member.id)));
        emit('confirm', selectedMembers);
        emit('update:open', false);
        return;
    }

    const selectedMember = sortedMembers.value.find((member) => normalizeId(member.id) === selectedMemberIdNormalized.value) || filteredMembers.value[0];
    if (selectedMember) {
        emit('select', selectedMember);
    }
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="flex max-h-[70vh] w-full max-w-[55vw] flex-col overflow-hidden xl:max-w-[70vw] 2xl:max-w-[55vw]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Users class="h-5 w-5" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>
                    {{ description }}
                    <span v-if="cooperativeName"> from {{ cooperativeName }}</span>
                </DialogDescription>
            </DialogHeader>

            <div class="grid grid-cols-1 gap-2 border-b pb-3 lg:grid-cols-[minmax(0,1fr)_140px_140px]">
                <div class="relative min-w-0">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="searchQuery" placeholder="Search by name, code, role, or ID..." class="h-9 pl-9" />
                </div>

                <Select v-model="genderFilter">
                    <SelectTrigger class="h-9 w-full">
                        <SelectValue placeholder="All Genders" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Genders</SelectItem>
                        <SelectItem value="Male">Male</SelectItem>
                        <SelectItem value="Female">Female</SelectItem>
                        <SelectItem value="Other">Other</SelectItem>
                    </SelectContent>
                </Select>

                <Select v-model="statusFilter">
                    <SelectTrigger class="h-9 w-full">
                        <SelectValue placeholder="All Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All Status</SelectItem>
                        <SelectItem value="Active">Active</SelectItem>
                        <SelectItem value="Suspended">Suspended</SelectItem>
                        <SelectItem value="Resigned">Resigned</SelectItem>
                        <SelectItem value="Deceased">Deceased</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div v-if="multiSelect" class="flex flex-wrap items-center justify-between gap-2 border-b py-2">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <CheckSquare class="h-4 w-4" />
                    <span>{{ selectedCountLabel }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" size="sm" :disabled="filteredMembers.length === 0" @click="toggleAllVisible(true)">
                        Select Visible
                    </Button>
                    <Button type="button" variant="outline" size="sm" :disabled="!someVisibleSelected" @click="toggleAllVisible(false)">
                        Clear Visible
                    </Button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div class="grid grid-cols-[44px_minmax(0,1.4fr)_90px_minmax(0,1fr)_130px] border-b bg-muted/60 px-4 py-2 sticky top-0 z-10">
                        <span class="text-xs font-semibold uppercase text-muted-foreground">
                            <span v-if="multiSelect">Select</span>
                            <span v-else>#</span>
                        </span>
                    <span class="text-xs font-semibold uppercase text-muted-foreground">Member</span>
                    <span class="text-xs font-semibold uppercase text-center text-muted-foreground">Gender</span>
                    <span class="text-xs font-semibold uppercase text-center text-muted-foreground">Role</span>
                    <span class="text-xs font-semibold uppercase text-center text-muted-foreground">Date Joined</span>
                </div>

                <div v-if="loading" class="flex items-center justify-center py-12">
                    <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
                </div>

                <div v-else>
                    <div v-if="filteredMembers.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
                        <Users class="mb-2 h-8 w-8 opacity-30" />
                        <p class="text-sm">No members found</p>
                    </div>

                    <button
                        v-for="(member, index) in filteredMembers"
                        :key="member.id"
                        type="button"
                        @click="toggleMember(member)"
                        class="group grid w-full grid-cols-[44px_minmax(0,1.4fr)_90px_minmax(0,1fr)_130px] items-center border-b px-4 py-3 text-left transition-colors duration-150 last:border-0"
                        :class="isSelected(member.id)
                            ? 'bg-blue-700 text-white hover:bg-blue-900 dark:bg-blue-900 dark:hover:bg-blue-900'
                            : index % 2 === 0
                                ? 'bg-background hover:bg-blue-500 dark:hover:bg-blue-900/40'
                                : 'bg-muted/30 hover:bg-blue-500 dark:hover:bg-blue-900/40'"
                    >
                        <div class="flex items-center justify-center">
                            <Checkbox
                                v-if="multiSelect"
                                :model-value="isSelected(member.id)"
                                @click.stop
                                @update:model-value="() => toggleMember(member)"
                                class="h-5 w-5 data-[state=checked]:border-blue-600 data-[state=checked]:bg-blue-600 data-[state=checked]:text-white"
                            />
                            <Check v-else-if="selectedMemberIdNormalized === String(member.id)" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            <span v-else class="text-[10px] text-muted-foreground">#</span>
                        </div>

                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300" :class="isSelected(member.id) ? 'bg-blue-500 text-white dark:bg-blue-600 dark:text-blue-100' : ''">
                                {{ getInitials(member.name) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium" :class="isSelected(member.id) ? 'text-white' : ''">{{ member.name }}</p>
                                <p class="truncate text-xs" :class="isSelected(member.id) ? 'text-blue-200' : 'text-muted-foreground'">
                                    {{ member.member_code || 'No member code available' }}
                                </p>
                            </div>
                        </div>

                        <div class="text-center text-xs" :class="isSelected(member.id) ? 'text-blue-200' : 'text-muted-foreground'">
                            {{ member.gender || '—' }}
                        </div>

                        <div class="min-w-0 text-center text-xs" :class="isSelected(member.id) ? 'text-blue-200' : 'text-muted-foreground'">
                            <span class="block truncate">
                                {{ member.role_names.length > 0 ? member.role_names.join(', ') : 'No roles assigned' }}
                            </span>
                        </div>

                        <div class="text-center text-xs" :class="isSelected(member.id) ? 'text-blue-200' : 'text-muted-foreground'">
                            {{ formatDateJoined(member.date_joined) }}
                        </div>
                    </button>
                </div>
            </div>

            <DialogFooter class="gap-2 sm:justify-between">
                <p class="text-xs text-muted-foreground">
                    {{ filteredMembers.length }} member{{ filteredMembers.length === 1 ? '' : 's' }} found
                </p>
                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="closeDialog">
                        Cancel
                    </Button>
                    <Button type="button" @click="confirmSelection">
                        {{ multiSelect ? 'Apply Selection' : 'Select Member' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>