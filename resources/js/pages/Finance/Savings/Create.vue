<script setup lang="ts">
import { AlertCircle, ArrowLeft, Building2, PiggyBank, Save, X } from 'lucide-vue-next';
import { useFormUx } from '@/composables/useFormUx';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const isFromCoopContext = computed(() => {
    // Check URL path for coop context
    if (window.location.pathname.startsWith('/cooperatives/')) {
        return true;
    }
    // Also check query parameter for backward compatibility
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const form = useForm({
    return_to: '',
    member_id: '',
    opening_balance: 0,
    interest_rate: 3,
});

const {
    inputErrorClass,
    clearError,
    handleCancel,
    markClean,
    scrollToFirstError,
    showErrorShake,
    triggerErrorShake,
} = useFormUx(form);

const props = defineProps<{
    members: Array<{ id: number; first_name: string; last_name: string }>;
    interestRate: number;
    coop?: { id: number; name: string } | null;
    isCoopContext?: boolean;
    coopContext?: {
        id: number;
        name: string;
        region?: string | null;
        classification?: string | null;
        status?: string | null;
    } | null;
}>();

const cooperative = computed(() => props.coopContext || null);

const handleBackClick = () => {
    handleCancel({ fallbackBack: true });
};

const handleCancelClick = () => {
    handleCancel({ fallbackBack: true });
};

const submit = () => {
    if (isFromCoopContext.value && coopIdFromUrl.value) {
        form.post(`/cooperatives/${coopIdFromUrl.value}/finance/savings`, {
            onSuccess: () => {
                markClean();
            },
            onError: () => {
                triggerErrorShake();
                scrollToFirstError();
            },
        });
    } else {
        form.post('/finance/savings', {
            onSuccess: () => {
                markClean();
            },
            onError: () => {
                triggerErrorShake();
                scrollToFirstError();
            },
        });
    }
};
</script>

<template>
    <Head title="Finance - Open Savings Account" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Open Savings Account</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Create a new member savings account with opening balance and rate.</p>
                    </div>
                    <Button variant="outline" @click="handleBackClick">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <div
                v-if="isFromCoopContext && cooperative"
                class="flex items-center gap-3 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10"
            >
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Creating record under</p>
                    <p class="truncate text-sm font-semibold">{{ cooperative.name }}</p>
                    <p class="mt-0.5 text-xs text-muted-foreground">
                        {{ cooperative.region }}{{ cooperative.classification ? ' · ' + cooperative.classification : '' }}
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                    {{ cooperative.status ?? 'Active' }}
                </span>
            </div>

            <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-lg">
                            <PiggyBank class="h-5 w-5" />
                            Account Details
                        </CardTitle>
                        <CardDescription>Select the member and define account defaults.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium leading-none">
                                    Member
                                    <span class="ml-0.5 text-red-500">*</span>
                                </label>
                                <Select v-model="form.member_id" @update:model-value="clearError('member_id')">
                                    <SelectTrigger :class="inputErrorClass('member_id')">
                                        <SelectValue placeholder="Select member" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="member in members" :key="member.id" :value="String(member.id)">
                                            {{ member.first_name }} {{ member.last_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.member_id" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.member_id }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">
                                    Opening Balance
                                    <span class="ml-0.5 text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.opening_balance"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :class="inputErrorClass('opening_balance')"
                                    @input="clearError('opening_balance')"
                                />
                                <p v-if="form.errors.opening_balance" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.opening_balance }}
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">
                                    Interest Rate (%)
                                    <span class="ml-0.5 text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="form.interest_rate"
                                    type="number"
                                    min="0"
                                    max="10"
                                    step="0.01"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    :class="inputErrorClass('interest_rate')"
                                    @input="clearError('interest_rate')"
                                />
                                <p v-if="form.errors.interest_rate" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.interest_rate }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="mt-6 flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="handleCancelClick">
                        <X class="mr-2 h-4 w-4" />
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Create Account' }}
                    </Button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
