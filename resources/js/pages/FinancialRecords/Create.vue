<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Wallet, Save, X } from 'lucide-vue-next';
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

interface Props {
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateRecord = computed(() => permissions.value.includes('create financial-&-support'));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    period: '',
    type: 'Income',
    amount: '',
    source: '',
    purpose: '',
    date_recorded: '',
    total_assets: '',
    total_liabilities: '',
    net_surplus: '',
    capital_build_up: '',
    external_assistance_received: '',
    type_of_assistance: '',
    reference_doc: '',
});

const typeOptions = ['Income', 'Expense', 'Grant', 'Loan', 'Support', 'Capital'];
const assistanceTypes = ['Grant', 'Loan', 'Training', 'Equipment', 'Technical Assistance', 'Other'];

const submit = () => {
    if (!canCreateRecord.value) return;
    form.post('/financial-records', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/financial-records');
};

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Financial Record</h1>
                    <p class="text-sm text-muted-foreground">Record cooperative financial data.</p>
                </div>
                <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <Wallet class="h-5 w-5" />
                            Financial Details
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
                                <Label for="period">Period</Label>
                                <Input id="period" v-model="form.period" placeholder="2025 or 2025-Q1" />
                                <p v-if="form.errors.period" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.period }}
                                </p>
                            </div>

                            <div>
                                <Label for="type">Type</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger id="type" :class="{ 'border-red-500': form.errors.type }">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in typeOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.type }}
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
                                <Label for="source">Source</Label>
                                <Input id="source" v-model="form.source" placeholder="LGU, Cooperative, Grant" />
                                <p v-if="form.errors.source" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.source }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_recorded">Date Recorded</Label>
                                <Input id="date_recorded" v-model="form.date_recorded" type="date" />
                                <p v-if="form.errors.date_recorded" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_recorded }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="purpose">Purpose</Label>
                                <Textarea id="purpose" v-model="form.purpose" placeholder="Description or purpose" />
                                <p v-if="form.errors.purpose" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.purpose }}
                                </p>
                            </div>

                            <div>
                                <Label for="total_assets">Total Assets</Label>
                                <Input id="total_assets" v-model="form.total_assets" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.total_assets" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.total_assets }}
                                </p>
                            </div>

                            <div>
                                <Label for="total_liabilities">Total Liabilities</Label>
                                <Input id="total_liabilities" v-model="form.total_liabilities" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.total_liabilities" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.total_liabilities }}
                                </p>
                            </div>

                            <div>
                                <Label for="net_surplus">Net Surplus</Label>
                                <Input id="net_surplus" v-model="form.net_surplus" type="number" step="0.01" />
                                <p v-if="form.errors.net_surplus" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.net_surplus }}
                                </p>
                            </div>

                            <div>
                                <Label for="capital_build_up">Capital Build-up</Label>
                                <Input id="capital_build_up" v-model="form.capital_build_up" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.capital_build_up" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.capital_build_up }}
                                </p>
                            </div>

                            <div>
                                <Label for="external_assistance_received">External Assistance Received</Label>
                                <Input id="external_assistance_received" v-model="form.external_assistance_received" type="number" min="0" step="0.01" />
                                <p v-if="form.errors.external_assistance_received" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.external_assistance_received }}
                                </p>
                            </div>

                            <div>
                                <Label for="type_of_assistance">Type of Assistance</Label>
                                <Select v-model="form.type_of_assistance">
                                    <SelectTrigger id="type_of_assistance" :class="{ 'border-red-500': form.errors.type_of_assistance }">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in assistanceTypes" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type_of_assistance" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.type_of_assistance }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="reference_doc">Reference Document</Label>
                                <Input id="reference_doc" v-model="form.reference_doc" placeholder="Reference document number" />
                                <p v-if="form.errors.reference_doc" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.reference_doc }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateRecord" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Record
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
