<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { onMounted, watch, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Building2, Save, X, MapPin } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router } from '@inertiajs/vue3';
import { usePsgc } from '@/composables/usePsgc';

const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

const selectedRegionCode = ref('');
const selectedProvinceCode = ref('');
const selectedCityCode = ref('');

const form = useForm({
    name: '',
    registration_number: '',
    coop_type: '',
    date_established: '',
    address: '',
    region: '',
    province: '',
    city_municipality: '',
    barangay: '',
    email: '',
    phone: '',
    status: 'Active',
    accreditation_status: '',
    accreditation_date: '',
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
    form.post('/cooperatives', {
        preserveScroll: true,
        onSuccess: () => {
            // Redirect handled by controller
        },
    });
};

const cancel = () => {
    router.get('/cooperatives');
};

const coopTypes = [
    'Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose',
    'Advocacy', 'Agrarian Reform', 'Dairy', 'Education', 'Electric', 'Fishermen',
    'Health Services', 'Housing', 'Insurance', 'Laboratory', 'Transport', 'Water Service', 'Workers'
];
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Register New Cooperative</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Create a new cooperative master profile
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Basic Information -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Basic Information
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="name">Cooperative Name <span class="text-red-500">*</span></Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter cooperative name"
                                    :class="{ 'border-red-500': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <Label for="registration_number">Registration Number <span class="text-red-500">*</span></Label>
                                <Input
                                    id="registration_number"
                                    v-model="form.registration_number"
                                    type="text"
                                    required
                                    placeholder="e.g., CDA-REG-5-2024-001"
                                    :class="{ 'border-red-500': form.errors.registration_number }"
                                />
                                <p v-if="form.errors.registration_number" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.registration_number }}
                                </p>
                            </div>

                            <div>
                                <Label for="coop_type">Cooperative Type <span class="text-red-500">*</span></Label>
                                <Select v-model="form.coop_type" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.coop_type }">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in coopTypes" :key="type" :value="type">
                                            {{ type }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.coop_type" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_type }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_established">Date Established <span class="text-red-500">*</span></Label>
                                <Input
                                    id="date_established"
                                    v-model="form.date_established"
                                    type="date"
                                    required
                                    :class="{ 'border-red-500': form.errors.date_established }"
                                />
                                <p v-if="form.errors.date_established" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_established }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status <span class="text-red-500">*</span></Label>
                                <Select v-model="form.status" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Inactive">Inactive</SelectItem>
                                        <SelectItem value="Suspended">Suspended</SelectItem>
                                        <SelectItem value="Dissolved">Dissolved</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- PSGC Address Information -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <MapPin class="h-5 w-5" />
                            Address Information (PSGC)
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="region">Region <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedRegionCode" required>
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.region }">
                                        <SelectValue placeholder="Select region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="region in regions" :key="region.code" :value="region.code">
                                            {{ region.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.region" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.region }}
                                </p>
                            </div>

                            <div>
                                <Label for="province">Province <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedProvinceCode" required :disabled="!selectedRegionCode || provinces.length === 0">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.province }">
                                        <SelectValue placeholder="Select province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="province in provinces" :key="province.code" :value="province.code">
                                            {{ province.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.province" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.province }}
                                </p>
                            </div>

                            <div>
                                <Label for="city_municipality">City/Municipality <span class="text-red-500">*</span></Label>
                                <Select v-model="selectedCityCode" required :disabled="!selectedProvinceCode || cities.length === 0">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.city_municipality }">
                                        <SelectValue placeholder="Select city/municipality" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="city in cities" :key="city.code" :value="city.code">
                                            {{ city.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.city_municipality" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.city_municipality }}
                                </p>
                            </div>

                            <div>
                                <Label for="barangay">Barangay</Label>
                                <Select v-model="form.barangay" :disabled="!selectedCityCode || barangays.length === 0">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.barangay }">
                                        <SelectValue placeholder="Select barangay" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">
                                            {{ barangay.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.barangay" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.barangay }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <Label for="address">Street/Building/Additional Address Details <span class="text-red-500">*</span></Label>
                            <Textarea
                                id="address"
                                v-model="form.address"
                                required
                                placeholder="e.g., 123 Main Street, Building A, Unit 5"
                                rows="3"
                                :class="{ 'border-red-500': form.errors.address }"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">
                                {{ form.errors.address }}
                            </p>
                        </div>
                        <p v-if="loading" class="mt-2 text-sm text-blue-600">Loading locations...</p>
                    </div>

                    <!-- Contact Information -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Contact Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="email">Email Address</Label>
                                <Input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    placeholder="email@example.com"
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
                                    type="text"
                                    placeholder="+63 XXX XXX XXXX"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.phone }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Accreditation Information -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Accreditation Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="accreditation_status">Accreditation Status</Label>
                                <Input
                                    id="accreditation_status"
                                    v-model="form.accreditation_status"
                                    type="text"
                                    placeholder="e.g., Level 1, Level 2, etc."
                                    :class="{ 'border-red-500': form.errors.accreditation_status }"
                                />
                                <p v-if="form.errors.accreditation_status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.accreditation_status }}
                                </p>
                            </div>

                            <div>
                                <Label for="accreditation_date">Accreditation Date</Label>
                                <Input
                                    id="accreditation_date"
                                    v-model="form.accreditation_date"
                                    type="date"
                                    :class="{ 'border-red-500': form.errors.accreditation_date }"
                                />
                                <p v-if="form.errors.accreditation_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.accreditation_date }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            @click="cancel"
                            class="gap-2"
                        >
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="gap-2"
                        >
                            <Save class="h-4 w-4" />
                            {{ form.processing ? 'Saving...' : 'Save Cooperative' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
