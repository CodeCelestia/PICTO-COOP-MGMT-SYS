<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { LifeBuoy, Save, X, AlertCircle, Building2 } from 'lucide-vue-next';
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
import { dateInputValue } from '@/utils/date';

interface Cooperative {
    id: number;
    name: string;
}

interface FinancialRecordOption {
    id: number;
    period: string;
    type: string;
    coop_id: number;
}

interface ExternalSupport {
    id: number;
    coop_id: number;
    financial_record_id: number | null;
    support_type: string;
    provider_name: string;
    amount: string | null;
    date_granted: string | null;
    date_completed: string | null;
    status: string;
    remarks: string | null;
}

interface Props {
    support: ExternalSupport;
    cooperatives: Cooperative[];
    financialRecords: FinancialRecordOption[];
    cooperative?: { id: number; name: string } | null;
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canUpdateSupport = computed(() => permissions.value.includes('update financial-&-support'));
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
    financial_record_id: props.support.financial_record_id?.toString() || 'none',
    support_type: props.support.support_type,
    provider_name: props.support.provider_name,
    amount: props.support.amount || '',
    date_granted: dateInputValue(props.support.date_granted),
    date_completed: dateInputValue(props.support.date_completed),
    status: props.support.status,
    remarks: props.support.remarks || '',
});

const { isDirty, isPreFilling, markClean, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake } = useFormUx(form);

onMounted(() => {
    // mark prefill phase, then clean state
    isPreFilling.value = true;
    // prefill already applied via initial form values
    markClean();
    isPreFilling.value = false;
});

const supportTypes = ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'];
const statusOptions = ['Ongoing', 'Completed', 'Pending'];

const filteredFinancials = computed(() => {
    if (!form.coop_id) return props.financialRecords;
    return props.financialRecords.filter(record => record.coop_id.toString() === form.coop_id);
});

const submit = () => {
    if (!canUpdateSupport.value) return;
    form.transform((data) => ({
        ...data,
        financial_record_id: data.financial_record_id === 'none' ? '' : data.financial_record_id,
    })).put(isPerCoopRoute.value && coopIdFromUrl.value
        ? `/cooperatives/${coopIdFromUrl.value}/finance/external-supports/${props.support.id}`
        : `/external-supports/${props.support.id}`, {
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

const handleBack = () => {
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
                        <CardContent class="flex items-center justify-between py-4">
                            <div>
                                <h1 class="text-xl font-semibold">Edit External Support</h1>
                                <p class="text-sm text-muted-foreground mt-1">Update external support details.</p>
                            </div>
                            <div>
                                <Button variant="outline" @click="handleCancel">
                                    <X class="mr-2 h-4 w-4" />
                                    Back
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                                    <LifeBuoy class="h-5 w-5" />
                                    Support Details
                                </h2>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="text-sm font-medium leading-none">Cooperative <span class="text-red-500 ml-0.5">*</span></label>
                                        <div v-if="!props.cooperative">
                                            <Select v-model="form.coop_id" :disabled="isCoopAdmin">
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
                                        <div v-else class="text-sm text-muted-foreground">{{ props.cooperative.name }}</div>
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
                        <Button @click="handleCancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canUpdateSupport" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Update Support
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </FinanceShellLayout>
</template>
