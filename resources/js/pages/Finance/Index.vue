<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { DollarSign, FileText, HandCoins } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const page = usePage();
const roles = (page.props.auth?.roles as string[]) || [];
const isSuperOrProv = roles.includes('Super Admin') || roles.includes('Provincial Admin');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Finance', href: '/finance' },
];

const fundingSourcesLink = isSuperOrProv ? '/activity-funding-sources/select' : '/activity-funding-sources';
const financialRecordsLink = isSuperOrProv ? '/financial-records/select' : '/financial-records';
const externalSupportsLink = isSuperOrProv ? '/external-supports/select' : '/external-supports';
</script>

<template>
    <Head title="Finance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4 sm:p-6">
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardHeader class="pb-3">
                    <CardTitle class="text-2xl font-semibold text-foreground">Finance</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        Manage funding sources, financial records, and external support in one place.
                    </p>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-2">
                        <Link :href="fundingSourcesLink">
                            <Button class="gap-2" variant="outline">
                                <HandCoins class="h-4 w-4" />
                                Funding Sources
                            </Button>
                        </Link>
                        <Link :href="financialRecordsLink">
                            <Button class="gap-2" variant="outline">
                                <FileText class="h-4 w-4" />
                                Financial Records
                            </Button>
                        </Link>
                        <Link :href="externalSupportsLink">
                            <Button class="gap-2" variant="outline">
                                <DollarSign class="h-4 w-4" />
                                External Supports
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
