<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Building2, Save, Search, X, MapPin } from 'lucide-vue-next';
import { computed, ref, watch, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePsgc } from '@/composables/usePsgc';

interface CooperativeData {
  id?: number;
  name?: string;
  registration_number?: string;
  classification?: 'Primary' | 'Secondary' | 'Tertiary' | null;
  types?: Array<{ id: number; name: string }>;
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
  cooperativeTypes: Array<{ id: number; name: string }>;
  action: string;
  method: 'post' | 'put';
  onCancel: () => void;
  canSubmit?: boolean;
}>();

const { regions, provinces, cities, barangays, fetchRegions, fetchProvinces, fetchCities, fetchBarangays } = usePsgc();

const selectedRegionCode = ref('all');
const selectedProvinceCode = ref('all');
const selectedCityCode = ref('all');
const isHydrating = ref(true);
const isCoopTypeDialogOpen = ref(false);
const coopTypeSearch = ref('');
const defaultProvinceName = 'Surigao del Norte';

const normalizeDateInput = (value?: string | null): string => {
  if (!value) return '';

  const dateOnlyMatch = value.match(/^(\d{4}-\d{2}-\d{2})/);
  if (dateOnlyMatch) return dateOnlyMatch[1];

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return '';

  return parsed.toISOString().slice(0, 10);
};

const normalizeLocationKey = (value?: string | null): string => {
  if (!value) return '';

  return value
    .toLowerCase()
    .replace(/\./g, '')
    .replace(/^province of\s+/, '')
    .replace(/^city of\s+/, '')
    .replace(/^municipality of\s+/, '')
    .replace(/^brgy\s+/, '')
    .replace(/^barangay\s+/, '')
    .replace(/\s+/g, ' ')
    .trim();
};

const findByNameOrCode = <T extends { code: string; name: string }>(
  items: T[],
  value?: string | null,
): T | undefined => {
  if (!value) return undefined;

  const normalizedValue = normalizeLocationKey(value);

  return items.find((item) => item.code === value)
    ?? items.find((item) => item.name === value)
    ?? items.find((item) => normalizeLocationKey(item.name) === normalizedValue);
};

const form = useForm({
  name: props.cooperative?.name || '',
  registration_number: props.cooperative?.registration_number || '',
  type_ids: (props.cooperative?.types || []).map((type) => type.id.toString()),
  classification: props.cooperative?.classification || '',
  date_established: normalizeDateInput(props.cooperative?.date_established),
  address: props.cooperative?.address || '',
  region: props.cooperative?.region || '',
  province: props.cooperative?.province || '',
  city_municipality: props.cooperative?.city_municipality || '',
  barangay: props.cooperative?.barangay || '',
  email: props.cooperative?.email || '',
  phone: props.cooperative?.phone || '',
  status: props.cooperative?.status || 'Active',
  accreditation_status: props.cooperative?.accreditation_status || '',
  accreditation_date: normalizeDateInput(props.cooperative?.accreditation_date),
});

const selectedTypeLabels = computed(() => {
  const selected = props.cooperativeTypes
    .filter((type) => form.type_ids.includes(type.id.toString()))
    .map((type) => type.name);

  return selected.join(', ');
});

const filteredCoopTypes = computed(() => {
  const query = coopTypeSearch.value.trim().toLowerCase();

  if (!query) return props.cooperativeTypes;

  return props.cooperativeTypes.filter((type) => type.name.toLowerCase().includes(query));
});

const toggleCoopType = (typeId: string) => {
  if (form.type_ids.includes(typeId)) {
    form.type_ids = form.type_ids.filter((id) => id !== typeId);
  } else {
    form.type_ids.push(typeId);
  }
  form.clearErrors('type_ids');
};

const clearCoopType = () => {
  form.type_ids = [];
};

onMounted(async () => {
  try {
    await fetchRegions();

    if (!props.cooperative) {
      const caragaRegion = regions.value.find((region) => {
        const normalized = normalizeLocationKey(region.name || region.regionName);
        return normalized.includes('caraga') || normalized.includes('region xiii');
      });

      if (caragaRegion) {
        selectedRegionCode.value = caragaRegion.code;
        form.region = caragaRegion.name;
        await fetchProvinces(caragaRegion.code);

        const province = findByNameOrCode(provinces.value, defaultProvinceName);
        if (province) {
          selectedProvinceCode.value = province.code;
          form.province = province.name;
        }
      }

      return;
    }

    const region = findByNameOrCode(regions.value, props.cooperative.region);
    if (region) {
      selectedRegionCode.value = region.code;
      form.region = region.name;
      await fetchProvinces(region.code);
    }

    const province = findByNameOrCode(provinces.value, props.cooperative.province);
    if (province) {
      selectedProvinceCode.value = province.code;
      form.province = province.name;
      await fetchCities(province.code);
    }

    const city = findByNameOrCode(cities.value, props.cooperative.city_municipality);
    if (city) {
      selectedCityCode.value = city.code;
      form.city_municipality = city.name;
      await fetchBarangays(city.code);
    }

    if (props.cooperative.barangay) {
      const barangay = findByNameOrCode(barangays.value, props.cooperative.barangay);
      form.barangay = barangay?.name || props.cooperative.barangay;
    }
  } finally {
    isHydrating.value = false;
  }
});

watch(selectedRegionCode, async (newRegion) => {
  if (isHydrating.value) return;

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
    provinces.value = [];
    cities.value = [];
    barangays.value = [];
  }
});

watch(selectedProvinceCode, async (newProvince) => {
  if (isHydrating.value) return;

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
    cities.value = [];
    barangays.value = [];
  }
});

watch(selectedCityCode, async (newCity) => {
  if (isHydrating.value) return;

  if (newCity && newCity !== 'all') {
    await fetchBarangays(newCity);
    const city = cities.value.find((c) => c.code === newCity);
    form.city_municipality = city?.name || '';
    form.barangay = '';
  } else {
    form.city_municipality = '';
    form.barangay = '';
    barangays.value = [];
  }
});

const submit = () => {
  if (props.canSubmit === false) {
    return;
  }
  if (!form.type_ids.length) {
    form.setError('type_ids', 'Please select at least one cooperative type.');
    return;
  }

  if (!form.classification) {
    form.setError('classification', 'Please select cooperative classification.');
    return;
  }

  if (props.method === 'post') {
    form.post(props.action, { preserveScroll: true });
  } else {
    form.put(props.action, { preserveScroll: true });
  }
};
</script>

<template>
  <div class="rounded-xl border border-border/80 bg-card shadow-sm">
    <form @submit.prevent="submit" class="space-y-6 p-5 sm:p-6">
      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-foreground sm:text-lg">
          <Building2 class="h-5 w-5 text-primary" />
          Basic Information
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="name">Cooperative Name <span class="text-red-500">*</span></Label>
            <Input id="name" v-model="form.name" required placeholder="Enter cooperative name" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.name}" />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
          </div>
          <div>
            <Label for="registration_number">Registration Number <span class="text-red-500">*</span></Label>
            <Input id="registration_number" v-model="form.registration_number" required placeholder="e.g., CDA-REG-5-2024-001" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.registration_number}" />
            <p v-if="form.errors.registration_number" class="mt-1 text-sm text-red-500">{{ form.errors.registration_number }}</p>
          </div>
          <div>
            <Label for="coop_type">Cooperative Type <span class="text-red-500">*</span></Label>
            <Button
              id="coop_type"
              type="button"
              variant="outline"
              class="w-full justify-between font-normal"
              :class="{'border-red-500 focus-visible:ring-red-500': form.errors.type_ids, 'text-muted-foreground': form.type_ids.length === 0}"
              @click="isCoopTypeDialogOpen = true"
            >
              <span class="truncate">{{ selectedTypeLabels || 'Select cooperative type(s)' }}</span>
              <span class="text-xs text-muted-foreground">Choose</span>
            </Button>
            <p class="mt-1 text-xs text-muted-foreground">Use the picker to search and select a cooperative type.</p>
            <p v-if="form.errors.type_ids" class="mt-1 text-sm text-red-500">{{ form.errors.type_ids }}</p>
          </div>
          <div>
            <Label for="classification">Cooperative Classification <span class="text-red-500">*</span></Label>
            <Select v-model="form.classification">
              <SelectTrigger id="classification" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.classification}">
                <SelectValue placeholder="Select classification" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Primary">Primary</SelectItem>
                <SelectItem value="Secondary">Secondary</SelectItem>
                <SelectItem value="Tertiary">Tertiary</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.classification" class="mt-1 text-sm text-red-500">{{ form.errors.classification }}</p>
          </div>
          <div>
            <Label for="date_established">Date Established <span class="text-red-500">*</span></Label>
            <Input id="date_established" v-model="form.date_established" type="date" required :class="{'border-red-500 focus-visible:ring-red-500': form.errors.date_established}" />
            <p v-if="form.errors.date_established" class="mt-1 text-sm text-red-500">{{ form.errors.date_established }}</p>
          </div>
          <div class="md:col-span-2">
            <Label for="address">Address <span class="text-red-500">*</span></Label>
            <Textarea id="address" v-model="form.address" required rows="3" placeholder="e.g., 123 Main Street" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.address}" />
            <p v-if="form.errors.address" class="mt-1 text-sm text-red-500">{{ form.errors.address }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-foreground sm:text-lg">
          <MapPin class="h-5 w-5 text-primary" />
          Address Information (PSGC)
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="region">Region <span class="text-red-500">*</span></Label>
            <Select v-model="selectedRegionCode" required>
              <SelectTrigger :class="{'border-red-500 focus-visible:ring-red-500': form.errors.region}"><SelectValue placeholder="Select region" /></SelectTrigger>
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
              <SelectTrigger :class="{'border-red-500 focus-visible:ring-red-500': form.errors.province}"><SelectValue placeholder="Select province" /></SelectTrigger>
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
              <SelectTrigger :class="{'border-red-500 focus-visible:ring-red-500': form.errors.city_municipality}"><SelectValue placeholder="Select city/municipality" /></SelectTrigger>
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
              <SelectTrigger :class="{'border-red-500 focus-visible:ring-red-500': form.errors.barangay}"><SelectValue placeholder="Select barangay" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="">None</SelectItem>
                <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">{{ barangay.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.barangay" class="mt-1 text-sm text-red-500">{{ form.errors.barangay }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 text-base font-semibold text-foreground sm:text-lg">Contact Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="email">Email Address</Label>
            <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.email}" />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
          </div>
          <div>
            <Label for="phone">Phone Number</Label>
            <Input id="phone" v-model="form.phone" type="text" placeholder="+63 XXX XXX XXXX" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.phone}" />
            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 text-base font-semibold text-foreground sm:text-lg">Accreditation Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="accreditation_status">Accreditation Status</Label>
            <Input id="accreditation_status" v-model="form.accreditation_status" type="text" placeholder="e.g. Level 1" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.accreditation_status}" />
            <p v-if="form.errors.accreditation_status" class="mt-1 text-sm text-red-500">{{ form.errors.accreditation_status }}</p>
          </div>
          <div>
            <Label for="accreditation_date">Accreditation Date</Label>
            <Input id="accreditation_date" v-model="form.accreditation_date" type="date" :class="{'border-red-500 focus-visible:ring-red-500': form.errors.accreditation_date}" />
            <p v-if="form.errors.accreditation_date" class="mt-1 text-sm text-red-500">{{ form.errors.accreditation_date }}</p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-6">
        <Button type="button" variant="outline" @click="props.onCancel" class="gap-2"><X class="h-4 w-4" />Cancel</Button>
        <Button v-if="props.canSubmit !== false" type="submit" :disabled="form.processing" class="gap-2"><Save class="h-4 w-4" />{{ form.processing ? 'Saving...' : 'Save Cooperative' }}</Button>
      </div>
    </form>

    <Dialog v-model:open="isCoopTypeDialogOpen">
      <DialogContent class="max-w-xl p-0">
        <DialogHeader class="border-b border-border px-6 py-4">
          <DialogTitle>Select Cooperative Type</DialogTitle>
          <DialogDescription>
            Search and choose the cooperative type to avoid long scrolling in the form.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 px-6 pb-6 pt-4">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              v-model="coopTypeSearch"
              type="text"
              placeholder="Search cooperative type..."
              class="pl-9"
            />
          </div>

          <div class="max-h-80 overflow-y-auto rounded-lg border border-border">
            <label
              v-for="type in filteredCoopTypes"
              :key="type.id"
              class="flex cursor-pointer items-center gap-3 border-b border-border px-4 py-3 text-sm transition-colors last:border-b-0 hover:bg-muted/30"
            >
              <input
                type="checkbox"
                :value="type.id.toString()"
                :checked="form.type_ids.includes(type.id.toString())"
                class="h-4 w-4 accent-primary"
                @change="toggleCoopType(type.id.toString())"
              />
              <span class="font-medium text-foreground">{{ type.name }}</span>
            </label>

            <div v-if="filteredCoopTypes.length === 0" class="px-4 py-8 text-center text-sm text-muted-foreground">
              No cooperative type matched your search.
            </div>
          </div>

          <div class="flex items-center justify-between gap-3">
            <Button type="button" variant="ghost" @click="clearCoopType">Clear selection</Button>
            <Button type="button" variant="outline" @click="isCoopTypeDialogOpen = false">Close</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
