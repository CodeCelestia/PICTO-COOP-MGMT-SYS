<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePsgc } from '@/composables/usePsgc';
import { Button } from '@/components/ui/button';
import PdsTabC1 from '@/pages/Pds/components/PdsTabC1.vue';
import PdsTabC2 from '@/pages/Pds/components/PdsTabC2.vue';
import PdsTabC3 from '@/pages/Pds/components/PdsTabC3.vue';
import PdsTabC4 from '@/pages/Pds/components/PdsTabC4.vue';

interface DynamicText {
    value: string;
}

interface ChildRow {
    name: string;
    date_of_birth: string;
}

interface EligibilityRow {
    name: string;
    rating: string;
    exam_date: string;
    exam_place: string;
    license_number: string;
    license_validity: string;
}

interface WorkRow {
    date_from: string;
    date_to: string;
    position_title: string;
    dept_agency: string;
    monthly_salary: string;
    salary_grade: string;
    status_appointment: string;
    govt_service: string;
}

interface VoluntaryRow {
    organization: string;
    date_from: string;
    date_to: string;
    hours: string;
    position: string;
}

interface LearningRow {
    title: string;
    date_from: string;
    date_to: string;
    hours: string;
    type: string;
    conducted_by: string;
}

interface ReferenceRow {
    name: string;
    address: string;
    tel_no: string;
}

interface EducationRow {
    school_name: string;
    degree: string;
    from: string;
    to: string;
    units: string;
    year_graduated: string;
    honors: string;
}

interface EducationSet {
    elementary: EducationRow;
    secondary: EducationRow;
    vocational: EducationRow;
    college: EducationRow;
    graduate: EducationRow;
}

interface PdsSubmission {
    id: number;
    status: 'draft' | 'final';
    updated_at?: string;
    created_at?: string;
    c1_data: Record<string, any>;
    c2_data: Record<string, any>;
    c3_data: Record<string, any>;
    c4_data: Record<string, any>;
}

interface Props {
    pds: PdsSubmission | null;
    isEdit: boolean;
}

const props = defineProps<Props>();
const page = usePage();
const {
    regions,
    provinces,
    cities,
    barangays,
    loading,
    fetchRegions,
    fetchProvinces,
    fetchCities,
    fetchBarangays,
} = usePsgc();
const {
    regions: permRegions,
    provinces: permProvinces,
    cities: permCities,
    barangays: permBarangays,
    loading: permLoading,
    fetchRegions: fetchPermRegions,
    fetchProvinces: fetchPermProvinces,
    fetchCities: fetchPermCities,
    fetchBarangays: fetchPermBarangays,
} = usePsgc();

const selectedResRegionCode = ref('');
const selectedResProvinceCode = ref('');
const selectedResCityCode = ref('');
const hydratingResidential = ref(false);
const selectedPermRegionCode = ref('');
const selectedPermProvinceCode = ref('');
const selectedPermCityCode = ref('');
const hydratingPermanent = ref(false);

const blankEducationRow = (): EducationRow => ({
    school_name: '',
    degree: '',
    from: '',
    to: '',
    units: '',
    year_graduated: '',
    honors: '',
});

const blankReference = (): ReferenceRow => ({ name: '', address: '', tel_no: '' });

const baseData = {
    surname: '',
    first_name: '',
    middle_name: '',
    name_extension: '',
    date_of_birth: '',
    place_of_birth: '',
    sex: '',
    civil_status: '',
    citizenship: 'Filipino',
    dual_country: '',
    dual_citizenship_type: '',
    height: '',
    weight: '',
    blood_type: '',
    umid_id: '',
    pagibig_id: '',
    philhealth_no: '',
    philsys_no: '',
    tin_no: '',
    agency_employee_no: '',
    telephone_no: '',
    mobile_no: '',
    email: '',
    res_house_no: '',
    res_street: '',
    res_subdivision: '',
    res_barangay: '',
    res_city: '',
    res_province: '',
    res_zipcode: '',
    perm_house_no: '',
    perm_street: '',
    perm_subdivision: '',
    perm_barangay: '',
    perm_city: '',
    perm_province: '',
    perm_zipcode: '',
    spouse_surname: '',
    spouse_firstname: '',
    spouse_middlename: '',
    spouse_extension: '',
    spouse_occupation: '',
    spouse_employer: '',
    spouse_business_addr: '',
    spouse_telephone: '',
    father_surname: '',
    father_firstname: '',
    father_middlename: '',
    father_extension: '',
    mother_surname: '',
    mother_firstname: '',
    mother_middlename: '',
    children: [] as ChildRow[],
    education: {
        elementary: blankEducationRow(),
        secondary: blankEducationRow(),
        vocational: blankEducationRow(),
        college: blankEducationRow(),
        graduate: blankEducationRow(),
    } as EducationSet,
    eligibility: [] as EligibilityRow[],
    work_experience: [] as WorkRow[],
    voluntary_work: [] as VoluntaryRow[],
    learning_development: [] as LearningRow[],
    special_skills: [] as string[],
    recognitions: [] as string[],
    memberships: [] as string[],
    q34: '',
    q34_details: '',
    q35: '',
    q35_details: '',
    q36: '',
    q36_details: '',
    q37: '',
    q37_details: '',
    q38a: '',
    q38a_details: '',
    q38b: '',
    q38b_details: '',
    q39: '',
    q39_details: '',
    q40a: '',
    q40a_details: '',
    q40b: '',
    q40b_details: '',
    q41: '',
    q41_details: '',
    references: [blankReference(), blankReference(), blankReference()] as ReferenceRow[],
    govt_id_type: '',
    govt_id_no: '',
    id_issue_date: '',
    id_issue_place: '',
    signature_date: '',
    status: 'draft',
    download: false,
};

const mergedData = props.pds
    ? {
          ...baseData,
          ...props.pds.c1_data,
          ...props.pds.c2_data,
          ...props.pds.c3_data,
          ...props.pds.c4_data,
          status: props.pds.status,
      }
    : baseData;

const form = useForm(mergedData);

const findRegionCodeByProvince = async (provinceName: string): Promise<string | null> => {
    for (const region of regions.value) {
        await fetchProvinces(region.code);
        if (provinces.value.some((item) => item.name === provinceName)) {
            return region.code;
        }
    }

    return null;
};

const findPermRegionCodeByProvince = async (provinceName: string): Promise<string | null> => {
    for (const region of permRegions.value) {
        await fetchPermProvinces(region.code);
        if (permProvinces.value.some((item) => item.name === provinceName)) {
            return region.code;
        }
    }

    return null;
};

onMounted(async () => {
    await Promise.all([fetchRegions(), fetchPermRegions()]);

    if (!form.res_province && !form.res_city && !form.res_barangay) {
        hydratingResidential.value = false;
    } else {
        hydratingResidential.value = true;

        if (form.res_province) {
            const regionCode = await findRegionCodeByProvince(form.res_province);
            if (regionCode) {
                selectedResRegionCode.value = regionCode;
                await fetchProvinces(regionCode);
            }
        }

        if (form.res_province) {
            const province = provinces.value.find((item) => item.name === form.res_province);
            if (province) {
                selectedResProvinceCode.value = province.code;
                await fetchCities(province.code);
            }
        }

        if (form.res_city) {
            const city = cities.value.find((item) => item.name === form.res_city);
            if (city) {
                selectedResCityCode.value = city.code;
                await fetchBarangays(city.code);
            }
        }

        hydratingResidential.value = false;
    }

    if (!form.perm_province && !form.perm_city && !form.perm_barangay) {
        return;
    }

    hydratingPermanent.value = true;

    if (form.perm_province) {
        const regionCode = await findPermRegionCodeByProvince(form.perm_province);
        if (regionCode) {
            selectedPermRegionCode.value = regionCode;
            await fetchPermProvinces(regionCode);
        }
    }

    if (form.perm_province) {
        const province = permProvinces.value.find((item) => item.name === form.perm_province);
        if (province) {
            selectedPermProvinceCode.value = province.code;
            await fetchPermCities(province.code);
        }
    }

    if (form.perm_city) {
        const city = permCities.value.find((item) => item.name === form.perm_city);
        if (city) {
            selectedPermCityCode.value = city.code;
            await fetchPermBarangays(city.code);
        }
    }

    hydratingPermanent.value = false;
});

watch(selectedResRegionCode, (newRegion) => {
    if (!newRegion) {
        return;
    }

    fetchProvinces(newRegion);

    if (!hydratingResidential.value) {
        form.res_province = '';
        form.res_city = '';
        form.res_barangay = '';
        selectedResProvinceCode.value = '';
        selectedResCityCode.value = '';
    }
});

watch(selectedResProvinceCode, (newProvince) => {
    if (!newProvince) {
        return;
    }

    const province = provinces.value.find((item) => item.code === newProvince);
    form.res_province = province?.name || '';
    fetchCities(newProvince);

    if (!hydratingResidential.value) {
        form.res_city = '';
        form.res_barangay = '';
        selectedResCityCode.value = '';
    }
});

watch(selectedResCityCode, (newCity) => {
    if (!newCity) {
        return;
    }

    const city = cities.value.find((item) => item.code === newCity);
    form.res_city = city?.name || '';
    fetchBarangays(newCity);

    if (!hydratingResidential.value) {
        form.res_barangay = '';
    }
});

watch(selectedPermRegionCode, (newRegion) => {
    if (!newRegion) {
        return;
    }

    fetchPermProvinces(newRegion);

    if (!hydratingPermanent.value) {
        form.perm_province = '';
        form.perm_city = '';
        form.perm_barangay = '';
        selectedPermProvinceCode.value = '';
        selectedPermCityCode.value = '';
    }
});

watch(selectedPermProvinceCode, (newProvince) => {
    if (!newProvince) {
        return;
    }

    const province = permProvinces.value.find((item) => item.code === newProvince);
    form.perm_province = province?.name || '';
    fetchPermCities(newProvince);

    if (!hydratingPermanent.value) {
        form.perm_city = '';
        form.perm_barangay = '';
        selectedPermCityCode.value = '';
    }
});

watch(selectedPermCityCode, (newCity) => {
    if (!newCity) {
        return;
    }

    const city = permCities.value.find((item) => item.code === newCity);
    form.perm_city = city?.name || '';
    fetchPermBarangays(newCity);

    if (!hydratingPermanent.value) {
        form.perm_barangay = '';
    }
});

const toDateInput = (value: string): string => {
    const raw = value?.trim();

    if (!raw) {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw;
    }

    if (/^\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{4}$/.test(raw)) {
        const [day, month, year] = raw.split(/[\/.\-]/);
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    return raw;
};

const prepareDateInputs = () => {
    form.date_of_birth = toDateInput(form.date_of_birth);
    form.id_issue_date = toDateInput(form.id_issue_date);
    form.signature_date = toDateInput(form.signature_date);

    form.children = form.children.map((item) => ({
        ...item,
        date_of_birth: toDateInput(item.date_of_birth),
    }));

    form.eligibility = form.eligibility.map((item) => ({
        ...item,
        exam_date: toDateInput(item.exam_date),
        license_validity: toDateInput(item.license_validity),
    }));

    form.work_experience = form.work_experience.map((item) => ({
        ...item,
        date_from: toDateInput(item.date_from),
        date_to: toDateInput(item.date_to),
    }));

    form.voluntary_work = form.voluntary_work.map((item) => ({
        ...item,
        date_from: toDateInput(item.date_from),
        date_to: toDateInput(item.date_to),
    }));

    form.learning_development = form.learning_development.map((item) => ({
        ...item,
        date_from: toDateInput(item.date_from),
        date_to: toDateInput(item.date_to),
    }));
};

prepareDateInputs();

const activeTab = ref<'c1' | 'c2' | 'c3' | 'c4'>('c1');
const openedTabs = ref<Set<'c1' | 'c2' | 'c3' | 'c4'>>(new Set(['c1']));
const copyPermanentAddress = ref(false);

watch(activeTab, (tab) => {
    if (openedTabs.value.has(tab)) {
        return;
    }

    const next = new Set(openedTabs.value);
    next.add(tab);
    openedTabs.value = next;
});

watch(copyPermanentAddress, async (enabled) => {
    if (!enabled) {
        return;
    }

    hydratingPermanent.value = true;

    form.perm_house_no = form.res_house_no;
    form.perm_street = form.res_street;
    form.perm_subdivision = form.res_subdivision;
    form.perm_barangay = form.res_barangay;
    form.perm_city = form.res_city;
    form.perm_province = form.res_province;
    form.perm_zipcode = form.res_zipcode;

    selectedPermRegionCode.value = selectedResRegionCode.value;
    if (selectedPermRegionCode.value) {
        await fetchPermProvinces(selectedPermRegionCode.value);
    }

    selectedPermProvinceCode.value = selectedResProvinceCode.value;
    if (selectedPermProvinceCode.value) {
        await fetchPermCities(selectedPermProvinceCode.value);
    }

    selectedPermCityCode.value = selectedResCityCode.value;
    if (selectedPermCityCode.value) {
        await fetchPermBarangays(selectedPermCityCode.value);
    }

    hydratingPermanent.value = false;
});

const ensureMinRows = () => {
    if (form.children.length === 0) form.children.push({ name: '', date_of_birth: '' });
    if (form.eligibility.length === 0) form.eligibility.push({ name: '', rating: '', exam_date: '', exam_place: '', license_number: '', license_validity: '' });
    if (form.work_experience.length === 0) form.work_experience.push({ date_from: '', date_to: '', position_title: '', dept_agency: '', monthly_salary: '', salary_grade: '', status_appointment: '', govt_service: '' });
    if (form.voluntary_work.length === 0) form.voluntary_work.push({ organization: '', date_from: '', date_to: '', hours: '', position: '' });
    if (form.learning_development.length === 0) form.learning_development.push({ title: '', date_from: '', date_to: '', hours: '', type: '', conducted_by: '' });
};

ensureMinRows();

const tabFieldMap: Record<string, Array<string>> = {
    c1: ['surname', 'first_name', 'date_of_birth', 'place_of_birth', 'sex', 'civil_status', 'citizenship', 'children', 'education'],
    c2: ['eligibility', 'work_experience'],
    c3: ['voluntary_work', 'learning_development'],
    c4: ['special_skills', 'recognitions', 'memberships', 'q34', 'references', 'govt_id_type', 'signature_date'],
};

const switchToErrorTab = () => {
    const keys = Object.keys(form.errors);
    if (keys.length === 0) return;

    const target = (['c1', 'c2', 'c3', 'c4'] as const).find((tab) => {
        return keys.some((key) => tabFieldMap[tab].some((field) => key.startsWith(field)));
    });

    if (target) {
        activeTab.value = target;
    }
};

const submit = (downloadAfterSave: boolean) => {
    form.download = downloadAfterSave;
    form.status = downloadAfterSave ? 'final' : 'draft';

    const onSuccess = (responsePage: any) => {
        const flash = responsePage?.props?.flash || page.props?.flash || {};

        if (downloadAfterSave && flash.trigger_download && flash.pds_id) {
            globalThis.location.href = `/pds/${flash.pds_id}/download`;
        }
    };

    form.post('/pds/my', {
        preserveScroll: true,
        onError: switchToErrorTab,
        onSuccess,
    });
};

const tabs = [
    { id: 'c1', label: 'C1 Personal Information' },
    { id: 'c2', label: 'C2 Eligibility & Work' },
    { id: 'c3', label: 'C3 Voluntary Work & L&D' },
    { id: 'c4', label: 'C4 Other Information' },
] as const;

const lastSavedDate = computed(() => {
    if (!props.pds) {
        return null;
    }

    const raw = props.pds.updated_at || props.pds.created_at;
    if (!raw) {
        return null;
    }

    return new Date(raw).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: 'long',
        day: '2-digit',
    });
});
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">My Personal Data Sheet</h1>
                <p class="mt-1 text-sm text-gray-500">CS Form No. 212 Revised 2025</p>
            </div>

            <div v-if="pds" class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">
                <p class="text-sm font-medium">
                    Your PDS was last saved on {{ lastSavedDate || 'a previous date' }}.
                    Download the official form using the button below.
                </p>
                <div class="mt-3">
                    <a :href="`/pds/${pds.id}/download`" target="_blank" rel="noopener noreferrer">
                        <Button type="button" variant="outline">Download PDS (CS Form 212)</Button>
                    </a>
                </div>
            </div>

            <div class="mb-6 flex flex-wrap gap-2">
                <Button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    :variant="activeTab === tab.id ? 'default' : 'outline'"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </Button>
            </div>

            <form class="space-y-6">
                <PdsTabC1
                    v-if="openedTabs.has('c1')"
                    v-show="activeTab === 'c1'"
                    :form="form"
                    :regions="regions"
                    :provinces="provinces"
                    :cities="cities"
                    :barangays="barangays"
                    :perm-regions="permRegions"
                    :perm-provinces="permProvinces"
                    :perm-cities="permCities"
                    :perm-barangays="permBarangays"
                    :loading="loading"
                    :perm-loading="permLoading"
                    v-model:selected-res-region-code="selectedResRegionCode"
                    v-model:selected-res-province-code="selectedResProvinceCode"
                    v-model:selected-res-city-code="selectedResCityCode"
                    v-model:selected-perm-region-code="selectedPermRegionCode"
                    v-model:selected-perm-province-code="selectedPermProvinceCode"
                    v-model:selected-perm-city-code="selectedPermCityCode"
                    v-model:copy-permanent-address="copyPermanentAddress"
                />
                <PdsTabC2 v-if="openedTabs.has('c2')" v-show="activeTab === 'c2'" :form="form" />
                <PdsTabC3 v-if="openedTabs.has('c3')" v-show="activeTab === 'c3'" :form="form" />
                <PdsTabC4 v-if="openedTabs.has('c4')" v-show="activeTab === 'c4'" :form="form" />

                <div class="flex flex-wrap gap-3">
                    <Button type="button" :disabled="form.processing" @click="submit(false)">
                        {{ form.processing ? 'Saving...' : 'Save as Draft' }}
                    </Button>
                    <Button type="button" :disabled="form.processing" @click="submit(true)">
                        {{ form.processing ? 'Saving...' : 'Save & Download PDS' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>