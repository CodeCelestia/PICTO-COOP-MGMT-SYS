<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { runBulkDelete, useBulkSelection, type CheckedState } from '@/composables/useBulkSelection';
import { confirmAction } from '@/lib/alerts';

interface MemberSummary {
    id: number;
    full_name: string;
}

interface ServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string | null;
    amount: string | number | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
    member?: MemberSummary | null;
}

const props = defineProps<{
    services: ServiceAvailed[];
    showMember?: boolean;
    enableBulkDelete?: boolean;
    memberId?: number | null;
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canBulkDelete = computed(() => Boolean(props.enableBulkDelete) && permissions.value.includes('delete members-profile'));

const visibleServices = computed(() => props.services);
const {
    clearSelection,
    isSelected,
    selectedCount,
    selectedIds,
    toggleOne,
} = useBulkSelection(visibleServices);

const resolveMemberId = (service: ServiceAvailed): number | null => {
    if (typeof service.member?.id === 'number') {
        return service.member.id;
    }

    if (typeof props.memberId === 'number') {
        return props.memberId;
    }

    return null;
};

const selectableServices = computed(() => props.services.filter((service) => resolveMemberId(service) !== null));

const allSelectableSelected = computed(() => {
    if (!selectableServices.value.length) {
        return false;
    }

    return selectableServices.value.every((service) => isSelected(service.id));
});

const toggleAllSelectable = (checked: CheckedState) => {
    if (checked !== true) {
        clearSelection();
        return;
    }

    const nextIds = new Set(selectedIds.value);
    selectableServices.value.forEach((service) => nextIds.add(service.id));
    selectedIds.value = Array.from(nextIds);
};

const bulkDeleteServices = async () => {
    if (!selectedCount.value || !canBulkDelete.value) return;

    const selectedMemberByService = new Map<number, number>();
    selectedIds.value.forEach((serviceId) => {
        const service = props.services.find((entry) => entry.id === serviceId);
        if (!service) return;

        const memberId = resolveMemberId(service);
        if (memberId === null) return;

        selectedMemberByService.set(service.id, memberId);
    });

    if (!selectedMemberByService.size) {
        return;
    }

    const confirmed = await confirmAction({
        title: 'Delete selected services?',
        text: `Delete ${selectedMemberByService.size} selected service record(s)? This action cannot be undone.`,
        confirmButtonText: 'Delete selected',
    });

    if (!confirmed) return;

    await runBulkDelete(Array.from(selectedMemberByService.keys()), (serviceId) => {
        const memberId = selectedMemberByService.get(serviceId);
        return `/members/${memberId}/services-availed/${serviceId}`;
    });

    clearSelection();
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatAmount = (amount: string | number | null) => {
    if (amount === null || amount === undefined || amount === '') return 'N/A';
    const value = Number(amount);
    if (Number.isNaN(value)) return String(amount);
    return value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <section class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
        <div v-if="canBulkDelete && selectedCount > 0" class="border-b border-border/70 px-4 py-3 sm:px-5">
            <div class="flex flex-wrap items-center gap-2 rounded-md border border-border/70 bg-muted/40 px-2 py-1">
                <span class="text-xs font-medium text-foreground">{{ selectedCount }} selected</span>
                <Button size="sm" variant="destructive" class="h-8 gap-1.5" @click="bulkDeleteServices">
                    <Trash2 class="h-3.5 w-3.5" />
                    Delete Selected
                </Button>
                <Button size="sm" variant="outline" class="h-8" @click="clearSelection">
                    Clear
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead v-if="canBulkDelete" class="w-12">
                            <Checkbox
                                :model-value="allSelectableSelected"
                                :disabled="selectableServices.length === 0"
                                aria-label="Select all service records"
                                @update:model-value="toggleAllSelectable"
                            />
                        </TableHead>
                        <TableHead v-if="showMember">Member</TableHead>
                        <TableHead>Service</TableHead>
                        <TableHead>Details</TableHead>
                        <TableHead>Date Availed</TableHead>
                        <TableHead>Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Reference No</TableHead>
                        <TableHead>Recorded By</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="services.length === 0">
                        <TableCell :colspan="(showMember ? 8 : 7) + (canBulkDelete ? 1 : 0)" class="py-10 text-center text-muted-foreground">
                            No services availed recorded yet.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="service in services" :key="service.id">
                        <TableCell v-if="canBulkDelete" class="w-12">
                            <Checkbox
                                :model-value="isSelected(service.id)"
                                :disabled="resolveMemberId(service) === null"
                                :aria-label="`Select ${service.service_type}`"
                                @update:model-value="(checked) => toggleOne(service.id, checked)"
                            />
                        </TableCell>
                        <TableCell v-if="showMember" class="text-sm text-foreground">
                            {{ service.member?.full_name || 'N/A' }}
                        </TableCell>
                        <TableCell class="text-sm text-foreground">{{ service.service_type }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.service_detail || 'N/A' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(service.date_availed) }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ formatAmount(service.amount) }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.status }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.reference_no || 'N/A' }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ service.recorded_by || 'N/A' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </section>
</template>
