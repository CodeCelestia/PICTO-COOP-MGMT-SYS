<script setup lang="ts">
import { Link, useForm, usePage, router } from '@inertiajs/vue3';
import { Users, Save, X, MapPin, Building2 } from 'lucide-vue-next';
import { computed, onMounted, watch, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import { confirmAction, notifySuccess } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
}

interface Member {
    id: number;
    coop_id: number;
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
    date_joined: string | null;
    membership_type: string | null;
    membership_status: string | null;
    share_capital: string | null;
    educational_attainment: string | null;
    primary_livelihood: string | null;
    sector: string | null;
    cooperative: Cooperative;
}

interface MemberServiceAvailed {
    id: number;
    service_type: string;
    service_detail: string | null;
    date_availed: string;
    amount: string | null;
    status: string;
    reference_no: string | null;
    remarks: string | null;
    recorded_by: string | null;
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

interface Props {
    member: Member;
    cooperatives: Cooperative[];
    availableRoles: {
        id: number;
        name: string;
        level: number;
    }[];
    userAccount: {
        id: number;
        email: string;
        roles: { id: number; name: string; level: number }[];
    } | null;
    servicesAvailed: MemberServiceAvailed[];
    sectorHistory: MemberSectorHistory[];
}

const props = defineProps<Props>();

const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canUpdateMember = computed(() => permissions.value.includes('update members-profile'));
const canCreateService = computed(() => permissions.value.includes('create members-profile'));
const canEditService = computed(() => permissions.value.includes('update members-profile'));
const canDeleteService = computed(() => permissions.value.includes('delete members-profile'));
const showServiceActions = computed(() => canEditService.value || canDeleteService.value);

const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');

const queryParams = computed(() => {
    const [, queryString = ''] = (page.url || '').split('?');
    return new URLSearchParams(queryString);
});

const cooperativeIdFromContext = computed(() => queryParams.value.get('coop_id') || '');
const cooperativeContextFlag = computed(() => queryParams.value.get('context') === 'cooperative');
const returnToPath = computed(() => {
    const value = queryParams.value.get('return_to') || '';
    if (!value.startsWith('/') || value.startsWith('//')) {
        return '';
    }

    return value;
});

const form = useForm({
    coop_id: props.member.coop_id.toString(),
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    birth_date: props.member.birth_date || '',
    gender: props.member.gender || '',
    address: props.member.address || '',
    region: props.member.region || '',
    province: props.member.province || '',
    city_municipality: props.member.city_municipality || '',
    barangay: props.member.barangay || '',
    phone: props.member.phone || '',
    email: props.member.email || '',
    date_joined: props.member.date_joined || '',
    membership_type: props.member.membership_type || 'Regular',
    membership_status: props.member.membership_status || 'Active',
    share_capital: props.member.share_capital || '',
    educational_attainment: props.member.educational_attainment || '',
    primary_livelihood: props.member.primary_livelihood || '',
    sector: props.member.sector || '',
    role_ids: props.userAccount?.roles?.map(role => role.id) || [],
    context: '',
    return_to: '',
});

const primaryLivelihoodTags = ref<string[]>(
    (props.member.primary_livelihood || '')
        .split(',')
        .map((item) => item.trim())
        .filter(Boolean)
);
const newPrimaryLivelihood = ref('');

const addPrimaryLivelihood = () => {
    const value = newPrimaryLivelihood.value.trim();
    if (!value) return;
    if (!primaryLivelihoodTags.value.includes(value)) {
        primaryLivelihoodTags.value.push(value);
    }
    newPrimaryLivelihood.value = '';
};

const removePrimaryLivelihood = (index: number) => {
    primaryLivelihoodTags.value.splice(index, 1);
};

watch(primaryLivelihoodTags, (tags) => {
    form.primary_livelihood = tags.join(', ');
}, { deep: true, immediate: true });

const serviceForm = useForm({
    service_type: '',
    service_detail: '',
    date_availed: '',
    amount: '',
    status: 'Active',
    reference_no: '',
    remarks: '',
});

const editingServiceId = ref<number | null>(null);

const resetServiceForm = () => {
    serviceForm.reset();
    serviceForm.status = 'Active';
    editingServiceId.value = null;
};

const startEditService = (service: MemberServiceAvailed) => {
    editingServiceId.value = service.id;
    serviceForm.service_type = service.service_type;
    serviceForm.service_detail = service.service_detail || '';
    serviceForm.date_availed = service.date_availed || '';
    serviceForm.amount = service.amount || '';
    serviceForm.status = service.status || 'Active';
    serviceForm.reference_no = service.reference_no || '';
    serviceForm.remarks = service.remarks || '';
};

const submitService = () => {
    if (editingServiceId.value && !canEditService.value) return;
    if (!editingServiceId.value && !canCreateService.value) return;
    if (editingServiceId.value) {
        serviceForm.put(`/members/${props.member.id}/services-availed/${editingServiceId.value}`, {
            preserveScroll: true,
            onSuccess: resetServiceForm,
        });
        return;
    }

    serviceForm.post(`/members/${props.member.id}/services-availed`, {
        preserveScroll: true,
        onSuccess: resetServiceForm,
    });
};

const deleteService = async (service: MemberServiceAvailed) => {
    if (!canDeleteService.value) return;
    const confirmed = await confirmAction({
        title: 'Delete service availment?',
        text: 'This action cannot be undone.',
    });

    if (!confirmed) return;

    router.delete(`/members/${props.member.id}/services-availed/${service.id}`, {
        preserveScroll: true,
    });
};

const formatDate = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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

onMounted(async () => {
    form.context = cooperativeContextFlag.value ? 'cooperative' : '';
    form.return_to = returnToPath.value;

    await fetchRegions();
    
    if (props.member.region) {
        const region = regions.value.find(r => r.name === props.member.region);
        if (region) {
            selectedRegionCode.value = region.code;
            await fetchProvinces(region.code);
            
            if (props.member.province) {
                const province = provinces.value.find(p => p.name === props.member.province);
                if (province) {
                    selectedProvinceCode.value = province.code;
                    await fetchCities(province.code);
                    
                    if (props.member.city_municipality) {
                        const city = cities.value.find(c => c.name === props.member.city_municipality);
                        if (city) {
                            selectedCityCode.value = city.code;
                            await fetchBarangays(city.code);
                        }
                    }
                }
            }
        }
    }
});

watch(selectedRegionCode, (newRegion) => {
    if (newRegion) {
        const region = regions.value.find(r => r.code === newRegion);
        if (region && region.name !== form.region) {
            form.region = region.name;
            fetchProvinces(newRegion);
            form.province = '';
            form.city_municipality = '';
            form.barangay = '';
            selectedProvinceCode.value = '';
            selectedCityCode.value = '';
        }
    }
});

watch(selectedProvinceCode, (newProvince) => {
    if (newProvince) {
        const province = provinces.value.find(p => p.code === newProvince);
        if (province && province.name !== form.province) {
            form.province = province.name;
            fetchCities(newProvince);
            form.city_municipality = '';
            form.barangay = '';
            selectedCityCode.value = '';
        }
    }
});

watch(selectedCityCode, (newCity) => {
    if (newCity) {
        const city = cities.value.find(c => c.code === newCity);
        if (city && city.name !== form.city_municipality) {
            form.city_municipality = city.name;
            fetchBarangays(newCity);
            form.barangay = '';
        }
    }
});

const submit = () => {
    if (!canUpdateMember.value) return;

    form.context = cooperativeContextFlag.value ? 'cooperative' : '';
    form.return_to = returnToPath.value;

    form.put(`/members/${props.member.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            notifySuccess('Member updated successfully.');
            goBackToCooperativeMembers();
        },
    });
};

const goBackToCooperativeMembers = () => {
    if (window.history.length > 1) {
        window.history.back();
        return;
    }

    if (returnToPath.value) {
        router.get(returnToPath.value);
        return;
    }

    const cooperativeId = cooperativeIdFromContext.value || String(props.member.coop_id || '');
    if (cooperativeId) {
        router.get(`/members/management/${cooperativeId}?tab=members`);
        return;
    }

    router.get('/dashboard');
};

const cancel = () => {
    goBackToCooperativeMembers();
};

const toggleRole = (roleId: number) => {
    const index = form.role_ids.indexOf(roleId);
    if (index > -1) {
        form.role_ids.splice(index, 1);
    } else {
        form.role_ids.push(roleId);
    }
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 md:p-6">
            <section class="rounded-xl border border-border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Edit Member</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Update member profile for {{ member.first_name }} {{ member.last_name }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="`/members/${props.member.id}/services-availed`">
                        <Button variant="outline">Services Availed</Button>
                    </Link>
                    <Link :href="`/members/${props.member.id}/activity-participants`">
                        <Button variant="outline">Activity Participation</Button>
                    </Link>
                </div>
            </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Cooperative Association -->
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <Building2 class="h-5 w-5" />
                            Cooperative Association
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative <span class="text-red-500">*</span></Label>
                                <Select v-model="form.coop_id" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.coop_id }">
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
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <Users class="h-5 w-5" />
                            Personal Information
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="first_name">First Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    placeholder="Enter first name"
                                    :class="{ 'border-red-500': form.errors.first_name }"
                                />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.first_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="last_name">Last Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    placeholder="Enter last name"
                                    :class="{ 'border-red-500': form.errors.last_name }"
                                />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.last_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="birth_date">Birth Date</Label>
                                <Input
                                    id="birth_date"
                                    v-model="form.birth_date"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.birth_date }"
                                />
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.birth_date }}
                                </p>
                            </div>

                            <div>
                                <Label for="gender">Gender</Label>
                                <Select v-model="form.gender">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.gender }">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Male">Male</SelectItem>
                                        <SelectItem value="Female">Female</SelectItem>
                                        <SelectItem value="Other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.gender" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.gender }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact & Address Information -->
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <MapPin class="h-5 w-5" />
                            Contact & Address
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="member@example.com"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="phone">Phone Number</Label>
                                <Input
                                    id="phone"
                                    v-model="form.phone"
                                    type="tel"
                                    placeholder="+63 XXX XXX XXXX"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="address">Street Address / Building</Label>
                                <Textarea
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Street, building, house number..."
                                    :class="{ 'border-red-500': form.errors.address }"
                                    rows="2"
                                />
                                <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.address }}
                                </p>
                            </div>

                            <div>
                                <Label for="region">Region</Label>
                                <Select v-model="selectedRegionCode" :disabled="loading">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="region in regions" :key="region.code" :value="region.code">
                                            {{ region.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label for="province">Province</Label>
                                <Select v-model="selectedProvinceCode" :disabled="!selectedRegionCode || loading">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="province in provinces" :key="province.code" :value="province.code">
                                            {{ province.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label for="city">City / Municipality</Label>
                                <Select v-model="selectedCityCode" :disabled="!selectedProvinceCode || loading">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select city/municipality" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="city in cities" :key="city.code" :value="city.code">
                                            {{ city.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div>
                                <Label for="barangay">Barangay</Label>
                                <Select v-model="form.barangay" :disabled="!selectedCityCode || loading">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select barangay" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">
                                            {{ barangay.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <!-- Membership Information -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Membership Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="date_joined">Date Joined</Label>
                                <Input
                                    id="date_joined"
                                    v-model="form.date_joined"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.date_joined }"
                                />
                                <p v-if="form.errors.date_joined" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_joined }}
                                </p>
                            </div>

                            <div>
                                <Label for="membership_type">Membership Type</Label>
                                <Select v-model="form.membership_type">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.membership_type }">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Regular">Regular</SelectItem>
                                        <SelectItem value="Associate">Associate</SelectItem>
                                        <SelectItem value="Honorary">Honorary</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.membership_type" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.membership_type }}
                                </p>
                            </div>

                            <div>
                                <Label for="membership_status">Membership Status</Label>
                                <Select v-model="form.membership_status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.membership_status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Suspended">Suspended</SelectItem>
                                        <SelectItem value="Resigned">Resigned</SelectItem>
                                        <SelectItem value="Deceased">Deceased</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.membership_status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.membership_status }}
                                </p>
                            </div>

                            <div>
                                <Label for="share_capital">Share Capital (₱)</Label>
                                <Input
                                    id="share_capital"
                                    v-model="form.share_capital"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    :class="{ 'border-red-500': form.errors.share_capital }"
                                />
                                <p v-if="form.errors.share_capital" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.share_capital }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Socio-Economic Profile -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Socio-Economic Profile</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="educational_attainment">Educational Attainment</Label>
                                <Select v-model="form.educational_attainment">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.educational_attainment }">
                                        <SelectValue placeholder="Select educational level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="None">None</SelectItem>
                                        <SelectItem value="Elementary">Elementary</SelectItem>
                                        <SelectItem value="High School">High School</SelectItem>
                                        <SelectItem value="Vocational">Vocational</SelectItem>
                                        <SelectItem value="College">College</SelectItem>
                                        <SelectItem value="Post-Graduate">Post-Graduate</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.educational_attainment" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.educational_attainment }}
                                </p>
                            </div>

                            <div>
                                <Label for="sector">Sector</Label>
                                <Select v-model="form.sector">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.sector }">
                                        <SelectValue placeholder="Select sector" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Farmer">Farmer</SelectItem>
                                        <SelectItem value="Fishfolk">Fishfolk</SelectItem>
                                        <SelectItem value="Employee">Employee</SelectItem>
                                        <SelectItem value="Entrepreneur">Entrepreneur</SelectItem>
                                        <SelectItem value="Youth">Youth</SelectItem>
                                        <SelectItem value="Women">Women</SelectItem>
                                        <SelectItem value="Senior Citizen">Senior Citizen</SelectItem>
                                        <SelectItem value="PWD">PWD</SelectItem>
                                        <SelectItem value="Other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.sector" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.sector }}
                                </p>
                            </div>

                            <div class="md:col-span-2 space-y-3">
                                <Label for="primary_livelihood">Primary Livelihood</Label>
                                <div class="flex flex-col gap-2 md:flex-row md:items-start">
                                    <Input
                                        id="primary_livelihood"
                                        v-model="newPrimaryLivelihood"
                                        type="text"
                                        placeholder="e.g., Rice farming, Fish vending, etc."
                                        :class="{ 'border-red-500': form.errors.primary_livelihood }"
                                        @keyup.enter.prevent="addPrimaryLivelihood"
                                    />
                                    <Button type="button" class="h-11 shrink-0" @click="addPrimaryLivelihood">
                                        Add
                                    </Button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="(tag, index) in primaryLivelihoodTags"
                                        :key="`${tag}-${index}`"
                                        class="inline-flex items-center gap-2 rounded-full px-3 py-1"
                                    >
                                        <span>{{ tag }}</span>
                                        <button
                                            type="button"
                                            class="rounded-full p-1 transition hover:bg-slate-200 dark:hover:bg-slate-700"
                                            @click="removePrimaryLivelihood(index)"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </Badge>
                                </div>
                                <p v-if="form.errors.primary_livelihood" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.primary_livelihood }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Services Availed -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Member Coop Services Availed</h2>
                        <p class="mb-4 text-sm text-muted-foreground">
                            Track services availed by this member.
                        </p>

                        <form
                            v-if="canCreateService || canEditService"
                            @submit.prevent="submitService"
                            class="rounded-lg border border-border bg-muted/40 p-4"
                        >
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div>
                                    <Label for="service_type">Service Type</Label>
                                    <Select v-model="serviceForm.service_type">
                                        <SelectTrigger :class="{ 'border-red-500': serviceForm.errors.service_type }">
                                            <SelectValue placeholder="Select service" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Loan">Loan</SelectItem>
                                            <SelectItem value="Marketing">Marketing</SelectItem>
                                            <SelectItem value="Training">Training</SelectItem>
                                            <SelectItem value="Savings">Savings</SelectItem>
                                            <SelectItem value="Insurance">Insurance</SelectItem>
                                            <SelectItem value="Technical Assistance">Technical Assistance</SelectItem>
                                            <SelectItem value="Other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="serviceForm.errors.service_type" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.service_type }}
                                    </p>
                                </div>

                                <div>
                                    <Label for="date_availed">Date Availed</Label>
                                    <Input
                                        id="date_availed"
                                        v-model="serviceForm.date_availed"
                                        type="date"
                                        :class="{ 'border-red-500': serviceForm.errors.date_availed }"
                                    />
                                    <p v-if="serviceForm.errors.date_availed" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.date_availed }}
                                    </p>
                                </div>

                                <div>
                                    <Label for="status">Status</Label>
                                    <Select v-model="serviceForm.status">
                                        <SelectTrigger :class="{ 'border-red-500': serviceForm.errors.status }">
                                            <SelectValue placeholder="Select status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Active">Active</SelectItem>
                                            <SelectItem value="Completed">Completed</SelectItem>
                                            <SelectItem value="Pending">Pending</SelectItem>
                                            <SelectItem value="Cancelled">Cancelled</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="serviceForm.errors.status" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.status }}
                                    </p>
                                </div>

                                <div>
                                    <Label for="amount">Amount (Optional)</Label>
                                    <Input
                                        id="amount"
                                        v-model="serviceForm.amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="0.00"
                                        :class="{ 'border-red-500': serviceForm.errors.amount }"
                                    />
                                    <p v-if="serviceForm.errors.amount" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.amount }}
                                    </p>
                                </div>

                                <div>
                                    <Label for="reference_no">Reference No.</Label>
                                    <Input
                                        id="reference_no"
                                        v-model="serviceForm.reference_no"
                                        type="text"
                                        placeholder="Reference number"
                                        :class="{ 'border-red-500': serviceForm.errors.reference_no }"
                                    />
                                    <p v-if="serviceForm.errors.reference_no" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.reference_no }}
                                    </p>
                                </div>

                                <div class="md:col-span-3">
                                    <Label for="service_detail">Service Detail</Label>
                                    <Input
                                        id="service_detail"
                                        v-model="serviceForm.service_detail"
                                        type="text"
                                        placeholder="Add details about the service"
                                        :class="{ 'border-red-500': serviceForm.errors.service_detail }"
                                    />
                                    <p v-if="serviceForm.errors.service_detail" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.service_detail }}
                                    </p>
                                </div>

                                <div class="md:col-span-3">
                                    <Label for="remarks">Remarks</Label>
                                    <Textarea
                                        id="remarks"
                                        v-model="serviceForm.remarks"
                                        rows="2"
                                        placeholder="Notes about this service availment"
                                        :class="{ 'border-red-500': serviceForm.errors.remarks }"
                                    />
                                    <p v-if="serviceForm.errors.remarks" class="mt-1 text-sm text-red-500">
                                        {{ serviceForm.errors.remarks }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-end gap-2">
                                <Button
                                    v-if="editingServiceId"
                                    type="button"
                                    variant="outline"
                                    @click="resetServiceForm"
                                >
                                    Cancel Edit
                                </Button>
                                <Button type="submit" :disabled="serviceForm.processing">
                                    {{ editingServiceId ? 'Update Service' : 'Add Service' }}
                                </Button>
                            </div>
                        </form>

                        <div class="mt-6 rounded-lg border border-border bg-card shadow-sm">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Service</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Notes</TableHead>
                                        <TableHead v-if="showServiceActions" class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="servicesAvailed.length === 0">
                                        <TableCell :colspan="showServiceActions ? 7 : 6" class="text-center text-gray-500">
                                            No service availments recorded yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="service in servicesAvailed" :key="service.id">
                                        <TableCell>
                                            <div class="font-medium text-gray-900">{{ service.service_type }}</div>
                                            <div class="text-xs text-gray-500" v-if="service.service_detail">
                                                {{ service.service_detail }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-sm text-gray-600">{{ formatDate(service.date_availed) }}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline">{{ service.status }}</Badge>
                                        </TableCell>
                                        <TableCell class="text-sm text-gray-600">
                                            {{ service.amount || 'N/A' }}
                                        </TableCell>
                                        <TableCell class="text-sm text-gray-600">
                                            {{ service.reference_no || 'N/A' }}
                                        </TableCell>
                                        <TableCell class="text-sm text-gray-600">
                                            {{ service.remarks || 'N/A' }}
                                        </TableCell>
                                        <TableCell v-if="showServiceActions" class="text-right">
                                            <div class="flex justify-end gap-2">
                                                <Button
                                                    v-if="canEditService"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="table-action-btn table-action-edit"
                                                    @click="startEditService(service)"
                                                >
                                                    Edit
                                                </Button>
                                                <Button
                                                    v-if="canDeleteService"
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    class="table-action-btn table-action-delete text-red-600 hover:text-red-700"
                                                    @click="deleteService(service)"
                                                >
                                                    Delete
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <!-- Sector History -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Member Sector History</h2>
                        <p class="mb-4 text-sm text-muted-foreground">
                            Track changes to sector classification and livelihood.
                        </p>

                        <div class="rounded-lg border border-border bg-card shadow-sm">
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
                                        <TableCell colspan="4" class="text-center text-gray-500">
                                            No sector history recorded yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="history in sectorHistory" :key="history.id">
                                        <TableCell class="text-sm text-gray-600">
                                            {{ formatDateTime(history.changed_at) }}
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm text-gray-900">{{ history.new_sector || 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">
                                                from {{ history.previous_sector || 'N/A' }}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div class="text-sm text-gray-900">{{ history.new_livelihood || 'N/A' }}</div>
                                            <div class="text-xs text-gray-500">
                                                from {{ history.previous_livelihood || 'N/A' }}
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-sm text-gray-600">
                                            {{ history.changed_by || 'N/A' }}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>

                    <!-- Role Assignment -->
                    <div v-if="(page.props.auth?.permissions || []).includes('assign roles')">
                        <h2 class="mb-4 text-lg font-semibold text-foreground">Role Assignment</h2>
                        <Label>Assign Roles</Label>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <Badge
                                v-for="role in availableRoles"
                                :key="role.id"
                                @click="toggleRole(role.id)"
                                :class="[
                                    'cursor-pointer border-2 transition-all',
                                    form.role_ids.includes(role.id)
                                        ? 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-700'
                                        : 'bg-background text-muted-foreground border-border hover:border-foreground/40'
                                ]"
                            >
                                <span class="flex items-center gap-1">
                                    {{ role.name }}
                                    <span v-if="form.role_ids.includes(role.id)" class="ml-1">✓</span>
                                </span>
                            </Badge>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Select one or more roles. If none selected, the Member role will be applied.
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canUpdateMember" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Updating...' : 'Update Member' }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
