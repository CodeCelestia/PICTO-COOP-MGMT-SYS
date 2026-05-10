<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatPhilippinePeso } from '@/composables/useCurrencyFormatter';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Edit, ChevronRight } from 'lucide-vue-next';
import { computed } from 'vue';
import FinanceShellLayout from '@/layouts/FinanceShellLayout.vue';

interface FinancialRecord {
    id: number;
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
    recorded_by: string | null;
    created_at: string | null;
    updated_at: string | null;
    cooperative?: { id: number; name?: string };
}

interface Props {
    record: FinancialRecord;
    permissions: {
        can_edit: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const coopSlug = computed(() => page.props.auth?.user?.coop_slug ?? 'my');

const isFromCoopContext = computed(() => window.location.pathname.startsWith('/cooperatives/'));
const coopIdFromUrl = computed(() => {
    const coopId = new URLSearchParams(window.location.search).get('coop_id');
    return coopId ? parseInt(coopId) : null;
});

const displayText = (value: string | null | undefined) => value || '—';

const formatTypeLabel = (value: string | null | undefined) => {
    if (!value) return 'Unknown';
    return value.replace(/[_-]+/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase());
};

const formatDate = (value: string | null) => {
    if (!value) return '—';
    const date = new Date(value);
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
};

const periodLabel = (value: string | null | undefined) => {
    if (!value) return '—';
    if (/^\d{4}-\d{2}$/.test(value)) {
        const [yearText, monthText] = value.split('-');
        const year = Number(yearText);
        const month = Number(monthText) - 1;
        if (!Number.isNaN(year) && !Number.isNaN(month) && month >= 0 && month <= 11) {
            return new Date(year, month, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        }
    }
    return value;
};

const statusBadgeClass = (type: string | null | undefined) => {
    if (!type) return 'bg-slate-100 text-slate-800 dark:bg-slate-900 dark:text-slate-200';
    const typeLower = (type || '').toLowerCase();
    if (['income', 'grant'].includes(typeLower)) return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
    if (['expense', 'loan'].includes(typeLower)) return 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
    return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
};

const displayTitle = (record: FinancialRecord) => {
    const description = (record.purpose || '').trim();
    return description || `Financial Record #${record.id}`;
};

const editHref = computed(() => {
    if (isFromCoopContext.value) {
        return `/cooperatives/${coopSlug.value}/finance/financial-records/${props.record.id}/edit`;
    }
    return coopIdFromUrl.value
        ? `/finance/financial-records/${props.record.id}/edit?coop_id=${coopIdFromUrl.value}`
        : `/finance/financial-records/${props.record.id}/edit`;
});

const handleBack = () => {
    if (isFromCoopContext.value) {
        router.get(`/cooperatives/${coopSlug.value}?tab=finance&subtab=financial-records`);
        return;
    }
    if (coopIdFromUrl.value) {
        router.get(`/finance/financial-records?coop_id=${coopIdFromUrl.value}`);
        return;
    }
    router.get('/finance/financial-records');
};

</script>

<template>
    <Head :title="`Finance - ${displayTitle(record)}`" />

    <FinanceShellLayout active-tab="financial-records" :hide-tabs="isFromCoopContext">
        <div class="space-y-6">
            <!-- Breadcrumb -->
            <div v-if="isFromCoopContext" class="text-sm flex items-center gap-2">
                <Link href="/cooperatives" class="text-primary hover:underline">Cooperatives</Link>
                <span class="text-muted-foreground">/</span>
                <Link :href="`/cooperatives/${coopSlug}`" class="text-primary hover:underline">{{ props.record.cooperative?.name || 'Cooperative' }}</Link>
                <span class="text-muted-foreground">/</span>
                <Link :href="isFromCoopContext ? `/cooperatives/${coopSlug}?tab=finance&subtab=financial-records` : '/finance/financial-records'" class="text-primary hover:underline">Financial Records</Link>
                <span class="text-muted-foreground">/</span>
                <span class="text-foreground">Record</span>
            </div>

            <!-- Header with Title and Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">{{ displayTitle(record) }}</h1>
                    <p class="text-sm text-muted-foreground">Read-only financial record details</p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" class="gap-2" @click="handleBack">
                        <ArrowLeft class="h-4 w-4" />
                        Back
                    </Button>
                    <Link v-if="props.permissions.can_edit" :href="editHref">
                        <Button size="sm" class="gap-2 bg-foreground text-background hover:bg-foreground/90">
                            <Edit class="h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Main Info Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-1">
                            <CardTitle class="text-lg">Basic Information</CardTitle>
                            <CardDescription>Core financial record details</CardDescription>
                        </div>
                        <Badge :class="statusBadgeClass(record.type)">
                            {{ formatTypeLabel(record.type) }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Type</p>
                            <p class="text-base font-medium text-foreground">{{ formatTypeLabel(record.type) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Period</p>
                            <p class="text-base font-medium text-foreground">{{ periodLabel(record.period) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Cooperative</p>
                            <p class="text-base font-medium text-foreground">{{ record.cooperative?.name || '—' }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Amount</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.amount) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Source</p>
                            <p class="text-base font-medium text-foreground">{{ displayText(record.source) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Date Recorded</p>
                            <p class="text-base font-medium text-foreground">{{ formatDate(record.date_recorded) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Description Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Description & Reference</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <p class="text-sm text-muted-foreground">Purpose / Description</p>
                        <p class="text-base text-foreground whitespace-pre-wrap">{{ displayText(record.purpose) }}</p>
                    </div>
                    <Separator />
                    <div class="space-y-2">
                        <p class="text-sm text-muted-foreground">Reference Document</p>
                        <p class="text-base font-medium text-foreground">{{ displayText(record.reference_doc) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Financial Summary Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-lg">Financial Summary</CardTitle>
                    <CardDescription>Comprehensive financial metrics</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Total Assets</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.total_assets) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Total Liabilities</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.total_liabilities) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Net Surplus</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.net_surplus) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Capital Build-up</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.capital_build_up) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">External Assistance</p>
                            <p class="text-base font-medium font-mono text-foreground">{{ formatPhilippinePeso(record.external_assistance_received) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Type of Assistance</p>
                            <p class="text-base font-medium text-foreground">{{ displayText(record.type_of_assistance) }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Record Metadata Card -->
            <Card class="bg-muted/50">
                <CardHeader>
                    <CardTitle class="text-sm">Record Information</CardTitle>
                </CardHeader>
                <CardContent class="text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Recorded By:</span>
                        <span class="font-medium text-foreground">{{ displayText(record.recorded_by) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Created:</span>
                        <span class="font-medium text-foreground">{{ formatDate(record.created_at) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Last Updated:</span>
                        <span class="font-medium text-foreground">{{ formatDate(record.updated_at) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </FinanceShellLayout>
</template>
