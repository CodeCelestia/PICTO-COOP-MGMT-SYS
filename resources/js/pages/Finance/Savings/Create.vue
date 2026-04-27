<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps<{
    members: Array<{ id: number; first_name: string; last_name: string }>;
    interestRate: number;
}>();

const form = useForm({
    member_id: '',
    opening_balance: 0,
    interest_rate: 3,
});

const submit = () => {
    form.post('/finance/savings');
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Finance - Open Savings Account" />

    <FinanceShellLayout active-tab="savings">
        <div class="max-w-2xl space-y-6">
            <div class="flex items-start justify-between gap-4">
                <h1 class="text-2xl font-semibold">Open Savings Account</h1>
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
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
                    <Link href="/finance/savings" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
