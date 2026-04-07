<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Building2 } from 'lucide-vue-next';
import { computed } from 'vue';
import CooperativeForm from '@/components/Cooperatives/CooperativeForm.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreateCoop = computed(() => permissions.value.includes('create coop-master-profile'));

const cancel = () => {
    router.get('/cooperatives');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6 lg:p-8">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 sm:p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <Badge variant="outline" class="mb-2">Cooperatives</Badge>
                            <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Register New Cooperative</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Create a new cooperative master profile</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <CooperativeForm
                action="/cooperatives"
                method="post"
                :onCancel="cancel"
                :canSubmit="canCreateCoop"
            />
        </div>
    </AppLayout>
</template>
