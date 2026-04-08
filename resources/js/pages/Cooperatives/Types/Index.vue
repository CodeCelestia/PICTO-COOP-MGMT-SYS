<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { confirmAction } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

interface CooperativeTypeNode {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    level: 'region' | 'province' | 'municipality';
    parent_id: number | null;
    sort_order: number | null;
    children?: CooperativeTypeNode[];
}

interface CooperativeTypeRow extends CooperativeTypeNode {
    depth: number;
}

interface Props {
    types: CooperativeTypeNode[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Cooperative Types',
        href: '/cooperative-types',
    },
];

const page = usePage();
const errors = computed<Record<string, string>>(() => (page.props.errors as Record<string, string>) || {});
const flash = computed<Record<string, string | undefined>>(() => (page.props.flash as Record<string, string | undefined>) || {});

const allRows = computed<CooperativeTypeRow[]>(() => {
    const rows: CooperativeTypeRow[] = [];

    const pushNode = (node: CooperativeTypeNode, depth: number) => {
        rows.push({ ...node, depth });
        node.children?.forEach((child) => pushNode(child, depth + 1));
    };

    props.types.forEach((region) => pushNode(region, 0));
    return rows;
});

const form = ref({
    id: null as number | null,
    name: '',
    slug: '',
    description: '',
    level: 'region' as 'region' | 'province' | 'municipality',
    parent_id: '',
    sort_order: '',
});

const isEditing = computed(() => form.value.id !== null);

const parentOptions = computed(() => {
    if (form.value.level === 'region') return [];
    if (form.value.level === 'province') return allRows.value.filter((row) => row.level === 'region');
    return allRows.value.filter((row) => row.level === 'province');
});

const resetForm = () => {
    form.value = {
        id: null,
        name: '',
        slug: '',
        description: '',
        level: 'region',
        parent_id: '',
        sort_order: '',
    };
};

const slugify = (value: string) =>
    value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

const startCreate = (level: 'region' | 'province' | 'municipality' = 'region', parentId?: number) => {
    resetForm();
    form.value.level = level;
    form.value.parent_id = parentId ? String(parentId) : '';
};

const startEdit = (row: CooperativeTypeRow) => {
    form.value = {
        id: row.id,
        name: row.name,
        slug: row.slug,
        description: row.description || '',
        level: row.level,
        parent_id: row.parent_id ? String(row.parent_id) : '',
        sort_order: row.sort_order !== null ? String(row.sort_order) : '',
    };
};

const submit = () => {
    const payload = {
        name: form.value.name,
        slug: form.value.slug,
        description: form.value.description || null,
        level: form.value.level,
        parent_id: form.value.parent_id ? Number(form.value.parent_id) : null,
        sort_order: form.value.sort_order === '' ? null : Number(form.value.sort_order),
    };

    if (isEditing.value && form.value.id) {
        router.put(`/cooperative-types/${form.value.id}`, payload, { preserveScroll: true });
        return;
    }

    router.post('/cooperative-types', payload, { preserveScroll: true });
};

const removeType = async (row: CooperativeTypeRow) => {
    const confirmed = await confirmAction({
        title: 'Delete cooperative type?',
        text: `Delete ${row.name} and its child hierarchy?`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) return;

    router.delete(`/cooperative-types/${row.id}`, { preserveScroll: true });
};

const levelBadgeClass = (level: string) => {
    if (level === 'region') return 'bg-blue-100 text-blue-800 border-blue-200';
    if (level === 'province') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    return 'bg-amber-100 text-amber-800 border-amber-200';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight">Cooperative Types</h1>
                        <p class="text-sm text-muted-foreground">Manage region, province, and municipality cooperative type hierarchy.</p>
                    </div>
                    <Button class="gap-2" @click="startCreate('region')">
                        <Plus class="h-4 w-4" />
                        Add Region Type
                    </Button>
                </div>
            </div>

            <div v-if="flash.success" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                {{ flash.success }}
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.2fr_2fr]">
                <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
                    <h2 class="mb-4 text-lg font-semibold">{{ isEditing ? 'Edit Type' : 'Add Type' }}</h2>

                    <div class="space-y-4">
                        <div>
                            <Label for="type_name">Name</Label>
                            <Input
                                id="type_name"
                                v-model="form.name"
                                class="mt-1"
                                placeholder="e.g., Agriculture Cooperative"
                                @input="form.slug = slugify(form.name)"
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-600">{{ errors.name }}</p>
                        </div>

                        <div>
                            <Label for="type_slug">Slug</Label>
                            <Input id="type_slug" v-model="form.slug" class="mt-1" placeholder="agriculture-cooperative" />
                            <p v-if="errors.slug" class="mt-1 text-xs text-red-600">{{ errors.slug }}</p>
                        </div>

                        <div>
                            <Label for="type_level">Level</Label>
                            <select
                                id="type_level"
                                v-model="form.level"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="region">Region</option>
                                <option value="province">Province</option>
                                <option value="municipality">Municipality</option>
                            </select>
                            <p v-if="errors.level" class="mt-1 text-xs text-red-600">{{ errors.level }}</p>
                        </div>

                        <div v-if="form.level !== 'region'">
                            <Label for="type_parent">Parent</Label>
                            <select
                                id="type_parent"
                                v-model="form.parent_id"
                                class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Select parent</option>
                                <option v-for="option in parentOptions" :key="option.id" :value="String(option.id)">
                                    {{ option.name }}
                                </option>
                            </select>
                            <p v-if="errors.parent_id" class="mt-1 text-xs text-red-600">{{ errors.parent_id }}</p>
                        </div>

                        <div>
                            <Label for="type_sort">Sort Order</Label>
                            <Input id="type_sort" v-model="form.sort_order" class="mt-1" type="number" min="0" placeholder="0" />
                            <p v-if="errors.sort_order" class="mt-1 text-xs text-red-600">{{ errors.sort_order }}</p>
                        </div>

                        <div>
                            <Label for="type_description">Description</Label>
                            <Textarea id="type_description" v-model="form.description" class="mt-1" rows="3" placeholder="Optional description" />
                            <p v-if="errors.description" class="mt-1 text-xs text-red-600">{{ errors.description }}</p>
                        </div>

                        <div class="flex gap-2">
                            <Button class="gap-2" @click="submit">
                                <Plus v-if="!isEditing" class="h-4 w-4" />
                                <Pencil v-else class="h-4 w-4" />
                                {{ isEditing ? 'Update Type' : 'Create Type' }}
                            </Button>
                            <Button variant="outline" @click="resetForm">Clear</Button>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
                    <h2 class="mb-4 text-lg font-semibold">Type List</h2>

                    <div class="overflow-x-auto">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Level</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Sort</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="row in allRows" :key="row.id">
                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <span class="text-muted-foreground" :style="{ width: `${row.depth * 16}px` }"></span>
                                            <span>{{ row.name }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium capitalize" :class="levelBadgeClass(row.level)">
                                            {{ row.level }}
                                        </span>
                                    </TableCell>
                                    <TableCell class="text-muted-foreground">{{ row.slug }}</TableCell>
                                    <TableCell>{{ row.sort_order ?? 0 }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button size="sm" variant="outline" class="gap-1" @click="startEdit(row)">
                                                <Pencil class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                            <Button
                                                v-if="row.level !== 'municipality'"
                                                size="sm"
                                                variant="outline"
                                                class="gap-1"
                                                @click="startCreate(row.level === 'region' ? 'province' : 'municipality', row.id)"
                                            >
                                                <Plus class="h-3.5 w-3.5" />
                                                Child
                                            </Button>
                                            <Button size="sm" variant="destructive" class="gap-1" @click="removeType(row)">
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Delete
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                                <TableRow v-if="allRows.length === 0">
                                    <TableCell colspan="5" class="py-6 text-center text-sm text-muted-foreground">
                                        No cooperative types yet.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
