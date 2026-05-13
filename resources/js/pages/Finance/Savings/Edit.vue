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
import { useCreateBack } from '@/composables/useCreateBack';
import { computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps<{
    savings: {
        id: number;
        interest_rate: string;
        account_status: string;
        coop_id?: number;
        cooperative?: {
            name?: string;
            region?: string | null;
            classification?: string | null;
            status?: string | null;
        } | null;
    };
    isCoopContext?: boolean;
    coopContext?: {
        id: number;
        name: string;
        region?: string | null;
        classification?: string | null;
        status?: string | null;
    } | null;
}>();

const coopSlug = computed(() => usePage().props.auth?.user?.coop_slug ?? 'my');

const isFromCoopContext = computed(() => {
    if (window.location.pathname.startsWith('/cooperatives/')) {
        return true;
    }

    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return !!coopId;
});

const coopIdFromUrl = computed(() => {
    // First try query parameter (backward compatibility)
    const queryCoopId = new URLSearchParams(window.location.search).get('coop_id');
    if (queryCoopId) {
        return parseInt(queryCoopId);
    }
    
    // Then try to extract from path: /cooperatives/{id}/finance/...
    const pathMatch = window.location.pathname.match(/\/cooperatives\/(\d+)\//);
    if (pathMatch && pathMatch[1]) {
        return parseInt(pathMatch[1]);
    }
    
    return null;
});

const coopContextId = computed(() => {
    if (props.coopContext?.id) {
        return props.coopContext.id;
    }

    if (props.savings.coop_id) {
        return props.savings.coop_id;
    }

    return coopIdFromUrl.value;
});

const backHref = computed(() => {
    if (isFromCoopContext.value && coopContextId.value) {
        return `/cooperatives/${coopContextId.value}?tab=finance&subtab=savings`;
    }
    return `/finance/savings/${props.savings.id}`;
});

const { returnToHref } = useCreateBack({ fallbackHref: backHref.value });

const form = useForm({
    return_to: returnToHref.value,
    interest_rate: Number(props.savings.interest_rate),
    account_status: props.savings.account_status,
});

const {
    isPreFilling,
    inputErrorClass,
    clearError,
    handleCancel,
    markClean,
    scrollToFirstError,
    showErrorShake,
    triggerErrorShake,
} = useFormUx(form);

onMounted(() => {
    isPreFilling.value = true;
    markClean();
    isPreFilling.value = false;
});

const cooperative = computed(() => props.coopContext || props.savings.cooperative || null);

const handleBackClick = () => {
    handleCancel({ fallbackBack: true, fallbackHref: backHref.value });
};

const handleCancelClick = () => {
    handleCancel({ fallbackBack: true, fallbackHref: backHref.value });
};

const submit = () => {
    if (isFromCoopContext.value && coopContextId.value) {
        form.put(`/cooperatives/${coopContextId.value}/finance/savings/${props.savings.id}`, {
            onSuccess: () => {
                markClean();
            },
            onError: () => {
                triggerErrorShake();
                scrollToFirstError();
            },
        });
    } else {
        form.put(`/finance/savings/${props.savings.id}`, {
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
    <Head :title="`Finance - Edit Savings ${savings.id}`" />

    <FinanceShellLayout active-tab="savings" :hide-tabs="isFromCoopContext">
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-center justify-between py-4">
                    <div>
                        <h1 class="text-xl font-semibold">Edit Savings Account</h1>
                        <p class="mt-1 text-sm text-muted-foreground">Update the account status and interest settings.</p>
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
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Record belongs to</p>
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
                            Account Settings
                        </CardTitle>
                        <CardDescription>Apply account lifecycle and rate updates.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
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

                            <div>
                                <label class="text-sm font-medium leading-none">
                                    Account Status
                                    <span class="ml-0.5 text-red-500">*</span>
                                </label>
                                <Select v-model="form.account_status" @update:model-value="clearError('account_status')">
                                    <SelectTrigger :class="inputErrorClass('account_status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Dormant">Dormant</SelectItem>
                                        <SelectItem value="Closed">Closed</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.account_status" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.account_status }}
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
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </Button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>
</template>
