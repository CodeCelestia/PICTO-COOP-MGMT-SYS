<script setup lang="ts">
import { router, Link, usePage } from '@inertiajs/vue3';
import { Sparkles, Plus, Pencil, Trash2, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { runBulkDelete, useBulkSelection } from '@/composables/useBulkSelection';
import AppLayout from '@/layouts/AppLayout.vue';
import FilterPanel from '@/components/FilterPanel.vue';
import { confirmAction } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface TrainingOption {
    id: number;
    title: string;
    coop_id: number;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface SkillInventory {
    id: number;
    member_id: number;
    coop_id: number;
    skill_name: string;
    proficiency_level: string;
    training_id: number;
    date_acquired: string | null;
    last_updated: string | null;
    remarks: string | null;
    member: {
        id: number;
        full_name: string;
    };
    training: {
        id: number;
        title: string;
    };
    cooperative: Cooperative;
}

interface Props {
    skills: {
        data: SkillInventory[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    trainings: TrainingOption[];
    members: MemberOption[];
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        proficiency_level?: string;
        training_id?: string;
        coop_id?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();

const filters = computed(() => props.filters);

const page = usePage();
const auth = computed(() => page.props.auth as { permissions?: string[] } | undefined);
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreate = computed(() => permissions.value.includes('create training-&-capacity'));
const canEdit = computed(() => permissions.value.includes('update training-&-capacity'));
const canDelete = computed(() => permissions.value.includes('delete training-&-capacity'));
const canBulkDelete = computed(() => canDelete.value);
const showActions = computed(() => canEdit.value || canDelete.value);

const search = ref(props.filters.search || '');
const proficiencyLevel = ref(props.filters.proficiency_level || 'all');
const trainingId = ref(props.filters.training_id || 'all');
const coopId = ref(props.filters.coop_id || 'all');
const presetPageSizes = ['5', '15', '30'];
const initialPerPageRaw = props.filters.per_page || String(props.skills.per_page || 15);
const perPage = ref(presetPageSizes.includes(initialPerPageRaw) ? initialPerPageRaw : 'custom');
const customPerPage = ref(presetPageSizes.includes(initialPerPageRaw) ? '' : initialPerPageRaw);

const resolvedPerPage = () => {
    if (perPage.value !== 'custom') return perPage.value;

    const parsed = Number(customPerPage.value);
    if (!Number.isInteger(parsed) || parsed < 1) return '15';

    return String(Math.min(parsed, 500));
};

const proficiencyOptions = ['Beginner', 'Intermediate', 'Advanced'];

const applyFilters = () => {
    router.get('/skill-inventories', {
        search: search.value,
        proficiency_level: proficiencyLevel.value === 'all' ? '' : proficiencyLevel.value,
        training_id: trainingId.value === 'all' ? '' : trainingId.value,
        coop_id: coopId.value === 'all' ? '' : coopId.value,
        per_page: resolvedPerPage(),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    search.value = '';
    proficiencyLevel.value = 'all';
    trainingId.value = 'all';
    coopId.value = 'all';
    perPage.value = '15';
    customPerPage.value = '';
    router.get('/skill-inventories');
};

const deleteSkill = async (skill: SkillInventory) => {
    if (!canDelete.value) return;
    const confirmed = await confirmAction({
        title: 'Delete skill record?',
        text: 'This action cannot be undone.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/skill-inventories/${skill.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const visibleSkills = computed(() => props.skills.data);

const {
    allVisibleSelected,
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleAll,
    toggleOne,
} = useBulkSelection(visibleSkills);

const bulkDeleteSkills = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const confirmed = await confirmAction({
        title: 'Delete selected skill records?',
        text: `Delete ${selectedCount.value} selected skill record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    const idsToDelete = [...selectedIds.value];
    await runBulkDelete(idsToDelete, (id) => `/skill-inventories/${id}`);
    clearSelection();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card/95 p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Skills Inventory</h1>
                    <p class="text-sm text-muted-foreground">Track skills and proficiency for cooperative members</p>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <div v-if="canBulkDelete && selectedCount > 0" class="flex items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                        <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                        <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteSkills">
                            <Trash2 class="h-3.5 w-3.5" />
                            Delete Selected
                        </Button>
                        <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                            Clear
                        </Button>
                    </div>
                    <Link href="/trainings" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
                        View Trainings
                    </Link>
                    <Link href="/training-participants" class="text-sm font-medium text-primary underline-offset-4 hover:underline">
                        View Participants
                    </Link>
                    <Link v-if="canCreate" href="/skill-inventories/create">
                        <Button class="gap-2">
                            <Plus class="h-4 w-4" />
                            Add Skill
                        </Button>
                    </Link>
                </div>
                </div>

                <FilterPanel
                    title="Filters"
                    description="Show skills inventory filters to refine results."
                    showLabel="Show filters"
                    hideLabel="Hide filters"
                >
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[repeat(auto-fit,minmax(220px,1fr))]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Search</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-muted-foreground" />
                            <Input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                placeholder="Skill or member..."
                                class="pl-9"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Cooperative</label>
                        <Select v-model="coopId">
                            <SelectTrigger id="coop_filter">
                                <SelectValue placeholder="All Cooperatives" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Cooperatives</SelectItem>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                    {{ coop.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Training</label>
                        <Select v-model="trainingId">
                            <SelectTrigger id="training_filter">
                                <SelectValue placeholder="All Trainings" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Trainings</SelectItem>
                                <SelectItem v-for="training in trainings" :key="training.id" :value="training.id.toString()">
                                    {{ training.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Proficiency</label>
                        <Select v-model="proficiencyLevel">
                            <SelectTrigger id="proficiency_filter">
                                <SelectValue placeholder="All Levels" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Levels</SelectItem>
                                <SelectItem v-for="option in proficiencyOptions" :key="option" :value="option">
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground/80">Rows Per Page</label>
                        <div class="flex gap-2">
                            <Select v-model="perPage">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select size" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="5">5</SelectItem>
                                    <SelectItem value="15">15</SelectItem>
                                    <SelectItem value="30">30</SelectItem>
                                    <SelectItem value="custom">Custom</SelectItem>
                                </SelectContent>
                            </Select>
                            <Input
                                v-if="perPage === 'custom'"
                                v-model="customPerPage"
                                type="number"
                                min="1"
                                max="500"
                                placeholder="Enter"
                                class="w-28"
                            />
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <Button @click="applyFilters" class="gap-2">
                        <Search class="h-4 w-4" />
                        Apply Filters
                    </Button>
                    <Button @click="resetFilters" variant="outline">Clear Filters</Button>
                </div>
            </FilterPanel>
            </div>

            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="allVisibleSelected"
                                    :disabled="skills.data.length === 0"
                                    aria-label="Select all skill records"
                                    @update:model-value="toggleAll"
                                />
                            </TableHead>
                            <TableHead>Skill</TableHead>
                            <TableHead>Member</TableHead>
                            <TableHead>Training</TableHead>
                            <TableHead>Proficiency</TableHead>
                            <TableHead>Last Updated</TableHead>
                            <TableHead v-if="showActions" class="text-center">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="skills.data.length === 0">
                            <TableCell :colspan="(showActions ? 6 : 5) + (canBulkDelete ? 1 : 0)" class="text-center text-muted-foreground">
                                No skill inventory records found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="skill in skills.data" :key="skill.id">
                            <TableCell v-if="canBulkDelete" class="w-12">
                                <Checkbox
                                    :model-value="isSelected(skill.id)"
                                    :aria-label="`Select ${skill.skill_name}`"
                                    @update:model-value="(checked) => toggleOne(skill.id, checked)"
                                />
                            </TableCell>
                            <TableCell>
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <Sparkles class="h-4 w-4" />
                                    </div>
                                    <div>
                                        <div class="font-medium text-foreground">{{ skill.skill_name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ skill.cooperative.name }}</div>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ skill.member.full_name }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ skill.training.title }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ skill.proficiency_level }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatDate(skill.last_updated) }}</TableCell>
                            <TableCell v-if="showActions" class="text-center">
                                <div class="flex flex-wrap justify-center gap-2">
                                    <Link v-if="canEdit" :href="`/skill-inventories/${skill.id}/edit`">
                                        <Button variant="ghost" size="sm" class="gap-2">
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="canDelete"
                                        @click="deleteSkill(skill)"
                                        variant="ghost"
                                        size="sm"
                                        class="gap-2 text-destructive hover:text-destructive"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                        Delete
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                    </Table>
                </div>

                <div v-if="skills.last_page > 1" class="border-t border-border px-4 py-4 sm:px-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ (skills.current_page - 1) * skills.per_page + 1 }} to
                            {{ Math.min(skills.current_page * skills.per_page, skills.total) }} of
                            {{ skills.total }} skills
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="page in skills.last_page"
                                :key="page"
                                variant="outline"
                                size="sm"
                                :disabled="page === skills.current_page"
                                @click="router.get('/skill-inventories', { ...filters, page }, { preserveScroll: true, preserveState: true })"
                            >
                                {{ page }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
