<script setup lang='ts'>
import { router, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';
import CooperativeForm from '@/components/Cooperatives/CooperativeForm.vue';
import { useCoopLabel } from '@/composables/useCoopLabel';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    types?: Array<{ id: number; name: string }>;
    date_established: string;
    address: string;
    province: string;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended';
    accreditation_status: string | null;
    accreditation_date: string | null;
    accreditations?: Array<{
        id?: number;
        level: string;
        date_granted: string;
        valid_until?: string | null;
        issuing_body?: string | null;
        remarks?: string | null;
    }>;
}

interface CooperativeStatusHistory {
    id: number;
    previous_status: string | null;
    new_status: string;
    change_reason: string | null;
    changed_by: string | null;
    changed_at: string | null;
    remarks: string | null;
}

interface CooperativeTypeOption {
    id: number;
    name: string;
}

const props = defineProps<{
    cooperative: Cooperative;
    cooperativeTypes: CooperativeTypeOption[];
    statusHistory: CooperativeStatusHistory[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canUpdateCoop = computed(() => permissions.value.includes('update coop-master-profile'));
const { cooperativeLabel } = useCoopLabel();

const cancel = () => {
    router.get('/cooperatives');
};

const actionUrl = computed(() => `/cooperatives/${props.cooperative.id}`);
</script>

<template>
    <AppLayout>
        <div class='space-y-6 p-4 sm:p-6 lg:p-8'>
            <Card class='border-border/80 bg-card/95 shadow-sm'>
                <CardContent class='p-5 sm:p-6'>
                    <div class='flex items-start gap-4'>
                        <div class='flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary'>
                            <Building2 class='h-5 w-5' />
                        </div>
                        <div>
                            <Badge variant='outline' class='mb-2'>{{ cooperativeLabel }}</Badge>
                            <h1 class='text-2xl font-semibold tracking-tight text-foreground sm:text-3xl'>Edit Cooperative</h1>
                            <p class='mt-1 text-sm text-muted-foreground'>Update cooperative master profile</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <CooperativeForm
                :cooperative='props.cooperative'
                :accreditations='props.cooperative.accreditations'
                :action='actionUrl'
                method='put'
                :cooperativeTypes='props.cooperativeTypes'
                :onCancel='cancel'
                :canSubmit="canUpdateCoop"
            />

            <Card class='border-border/80 bg-card shadow-sm'>
                <CardHeader>
                    <CardTitle class='text-lg font-semibold text-foreground'>Status History</CardTitle>
                    <CardDescription>Timeline of cooperative status updates.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if='props.statusHistory.length === 0' class='rounded-lg border border-dashed border-border px-4 py-6 text-sm text-muted-foreground'>
                        No status change history available.
                    </div>
                    <ul v-else class='space-y-3'>
                        <li v-for='history in props.statusHistory' :key='history.id' class='rounded-lg border border-border bg-muted/40 p-4'>
                            <div class='flex flex-wrap items-center gap-2 text-sm text-foreground'>
                                <span><strong>From:</strong> {{ history.previous_status || 'N/A' }}</span>
                                <span class='text-muted-foreground'>to</span>
                                <Badge variant='outline'>{{ history.new_status }}</Badge>
                            </div>
                            <div class='mt-2 text-xs text-muted-foreground'>{{ history.changed_at || 'Unknown date' }} by {{ history.changed_by || 'Unknown' }}</div>
                            <div class='mt-2 text-xs text-muted-foreground'>Reason: {{ history.change_reason || 'None' }}</div>
                            <div class='text-xs text-muted-foreground'>Remarks: {{ history.remarks || 'None' }}</div>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
