<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import TrainingListPanel from '@/components/panels/TrainingListPanel.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Training {
    id: number;
    coop_id: number;
    cooperatives_participating_count?: number;
    title: string;
    date_conducted: string | null;
    facilitator: string | null;
    target_group: string;
    status: string;
    cooperative: Cooperative;
}

interface Props {
    trainings: {
        data: Training[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: Cooperative[];
    filters: {
        search?: string;
        status?: string;
        target_group?: string;
        coop_id?: string;
        per_page?: string;
    };
}

defineProps<Props>();
</script>

<template>
    <AppLayout>
        <TrainingListPanel
            :trainings="trainings"
            :cooperatives="cooperatives"
            :filters="filters"
            :show-view-action-in-rows="true"
        />
    </AppLayout>
</template>
