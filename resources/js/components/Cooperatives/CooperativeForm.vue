<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Building2, Eye, MapPin, Save, Search, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import { AlertCircle } from 'lucide-vue-next';
import { useFormUx } from '@/composables/useFormUx';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { usePsgc } from '@/composables/usePsgc';
import { notifySuccess } from '@/lib/alerts';
import { getFileTypeConfig, getPreviewSuggestion } from '@/lib/activityFileTypes';

interface CooperativeAccreditation {
  id?: number;
  level: string;
  date_granted: string;
  valid_until?: string;
  accreditation_date?: string;
  issuing_body?: string;
  remarks?: string;
}

interface CooperativeRequirementStoredFile {
  file_path: string;
  original_name: string;
}

interface CooperativeRequirement {
  checked: boolean;
  file: File | null;
  stored_file: CooperativeRequirementStoredFile | null;
  description: string;
}

interface CooperativeRequirements {
  coc_certificate: CooperativeRequirement;
  prs_certification: CooperativeRequirement;
  certificate_of_registration: CooperativeRequirement;
}

interface CooperativeData {
  id?: number;
  name?: string;
  registration_number?: string;
  classification?: 'micro' | 'small' | 'medium' | 'large' | 'billion' | null;
  types?: Array<{ id: number; name: string }>;
  date_established?: string;
  address?: string;
  region?: string | null;
  province?: string | null;
  city_municipality?: string | null;
  barangay?: string | null;
  email?: string | null;
  phone?: string | null;
  status?: 'Active' | 'Inactive' | 'Dissolved' | 'Suspended' | 'Pending';
  accreditations?: CooperativeAccreditation[];
  requirements?: CooperativeRequirements;
}

const props = defineProps<{
  cooperative?: CooperativeData;
  accreditations?: CooperativeAccreditation[];
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

  return value.substring(0, 10);
};

const normalizeAccreditations = (accreditations?: CooperativeAccreditation[]): CooperativeAccreditation[] => {
  if (!Array.isArray(accreditations)) return [];

  return accreditations.map((item) => ({
    ...item,
    date_granted: normalizeDateInput(item?.date_granted),
    valid_until: normalizeDateInput(item?.valid_until),
    accreditation_date: normalizeDateInput(item?.accreditation_date),
  }));
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

const requirementKeys = ['coc_certificate', 'prs_certification', 'certificate_of_registration'] as const;
type RequirementKey = (typeof requirementKeys)[number];

const requirementLabels: Record<RequirementKey, string> = {
  coc_certificate: 'COC Certificate',
  prs_certification: 'PRS Certification',
  certificate_of_registration: 'Certificate of registration',
};

const requirementDescriptions: Record<RequirementKey, string> = {
  coc_certificate: 'Certificate of Cooperative Registration',
  prs_certification: 'PRS certification document',
  certificate_of_registration: 'Official certificate of registration',
};

const getDefaultRequirements = (): CooperativeRequirements => ({
  coc_certificate: { checked: false, file: null, stored_file: null, description: '' },
  prs_certification: { checked: false, file: null, stored_file: null, description: '' },
  certificate_of_registration: { checked: false, file: null, stored_file: null, description: '' },
});

const normalizeRequirements = (requirements?: Partial<CooperativeRequirements> | Record<string, unknown>): CooperativeRequirements => {
  const defaults = getDefaultRequirements();

  return requirementKeys.reduce((normalized, key) => {
    const requirement = (requirements as Record<string, Record<string, unknown>> | undefined)?.[key] ?? {};

    normalized[key] = {
      checked: Boolean(requirement.checked),
      file: requirement.file instanceof File ? requirement.file : null,
      stored_file: requirement.file_path && requirement.original_name
        ? {
            file_path: String(requirement.file_path),
            original_name: String(requirement.original_name),
          }
        : null,
      description: typeof requirement.description === 'string' ? requirement.description : '',
    };

    return normalized;
  }, defaults);
};

const form = useForm({
  name: props.cooperative?.name || '',
  registration_number: props.cooperative?.registration_number || '',
  type_ids: (props.cooperative?.types || []).map((type) => type.id.toString()),
  classification: props.cooperative?.classification || '',
  date_established: props.cooperative?.date_established
    ? String(props.cooperative.date_established).substring(0, 10)
    : '',
  address: props.cooperative?.address || '',
  region: props.cooperative?.region || '',
  province: props.cooperative?.province || '',
  city_municipality: props.cooperative?.city_municipality || '',
  barangay: props.cooperative?.barangay || '',
  email: props.cooperative?.email || '',
  phone: props.cooperative?.phone || '',
  status: props.cooperative?.status || 'Active',
  accreditations: normalizeAccreditations(props.accreditations ?? props.cooperative?.accreditations ?? []),
  requirements: normalizeRequirements(props.cooperative?.requirements),
});

const requirementModalOpen = ref(false);
const activeRequirementKey = ref<RequirementKey | null>(null);
const requirementModalError = ref('');
const requirementFile = ref<File | null>(null);
const requirementExistingFile = ref<File | CooperativeRequirementStoredFile | null>(null);
const requirementFileInput = ref<HTMLInputElement | null>(null);
const requirementDescription = ref('');
const requirementPreviewUrls = new Map<File, string>();

const isRequirementsComplete = computed(() => requirementKeys.every((key) => form.requirements[key]?.checked));

const isStoredRequirementFile = (value: unknown): value is CooperativeRequirementStoredFile => {
  return Boolean(
    value
    && typeof value === 'object'
    && 'file_path' in value
    && 'original_name' in value,
  );
};

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) {
    return `${bytes} B`;
  }

  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(bytes < 10 * 1024 ? 1 : 0)} KB`;
  }

  return `${(bytes / (1024 * 1024)).toFixed(bytes < 10 * 1024 * 1024 ? 1 : 0)} MB`;
};

const getRequirementFile = (key: RequirementKey) => form.requirements[key].file;

const getRequirementStoredFile = (key: RequirementKey) => form.requirements[key].stored_file;

const getRequirementFileName = (key: RequirementKey) => {
  const file = getRequirementFile(key);
  const storedFile = getRequirementStoredFile(key);

  if (file instanceof File) {
    return file.name;
  }

  if (storedFile) {
    return storedFile.original_name || 'Attached file';
  }

  return 'No file selected';
};

const getRequirementFileSizeLabel = (key: RequirementKey) => {
  const file = getRequirementFile(key);
  const storedFile = getRequirementStoredFile(key);

  if (file instanceof File) {
    return formatFileSize(file.size);
  }

  if (storedFile) {
    return 'Saved attachment';
  }

  return 'No file';
};

const getRequirementFileTypeConfig = (key: RequirementKey) => getFileTypeConfig(getRequirementFileName(key));

const hasRequirementFile = (key: RequirementKey) => Boolean(getRequirementFile(key));
const hasStoredRequirementFile = (key: RequirementKey) => Boolean(getRequirementStoredFile(key));

const getRequirementActiveFile = () => requirementFile.value ?? requirementExistingFile.value;

const getRequirementActiveFileName = () => {
  const file = getRequirementActiveFile();

  if (file instanceof File) {
    return file.name;
  }

  if (isStoredRequirementFile(file)) {
    return file.original_name || 'Attached file';
  }

  return 'No file selected';
};

const getRequirementActiveFileSizeLabel = () => {
  const file = getRequirementActiveFile();

  if (file instanceof File) {
    return formatFileSize(file.size);
  }

  if (isStoredRequirementFile(file)) {
    return 'Saved attachment';
  }

  return 'No file';
};

const getRequirementActiveFileTypeConfig = () => getFileTypeConfig(getRequirementActiveFileName());

const getRequirementActivePreviewUrl = () => {
  const file = getRequirementActiveFile();

  if (file instanceof File) {
    const existing = requirementPreviewUrls.get(file);

    if (existing) {
      return existing;
    }

    const url = URL.createObjectURL(file);
    requirementPreviewUrls.set(file, url);
    return url;
  }

  if (isStoredRequirementFile(file) && file.file_path) {
    return `/storage/${file.file_path}`;
  }

  return '';
};

const getRequirementPreviewUrl = (key: RequirementKey) => {
  const file = getRequirementFile(key);

  if (file instanceof File) {
    const existing = requirementPreviewUrls.get(file);

    if (existing) {
      return existing;
    }

    const url = URL.createObjectURL(file);
    requirementPreviewUrls.set(file, url);
    return url;
  }

  const storedFile = getRequirementStoredFile(key);

  if (storedFile?.file_path) {
    return `/storage/${storedFile.file_path}`;
  }

  return '';
};

const openRequirementPreview = (key: RequirementKey) => {
  const url = getRequirementPreviewUrl(key);

  if (!url) {
    return;
  }

  window.open(url, '_blank', 'noopener,noreferrer');
};

const openRequirementActivePreview = () => {
  const url = getRequirementActivePreviewUrl();

  if (!url) {
    return;
  }

  window.open(url, '_blank', 'noopener,noreferrer');
};

const openRequirementModal = (key: RequirementKey) => {
  activeRequirementKey.value = key;
  requirementModalError.value = '';
  requirementExistingFile.value = form.requirements[key].file ?? form.requirements[key].stored_file ?? null;
  requirementFile.value = null;
  requirementDescription.value = form.requirements[key].description ?? '';
  requirementModalOpen.value = true;
};

const clearRequirement = (key: RequirementKey) => {
  form.requirements[key] = {
    checked: false,
    file: null,
    stored_file: null,
    description: '',
  };
};

const onRequirementChecked = (key: RequirementKey, checked: boolean) => {
  if (checked) {
    openRequirementModal(key);
    return;
  }

  clearRequirement(key);
};

const handleRequirementFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  requirementFile.value = target.files?.[0] ?? null;
  if (requirementFile.value) {
    requirementModalError.value = '';
  }
};

const clearRequirementModalFile = () => {
  requirementFile.value = null;
  requirementExistingFile.value = null;
  requirementModalError.value = '';

  if (requirementFileInput.value) {
    requirementFileInput.value.value = '';
  }
};

const removeRequirementAttachment = (key: RequirementKey) => {
  form.requirements[key] = {
    checked: false,
    file: null,
    stored_file: null,
    description: '',
  };

  if (activeRequirementKey.value === key) {
    clearRequirementModalFile();
  }
};

const saveRequirement = () => {
  if (!activeRequirementKey.value) {
    return;
  }

  const nextFile = requirementFile.value ?? requirementExistingFile.value;

  if (!nextFile) {
    requirementModalError.value = 'Please attach a file before saving.';
    return;
  }

  form.requirements[activeRequirementKey.value] = {
    checked: true,
    file: requirementFile.value,
    stored_file: isStoredRequirementFile(requirementExistingFile.value)
      ? requirementExistingFile.value
      : null,
    description: requirementDescription.value,
  };

  requirementModalOpen.value = false;
  requirementExistingFile.value = null;
};

const cancelRequirement = () => {
  requirementModalOpen.value = false;
  activeRequirementKey.value = null;
  requirementExistingFile.value = null;
  requirementFile.value = null;
  requirementModalError.value = '';
};

onUnmounted(() => {
  for (const url of requirementPreviewUrls.values()) {
    URL.revokeObjectURL(url);
  }

  requirementPreviewUrls.clear();
});

watch(
  () => form.requirements,
  () => {
    if (!isRequirementsComplete.value) {
      form.status = 'Pending';
      return;
    }

    if (form.status === 'Pending') {
      form.status = 'Active';
    }
  },
  { deep: true, immediate: true },
);

// Frontend validation errors (optional override)
const formErrors = ref<Record<string, string>>({});

// use shared composable for form UX (dirty tracking, errors, shake, scroll, cancel)
const { isPreFilling, isDirty, showErrorShake, inputErrorClass, clearError: clearErrorUx, scrollToFirstError, triggerErrorShake, handleCancel, markClean } = useFormUx(form);

// local wrapper to also clear frontend override errors
const clearError = (field: string) => {
  if (formErrors.value[field]) delete formErrors.value[field];

  const errors = form.errors as Record<string, unknown> | undefined;
  if (errors && errors[field]) {
    if (typeof form.clearErrors === 'function') {
        (form.clearErrors as (field: string) => void)(field);
    }
  }

  // also call composable clear for safety
  try { clearErrorUx(field); } catch (e) {}
};

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
  clearError('type_ids');
};

const clearCoopType = () => {
  form.type_ids = [];
  clearError('type_ids');
};

const saveCoopTypeSelection = () => {
  isCoopTypeDialogOpen.value = false;
  notifySuccess('Successfully added.');
};

const addAccreditationRow = () => {
  form.accreditations.push({
    level: '',
    date_granted: '',
    valid_until: '',
    issuing_body: 'CDA',
    remarks: '',
  });
};

const removeAccreditationRow = (index: number) => {
  form.accreditations.splice(index, 1);
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
    // mark pre-filling complete so dirty tracking starts
    isPreFilling.value = false;
    isDirty.value = false;
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
    void triggerErrorShake();
    scrollToFirstError();
    return;
  }

  const onOptions = {
    preserveScroll: true,
    onError: () => {
      void triggerErrorShake();
      scrollToFirstError();
    },
    onSuccess: () => {
      markClean();
    },
  };

  if (props.method === 'post') {
    form.post(props.action, onOptions);
  } else {
    form.put(props.action, onOptions);
  }
};
</script>

<template>
  <div class="rounded-xl border border-border/80 bg-card shadow-sm">
    <form @submit.prevent="submit" class="space-y-6 p-5 sm:p-6" :class="{ 'animate-shake': showErrorShake }">
      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 flex items-center gap-2 text-base font-semibold text-foreground sm:text-lg">
          <Building2 class="h-5 w-5 text-primary" />
          Basic Information
        </h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Cooperative Name
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Input id="name" v-model="form.name" required placeholder="Enter cooperative name" :class="inputErrorClass('name')" @input="clearError('name')" />
            <p v-if="form.errors.name || formErrors.name" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.name || formErrors.name }}
            </p>
          </div>
          <div>
            <Label for="registration_number" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Registration Number
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Input id="registration_number" v-model="form.registration_number" required placeholder="e.g., CDA-REG-5-2024-001" :class="inputErrorClass('registration_number')" @input="clearError('registration_number')" />
            <p v-if="form.errors.registration_number || formErrors.registration_number" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.registration_number || formErrors.registration_number }}
            </p>
          </div>
          <div>
            <Label for="coop_type" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Cooperative Type
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Button
              id="coop_type"
              type="button"
              variant="outline"
              class="w-full justify-between font-normal"
              :class="[inputErrorClass('type_ids'), { 'text-muted-foreground': form.type_ids.length === 0 }]"
              @click="isCoopTypeDialogOpen = true"
            >
              <span class="truncate">{{ selectedTypeLabels || 'Select cooperative type(s)' }}</span>
              <span class="text-xs text-muted-foreground">Choose</span>
            </Button>
            <p class="mt-1 text-xs text-muted-foreground">Use the picker to search and select a cooperative type.</p>
            <p v-if="form.errors.type_ids || formErrors.type_ids" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.type_ids || formErrors.type_ids }}
            </p>
          </div>
          <div>
            <Label for="date_established" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Date Established
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Input id="date_established" v-model="form.date_established" type="date" required @change="(e: Event) => { const target = e.target as HTMLInputElement | null; form.date_established = target?.value ?? ''; clearError('date_established'); }" :class="inputErrorClass('date_established')" />
            <p v-if="form.errors.date_established || formErrors.date_established" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.date_established || formErrors.date_established }}
            </p>
          </div>
          <div>
            <Label for="classification" class="text-sm font-medium leading-none">
              Classification
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Select v-model="form.classification" @update:modelValue="() => clearError('classification')">
              <SelectTrigger id="classification" :class="inputErrorClass('classification')">
                <SelectValue placeholder="Select classification (optional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="">None</SelectItem>
                <SelectItem value="micro">Micro</SelectItem>
                <SelectItem value="small">Small</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="large">Large</SelectItem>
                <SelectItem value="Billion">Billion</SelectItem>
              </SelectContent>
            </Select>
            <p class="mt-1 text-xs text-muted-foreground">Cooperative size classification.</p>
            <p v-if="form.errors.classification || formErrors.classification" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.classification || formErrors.classification }}
            </p>
          </div>
          <div>
            <Label for="status" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Status
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Select v-model="form.status" @update:modelValue="() => clearError('status')">
              <SelectTrigger id="status" :class="inputErrorClass('status')">
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="Active">Active</SelectItem>
                <SelectItem value="Pending">Pending</SelectItem>
                <SelectItem value="Inactive">Inactive</SelectItem>
                <SelectItem value="Dissolved">Dissolved</SelectItem>
                <SelectItem value="Suspended">Suspended</SelectItem>
              </SelectContent>
            </Select>
            <p class="mt-1 text-xs text-muted-foreground">Select the cooperative's registration status. Incomplete requirement uploads force Pending.</p>
            <p v-if="form.errors.status || formErrors.status" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.status || formErrors.status }}
            </p>
          </div>
          <div class="md:col-span-2">
            <Label for="address" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Address
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Textarea id="address" v-model="form.address" required rows="3" placeholder="e.g., 123 Main Street" :class="inputErrorClass('address')" @input="clearError('address')" />
            <p v-if="form.errors.address || formErrors.address" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.address || formErrors.address }}
            </p>
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
            <Label for="region" class="text-sm font-medium leading-none">
              Region
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Select v-model="selectedRegionCode" @update:modelValue="() => clearError('region')">
              <SelectTrigger :class="inputErrorClass('region')"><SelectValue placeholder="Select region" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Regions</SelectItem>
                <SelectItem v-for="region in regions" :key="region.code" :value="region.code">{{ region.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.region || formErrors.region" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.region || formErrors.region }}
            </p>
          </div>
          <div>
            <Label for="province" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
              Province
              <span class="text-red-500 ml-0.5">*</span>
            </Label>
            <Select v-model="selectedProvinceCode" :disabled="selectedRegionCode === 'all' || provinces.length === 0" @update:modelValue="() => clearError('province')">
              <SelectTrigger :class="inputErrorClass('province')"><SelectValue placeholder="Select province" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Provinces</SelectItem>
                <SelectItem v-for="provinceItem in provinces" :key="provinceItem.code" :value="provinceItem.code">{{ provinceItem.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.province || formErrors.province" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.province || formErrors.province }}
            </p>
          </div>
          <div>
            <Label for="city_municipality" class="text-sm font-medium leading-none">
              City/Municipality
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Select v-model="selectedCityCode" :disabled="selectedProvinceCode === 'all' || cities.length === 0" @update:modelValue="() => clearError('city_municipality')">
              <SelectTrigger :class="inputErrorClass('city_municipality')"><SelectValue placeholder="Select city/municipality" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Municipalities</SelectItem>
                <SelectItem v-for="cityItem in cities" :key="cityItem.code" :value="cityItem.code">{{ cityItem.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.city_municipality || formErrors.city_municipality" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.city_municipality || formErrors.city_municipality }}
            </p>
          </div>
          <div>
            <Label for="barangay" class="text-sm font-medium leading-none">
              Barangay
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Select v-model="form.barangay" :disabled="selectedCityCode === 'all' || barangays.length === 0" @update:modelValue="() => clearError('barangay')">
              <SelectTrigger :class="inputErrorClass('barangay')"><SelectValue placeholder="Select barangay" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="">None</SelectItem>
                <SelectItem v-for="barangay in barangays" :key="barangay.code" :value="barangay.name">{{ barangay.name }}</SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.barangay || formErrors.barangay" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.barangay || formErrors.barangay }}
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <h2 class="mb-4 text-base font-semibold text-foreground sm:text-lg">Contact Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <Label for="email" class="text-sm font-medium leading-none">
              Email Address
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Input id="email" v-model="form.email" type="email" placeholder="email@example.com" :class="inputErrorClass('email')" @input="clearError('email')" />
            <p v-if="form.errors.email || formErrors.email" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.email || formErrors.email }}
            </p>
          </div>
          <div>
            <Label for="phone" class="text-sm font-medium leading-none">
              Phone Number
              <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
            </Label>
            <Input id="phone" v-model="form.phone" type="text" placeholder="+63 XXX XXX XXXX" :class="inputErrorClass('phone')" @input="clearError('phone')" />
            <p v-if="form.errors.phone || formErrors.phone" class="text-sm text-red-500 mt-1 flex items-center gap-1">
              <AlertCircle class="h-3.5 w-3.5 shrink-0" />
              {{ form.errors.phone || formErrors.phone }}
            </p>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-base font-semibold text-foreground sm:text-lg">Accreditation Builder</h2>
          <Button type="button" variant="outline" class="gap-2" @click="addAccreditationRow">
            Add accreditation row
          </Button>
        </div>

        <div v-if="form.accreditations.length === 0" class="mt-4 rounded-lg border border-dashed border-border/70 bg-background/80 p-4 text-sm text-muted-foreground">
          Add one or more accreditation entries before saving the cooperative.
        </div>

        <div class="mt-4 space-y-4">
          <div
            v-for="(accreditation, index) in form.accreditations"
            :key="accreditation.id ?? index"
            class="rounded-lg border border-border/70 bg-background p-4"
          >
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <Label :for="`accreditation_level_${index}`" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                  Level
                  <span class="text-red-500 ml-0.5">*</span>
                </Label>
                <Input
                  :id="`accreditation_level_${index}`"
                  v-model="form.accreditations[index].level"
                  type="text"
                  placeholder="e.g. Level 1"
                  :class="inputErrorClass(`accreditations.${index}.level`)"
                  @input="() => clearError(`accreditations.${index}.level`)"
                />
                <p v-if="form.errors[`accreditations.${index}.level`] || formErrors[`accreditations.${index}.level`]" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                  <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                  {{ form.errors[`accreditations.${index}.level`] || formErrors[`accreditations.${index}.level`] }}
                </p>
              </div>
              <div>
                <Label :for="`accreditation_date_granted_${index}`" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                  Date Granted
                  <span class="text-red-500 ml-0.5">*</span>
                </Label>
                <Input
                  :id="`accreditation_date_granted_${index}`"
                  v-model="form.accreditations[index].date_granted"
                  type="date"
                  :class="inputErrorClass(`accreditations.${index}.date_granted`)"
                  @change="() => clearError(`accreditations.${index}.date_granted`)"
                />
                <p v-if="form.errors[`accreditations.${index}.date_granted`] || formErrors[`accreditations.${index}.date_granted`]" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                  <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                  {{ form.errors[`accreditations.${index}.date_granted`] || formErrors[`accreditations.${index}.date_granted`] }}
                </p>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-4">
              <div>
                <Label :for="`accreditation_valid_until_${index}`" class="text-sm font-medium leading-none">
                  Valid Until
                  <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                </Label>
                <Input
                  :id="`accreditation_valid_until_${index}`"
                  v-model="form.accreditations[index].valid_until"
                  type="date"
                  :class="inputErrorClass(`accreditations.${index}.valid_until`)"
                  @change="() => clearError(`accreditations.${index}.valid_until`)"
                />
                <p v-if="form.errors[`accreditations.${index}.valid_until`] || formErrors[`accreditations.${index}.valid_until`]" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                  <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                  {{ form.errors[`accreditations.${index}.valid_until`] || formErrors[`accreditations.${index}.valid_until`] }}
                </p>
              </div>
              <div>
                <Label :for="`accreditation_issuing_body_${index}`" class="text-sm font-medium leading-none">
                  Issuing Body
                  <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
                </Label>
                <Input
                  :id="`accreditation_issuing_body_${index}`"
                  v-model="form.accreditations[index].issuing_body"
                  type="text"
                  placeholder="CDA"
                  :class="inputErrorClass(`accreditations.${index}.issuing_body`)"
                  @input="() => clearError(`accreditations.${index}.issuing_body`)"
                />
                <p v-if="form.errors[`accreditations.${index}.issuing_body`] || formErrors[`accreditations.${index}.issuing_body`]" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                  <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                  {{ form.errors[`accreditations.${index}.issuing_body`] || formErrors[`accreditations.${index}.issuing_body`] }}
                </p>
              </div>
            </div>

            <div class="mt-4">
              <Label :for="`accreditation_remarks_${index}`" class="text-sm font-medium leading-none">
                Remarks
                <span class="text-xs text-muted-foreground font-normal ml-1">(Optional)</span>
              </Label>
              <Textarea
                :id="`accreditation_remarks_${index}`"
                v-model="form.accreditations[index].remarks"
                rows="2"
                placeholder="Optional remarks"
                :class="inputErrorClass(`accreditations.${index}.remarks`)"
                @input="() => clearError(`accreditations.${index}.remarks`)"
              />
              <p v-if="form.errors[`accreditations.${index}.remarks`] || formErrors[`accreditations.${index}.remarks`]" class="text-sm text-red-500 mt-1 flex items-center gap-1">
                <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                {{ form.errors[`accreditations.${index}.remarks`] || formErrors[`accreditations.${index}.remarks`] }}
              </p>
            </div>

            <div class="mt-4 flex justify-end">
              <Button type="button" variant="outline" size="sm" class="gap-2" @click="removeAccreditationRow(index)">
                Remove
              </Button>
            </div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-border bg-muted/30 p-4 sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-base font-semibold text-foreground sm:text-lg">Registration Requirements</h2>
          <p class="text-sm text-muted-foreground">Upload each required document. Missing documents auto-set status to Pending.</p>
        </div>

        <div class="mt-4 space-y-4">
          <div
            v-for="key in requirementKeys"
            :key="key"
            class="rounded-lg border border-border/70 bg-background p-4"
          >
            <div class="flex items-start gap-3">
              <input
                :id="`requirement_${key}`"
                type="checkbox"
                class="mt-1 h-4 w-4 accent-primary"
                :checked="form.requirements[key].checked"
                @change="(event: Event) => { const target = event.target as HTMLInputElement | null; onRequirementChecked(key, target?.checked ?? false); }"
              />
              <div class="flex-1">
                <Label :for="`requirement_${key}`" class="text-sm font-medium leading-none">
                  {{ requirementLabels[key] }}
                </Label>
                <p class="text-xs text-muted-foreground mt-1">
                  {{ requirementDescriptions[key] }}
                </p>

                <div v-if="form.requirements[key].checked && (hasRequirementFile(key) || hasStoredRequirementFile(key))" class="mt-3 overflow-hidden rounded-xl border border-border/70 bg-background shadow-sm">
                  <div class="flex flex-col gap-4 p-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex min-w-0 flex-1 items-center gap-4">
                      <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border/70 bg-muted/20">
                        <img
                          v-if="getRequirementPreviewUrl(key) && getRequirementFileTypeConfig(key).group === 'Images'"
                          :src="getRequirementPreviewUrl(key)"
                          :alt="getRequirementFileName(key)"
                          class="h-full w-full object-cover"
                        />
                        <component
                          v-else
                          :is="getRequirementFileTypeConfig(key).icon"
                          class="h-10 w-10 shrink-0"
                          :class="getRequirementFileTypeConfig(key).iconColorClass"
                        />
                      </div>

                      <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                          <p class="truncate text-sm font-semibold text-foreground" :title="getRequirementFileName(key)">
                            {{ requirementLabels[key] }}
                          </p>
                          <Badge
                            class="text-xs font-semibold"
                            :class="form.requirements[key].checked
                              ? 'border border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-400/40 dark:bg-emerald-500/20 dark:text-emerald-200'
                              : 'border border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-400/40 dark:bg-amber-500/20 dark:text-amber-200'"
                          >
                            {{ form.requirements[key].checked ? 'Completed' : 'Pending' }}
                          </Badge>
                          <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getRequirementFileTypeConfig(key).badgeClass">
                            {{ getRequirementFileTypeConfig(key).extension }}
                          </span>
                          <Badge variant="outline" class="text-xs font-medium">
                            {{ getRequirementFileTypeConfig(key).group }}
                          </Badge>
                        </div>

                        <p class="truncate text-sm text-muted-foreground">
                          {{ form.requirements[key].description || requirementDescriptions[key] }}
                        </p>

                        <p class="flex items-center gap-1 text-sm text-foreground">
                          <span class="font-semibold text-muted-foreground shrink-0">Attachment:</span>
                          <span class="ml-1 truncate max-w-50" :title="getRequirementFileName(key)">
                            {{ getRequirementFileName(key) }}
                          </span>
                        </p>
                      </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap gap-2">
                      <Button v-if="hasRequirementFile(key) || hasStoredRequirementFile(key)" type="button" variant="ghost" size="sm" class="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700" @click="removeRequirementAttachment(key)">
                        <X class="h-3.5 w-3.5" />
                        Remove
                      </Button>
                      <Button type="button" variant="outline" size="sm" class="gap-2" :disabled="!getRequirementPreviewUrl(key)" @click="openRequirementPreview(key)">
                        <Eye class="h-3.5 w-3.5" />
                        Preview
                      </Button>
                      <Button type="button" variant="secondary" size="sm" @click="openRequirementModal(key)">Replace</Button>
                    </div>
                  </div>
                </div>

                <div v-else-if="form.requirements[key].checked" class="mt-3 overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/20">
                  <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-background ring-1 ring-border/60">
                        <Upload class="h-6 w-6 text-muted-foreground" />
                      </div>
                      <div>
                        <p class="font-medium text-foreground">No file attached yet</p>
                        <p class="text-xs text-muted-foreground">
                          {{ requirementDescriptions[key] }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                          Add a document to unlock preview and save this requirement.
                        </p>
                      </div>
                    </div>

                    <Button type="button" variant="outline" size="sm" class="gap-2 self-start sm:self-center" @click="openRequirementModal(key)">
                      <Upload class="h-3.5 w-3.5" />
                      Attach File
                    </Button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-6">
        <Button
          type="button"
          variant="outline"
          @click="handleCancel(props.method === 'put' ? {
            confirmAlways: true,
            confirmTitle: 'Are you sure you want to cancel?',
            confirmText: 'Any unsaved changes will be lost.',
            confirmButtonText: 'Yes, cancel',
            cancelButtonText: 'Continue editing',
          } : undefined)"
          class="gap-2"
        ><X class="h-4 w-4" />Cancel</Button>
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
            <Button type="button" variant="outline" @click="saveCoopTypeSelection">Save</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <Dialog v-model:open="requirementModalOpen">
      <DialogContent class="max-w-xl p-0">
        <DialogHeader class="border-b border-border px-6 py-4">
          <DialogTitle>Upload Requirement</DialogTitle>
          <DialogDescription>
            Attach the document and provide a short description for this requirement.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 px-6 pb-6 pt-4">
          <div>
            <Label for="requirement_file" class="text-sm font-medium leading-none">Requirement file</Label>
            <input
              id="requirement_file"
              ref="requirementFileInput"
              type="file"
              accept=".pdf,.jpg,.jpeg,.png"
              class="hidden"
              @change="handleRequirementFileChange"
            />
            <Button
              type="button"
              variant="outline"
              class="mt-2 w-full justify-start truncate"
              @click="() => requirementFileInput?.click()"
            >
              <Upload class="mr-2 h-4 w-4 shrink-0" />
              <span class="truncate max-w-70 block">
                {{ getRequirementActiveFileName() || 'Choose File' }}
              </span>
            </Button>
            <p v-if="requirementModalError" class="text-sm text-red-500 mt-2">{{ requirementModalError }}</p>
          </div>

          <div v-if="activeRequirementKey" class="overflow-hidden rounded-xl border border-border/70 bg-muted/20">
            <div class="flex items-start gap-3 p-4">
              <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-border/70 bg-background ring-1 ring-border/60">
                <img
                  v-if="getRequirementActivePreviewUrl() && getRequirementActiveFileTypeConfig().group === 'Images'"
                  :src="getRequirementActivePreviewUrl()"
                  :alt="getRequirementActiveFileName()"
                  class="h-full w-full object-cover"
                />
                <component
                  v-else
                  :is="getRequirementActiveFileTypeConfig().icon"
                  class="h-10 w-10 shrink-0"
                  :class="getRequirementActiveFileTypeConfig().iconColorClass"
                />
              </div>

              <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="min-w-16 rounded-md border px-2 py-0.5 text-center text-xs font-bold" :class="getRequirementActiveFileTypeConfig().badgeClass">
                    {{ getRequirementActiveFileTypeConfig().extension }}
                  </span>
                  <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                    {{ getRequirementActiveFileTypeConfig().group }}
                  </span>
                </div>

                <p class="truncate max-w-50 font-medium text-foreground" :title="getRequirementActiveFileName()">
                  {{ getRequirementActiveFileName() }}
                </p>

                <p class="text-xs text-muted-foreground">
                  {{ getRequirementActiveFileSizeLabel() }}
                  <span v-if="requirementDescription">· {{ requirementDescription }}</span>
                </p>

                <p class="text-xs text-muted-foreground">
                  {{ getPreviewSuggestion(getRequirementActiveFileName()) }}
                </p>
              </div>

              <div class="flex shrink-0 flex-col gap-2">
                <Button type="button" variant="outline" size="sm" class="gap-2" :disabled="!getRequirementActivePreviewUrl()" @click="openRequirementActivePreview">
                  <Eye class="h-3.5 w-3.5" />
                  Preview
                </Button>
                <Button type="button" variant="ghost" size="sm" class="gap-2 text-red-600 hover:bg-red-50 hover:text-red-700" @click="clearRequirementModalFile">
                  <X class="h-3.5 w-3.5" />
                  Remove
                </Button>
              </div>
            </div>

          </div>

          <div>
            <Label for="requirement_description" class="text-sm font-medium leading-none">Description</Label>
            <Textarea
              id="requirement_description"
              v-model="requirementDescription"
              rows="4"
              placeholder="Short description of the document"
              class="mt-2"
            />
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <Button type="button" variant="outline" @click="cancelRequirement">Cancel</Button>
            <Button type="button" @click="saveRequirement">Save</Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%,
  100% {
    transform: translateX(0);
  }
  20% {
    transform: translateX(-4px);
  }
  40% {
    transform: translateX(4px);
  }
  60% {
    transform: translateX(-4px);
  }
  80% {
    transform: translateX(4px);
  }
}

.animate-shake {
  animation: shake 0.4s ease-in-out;
}
</style>
