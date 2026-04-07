<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import ServiceAvailedListPanel from '@/components/panels/ServiceAvailedListPanel.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    cooperative: Cooperative;
}

interface ServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string | null;
    amount: string | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
}

interface Props {
    member: Member;
    services: ServiceAvailed[];
}

const props = defineProps<Props>();
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Services Availed</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ member.first_name }} {{ member.last_name }} · {{ member.cooperative.name }}
                    </p>
                </div>
                <Link :href="`/members/${member.id}`">
                    <Button variant="outline">Back to Member</Button>
                </Link>
            </div>
            </section>

            <ServiceAvailedListPanel :services="services" />
        </div>
    </AppLayout>
</template>
