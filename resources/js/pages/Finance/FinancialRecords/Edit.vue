<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, Building2, ReceiptText, Save, TrendingUp } from 'lucide-vue-next';
import { computed, nextTick, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useFormUx } from '@/composables/useFormUx';
import AppLayout from '@/layouts/AppLayout.vue';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface FinancialRecord {
    id: number;
    coop_id: number;
    period: string;
    type: string;
    amount: string | null;
    source: string | null;
    purpose: string | null;
    date_recorded: string | null;
    total_assets: string | null;
    total_liabilities: string | null;
    net_surplus: string | null;
    capital_build_up: string | null;
    external_assistance_received: string | null;
    type_of_assistance: string | null;
    reference_doc: string | null;
}

interface Props {
    record: FinancialRecord;
    cooperatives: Cooperative[];
    isCoopContext?: boolean;
    coopContext?: Cooperative | null;
}

const props = defineProps<Props>();
const page = usePage();

const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdate = computed(() => permissions.value.includes('update finance-ledger-entries'));

const isCoopContext = computed(() => Boolean(props.isCoopContext && props.coopContext));
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');

const parseDateLocal = (dateString: string | null): string => {
    if (!dateString) return '';
    const date = new Date(dateString + 'T00:00:00');
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const form = useForm({
    coop_id: String(props.record.coop_id),
    period: props.record.period || '',
    type: props.record.type || 'Income',
    amount: props.record.amount || '',
    source: props.record.source || '',
    purpose: props.record.purpose || '',
    date_recorded: parseDateLocal(props.record.date_recorded),
    total_assets: props.record.total_assets || '',
    total_liabilities: props.record.total_liabilities || '',
    net_surplus: props.record.net_surplus || '',
    capital_build_up: props.record.capital_build_up || '',
    external_assistance_received: props.record.external_assistance_received || '',
    type_of_assistance: props.record.type_of_assistance || '',
    reference_doc: props.record.reference_doc || '',
});

const { isDirty, isPreFilling, inputErrorClass, clearError, markClean, handleCancel, showErrorShake, scrollToFirstError } = useFormUx(form);

const typeOptions = ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'];
const assistanceTypes = ['Grant', 'Loan', 'Training', 'Equipment', 'Technical Assistance', 'Other'];

onMounted(async () => {
    isPreFilling.value = true;
    await nextTick();
    markClean();
    isPreFilling.value = false;
});

const handleFormCancel = async () => {
    if (isCoopContext.value && props.coopContext) {
        const result = await handleCancel();
        if (!result?.isConfirmed) return;
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=financial-records`);
        return;
    }
    const result = await handleCancel();
    if (!result?.isConfirmed) return;
    router.get('/finance/financial-records');
};

const submit = () => {
    if (!canUpdate.value) return;

    form.put(isCoopContext.value && props.coopContext
        ? `/cooperatives/${props.coopContext.id}/finance/financial-records/${props.record.id}`
        : `/finance/financial-records/${props.record.id}`, {
        preserveScroll: true,
        onSuccess: () => markClean(),
        onError: () => {
            scrollToFirstError();
            showErrorShake.value = true;
            setTimeout(() => { showErrorShake.value = false; }, 600);
        },
    });
};
</script>

<template>
    <FinanceShellLayout v-if="isCoopContext" active-tab="financial-records" :hide-tabs="isCoopContext">
        <div class="space-y-6">
            <Card>
                <CardContent class="flex items-center justify-between gap-4 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                            <ReceiptText class="h-5 w-5" />
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">Edit Financial Record</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Update cooperative financial data with the current ledger details.</p>
                        </div>
                    </div>
                    <Button variant="outline" class="gap-2" @click="handleFormCancel">
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <Card class="border-blue-200 bg-blue-50/60 dark:border-blue-800 dark:bg-blue-900/10">
                <CardContent class="flex items-center gap-3 p-4">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                        <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Record belongs to</p>
                        <p class="truncate text-sm font-semibold text-foreground">{{ props.coopContext?.name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ props.coopContext?.region || 'Cooperative context' }}{{ props.coopContext?.classification ? ' · ' + props.coopContext.classification : '' }}</p>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">
                        {{ props.coopContext?.status ?? 'Active' }}
                    </span>
                </CardContent>
            </Card>

            <Card v-if="!isCoopContext">
                <CardHeader>
                    <CardTitle class="text-lg">Cooperative</CardTitle>
                    <CardDescription>The cooperative for this record</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="coop_id">Cooperative <span class="text-red-500 ml-0.5">*</span></Label>
                        <Select v-model="form.coop_id" disabled @update:modelValue="clearError('coop_id')">
                            <SelectTrigger id="coop_id" :class="inputErrorClass('coop_id')"><SelectValue placeholder="Select cooperative" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">{{ coop.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.coop_id" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.coop_id }}</p>
                    </div>
                </CardContent>
            </Card>

            <form @submit.prevent="submit" class="space-y-6" :class="{ 'animate-shake': showErrorShake }">
                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl"><ReceiptText class="h-5 w-5" />Financial Details</CardTitle>
                        <CardDescription>Update the required ledger fields and supporting notes.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2"><Label for="period">Period <span class="text-red-500 ml-0.5">*</span></Label><Input id="period" v-model="form.period" placeholder="2025 or 2025-Q1" :class="inputErrorClass('period')" @input="clearError('period')" /><p v-if="form.errors.period" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.period }}</p></div>
                            <div class="space-y-2"><Label for="type">Type <span class="text-red-500 ml-0.5">*</span></Label><Select v-model="form.type" @update:modelValue="clearError('type')"><SelectTrigger id="type" :class="inputErrorClass('type')"><SelectValue placeholder="Select type" /></SelectTrigger><SelectContent><SelectItem v-for="option in typeOptions" :key="option" :value="option">{{ option }}</SelectItem></SelectContent></Select><p v-if="form.errors.type" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.type }}</p></div>
                            <div class="space-y-2"><Label for="source">Source <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="source" v-model="form.source" placeholder="LGU, Cooperative, Grant" :class="inputErrorClass('source')" @input="clearError('source')" /><p v-if="form.errors.source" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.source }}</p></div>
                            <div class="space-y-2"><Label for="date_recorded">Date Recorded <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="date_recorded" v-model="form.date_recorded" type="date" :class="inputErrorClass('date_recorded')" @input="clearError('date_recorded')" /><p v-if="form.errors.date_recorded" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.date_recorded }}</p></div>
                            <div class="col-span-1 space-y-2 md:col-span-2"><Label for="purpose">Purpose / Description <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Textarea id="purpose" v-model="form.purpose" placeholder="Provide details about this financial transaction" rows="3" :class="inputErrorClass('purpose')" @input="clearError('purpose')" /><p v-if="form.errors.purpose" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.purpose }}</p></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl"><TrendingUp class="h-5 w-5" />Financial Summary</CardTitle>
                        <CardDescription>Optional amounts and classification fields for the record.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2"><Label for="total_assets">Total Assets <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="total_assets" v-model="form.total_assets" type="number" min="0" step="0.01" placeholder="0.00" :class="inputErrorClass('total_assets')" @input="clearError('total_assets')" /><p v-if="form.errors.total_assets" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.total_assets }}</p></div>
                            <div class="space-y-2"><Label for="total_liabilities">Total Liabilities <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="total_liabilities" v-model="form.total_liabilities" type="number" min="0" step="0.01" placeholder="0.00" :class="inputErrorClass('total_liabilities')" @input="clearError('total_liabilities')" /><p v-if="form.errors.total_liabilities" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.total_liabilities }}</p></div>
                            <div class="space-y-2"><Label for="net_surplus">Net Surplus <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="net_surplus" v-model="form.net_surplus" type="number" step="0.01" placeholder="0.00" :class="inputErrorClass('net_surplus')" @input="clearError('net_surplus')" /><p v-if="form.errors.net_surplus" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.net_surplus }}</p></div>
                            <div class="space-y-2"><Label for="capital_build_up">Capital Build-up <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="capital_build_up" v-model="form.capital_build_up" type="number" min="0" step="0.01" placeholder="0.00" :class="inputErrorClass('capital_build_up')" @input="clearError('capital_build_up')" /><p v-if="form.errors.capital_build_up" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.capital_build_up }}</p></div>
                            <div class="space-y-2"><Label for="external_assistance_received">External Assistance <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="external_assistance_received" v-model="form.external_assistance_received" type="number" min="0" step="0.01" placeholder="0.00" :class="inputErrorClass('external_assistance_received')" @input="clearError('external_assistance_received')" /><p v-if="form.errors.external_assistance_received" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.external_assistance_received }}</p></div>
                            <div class="space-y-2"><Label for="type_of_assistance">Type of Assistance <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Select v-model="form.type_of_assistance" @update:modelValue="clearError('type_of_assistance')"><SelectTrigger id="type_of_assistance" :class="inputErrorClass('type_of_assistance')"><SelectValue placeholder="Select type" /></SelectTrigger><SelectContent><SelectItem value="">None</SelectItem><SelectItem v-for="type in assistanceTypes" :key="type" :value="type">{{ type }}</SelectItem></SelectContent></Select><p v-if="form.errors.type_of_assistance" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.type_of_assistance }}</p></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="text-lg">Reference</CardTitle>
                        <CardDescription>Optional document or receipt information for audit trail purposes.</CardDescription>
                    </CardHeader>
                    <CardContent class="pt-0">
                        <div class="space-y-2"><Label for="reference_doc">Reference Document <span class="text-xs font-normal text-muted-foreground ml-1">(Optional)</span></Label><Input id="reference_doc" v-model="form.reference_doc" placeholder="Receipt number, invoice ID, etc." :class="inputErrorClass('reference_doc')" @input="clearError('reference_doc')" /><p v-if="form.errors.reference_doc" class="mt-1 flex items-center gap-1 text-sm text-red-500"><AlertCircle class="h-3.5 w-3.5 shrink-0" />{{ form.errors.reference_doc }}</p></div>
                    </CardContent>
                </Card>

                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" :disabled="form.processing" @click="handleFormCancel">Cancel</Button>
                    <Button type="submit" :disabled="form.processing || !canUpdate || !isDirty" class="gap-2 bg-foreground text-background hover:bg-foreground/90"><Save class="h-4 w-4" />Save Changes</Button>
                </div>
            </form>
        </div>
    </FinanceShellLayout>

    <AppLayout v-else>
        <div class="space-y-6 p-4 sm:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Edit Financial Record</h1>
                    <p class="text-sm text-muted-foreground">Update cooperative financial data</p>
                </div>
                <Button variant="outline" size="sm" class="gap-2" @click="handleFormCancel">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Cooperative</CardTitle>
                    <CardDescription>The cooperative for this record</CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="coop_id">
                            Cooperative
                            <span class="text-red-500 ml-0.5">*</span>
                        </Label>
                        <Select v-model="form.coop_id" :disabled="true">
                            <SelectTrigger id="coop_id" :class="inputErrorClass('coop_id')">
                                <SelectValue placeholder="Select cooperative" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                    {{ coop.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.coop_id" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ form.errors.coop_id }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Financial Details</CardTitle>
                    <CardDescription>Update the financial record information below</CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Period & Type Row -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="period">
                                    Period
                                    <span class="text-red-500 ml-0.5">*</span>
                                </Label>
                                <Input
                                    id="period"
                                    v-model="form.period"
                                    placeholder="2025 or 2025-Q1"
                                    :class="inputErrorClass('period')"
                                    @input="clearError('period')"
                                />
                                <p v-if="form.errors.period" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.period }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="type">
                                    Type
                                    <span class="text-red-500 ml-0.5">*</span>
                                </Label>
                                <Select v-model="form.type">
                                    <SelectTrigger id="type" :class="inputErrorClass('type')">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in typeOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.type }}
                                </p>
                            </div>
                        </div>

                        <!-- Amount & Source Row -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="amount">
                                    Amount
                                    <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                </Label>
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    :class="inputErrorClass('amount')"
                                    @input="clearError('amount')"
                                />
                                <p v-if="form.errors.amount" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.amount }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="source">
                                    Source
                                    <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                </Label>
                                <Input
                                    id="source"
                                    v-model="form.source"
                                    placeholder="LGU, Cooperative, Grant"
                                    :class="inputErrorClass('source')"
                                    @input="clearError('source')"
                                />
                                <p v-if="form.errors.source" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.source }}
                                </p>
                            </div>
                        </div>

                        <!-- Date Recorded -->
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="date_recorded">
                                    Date Recorded
                                    <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                </Label>
                                <Input
                                    id="date_recorded"
                                    v-model="form.date_recorded"
                                    type="date"
                                    :class="inputErrorClass('date_recorded')"
                                    @input="clearError('date_recorded')"
                                />
                                <p v-if="form.errors.date_recorded" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                    <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                    {{ form.errors.date_recorded }}
                                </p>
                            </div>
                        </div>

                        <!-- Purpose -->
                        <div class="space-y-2">
                            <Label for="purpose">
                                Purpose / Description
                                <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                            </Label>
                            <Textarea
                                id="purpose"
                                v-model="form.purpose"
                                placeholder="Provide details about this financial transaction"
                                rows="3"
                                :class="inputErrorClass('purpose')"
                                @input="clearError('purpose')"
                            />
                            <p v-if="form.errors.purpose" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                {{ form.errors.purpose }}
                            </p>
                        </div>

                        <!-- Financial Summary Section -->
                        <div class="border-t pt-6">
                            <h3 class="text-sm font-semibold text-foreground mb-4">Financial Summary</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <Label for="total_assets">
                                        Total Assets
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Input
                                        id="total_assets"
                                        v-model="form.total_assets"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        :class="inputErrorClass('total_assets')"
                                        @input="clearError('total_assets')"
                                    />
                                    <p v-if="form.errors.total_assets" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.total_assets }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="total_liabilities">
                                        Total Liabilities
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Input
                                        id="total_liabilities"
                                        v-model="form.total_liabilities"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        :class="inputErrorClass('total_liabilities')"
                                        @input="clearError('total_liabilities')"
                                    />
                                    <p v-if="form.errors.total_liabilities" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.total_liabilities }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="net_surplus">
                                        Net Surplus
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Input
                                        id="net_surplus"
                                        v-model="form.net_surplus"
                                        type="number"
                                        step="0.01"
                                        placeholder="0.00"
                                        :class="inputErrorClass('net_surplus')"
                                        @input="clearError('net_surplus')"
                                    />
                                    <p v-if="form.errors.net_surplus" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.net_surplus }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="capital_build_up">
                                        Capital Build-up
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Input
                                        id="capital_build_up"
                                        v-model="form.capital_build_up"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        :class="inputErrorClass('capital_build_up')"
                                        @input="clearError('capital_build_up')"
                                    />
                                    <p v-if="form.errors.capital_build_up" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.capital_build_up }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="external_assistance_received">
                                        External Assistance
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Input
                                        id="external_assistance_received"
                                        v-model="form.external_assistance_received"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        :class="inputErrorClass('external_assistance_received')"
                                        @input="clearError('external_assistance_received')"
                                    />
                                    <p v-if="form.errors.external_assistance_received" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.external_assistance_received }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="type_of_assistance">
                                        Type of Assistance
                                        <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                                    </Label>
                                    <Select v-model="form.type_of_assistance">
                                        <SelectTrigger id="type_of_assistance" :class="inputErrorClass('type_of_assistance')">
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">None</SelectItem>
                                            <SelectItem v-for="type in assistanceTypes" :key="type" :value="type">
                                                {{ type }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.type_of_assistance" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                        <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                        {{ form.errors.type_of_assistance }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Reference Doc -->
                        <div class="space-y-2">
                            <Label for="reference_doc">
                                Reference Document
                                <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                            </Label>
                            <Input
                                id="reference_doc"
                                v-model="form.reference_doc"
                                placeholder="Receipt number, invoice ID, etc."
                                :class="inputErrorClass('reference_doc')"
                                @input="clearError('reference_doc')"
                            />
                            <p v-if="form.errors.reference_doc" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                                {{ form.errors.reference_doc }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3 pt-6 border-t">
                            <Button
                                type="submit"
                                :disabled="form.processing || !canUpdate || !isDirty"
                                class="gap-2 bg-foreground text-background hover:bg-foreground/90"
                            >
                                <Save class="h-4 w-4" />
                                Save Changes
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                                @click="handleFormCancel"
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
