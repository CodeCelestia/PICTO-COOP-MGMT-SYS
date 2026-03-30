<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePsgc } from '@/composables/usePsgc';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Building2, Save, X, MapPin } from 'lucide-vue-next';

interface CooperativeData {
  id?: number;
  name?: string;
  registration_number?: string;
  coop_type?: string;
  date_established?: string;
  address?: string;
  region?: string | null;
  province?: string | null;
  city_municipality?: string | null;
  barangay?: string | null;
  email?: string | null;
  phone?: string | null;
  status?: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended';
  accreditation_status?: string | null;
  accreditation_date?: string | null;
}

const props = defineProps<{
  cooperative?: CooperativeData;
  action: string;
  method: 'post' | 'put';
  onCancel: () => void;
}>();

const { regions, provinces, cities, barangays, loading, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

const selectedRegionCode = ref(props.cooperative?.region ? '' : '');
const selectedProvinceCode = ref(props.cooperative?.province ? '' : '');
const selectedCityCode = ref(props.cooperative?.city_municipality ? '' : '');

const form = useForm({
  name: props.cooperative?.name || '',
  registration_number: props.cooperative?.registration_number || '',
  coop_type: props.cooperative?.coop_type || '',
  date_established: props.cooperative?.date_established || '',
  address: props.cooperative?.address || '',
  region: props.cooperative?.region || '',
  province: props.cooperative?.province || '',
  city_municipality: props.cooperative?.city_municipality || '',
  barangay: props.cooperative?.barangay || '',
  email: props.cooperative?.email || '',
  phone: props.cooperative?.phone || '',
  status: props.cooperative?.status || 'Active',
  accreditation_status: props.cooperative?.accreditation_status || '',
  accreditation_date: props.cooperative?.accreditation_date || '',
});

const coopTypes = [
  'Credit', 'Consumers', 'Producers', 'Marketing', 'Service', 'Multipurpose',
  'Advocacy', 'Agrarian Reform', 'Dairy', 'Education', 'Electric', 'Fishermen',
  'Health Services', 'Housing', 'Insurance', 'Laboratory', 'Transport', 'Water Service', 'Workers',
];

onMounted(async () => {
  await fetchRegions();

  if (props.cooperative?.region) {
    const region = regions.value.find((r) => r.name === props.cooperative?.region);
    if (region) {
      selectedRegionCode.value = region.code;
      await fetchProvinces(region.code);
    }
  }

  if (props.cooperative?.province) {
    const province = provinces.value.find((p) => p.name === props.cooperative?.province);
    if (province) {
      selectedProvinceCode.value = province.code;
      await fetchCities(province.code);
    }
  }

  if (props.cooperative?.city_municipality) {
    const city = cities.value.find((c) => c.name === props.cooperative?.city_municipality);
    if (city) {
      selectedCityCode.value = city.code;
    }
  }
});

watch(selectedRegionCode, async (newRegion) => {
  if (newRegion && newRegion !== 'all') {
    await fetchProvinces(newRegion);
    const region = regions.value.find((r) => r.code === newRegion);
    form.region = region?.name || '';
    selectedProvinceCode.value = 'all';
    selectedCityCode.value = 'all';
    form.province = '';
    form.city_municipality = '';
    form.barangay = '';
  } else {
    selectedProvinceCode.value = 'all';
    selectedCityCode.value = 'all';
    form.region = '';
    form.province = '';
    form.city_municipality = '';
    form.barangay = '';
  }
});

watch(selectedProvinceCode, async (newProvince) => {
  if (newProvince && newProvince !== 'all') {
    await fetchCities(newProvince);
    const province = provinces.value.find((p) => p.code === newProvince);
    form.province = province?.name || '';
    selectedCityCode.value = 'all';
    form.city_municipality = '';
    form.barangay = '';
  } else {
    selectedCityCode.value = 'all';
    form.province = '';
    form.city_municipality = '';
    form.barangay = '';
  }
});

watch(selectedCityCode, (newCity) => {
  if (newCity && newCity !== 'all') {
    const city = cities.value.find((c) => c.code === newCity);
    form.city_municipality = city?.name || '';
    form.barangay = '';
  } else {
    form.city_municipality = '';
  }
});

const submit = () => {
  if (props.method === 'post') {
    form.post(props.action, { preserveScroll: true });
  } else {
    form.put(props.action, { preserveScroll: true });
  }
};
</script>

<template>
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
            <Input id="name" v-model="form.name" required placeholder="Enter cooperative name" :class="{'border-red-500': form.errors.name}" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
          </div>
          <div>
            <Label for="registration_number">Registration Number <span class="text-red-500">*</span></Label>
            <Input id="registration_number" v-model="form.registration_number" required placeholder="e.g., CDA-REG-5-2024-001" :class="{'border-red-500': form.errors.registration_number}" />
            <p v-if="form.errors.registration_number" class="mt-1 text-sm text-red-500">{{ form.errors.registration_number }}</p>
          </div>
          <div>
            <Label for="coop_type">Cooperative Type <span class="text-red-500">*</span></Label>
            <Select v-model="form.coop_type" required>
              <SelectTrigger :class="{'border-red-500': form.errors.coop_type}"><SelectValue placeholder="Select type" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="">Select type</SelectItem>
                <SelectItem v-for="type in coopTypes" :key="type" :value="type">{{ type }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.coop_type" class="mt-1 text-sm text-red-500">{{ form.errors.coop_type }}</p>
          </div>
          <div>
            <Label for="date_established">Date Established <span class="text-red-500">*</span></Label>
            <Input id="date_established" v-model="form.date_established" type="date" required :class="{'border-red-500': form.errors.date_established}" />
            <p v-if="form.errors.date_established" class="mt-1 text-sm text-red-500">{{ form.errors.date_established }}</p>
          </div>
          <div class="md:col-span-2">
            <Label for="address">Address <span class="text-red-500">*</span></Label>
            <Textarea id="address" v-model="form.address" required rows="3" placeholder="e.g., 123 Main Street" :class="{'border-red-500': form.errors.address}" />
            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
          </div>
        </div>
      </div>

      <!-- Address Information -->
      <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
          <MapPin class="h-5 w-5" />
          Address Information (PSGC)
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="region">Region <span class="text-red-500">*</span></Label>
            <Select v-model="selectedRegionCode" required>
              <SelectTrigger :class="{'border-red-500': form.errors.region}"><SelectValue placeholder="Select region" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Regions</SelectItem>
                <SelectItem v-for="region in regions" :key="region.code" :value="region.code">{{ region.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.region" class="mt-1 text-sm text-red-500">{{ form.errors.region }}</p>
          </div>
          <div>
            <Label for="province">Province <span class="text-red-500">*</span></Label>
            <Select v-model="selectedProvinceCode" :disabled="selectedRegionCode === 'all' || provinces.length === 0" required>
              <SelectTrigger :class="{'border-red-500': form.errors.province}"><SelectValue placeholder="Select province" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Provinces</SelectItem>
                <SelectItem v-for="provinceItem in provinces" :key="provinceItem.code" :value="provinceItem.code">{{ provinceItem.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.province" class="mt-1 text-sm text-red-500">{{ form.errors.province }}</p>
          </div>
          <div>
            <Label for="city_municipality">City/Municipality <span class="text-red-500">*</span></Label>
            <Select v-model="selectedCityCode" :disabled="selectedProvinceCode === 'all' || cities.length === 0" required>
              <SelectTrigger :class="{'border-red-500': form.errors.city_municipality}"><SelectValue placeholder="Select city/municipality" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Municipalities</SelectItem>
                <SelectItem v-for="cityItem in cities" :key="cityItem.code" :value="cityItem.code">{{ cityItem.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.city_municipality" class="mt-1 text-sm text-red-500">{{ form.errors.city_municipality }}</p>
          </div>
          <div>
            <Label for="barangay">Barangay</Label>
            <Select v-model="form.barangay" :disabled="selectedCityCode === 'all' || barangays.length === 0">
              <SelectTrigger :class="{'border-red-500': form.errors.barangay}"><SelectValue placeholder="Select barangay" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="">None</SelectItem>
                <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">{{ barangay.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.barangay" class="mt-1 text-sm text-red-500">{{ form.errors.barangay }}</p>
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Contact Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="email">Email Address</Label>
            <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" :class="{'border-red-500': form.errors.email}" />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
          </div>
          <div>
            <Label for="phone">Phone Number</Label>
            <Input id="phone" v-model="form.phone" type="text" placeholder="+63 XXX XXX XXXX" :class="{'border-red-500': form.errors.phone}" />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
          </div>
        </div>
      </div>

      <!-- Accreditation Information -->
      <div>
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Accreditation Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="accreditation_status">Accreditation Status</Label>
            <Input id="accreditation_status" v-model="form.accreditation_status" type="text" placeholder="e.g. Level 1" :class="{'border-red-500': form.errors.accreditation_status}" />
            <p v-if="form.errors.accreditation_status" class="mt-1 text-sm text-red-500">{{ form.errors.accreditation_status }}</p>
          </div>
          <div>
            <Label for="accreditation_date">Accreditation Date</Label>
            <Input id="accreditation_date" v-model="form.accreditation_date" type="date" :class="{'border-red-500': form.errors.accreditation_date}" />
            <p v-if="form.errors.accreditation_date" class="mt-1 text-sm text-red-500">{{ form.errors.accreditation_date }}</p>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-3 border-t border-gray-200 pt-6">
        <Button type="button" variant="outline" @click="props.onCancel" class="gap-2"><X class="h-4 w-4" />Cancel</Button>
        <Button type="submit" :disabled="form.processing" class="gap-2"><Save class="h-4 w-4" />{{ form.processing ? 'Saving...' : 'Save Cooperative' }}</Button>
      </div>
    </form>
  </div>
</template>
"@; $complete | Set-Content -Path resources/js/components/Cooperatives/CooperativeForm.vue -Force
