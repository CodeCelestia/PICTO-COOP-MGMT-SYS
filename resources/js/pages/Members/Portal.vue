<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Pencil, Save } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { notifyError, notifySuccess } from '@/lib/alerts';
import { useInitials } from '@/composables/useInitials';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    region: string | null;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    phone: string | null;
    email: string | null;
}

interface Cooperative {
    id: number;
    name: string;
    registration_number: string | null;
    coop_type: string | null;
    address: string | null;
    province: string | null;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: string | null;
    accreditation_status: string | null;
}

interface MemberSectorHistory {
    id: number;
    previous_sector: string | null;
    new_sector: string | null;
    previous_livelihood: string | null;
    new_livelihood: string | null;
    change_reason: string | null;
    changed_by: string | null;
    changed_at: string | null;
}

const props = defineProps<{
    member: Member;
    cooperative: Cooperative | null;
    sectorHistory: MemberSectorHistory[];
    servicesCount: number;
    activitiesCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Profile', href: '/member-portal' },
];

const profileOverview = ref({
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    birth_date: props.member.birth_date ?? '',
    gender: props.member.gender ?? '',
    address: props.member.address ?? '',
    region: props.member.region ?? '',
    province: props.member.province ?? '',
    city_municipality: props.member.city_municipality ?? '',
    barangay: props.member.barangay ?? '',
    phone: props.member.phone ?? '',
    email: props.member.email ?? '',
});

const form = useForm({
    first_name: profileOverview.value.first_name,
    last_name: profileOverview.value.last_name,
    birth_date: profileOverview.value.birth_date,
    gender: profileOverview.value.gender,
    address: profileOverview.value.address,
    region: profileOverview.value.region,
    province: profileOverview.value.province,
    city_municipality: profileOverview.value.city_municipality,
    barangay: profileOverview.value.barangay,
    phone: profileOverview.value.phone,
    email: profileOverview.value.email,
    profile_photo: null as File | null,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user as { name?: string; avatar?: string } | undefined);
const { getInitials } = useInitials();
const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();
const isUpdateProfileModalOpen = ref(false);
const photoPreview = ref<string | null>(null);
const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');
const isSyncingAddressSelectors = ref(false);

const normalizeLocationName = (value: string) =>
    String(value || '')
        .toLowerCase()
        .replace(/\bcity\s+of\b/g, '')
        .replace(/\bmunicipality\s+of\b/g, '')
        .replace(/\bcity\b/g, '')
        .replace(/\bmunicipality\b/g, '')
        .replace(/\bmun\.?\b/g, '')
        .replace(/\bbrgy\.?\b/g, 'barangay')
        .replace(/[^a-z0-9]/g, '')
        .trim();

const findMatchingByName = <T extends { name: string }>(items: T[], target: string) => {
    if (!target) return undefined;

    const normalizedTarget = normalizeLocationName(target);

    return items.find((item) => {
        const normalizedItem = normalizeLocationName(item.name);

        return (
            normalizedItem === normalizedTarget ||
            normalizedItem.includes(normalizedTarget) ||
            normalizedTarget.includes(normalizedItem)
        );
    });
};

const syncFormFromOverview = () => {
    form.first_name = profileOverview.value.first_name;
    form.last_name = profileOverview.value.last_name;
    form.birth_date = profileOverview.value.birth_date;
    form.gender = profileOverview.value.gender;
    form.address = profileOverview.value.address;
    form.region = profileOverview.value.region;
    form.province = profileOverview.value.province;
    form.city_municipality = profileOverview.value.city_municipality;
    form.barangay = profileOverview.value.barangay;
    form.phone = profileOverview.value.phone;
    form.email = profileOverview.value.email;
    form.profile_photo = null;
};

const syncAddressSelectors = async () => {
    isSyncingAddressSelectors.value = true;

    try {
    if (!regions.value.length) {
        await fetchRegions();
    }

    selectedRegionCode.value = '';
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';

    if (!form.region) {
        return;
    }

    const region = findMatchingByName(regions.value, form.region);
    if (!region) {
        return;
    }

    selectedRegionCode.value = region.code;
    await fetchProvinces(region.code);

    if (!form.province) {
        return;
    }

    const province = findMatchingByName(provinces.value, form.province);
    if (!province) {
        return;
    }

    selectedProvinceCode.value = province.code;
    await fetchCities(province.code);

    if (!form.city_municipality) {
        return;
    }

    const city = findMatchingByName(cities.value, form.city_municipality);
    if (!city) {
        return;
    }

    selectedCityCode.value = city.code;
    await fetchBarangays(city.code);
    } finally {
        isSyncingAddressSelectors.value = false;
    }
};

onMounted(async () => {
    await fetchRegions();
    await syncAddressSelectors();
});

watch(selectedRegionCode, (newRegion) => {
    if (isSyncingAddressSelectors.value) {
        return;
    }

    if (newRegion) {
        const region = regions.value.find((item) => item.code === newRegion);
        if (region && region.name !== form.region) {
            form.region = region.name;
            form.province = '';
            form.city_municipality = '';
            form.barangay = '';
            selectedProvinceCode.value = '';
            selectedCityCode.value = '';
        }
        void fetchProvinces(newRegion);
        return;
    }

    form.region = '';
    form.province = '';
    form.city_municipality = '';
    form.barangay = '';
    selectedProvinceCode.value = '';
    selectedCityCode.value = '';
});

watch(selectedProvinceCode, (newProvince) => {
    if (isSyncingAddressSelectors.value) {
        return;
    }

    if (newProvince) {
        const province = provinces.value.find((item) => item.code === newProvince);
        if (province && province.name !== form.province) {
            form.province = province.name;
            form.city_municipality = '';
            form.barangay = '';
            selectedCityCode.value = '';
        }
        void fetchCities(newProvince);
        return;
    }

    form.province = '';
    form.city_municipality = '';
    form.barangay = '';
    selectedCityCode.value = '';
});

watch(selectedCityCode, (newCity) => {
    if (isSyncingAddressSelectors.value) {
        return;
    }

    if (newCity) {
        const city = cities.value.find((item) => item.code === newCity);
        if (city && city.name !== form.city_municipality) {
            form.city_municipality = city.name;
            form.barangay = '';
        }
        void fetchBarangays(newCity);
        return;
    }

    form.city_municipality = '';
    form.barangay = '';
});

const openUpdateProfileModal = () => {
    syncFormFromOverview();
    form.clearErrors();
    photoPreview.value = null;
    isUpdateProfileModalOpen.value = true;
    void syncAddressSelectors();
};

const closeUpdateProfileModal = () => {
    isUpdateProfileModalOpen.value = false;
    syncFormFromOverview();
    form.clearErrors();
    photoPreview.value = null;
};

const onPhotoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] || null;
    form.profile_photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
};

const submitProfileUpdate = () => {
    form.put('/member-portal', {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            profileOverview.value = {
                first_name: form.first_name,
                last_name: form.last_name,
                birth_date: form.birth_date,
                gender: form.gender,
                address: form.address,
                region: form.region,
                province: form.province,
                city_municipality: form.city_municipality,
                barangay: form.barangay,
                phone: form.phone,
                email: form.email,
            };

            isUpdateProfileModalOpen.value = false;
            notifySuccess('Profile updated successfully.');
        },
        onError: () => {
            notifyError('Please review the highlighted profile fields and try again.');
        },
    });
};

const formatDateTime = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

</script>

<template>
    <Head title="My Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="h-full rounded-xl border border-border bg-card p-6 shadow-sm lg:col-span-2">
                    <CardHeader class="px-0 pt-0">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <CardTitle class="text-2xl font-bold tracking-tight text-foreground">Profile Overview</CardTitle>
                                <CardDescription class="mt-1 text-base text-muted-foreground">Quick snapshot of your account and contact details.</CardDescription>
                            </div>
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                <Link href="/dashboard">
                                    <Button variant="outline" class="gap-2">
                                        <ArrowLeft class="h-4 w-4" />
                                        Back to Dashboard
                                    </Button>
                                </Link>
                                <Button class="gap-2 bg-[#0e7ea0] text-white hover:bg-[#1294bc]" @click="openUpdateProfileModal">
                                    <Pencil class="h-4 w-4" />
                                    Update Profile
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6 px-0 pb-0">

                    <div class="flex flex-col gap-4 rounded-lg border border-border/70 bg-background/60 p-4 sm:flex-row sm:items-center">
                        <Avatar class="h-16 w-16 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="photoPreview || authUser?.avatar"
                                :src="photoPreview || authUser?.avatar || ''"
                                :alt="authUser?.name || 'Profile photo'"
                            />
                            <AvatarFallback class="rounded-full text-lg font-semibold">
                                {{ getInitials(authUser?.name || 'User') }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="space-y-1">
                            <p class="text-base font-semibold text-foreground">{{ profileOverview.first_name }} {{ profileOverview.last_name }}</p>
                            <p class="text-sm text-muted-foreground">{{ profileOverview.email || 'N/A' }}</p>
                            <p class="text-xs text-muted-foreground">{{ cooperative?.name || 'No cooperative assigned' }}</p>
                        </div>
                    </div>

                    <div class="grid gap-x-6 gap-y-5 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">First Name</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.first_name || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Last Name</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.last_name || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Email</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.email || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Contact</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.phone || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Gender</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.gender || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Birth Date</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.birth_date || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Cooperative</p>
                            <p class="text-base font-medium text-foreground">{{ cooperative?.name || 'N/A' }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <p class="text-sm font-medium text-muted-foreground">Address</p>
                            <p class="text-base font-medium text-foreground">{{ profileOverview.address || 'N/A' }}</p>
                        </div>
                    </div>
                    </CardContent>
                </Card>

                <Card class="h-full rounded-xl border border-border bg-card p-6 shadow-sm">
                    <CardHeader class="px-0 pt-0">
                        <CardTitle class="text-2xl font-bold tracking-tight text-foreground">My Cooperative</CardTitle>
                        <CardDescription class="text-base text-muted-foreground">Your cooperative assignment and details.</CardDescription>
                    </CardHeader>

                    <div v-if="cooperative" class="space-y-4 text-base">
                        <div>
                            <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Name</div>
                            <div class="text-lg font-semibold text-foreground">{{ cooperative.name }}</div>
                        </div>
                        <div class="grid gap-4">
                            <div v-if="cooperative.registration_number">
                                <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Registration No.</div>
                                <div class="text-base font-medium text-foreground">{{ cooperative.registration_number }}</div>
                            </div>
                            <div v-if="cooperative.coop_type">
                                <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Type</div>
                                <div class="text-base font-medium text-foreground">{{ cooperative.coop_type }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Location</div>
                                <div class="text-base font-medium text-foreground">
                                    {{ cooperative.city_municipality || 'N/A' }}
                                    {{ cooperative.province ? `, ${cooperative.province}` : '' }}
                                </div>
                            </div>
                            <div v-if="cooperative.status">
                                <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Status</div>
                                <div class="text-base font-medium text-foreground">{{ cooperative.status }}</div>
                            </div>
                            <div v-if="cooperative.accreditation_status">
                                <div class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">Accreditation</div>
                                <div class="text-base font-medium text-foreground">{{ cooperative.accreditation_status }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="rounded-lg border border-dashed border-border/70 bg-background/60 px-4 py-3 text-sm text-muted-foreground">
                        No cooperative is assigned to your account yet. Please contact your cooperative admin.
                    </div>
                </Card>
            </div>

            <Card class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <CardHeader class="px-0 pt-0">
                    <CardTitle class="text-base font-semibold text-foreground">My Services & Activities</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">View your service availments and participation history.</CardDescription>
                </CardHeader>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-border bg-background/60 p-4">
                        <div class="text-sm text-muted-foreground">Services Availed</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground">{{ servicesCount }}</div>
                        <Link href="/member-portal/services" class="mt-4 inline-flex">
                            <Button variant="outline">View Services</Button>
                        </Link>
                    </div>
                    <div class="rounded-lg border border-border bg-background/60 p-4">
                        <div class="text-sm text-muted-foreground">Activity Participation</div>
                        <div class="mt-2 text-2xl font-semibold text-foreground">{{ activitiesCount }}</div>
                        <Link href="/member-portal/activities" class="mt-4 inline-flex">
                            <Button variant="outline">View Activities</Button>
                        </Link>
                    </div>
                </div>
            </Card>

            <Card class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <CardHeader class="px-0 pt-0">
                    <CardTitle class="text-base font-semibold text-foreground">Member Sector History</CardTitle>
                    <CardDescription class="text-sm text-muted-foreground">Updates to your sector and livelihood classification.</CardDescription>
                </CardHeader>

                <div class="rounded-lg border border-border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Changed At</TableHead>
                                <TableHead>Sector</TableHead>
                                <TableHead>Livelihood</TableHead>
                                <TableHead>Changed By</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="sectorHistory.length === 0">
                                <TableCell colspan="4" class="text-center text-muted-foreground">
                                    No sector history recorded yet.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="history in sectorHistory" :key="history.id">
                                <TableCell class="text-sm text-muted-foreground">
                                    {{ formatDateTime(history.changed_at) }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ history.new_sector || 'N/A' }}</div>
                                    <div class="text-xs text-muted-foreground">from {{ history.previous_sector || 'N/A' }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-foreground">{{ history.new_livelihood || 'N/A' }}</div>
                                    <div class="text-xs text-muted-foreground">from {{ history.previous_livelihood || 'N/A' }}</div>
                                </TableCell>
                                <TableCell class="text-sm text-muted-foreground">{{ history.changed_by || 'N/A' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </Card>

            <Dialog v-model:open="isUpdateProfileModalOpen">
                <DialogContent class="w-full max-w-3xl sm:max-w-3xl max-h-[85vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Update Profile</DialogTitle>
                        <DialogDescription>
                            Edit your personal details below and save changes.
                        </DialogDescription>
                    </DialogHeader>

                    <form class="space-y-5" @submit.prevent="submitProfileUpdate">
                        <div class="flex flex-col gap-4 rounded-lg border border-border/70 bg-background/60 p-4 sm:flex-row sm:items-center">
                            <Avatar class="h-16 w-16 overflow-hidden rounded-full">
                                <AvatarImage
                                    v-if="photoPreview || authUser?.avatar"
                                    :src="photoPreview || authUser?.avatar || ''"
                                    :alt="authUser?.name || 'Profile photo'"
                                />
                                <AvatarFallback class="rounded-full text-lg font-semibold">
                                    {{ getInitials(authUser?.name || 'User') }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1 space-y-2">
                                <Label for="profile_photo">Profile Photo</Label>
                                <Input
                                    id="profile_photo"
                                    type="file"
                                    accept="image/*"
                                    @change="onPhotoChange"
                                />
                                <InputError :message="form.errors.profile_photo" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_name" class="flex items-center gap-1">
                                    First name
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input id="first_name" v-model="form.first_name" placeholder="Enter your first name" />
                                <InputError :message="form.errors.first_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="last_name" class="flex items-center gap-1">
                                    Last name
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input id="last_name" v-model="form.last_name" placeholder="Enter your last name" />
                                <InputError :message="form.errors.last_name" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="birth_date">Birth date</Label>
                                <Input id="birth_date" type="date" v-model="form.birth_date" />
                                <InputError :message="form.errors.birth_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="gender">Gender</Label>
                                <select
                                    id="gender"
                                    v-model="form.gender"
                                    class="h-10 rounded-md border border-input bg-background px-3 text-sm text-foreground"
                                >
                                    <option value="">Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError :message="form.errors.gender" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="email" class="flex items-center gap-1">
                                    Email
                                    <span class="text-destructive">*</span>
                                </Label>
                                <Input id="email" type="email" v-model="form.email" placeholder="you@example.com" />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="form.phone" placeholder="09XXXXXXXXX" />
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="form.address" placeholder="Street, subdivision, or landmark" />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="region">Region</Label>
                                <Select v-model="selectedRegionCode" :disabled="loading">
                                    <SelectTrigger id="region">
                                        <SelectValue :placeholder="form.region || 'Select region'" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="region in regions" :key="region.code" :value="region.code">
                                            {{ region.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.region" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="province">Province</Label>
                                <Select v-model="selectedProvinceCode" :disabled="!selectedRegionCode || loading">
                                    <SelectTrigger id="province">
                                        <SelectValue :placeholder="form.province || 'Select province'" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="province in provinces" :key="province.code" :value="province.code">
                                            {{ province.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.province" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="city_municipality">City/Municipality</Label>
                                <Select v-model="selectedCityCode" :disabled="!selectedProvinceCode || loading">
                                    <SelectTrigger id="city_municipality">
                                        <SelectValue :placeholder="form.city_municipality || 'Select city/municipality'" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="city in cities" :key="city.code" :value="city.code">
                                            {{ city.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.city_municipality" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="barangay">Barangay</Label>
                            <Select v-model="form.barangay" :disabled="!selectedCityCode || loading">
                                <SelectTrigger id="barangay">
                                    <SelectValue :placeholder="form.barangay || 'Select barangay'" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">
                                        {{ barangay.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.barangay" />
                        </div>

                        <DialogFooter>
                            <Button type="button" variant="outline" @click="closeUpdateProfileModal" :disabled="form.processing">
                                Cancel
                            </Button>
                            <Button type="submit" class="gap-2 bg-[#0e7ea0] text-white hover:bg-[#1294bc]" :disabled="form.processing">
                                <Save class="h-4 w-4" />
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
