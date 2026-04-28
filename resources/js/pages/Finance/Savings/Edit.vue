<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useCreateBack } from '@/composables/useCreateBack';
import { computed } from 'vue';

const props = defineProps<{
    savings: {
        id: number;
        interest_rate: string;
        account_status: string;
        coop_id?: number;
        cooperative?: { name?: string } | null;
    };
}>();

const isFromCoopContext = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const cooperativeName = computed(() => props.savings.cooperative?.name || 'Cooperative');

const fallbackHref = computed(() => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        return `/finance/savings/${props.savings.id}?coop_id=${coopIdFromUrl.value}`;
    }
    return `/finance/savings/${props.savings.id}`;
});

const { goBack, returnToHref } = useCreateBack({ fallbackHref });

const form = useForm({
    return_to: returnToHref.value,
    interest_rate: Number(props.savings.interest_rate),
    account_status: props.savings.account_status,
});

const handleBackClick = () => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        window.location.href = `/cooperatives/${coopIdFromUrl.value}?tab=members`;
    } else {
        goBack();
    }
};

const submit = () => {
    form.put(`/finance/savings/${props.savings.id}`);
};
</script>

<template>
    <Head :title="`Finance - Edit Savings ${savings.id}`" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="max-w-2xl space-y-6">
            <div v-if="isFromCoopContext" class="flex items-center gap-4">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">{{ cooperativeName }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Edit Savings</span>
                </nav>
            </div>
            <h1 class="text-2xl font-semibold">Edit Savings Account</h1>

            <form class="space-y-4 rounded-lg border bg-card p-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Interest Rate (%)</label>
                    <input v-model.number="form.interest_rate" type="number" step="0.01" min="0" max="10" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Account Status</label>
                    <select v-model="form.account_status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="Active">Active</option>
                        <option value="Dormant">Dormant</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">Save</button>
                    <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="handleBackClick">Cancel</button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
