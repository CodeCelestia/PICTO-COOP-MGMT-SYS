<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowUpLeft } from 'lucide-vue-next';
import { useCreateBack } from '@/composables/useCreateBack';
import { computed } from 'vue';

defineProps<{
    members: Array<{ id: number; first_name: string; last_name: string }>;
    interestRate: number;
    coop?: { id: number; name: string } | null;
}>();

const isFromCoopContext = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const { goBack, returnToHref } = useCreateBack({ fallbackHref: '/finance/savings' });

const form = useForm({
    return_to: returnToHref.value,
    member_id: '',
    opening_balance: 0,
    interest_rate: 3,
});

const handleBackClick = () => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        window.location.href = `/cooperatives/${coopIdFromUrl.value}?tab=members`;
    } else {
        goBack();
    }
};

const submit = () => {
    form.post('/finance/savings');
};
</script>

<template>
    <Head title="Finance - Open Savings Account" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="max-w-2xl space-y-6">
            <div v-if="isFromCoopContext" class="flex items-center justify-between gap-4">
                <nav class="flex items-center gap-2 text-sm">
                    <a href="/cooperatives" class="text-primary hover:underline">Cooperatives</a>
                    <span class="text-muted-foreground">/</span>
                    <a :href="`/cooperatives/${coopIdFromUrl}`" class="text-primary hover:underline">{{ coop?.name || 'Cooperative' }}</a>
                    <span class="text-muted-foreground">/</span>
                    <span class="text-foreground">Open Savings</span>
                </nav>
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm" @click="handleBackClick">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
            </div>
            <div v-else>
                <h1 class="text-2xl font-semibold">Open Savings Account</h1>
            </div>

            <form class="space-y-4 rounded-lg border bg-card p-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Member</label>
                    <select v-model="form.member_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="">Select member</option>
                        <option v-for="member in members" :key="member.id" :value="member.id">
                            {{ member.first_name }} {{ member.last_name }}
                        </option>
                    </select>
                    <div v-if="form.errors.member_id" class="mt-1 text-xs text-red-600">{{ form.errors.member_id }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Opening Balance</label>
                    <input v-model.number="form.opening_balance" type="number" step="0.01" min="0" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Interest Rate (%)</label>
                    <input v-model.number="form.interest_rate" type="number" step="0.01" min="0" max="10" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Account' }}
                    </button>
                    <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="handleBackClick">Cancel</button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
