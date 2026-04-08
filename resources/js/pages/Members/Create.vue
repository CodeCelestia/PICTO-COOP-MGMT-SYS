<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Users, Save, X, MapPin, Building2, Lock, UserPlus } from 'lucide-vue-next';
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
import { Textarea } from '@/components/ui/textarea';
import { usePsgc } from '@/composables/usePsgc';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface Props {
    cooperatives: Cooperative[];
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
const canCreateUserAccounts = computed(() => props.canCreateUserAccounts);

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
    password: '',
    password_confirmation: '',
    role_ids: [] as number[],
});

onMounted(() => {
    fetchRegions();
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
    form.post('/members', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/members');
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
                <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">Register New Member</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create a new member profile
                </p>
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
                                <Label for="birth_date">Birth Date <span class="text-red-500">*</span></Label>
                                <Input
                                    id="birth_date"
                                    v-model="form.birth_date"
                                    type="date"
                                    required
                                    :class="{ 'border-red-500': form.errors.birth_date }"
                                />
                                <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.birth_date }}
                                </p>
                            </div>

                            <div>
                                <Label for="gender">Gender <span class="text-red-500">*</span></Label>
                                <Select v-model="form.gender" required>
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
                                <Label for="email">Email <span class="text-red-500">*</span></Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="member@example.com"
                                    required
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    This will be used as the account email.
                                </p>
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
                                <Label for="address">Street Address / Building <span class="text-red-500">*</span></Label>
                                <Textarea
                                    id="address"
                                    v-model="form.address"
                                    placeholder="Street, building, house number..."
                                    required
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

                            <div class="md:col-span-2">
                                <Label for="primary_livelihood">Primary Livelihood</Label>
                                <Input
                                    id="primary_livelihood"
                                    v-model="form.primary_livelihood"
                                    type="text"
                                    placeholder="e.g., Rice farming, Fish vending, etc."
                                    :class="{ 'border-red-500': form.errors.primary_livelihood }"
                                />
                                <p v-if="form.errors.primary_livelihood" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.primary_livelihood }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Account Access -->
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <UserPlus class="h-5 w-5" />
                            Account Access
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="account_email">Account Email</Label>
                                <Input
                                    id="account_email"
                                    v-model="form.email"
                                    type="email"
                                    readonly
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="password">Create Password</Label>
                                <Input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Set a password"
                                    required
                                    :class="{ 'border-red-500': form.errors.password }"
                                />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <div>
                                <Label for="password_confirmation" class="flex items-center gap-2">
                                    <Lock class="h-4 w-4" />
                                    Confirm Password
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Confirm password"
                                    required
                                />
                            </div>
                        </div>

                        <div v-if="canCreateUserAccounts" class="mt-4">
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
                                Select one or more roles for this member account.
                            </p>
                            <p v-if="form.role_ids.length === 0" class="mt-1 text-xs text-gray-500">
                                If none selected, the account will default to the Member role.
                            </p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateMember" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Register Member' }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </AppLayout>
</template>
