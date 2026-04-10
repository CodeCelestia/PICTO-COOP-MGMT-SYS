<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    members: Array<{ id: number; first_name: string; last_name: string }>;
    interestRates: number[];
}>();

const form = useForm({
    member_id: '',
    principal: 0,
    interest_rate: props.interestRates?.[0] ?? 12,
    term_months: 24,
    purpose: '',
});

const monthlyPayment = computed(() => {
    const p = Number(form.principal);
    const r = Number(form.interest_rate) / 100 / 12;
    const n = Number(form.term_months);

    if (!n || n <= 0) return 0;
    if (r === 0) return p / n;

    return p * (r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1);
});

const totalAmount = computed(() => monthlyPayment.value * Number(form.term_months || 0));
const totalInterest = computed(() => totalAmount.value - Number(form.principal || 0));

const submit = () => {
    form.post('/finance/loans');
};
</script>

<template>
    <Head title="Finance - Create Loan" />

    <FinanceShellLayout active-tab="loans">
        <div class="max-w-3xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold">New Member Loan</h1>
                <p class="text-sm text-muted-foreground">Create a loan application and generate repayment schedule.</p>
            </div>

            <form class="space-y-5 rounded-lg border bg-card p-5" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm">Member</label>
                    <select v-model="form.member_id" class="w-full rounded-md border px-3 py-2 text-sm">
                        <option value="">Select member</option>
                        <option v-for="member in members" :key="member.id" :value="member.id">
                            {{ member.first_name }} {{ member.last_name }}
                        </option>
                    </select>
                    <div v-if="form.errors.member_id" class="mt-1 text-xs text-red-600">{{ form.errors.member_id }}</div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm">Principal</label>
                        <input v-model.number="form.principal" type="number" min="100" class="w-full rounded-md border px-3 py-2 text-sm" />
                        <div v-if="form.errors.principal" class="mt-1 text-xs text-red-600">{{ form.errors.principal }}</div>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm">Interest Rate (%)</label>
                        <select v-model.number="form.interest_rate" class="w-full rounded-md border px-3 py-2 text-sm">
                            <option v-for="rate in interestRates" :key="rate" :value="rate">{{ rate }}%</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Term (Months)</label>
                    <input v-model.number="form.term_months" type="number" min="1" max="60" class="w-full rounded-md border px-3 py-2 text-sm" />
                    <div v-if="form.errors.term_months" class="mt-1 text-xs text-red-600">{{ form.errors.term_months }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm">Purpose</label>
                    <textarea v-model="form.purpose" rows="3" class="w-full rounded-md border px-3 py-2 text-sm"></textarea>
                </div>

                <div class="rounded-md bg-muted/40 p-3 text-sm">
                    <div>Estimated Monthly Payment: {{ monthlyPayment.toFixed(2) }}</div>
                    <div>Total Interest: {{ totalInterest.toFixed(2) }}</div>
                    <div>Total Amount: {{ totalAmount.toFixed(2) }}</div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        {{ form.processing ? 'Saving...' : 'Create Loan' }}
                    </button>
                    <Link href="/finance/loans" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
