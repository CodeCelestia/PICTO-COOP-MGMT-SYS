<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Building2 } from 'lucide-vue-next';
import { computed } from 'vue';
import CooperativeForm from '@/components/Cooperatives/CooperativeForm.vue';
import { useCoopLabel } from '@/composables/useCoopLabel';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface CooperativeTypeOption {
    id: number;
    name: string;
}

defineProps<{
    cooperativeTypes: CooperativeTypeOption[];
}>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreateCoop = computed(() => permissions.value.includes('create coop-master-profile'));
const { cooperativeLabel } = useCoopLabel();

const cancel = () => {
    router.get('/cooperatives');
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <Building2 class="h-5 w-5" />
                            </div>
                            <div>
                                <Badge variant="outline" class="mb-2">{{ cooperativeLabel }}</Badge>
                                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Register New Cooperative</h1>
                                <p class="mt-1 text-sm text-muted-foreground">Create a new cooperative master profile</p>
                            </div>
                        </div>
                        <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <CooperativeForm
                action="/cooperatives"
                method="post"
                :cooperativeTypes="cooperativeTypes"
                :onCancel="cancel"
                :canSubmit="canCreateCoop"
            />
        </div>
    </AppLayout>
</template>
