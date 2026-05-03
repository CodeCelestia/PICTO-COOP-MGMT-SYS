<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, BadgeCheck, Briefcase, Building2, FileText, MapPin, Save, User, UserPlus, X } from 'lucide-vue-next';
import { computed, onMounted, watch, ref } from 'vue';
import { useFormUx } from '@/composables/useFormUx';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Textarea } from '@/components/ui/textarea';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';
import { notifySuccess } from '@/lib/alerts';

interface Cooperative {
    id: number;
    name: string;
    region?: string | null;
    classification?: string | null;
    status?: string | null;
}

interface Props {
    cooperatives: Cooperative[];
    cooperative?: Cooperative | null;
    canCreateUserAccounts: boolean;
    availableRoles: {
        id: number;
        name: string;
        level: number;
    }[];
}

const props = defineProps<Props>();

const page = usePage();
const permissions = computed<string[]>(() => (page.props.auth?.permissions as string[]) || []);
const canCreateMember = computed(() => permissions.value.includes('create members-profile'));

const queryParams = computed(() => {
    const [, queryString = ''] = (page.url || '').split('?');
    return new URLSearchParams(queryString);
});

const cooperativeIdFromContext = computed(() => queryParams.value.get('coop_id') || '');
const cooperativeContextFlag = computed(() => queryParams.value.get('context') === 'cooperative');
const cooperative = computed(() => props.cooperative || props.cooperatives.find((item) => String(item.id) === cooperativeIdFromContext.value) || null);
const returnToPath = computed(() => {
    const value = queryParams.value.get('return_to') || '';
    if (!value.startsWith('/') || value.startsWith('//')) {
        return '';
    }

    return value;
});

const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');

const form = useForm({
    coop_id: '',
    first_name: '',
    last_name: '',
    birth_date: '',
    gender: '',
    address: '',
    region: '',
    province: '',
    city_municipality: '',
    barangay: '',
    phone: '',
    email: '',
    date_joined: '',
    membership_type: 'Regular',
    membership_status: 'Active',
    share_capital: '',
    educational_attainment: '',
    primary_livelihood: '',
    sector: '',
    role_ids: [] as number[],
    context: '',
    return_to: '',
});

const { isPreFilling, isDirty, showErrorShake, inputErrorClass, clearError, scrollToFirstError, triggerErrorShake, handleCancel, markClean } = useFormUx(form);

const primaryLivelihoodTags = ref<string[]>([]);
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
}, { deep: true });

onMounted(() => {
    fetchRegions();

    if (cooperative.value?.id && !form.coop_id) {
        form.coop_id = String(cooperative.value.id);
    } else if (cooperativeIdFromContext.value && !form.coop_id) {
        form.coop_id = cooperativeIdFromContext.value;
    }

    form.context = cooperativeContextFlag.value ? 'cooperative' : '';
    form.return_to = returnToPath.value;
});

watch(selectedRegionCode, (newRegion) => {
    if (newRegion) {
        const region = regions.value.find(r => r.code === newRegion);
        form.region = region?.name || '';
        fetchProvinces(newRegion);
        form.province = '';
        form.city_municipality = '';
        form.barangay = '';
        selectedProvinceCode.value = '';
        selectedCityCode.value = '';
    }
});

watch(selectedProvinceCode, (newProvince) => {
    if (newProvince) {
        const province = provinces.value.find(p => p.code === newProvince);
        form.province = province?.name || '';
        fetchCities(newProvince);
        form.city_municipality = '';
        form.barangay = '';
        selectedCityCode.value = '';
    }
});

watch(selectedCityCode, (newCity) => {
    if (newCity) {
        const city = cities.value.find(c => c.code === newCity);
        form.city_municipality = city?.name || '';
        fetchBarangays(newCity);
        form.barangay = '';
    }
});

const submit = () => {
    if (!canCreateMember.value) return;

    form.context = cooperativeContextFlag.value ? 'cooperative' : '';
    form.return_to = returnToPath.value;

    form.post('/members', {
        preserveScroll: true,
        onSuccess: () => {
            markClean();
            notifySuccess('Member registered successfully.');
        },
        onError: () => {
            triggerErrorShake();
            scrollToFirstError();
        },
    });
};

const goBackToCooperativeMembers = () => {
    window.history.back();
};

// use composable-provided handler which defaults to window.history.back()
// `handleCancel` is provided by the composable

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
            <Card class="border-border/80 bg-card/95 shadow-sm">
                <CardContent class="p-5 md:p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1 class="text-xl font-semibold tracking-tight text-foreground md:text-3xl">Register New Member</h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Fill in the member's details to register them under the selected cooperative.
                            </p>
                        </div>
                        <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBackToCooperativeMembers">
                            <ArrowLeft class="h-4 w-4" />
                            Back
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <div v-if="cooperative" class="mb-6 flex items-center gap-3 rounded-lg border border-border/80 bg-card p-4 shadow-sm ring-1 ring-foreground/5 dark:ring-white/5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground">
                    <Building2 class="h-5 w-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="mb-0.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Registering member under</p>
                    <p class="truncate text-sm font-semibold text-foreground">{{ cooperative.name }}</p>
                    <p v-if="cooperative.region || cooperative.classification" class="mt-0.5 text-xs text-muted-foreground">
                        {{ cooperative.region || '' }}
                        {{ cooperative.region && cooperative.classification ? '·' : '' }}
                        {{ cooperative.classification || '' }}
                    </p>
                </div>

                <div class="shrink-0">
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                        {{ cooperative.status ?? 'Active' }}
                    </span>
                </div>
            </div>

            <form @submit.prevent="submit" :class="{ 'animate-shake': showErrorShake }" class="space-y-6">
                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <User class="h-5 w-5" />
                            Personal Information
                        </CardTitle>
                        <CardDescription>Capture the core identity details of the member.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="first_name">First Name <span class="text-red-500 ml-0.5">*</span></Label>
                                <Input id="first_name" v-model="form.first_name" type="text" required placeholder="Enter first name" :class="inputErrorClass('first_name')" @input="clearError('first_name')" />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-500">{{ form.errors.first_name }}</p>
                            </div>

                            <div>
                                <Label for="last_name">Last Name <span class="text-red-500 ml-0.5">*</span></Label>
                                <Input id="last_name" v-model="form.last_name" type="text" required placeholder="Enter last name" :class="inputErrorClass('last_name')" @input="clearError('last_name')" />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-500">{{ form.errors.last_name }}</p>
                            </div>

                            <div>
                                <Label for="birth_date">Birth Date <span class="text-red-500 ml-0.5">*</span></Label>
                                <Input id="birth_date" v-model="form.birth_date" type="date" required :class="inputErrorClass('birth_date')" @input="clearError('birth_date')" />
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-500">{{ form.errors.birth_date }}</p>
                            </div>

                            <div>
                                <Label for="gender">Gender <span class="text-red-500 ml-0.5">*</span></Label>
                                <Select v-model="form.gender" required @update:modelValue="() => clearError('gender')">
                                    <SelectTrigger :class="inputErrorClass('gender')">
                                        <SelectValue placeholder="Select gender" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Male">Male</SelectItem>
                                        <SelectItem value="Female">Female</SelectItem>
                                        <SelectItem value="Other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.gender" class="mt-1 text-sm text-red-500">{{ form.errors.gender }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <MapPin class="h-5 w-5" />
                            Contact Information
                        </CardTitle>
                        <CardDescription>Enter contact details and the member's home address.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="email">Email Address <span class="text-red-500 ml-0.5">*</span></Label>
                                <Input id="email" v-model="form.email" type="email" placeholder="member@example.com" :class="inputErrorClass('email')" @input="clearError('email')" />
                                <p class="mt-1 text-xs text-muted-foreground">This will be used as the account email.</p>
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <Label for="phone">Phone Number <span class="ml-1 text-xs text-muted-foreground">(Optional)</span></Label>
                                <Input id="phone" v-model="form.phone" type="tel" placeholder="+63 XXX XXX XXXX" :class="inputErrorClass('phone')" @input="clearError('phone')" />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="address">Street Address / Building <span class="text-red-500 ml-0.5">*</span></Label>
                                <Textarea id="address" v-model="form.address" placeholder="Street, building, house number..." required rows="2" :class="inputErrorClass('address')" @input="clearError('address')" />
                                <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
                            </div>

                            <div>
                                <Label for="region">Region</Label>
                                <Select v-model="selectedRegionCode" :disabled="loading">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="region in regions" :key="region.code" :value="region.code">{{ region.name }}</SelectItem>
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
                                        <SelectItem v-for="province in provinces" :key="province.code" :value="province.code">{{ province.name }}</SelectItem>
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
                                        <SelectItem v-for="city in cities" :key="city.code" :value="city.code">{{ city.name }}</SelectItem>
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
                                        <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">{{ barangay.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <BadgeCheck class="h-5 w-5" />
                            Membership Information
                        </CardTitle>
                        <CardDescription>Set the member's registration and membership status details.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="date_joined">Date Joined</Label>
                                <Input id="date_joined" v-model="form.date_joined" type="date" :class="{ 'border-red-500': form.errors.date_joined }" />
                                <p v-if="form.errors.date_joined" class="mt-1 text-sm text-red-500">{{ form.errors.date_joined }}</p>
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
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.membership_type" class="mt-1 text-sm text-red-500">{{ form.errors.membership_type }}</p>
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
                                <p v-if="form.errors.membership_status" class="mt-1 text-sm text-red-500">{{ form.errors.membership_status }}</p>
                            </div>

                            <div>
                                <Label for="share_capital">Share Capital (₱)</Label>
                                <Input id="share_capital" v-model="form.share_capital" type="number" step="0.01" min="0" placeholder="0.00" :class="{ 'border-red-500': form.errors.share_capital }" />
                                <p v-if="form.errors.share_capital" class="mt-1 text-sm text-red-500">{{ form.errors.share_capital }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <Briefcase class="h-5 w-5" />
                            Employment / Economic Information
                        </CardTitle>
                        <CardDescription>Capture education, sector, and livelihood details that help profile the member.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4 pt-0">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="educational_attainment">Educational Attainment</Label>
                                <Select v-model="form.educational_attainment">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.educational_attainment }">
                                        <SelectValue placeholder="Select educational level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="No Formal Education">No Formal Education</SelectItem>
                                        <SelectItem value="Elementary">Elementary</SelectItem>
                                        <SelectItem value="High School">High School</SelectItem>
                                        <SelectItem value="Vocational">Vocational</SelectItem>
                                        <SelectItem value="College">College</SelectItem>
                                        <SelectItem value="Post-Graduate">Post-Graduate</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.educational_attainment" class="mt-1 text-sm text-red-500">{{ form.errors.educational_attainment }}</p>
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
                                <p v-if="form.errors.sector" class="mt-1 text-sm text-red-500">{{ form.errors.sector }}</p>
                            </div>

                            <div class="space-y-3 md:col-span-2">
                                <Label for="primary_livelihood">Primary Livelihood</Label>
                                <div class="flex flex-col gap-2 md:flex-row md:items-start">
                                    <Input id="primary_livelihood" v-model="newPrimaryLivelihood" type="text" placeholder="e.g., Rice farming, Fish vending, etc." :class="{ 'border-red-500': form.errors.primary_livelihood }" @keyup.enter.prevent="addPrimaryLivelihood" />
                                    <Button type="button" class="h-11 shrink-0" @click="addPrimaryLivelihood">Add</Button>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Badge v-for="(tag, index) in primaryLivelihoodTags" :key="`${tag}-${index}`" class="inline-flex items-center gap-2 rounded-full px-3 py-1">
                                        <span>{{ tag }}</span>
                                        <button type="button" class="rounded-full p-1 transition hover:bg-slate-200 dark:hover:bg-slate-700" @click="removePrimaryLivelihood(index)">
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </Badge>
                                </div>
                                <p v-if="form.errors.primary_livelihood" class="mt-1 text-sm text-red-500">{{ form.errors.primary_livelihood }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="(page.props.auth?.permissions || []).includes('assign roles')" class="border-border/80 bg-card/95 shadow-sm">
                    <CardHeader class="space-y-1 pb-4">
                        <CardTitle class="flex items-center gap-2 text-xl">
                            <FileText class="h-5 w-5" />
                            Additional Information
                        </CardTitle>
                        <CardDescription>Assign any additional roles for this member account.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3 pt-0">
                        <Label>Assign Roles</Label>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="role in availableRoles"
                                :key="role.id"
                                @click="toggleRole(role.id)"
                                :class="[
                                    'cursor-pointer border-2 transition-all',
                                    form.role_ids.includes(role.id)
                                        ? 'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-700 dark:bg-blue-900/30 dark:text-blue-200'
                                        : 'border-border bg-background text-muted-foreground hover:border-foreground/40',
                                ]"
                            >
                                <span class="flex items-center gap-1">
                                    {{ role.name }}
                                    <span v-if="form.role_ids.includes(role.id)" class="ml-1">✓</span>
                                </span>
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">Select one or more roles for this member. If none are selected, the default role behavior applies.</p>
                    </CardContent>
                </Card>

                    <div class="flex justify-end gap-3 pt-0">
                    <Button @click="handleCancel" type="button" variant="outline" class="gap-2">
                        <X class="h-4 w-4" />
                        Cancel
                    </Button>
                    <Button v-if="canCreateMember" type="submit" :disabled="form.processing" class="gap-2">
                        <UserPlus class="h-4 w-4" />
                        {{ form.processing ? 'Saving...' : 'Register Member' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
