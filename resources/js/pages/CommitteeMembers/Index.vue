<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import CommitteeListPanel from '@/components/panels/CommitteeListPanel.vue';
import type { CommitteeMember, CooperativeSummary } from '@/types/models';

interface Props {
    committeeMembers: {
        data: CommitteeMember[];
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
        <CommitteeListPanel
            :committee-members="committeeMembers"
            :cooperatives="cooperatives"
            :filters="filters"
        />
    </AppLayout>
</template>
