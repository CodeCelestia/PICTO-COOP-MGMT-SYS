<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    loan: {
        id: number;
        interest_rate: string;
        term_months: number;
        purpose: string | null;
        status: string;
    };
}>();

const form = useForm({
    interest_rate: Number(props.loan.interest_rate),
    term_months: props.loan.term_months,
    purpose: props.loan.purpose || '',
    status: props.loan.status,
});

const submit = () => {
    form.put(`/finance/loans/${props.loan.id}`);
};
</script>

<template>
    <Head :title="`Finance - Edit Loan #${loan.id}`" />

    <FinanceShellLayout active-tab="loans">
        <div class="max-w-2xl space-y-6">
            <h1 class="text-2xl font-semibold">Edit Loan #{{ loan.id }}</h1>

            <form class="space-y-4 rounded-lg border bg-card p-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Interest Rate (%)</label>
                    <input v-model.number="form.interest_rate" type="number" min="0" max="50" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Term (Months)</label>
                    <input v-model.number="form.term_months" type="number" min="1" max="60" class="w-full rounded-md border px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="mb-1 block text-sm">Status</label>
                    <select v-model="form.status" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="Defaulted">Defaulted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Purpose</label>
                    <textarea v-model="form.purpose" rows="3" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        Save
                    </button>
                    <Link :href="`/finance/loans/${loan.id}`" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
