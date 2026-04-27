<script setup lang="ts">
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{
    members: Array<{
        id: number;
        first_name: string;
        last_name: string;
        coop_id: number;
        cooperative?: {
            classification: 'micro' | 'small' | 'medium' | 'large' | null;
        } | null;
    }>;
    loanTypes: Array<{
        id: number;
        name: string;
        cooperative_id: number;
        classification: 'micro' | 'small' | 'medium' | 'large' | null;
    }>;
    cooperatives: Array<{
        id: number;
        name: string;
        classification: 'micro' | 'small' | 'medium' | 'large' | null;
        members: Array<{ id: number; first_name: string; last_name: string; coop_id: number }>;
        loan_types: Array<{ id: number; name: string; cooperative_id: number; classification: 'micro' | 'small' | 'medium' | 'large' | null }>;
    }>;
    showCooperativePicker: boolean;
    preselectedCooperativeId: number | null;
}>();

const form = useForm({
    cooperative_id: props.preselectedCooperativeId ? String(props.preselectedCooperativeId) : '',
    member_id: '',
    loan_type_id: '',
    principal: '',
    purpose: '',
    attachments: [] as File[],
});

const onAttachmentsChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.attachments = target.files ? Array.from(target.files) : [];
};

const selectedCooperative = computed(() => {
    if (!props.showCooperativePicker) {
        return null;
    }

    return props.cooperatives.find((cooperative) => String(cooperative.id) === String(form.cooperative_id)) || null;
});

const availableMembers = computed(() => {
    if (props.showCooperativePicker) {
        return selectedCooperative.value?.members || [];
    }

    return props.members;
});

const selectedMember = computed(() => {
    return availableMembers.value.find((member) => String(member.id) === String(form.member_id)) || null;
});

const filteredLoanTypes = computed(() => {
    const sourceLoanTypes = props.showCooperativePicker
        ? (selectedCooperative.value?.loan_types || [])
        : props.loanTypes;

    if (!selectedMember.value && !props.showCooperativePicker) {
        return props.loanTypes;
    }

    if (props.showCooperativePicker && !selectedCooperative.value) {
        return [];
    }

    const cooperativeClassification = props.showCooperativePicker
        ? selectedCooperative.value?.classification || null
        : selectedMember.value?.cooperative?.classification || null;

    return sourceLoanTypes.filter((loanType) => {
        if (selectedMember.value && loanType.cooperative_id !== selectedMember.value.coop_id) {
            return false;
        }

        if (!cooperativeClassification) {
            return true;
        }

        return !loanType.classification || loanType.classification === cooperativeClassification;
    });
});

watch(() => form.cooperative_id, () => {
    if (!props.showCooperativePicker) {
        return;
    }

    form.member_id = '';
    form.loan_type_id = '';
});

watch(filteredLoanTypes, (loanTypes) => {
    if (!form.loan_type_id) {
        return;
    }

    const exists = loanTypes.some((loanType) => String(loanType.id) === String(form.loan_type_id));
    if (!exists) {
        form.loan_type_id = '';
    }
});

const submit = () => {
    if (props.showCooperativePicker && !form.cooperative_id) {
        form.setError('cooperative_id', 'Please select a cooperative first.');
        return;
    }

    form.post('/finance/loans', {
        forceFormData: true,
    });
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="Finance - Create Loan" />

    <FinanceShellLayout active-tab="loans">
        <div class="max-w-3xl space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold">New Member Loan</h1>
                    <p class="text-sm text-muted-foreground">Fill out this short form to submit a loan application.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </button>
            </div>

            <form class="space-y-5 rounded-lg border bg-card p-5" @submit.prevent="submit">
                <div v-if="showCooperativePicker">
                    <label class="mb-1 block text-sm font-medium">Cooperative</label>
                    <select v-model="form.cooperative_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground">
                        <option value="">Select cooperative</option>
                        <option v-for="cooperative in cooperatives" :key="cooperative.id" :value="cooperative.id">
                            {{ cooperative.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-muted-foreground">Select a cooperative first to load members and loan types.</p>
                    <div v-if="form.errors.cooperative_id" class="mt-1 text-xs text-red-600">{{ form.errors.cooperative_id }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Member</label>
                    <select v-model="form.member_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" :disabled="showCooperativePicker && !form.cooperative_id">
                        <option value="">Select member</option>
                        <option v-for="member in availableMembers" :key="member.id" :value="member.id">
                            {{ member.first_name }} {{ member.last_name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-muted-foreground">Choose the member requesting the loan.</p>
                    <div v-if="form.errors.member_id" class="mt-1 text-xs text-red-600">{{ form.errors.member_id }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Loan Type</label>
                    <select v-model="form.loan_type_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground" :disabled="showCooperativePicker && !form.cooperative_id">
                        <option value="">Select loan type</option>
                        <option v-for="loanType in filteredLoanTypes" :key="loanType.id" :value="loanType.id">
                            {{ loanType.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-muted-foreground">Loan types are filtered by the selected member's cooperative and classification tier.</p>
                    <div v-if="form.errors.loan_type_id" class="mt-1 text-xs text-red-600">{{ form.errors.loan_type_id }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Loan Amount</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-muted-foreground">₱</span>
                        <input
                            v-model="form.principal"
                            type="number"
                            min="0"
                            step="0.01"
                            placeholder="0.00"
                            class="w-full rounded-md border py-2 pl-8 pr-3 text-sm"
                        />
                    </div>
                    <div v-if="form.errors.principal" class="mt-1 text-xs text-red-600">{{ form.errors.principal }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Purpose</label>
                    <textarea
                        v-model="form.purpose"
                        rows="4"
                        class="w-full rounded-md border px-3 py-2 text-sm"
                        placeholder="Briefly describe the reason for this loan application"
                    ></textarea>
                    <div v-if="form.errors.purpose" class="mt-1 text-xs text-red-600">{{ form.errors.purpose }}</div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">File Attachments</label>
                    <input type="file" multiple class="w-full rounded-md border px-3 py-2 text-sm" @change="onAttachmentsChange" />
                    <p class="mt-1 text-xs text-muted-foreground">You may upload one or more supporting files.</p>
                    <div v-if="form.errors.attachments" class="mt-1 text-xs text-red-600">{{ form.errors.attachments }}</div>
                    <div v-if="form.errors['attachments.0']" class="mt-1 text-xs text-red-600">{{ form.errors['attachments.0'] }}</div>
                    <ul v-if="form.attachments.length > 0" class="mt-2 list-disc space-y-1 pl-5 text-xs text-muted-foreground">
                        <li v-for="file in form.attachments" :key="file.name + file.size">
                            {{ file.name }}
                        </li>
                    </ul>
                </div>

                <div class="flex items-center gap-3 border-t pt-4">
                    <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground" :disabled="form.processing">
                        {{ form.processing ? 'Submitting...' : 'Submit Loan Application' }}
                    </button>
                    <Link href="/finance/loans" class="rounded-md border px-4 py-2 text-sm">Cancel</Link>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
