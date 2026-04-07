<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import MemberListPanel from '@/components/panels/MemberListPanel.vue';
import type { Member, CooperativeSummary } from '@/types/models';

interface Props {
    members: {
        data: Member[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    cooperatives: CooperativeSummary[];
    filters: {
        search?: string;
        membership_status?: string;
        per_page?: string;
    };
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <MemberListPanel :members="members" :filters="filters" />
    </AppLayout>
</template>
