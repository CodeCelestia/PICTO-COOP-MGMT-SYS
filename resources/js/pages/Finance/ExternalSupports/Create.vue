<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, LifeBuoy, Save, X, AlertCircle, Building2 } from 'lucide-vue-next';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
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
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';
import Swal from 'sweetalert2';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { useFormUx } from '@/composables/useFormUx';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface FinancialRecordOption {
    id: number;
    period: string;
    type: string;
    coop_id: number;
}

interface Props {
    cooperatives: Cooperative[];
    financialRecords: FinancialRecordOption[];
    cooperative?: Cooperative | null;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateSupport = computed(() => permissions.value.includes('create financial-&-support'));
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');

const coopIdFromUrl = computed(() => {
    if (props.cooperative?.id) return props.cooperative.id;
    const param = new URLSearchParams(window.location.search).get('coop_id');
    return param ? parseInt(param) : null;
});
const isFromCoopContext = computed(() => coopIdFromUrl.value !== null);
const isPerCoopRoute = computed(() => !!props.cooperative?.id);

const form = useForm({
    coop_id: coopIdFromUrl.value?.toString() ?? '',
    financial_record_id: 'none',
    support_type: 'Grant',
    provider_name: '',
    amount: '',
    date_granted: '',
    date_completed: '',
    status: 'Pending',
    remarks: '',
});

// UX helpers
const { isDirty, isPreFilling, markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake } = useFormUx(form);

const cooperativeObj = computed(() => {
    if (props.cooperative) return props.cooperative;
    if (form.coop_id) return props.cooperatives?.find(c => String(c.id) === String(form.coop_id)) || null;
    return null;
});

const supportTypes = ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'];
const statusOptions = ['Ongoing', 'Completed', 'Pending'];

const filteredFinancials = computed(() => {
    if (!form.coop_id) return props.financialRecords;
    return props.financialRecords.filter(record => record.coop_id.toString() === form.coop_id);
});

const submit = () => {
    if (!canCreateSupport.value) return;
    form.transform((data) => ({
        ...data,
        financial_record_id: data.financial_record_id === 'none' ? '' : data.financial_record_id,
    })).post((isPerCoopRoute.value && coopIdFromUrl.value) || (isFromCoopContext.value && coopIdFromUrl.value)
        ? `/cooperatives/${coopIdFromUrl.value}/finance/external-supports`
        : '/external-supports', {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
        },
        onError: () => {
            triggerErrorShake();
            scrollToFirstError();
        },
    });
};

const handleBack = async () => {
    if (isDirty.value) {
        const result = await Swal.fire({
            title: 'Discard changes?',
            text: 'You have unsaved changes. Are you sure you want to discard them?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Discard',
            cancelButtonText: 'Keep editing',
        });
        if (!result.isConfirmed) return;
    }

    if (isPerCoopRoute.value || coopIdFromUrl.value) {
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=external-supports`);
        return;
    }
    router.get('/finance/external-supports');
};

const handleCancel = async () => {
    const result = await Swal.fire({
        title: 'Are you sure you want to cancel?',
        text: 'Any unsaved changes will be lost.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, cancel',
        cancelButtonText: 'Keep editing',
        confirmButtonColor: '#dc2626',
    });
    if (!result.isConfirmed) { return; }
    if (isPerCoopRoute.value) {
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=external-supports`);
        return;
    }
    if (coopIdFromUrl.value) {
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=external-supports`);
        return;
    }
    router.get('/finance/external-supports');
};
</script>

<template>
    <FinanceShellLayout active-tab="external-supports" :hide-tabs="isFromCoopContext">
        <div class="space-y-6 p-4 sm:p-6">
            <Card>
                <CardContent class="flex items-start justify-between gap-4 py-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300">
                            <Building2 class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-semibold">Add External Support</h1>
                            <p class="mt-1 text-sm text-muted-foreground">Record external support or assistance.</p>
                        </div>
                    </div>
                    <Button variant="outline" @click="handleBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </CardContent>
            </Card>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <LifeBuoy class="h-5 w-5" />
                            Support Details
                        </h2>
                        <div v-if="cooperativeObj" class="mb-5 rounded-lg border border-blue-200 bg-blue-50/60 p-4 dark:border-blue-800 dark:bg-blue-900/10">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                                    <Building2 class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Creating record under</p>
                                    <p class="truncate text-sm font-semibold text-foreground">{{ cooperativeObj.name }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">{{ cooperativeObj.region || '' }}{{ cooperativeObj.classification ? ' · ' + cooperativeObj.classification : '' }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">{{ cooperativeObj.status ?? 'Active' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label class="text-sm font-medium leading-none">Cooperative <span class="text-red-500 ml-0.5">*</span></label>
                                <div v-if="!cooperativeObj">
                                    <Select v-model="form.coop_id">
                                        <SelectTrigger id="coop_id" :class="inputErrorClass('coop_id')">
                                            <SelectValue placeholder="Select cooperative" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                                {{ coop.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.coop_id }}</p>
                                </div>
                                <div v-else class="text-sm text-muted-foreground">{{ cooperativeObj.name }}</div>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Linked Financial Record <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Select v-model="form.financial_record_id" @update:model-value="(v)=>clearError('financial_record_id')">
                                    <SelectTrigger id="financial_record_id" :class="inputErrorClass('financial_record_id')">
                                        <SelectValue placeholder="Select record (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No linked record</SelectItem>
                                        <SelectItem
                                            v-for="record in filteredFinancials"
                                            :key="record.id"
                                            :value="record.id.toString()"
                                        >
                                            {{ record.period }} · {{ record.type }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.financial_record_id" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.financial_record_id }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Support Type <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.support_type" @update:model-value="(v)=>clearError('support_type')">
                                    <SelectTrigger id="support_type" :class="inputErrorClass('support_type')">
                                        <SelectValue placeholder="Select support type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in supportTypes" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.support_type" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.support_type }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Provider Name <span class="text-red-500 ml-0.5">*</span></label>
                                <Input id="provider_name" v-model="form.provider_name" :class="inputErrorClass('provider_name')" @input="clearError('provider_name')" placeholder="Agency or organization" />
                                <p v-if="form.errors.provider_name" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.provider_name }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Amount <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="amount" v-model="form.amount" :class="inputErrorClass('amount')" @input="clearError('amount')" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.amount }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Date Granted <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="date_granted" v-model="form.date_granted" :class="inputErrorClass('date_granted')" @input="clearError('date_granted')" type="date" />
                                <p v-if="form.errors.date_granted" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.date_granted }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Date Completed <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Input id="date_completed" v-model="form.date_completed" :class="inputErrorClass('date_completed')" @input="clearError('date_completed')" type="date" />
                                <p v-if="form.errors.date_completed" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.date_completed }}</p>
                            </div>

                            <div>
                                <label class="text-sm font-medium leading-none">Status <span class="text-red-500 ml-0.5">*</span></label>
                                <Select v-model="form.status" @update:model-value="(v)=>clearError('status')">
                                    <SelectTrigger id="status" :class="inputErrorClass('status')">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.status }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="text-sm font-medium leading-none">Remarks <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span></label>
                                <Textarea id="remarks" v-model="form.remarks" :class="inputErrorClass('remarks')" @input="clearError('remarks')" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500 flex items-center gap-1"><AlertCircle class="h-3.5 w-3.5" />{{ form.errors.remarks }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="handleBack" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateSupport" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Support
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </FinanceShellLayout>
</template>
