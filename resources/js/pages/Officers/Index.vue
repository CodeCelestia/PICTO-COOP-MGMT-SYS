<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import OfficerListPanel from '@/components/panels/OfficerListPanel.vue';
import type { CooperativeSummary, Officer } from '@/types/models';

interface Props {
    officers: {
        data: Officer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: CooperativeSummary[];
    filters: {
        search?: string;
        coop_id?: string;
        status?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <OfficerListPanel :officers="officers" :cooperatives="cooperatives" :filters="filters" />
    </AppLayout>
</template>
