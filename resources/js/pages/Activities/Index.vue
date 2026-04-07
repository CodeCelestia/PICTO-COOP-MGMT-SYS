<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import ActivityListPanel from '@/components/panels/ActivityListPanel.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Officer {
    id: number;
    member: {
        full_name: string;
    };
}

interface Activity {
    id: number;
    coop_id: number;
    title: string;
    description: string | null;
    category: string;
    date_started: string | null;
    date_ended: string | null;
    status: string;
    responsible_officer_id: number | null;
    funding_source: string | null;
    cooperative: Cooperative;
    responsible_officer?: Officer | null;
}

interface Props {
    activities: {
        data: Activity[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        category?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <ActivityListPanel
            :activities="activities"
            :cooperatives="cooperatives"
            :filters="filters"
        />
    </AppLayout>
</template>
