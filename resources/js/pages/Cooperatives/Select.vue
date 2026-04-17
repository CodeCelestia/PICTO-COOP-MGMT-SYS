<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { useCoopLabel } from '@/composables/useCoopLabel';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface CooperativeOption {
    id: number;
    name: string;
}

const props = defineProps<{
    title: string;
    description: string;
    targetUrl: string;
    cooperatives: CooperativeOption[];
}>();
const { noCooperativesFoundLabel } = useCoopLabel();

const goToTarget = (coopId: number) => {
    router.get(`${props.targetUrl}?coop_id=${coopId}`);
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <Card class="border-border bg-card">
                <CardHeader class="space-y-2">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-foreground">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <CardTitle class="text-xl font-semibold text-foreground">{{ title }}</CardTitle>
                            <p class="text-sm text-muted-foreground">{{ description }}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                        <div class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cooperative</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="cooperatives.length === 0">
                                        <TableCell class="py-10 text-center text-muted-foreground">
                                            {{ noCooperativesFoundLabel }}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow
                                        v-for="coop in cooperatives"
                                        :key="coop.id"
                                        class="cursor-pointer"
                                        @click="goToTarget(coop.id)"
                                    >
                                        <TableCell class="font-medium text-foreground">
                                            {{ coop.name }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
