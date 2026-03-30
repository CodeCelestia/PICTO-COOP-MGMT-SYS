<script setup lang='ts'>
import AppLayout from '@/layouts/AppLayout.vue';
import CooperativeForm from '@/components/Cooperatives/CooperativeForm.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Cooperative {
    id: number;
    name: string;
    registration_number: string;
    coop_type: string;
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

const props = defineProps<{
    cooperative: Cooperative;
    statusHistory: CooperativeStatusHistory[];
}>();

const cancel = () => {
    router.get('/cooperatives');
};

const actionUrl = computed(() => `/cooperatives/${props.cooperative.id}`);
</script>

<template>
    <AppLayout>
        <div class='p-6'>
            <div class='mb-6'>
                <h1 class='text-3xl font-bold text-gray-900'>Edit Cooperative</h1>
                <p class='mt-1 text-sm text-gray-500'>Update cooperative master profile</p>
            </div>

            <CooperativeForm
                :cooperative='props.cooperative'
                :action='actionUrl'
                method='put'
                :onCancel='cancel'
            />

            <div class='mt-8'>
                <h2 class='text-lg font-semibold text-gray-900'>Status History</h2>
                <div class='mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm'>
                    <div v-if='props.statusHistory.length === 0' class='text-sm text-gray-500'>No status change history available.</div>
                    <ul v-else class='space-y-3'>
                        <li v-for='history in props.statusHistory' :key='history.id' class='rounded border border-gray-200 p-3'>
                            <div class='text-sm text-gray-700'><strong>From:</strong> {{ history.previous_status || 'N/A' }} ? <strong>To:</strong> {{ history.new_status }}</div>
                            <div class='text-xs text-gray-500'>{{ history.changed_at || 'Unknown date' }} by {{ history.changed_by || 'Unknown' }}</div>
                            <div class='mt-1 text-xs text-gray-600'>Reason: {{ history.change_reason || 'None' }}</div>
                            <div class='text-xs text-gray-600'>Remarks: {{ history.remarks || 'None' }}</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
