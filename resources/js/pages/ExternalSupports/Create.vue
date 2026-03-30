<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { LifeBuoy, Save, X } from 'lucide-vue-next';
import { computed } from 'vue';
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
import AppLayout from '@/layouts/AppLayout.vue';

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

interface Props {
    cooperatives: Cooperative[];
    financialRecords: FinancialRecordOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    financial_record_id: 'none',
    support_type: 'Grant',
    provider_name: '',
    amount: '',
    date_granted: '',
    date_completed: '',
    status: 'Pending',
    remarks: '',
});

const supportTypes = ['Grant', 'Loan', 'Equipment', 'Training', 'Technical Assistance', 'Other'];
const statusOptions = ['Ongoing', 'Completed', 'Pending'];

const filteredFinancials = computed(() => {
    if (!form.coop_id) return props.financialRecords;
    return props.financialRecords.filter(record => record.coop_id.toString() === form.coop_id);
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        financial_record_id: data.financial_record_id === 'none' ? '' : data.financial_record_id,
    })).post('/external-supports', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/external-supports');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add External Support</h1>
                <p class="mt-1 text-sm text-gray-500">Record external support or assistance.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <LifeBuoy class="h-5 w-5" />
                            Support Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative</Label>
                                <Select v-model="form.coop_id" :disabled="isCoopAdmin">
                                    <SelectTrigger id="coop_id" :class="{ 'border-red-500': form.errors.coop_id }">
                                        <SelectValue placeholder="Select cooperative" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="financial_record_id">Linked Financial Record</Label>
                                <Select v-model="form.financial_record_id">
                                    <SelectTrigger id="financial_record_id" :class="{ 'border-red-500': form.errors.financial_record_id }">
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
                                <p v-if="form.errors.financial_record_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.financial_record_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="support_type">Support Type</Label>
                                <Select v-model="form.support_type">
                                    <SelectTrigger id="support_type" :class="{ 'border-red-500': form.errors.support_type }">
                                        <SelectValue placeholder="Select support type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in supportTypes" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.support_type" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.support_type }}
                                </p>
                            </div>

                            <div>
                                <Label for="provider_name">Provider Name</Label>
                                <Input id="provider_name" v-model="form.provider_name" placeholder="Agency or organization" />
                                <p v-if="form.errors.provider_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.provider_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="amount">Amount</Label>
                                <Input id="amount" v-model="form.amount" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.amount" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.amount }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_granted">Date Granted</Label>
                                <Input id="date_granted" v-model="form.date_granted" type="date" />
                                <p v-if="form.errors.date_granted" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_granted }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_completed">Date Completed</Label>
                                <Input id="date_completed" v-model="form.date_completed" type="date" />
                                <p v-if="form.errors.date_completed" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_completed }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="remarks">Remarks</Label>
                                <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.remarks }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Support
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
