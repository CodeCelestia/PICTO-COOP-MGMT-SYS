<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { usePsgc } from '@/composables/usePsgc';
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
const copyPermanentAddress = ref(false);

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

const addStringRow = (target: 'special_skills' | 'recognitions' | 'memberships') => {
    form[target].push('');
};

const removeStringRow = (target: 'special_skills' | 'recognitions' | 'memberships', index: number) => {
    form[target].splice(index, 1);
};

const yesNoQuestions = [
    { key: 'q34', details: 'q34_details', text: '34. Have you ever been found guilty of any administrative offense?' },
    { key: 'q35', details: 'q35_details', text: '35. Have you been criminally charged before any court?' },
    { key: 'q36', details: 'q36_details', text: '36. Have you ever been convicted of any crime?' },
    { key: 'q37', details: 'q37_details', text: '37. Have you ever been separated from the service?' },
    { key: 'q38a', details: 'q38a_details', text: '38(a). Have you ever been a candidate in a national or local election?' },
    { key: 'q38b', details: 'q38b_details', text: '38(b). Have you resigned from government service during election period?' },
    { key: 'q39', details: 'q39_details', text: '39. Have you acquired status of immigrant or permanent resident of another country?' },
    { key: 'q40a', details: 'q40a_details', text: '40(a). Pursuant to Indigenous Peoples rights, are you a member of an indigenous group?' },
    { key: 'q40b', details: 'q40b_details', text: '40(b). Are you a person with disability?' },
    { key: 'q41', details: 'q41_details', text: '41. Are you a solo parent?' },
] as const;

const civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Solo Parent', 'Others'];
const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
const lndTypes = ['Managerial', 'Supervisory', 'Technical', 'Clerical', 'Others'];
const educationYearStart = 1950;
const currentYear = new Date().getFullYear();
const yearOptions = Array.from({ length: currentYear - educationYearStart + 1 }, (_, index) => (currentYear - index).toString());

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
                <div v-show="activeTab === 'c1'" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-8">
                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Section 1: Personal Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div>
                                <Label>Surname *</Label>
                                <Input v-model="form.surname" />
                                <InputError :message="form.errors.surname" />
                            </div>
                            <div>
                                <Label>First Name *</Label>
                                <Input v-model="form.first_name" />
                                <InputError :message="form.errors.first_name" />
                            </div>
                            <div>
                                <Label>Middle Name</Label>
                                <Input v-model="form.middle_name" />
                            </div>
                            <div>
                                <Label>Name Extension</Label>
                                <Input v-model="form.name_extension" />
                            </div>
                            <div>
                                <Label>Date of Birth *</Label>
                                <Input v-model="form.date_of_birth" type="date" />
                                <InputError :message="form.errors.date_of_birth" />
                            </div>
                            <div class="md:col-span-2">
                                <Label>Place of Birth *</Label>
                                <Input v-model="form.place_of_birth" />
                                <InputError :message="form.errors.place_of_birth" />
                            </div>
                            <div>
                                <Label>Sex *</Label>
                                <Select v-model="form.sex">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Male">Male</SelectItem>
                                        <SelectItem value="Female">Female</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.sex" />
                            </div>
                            <div>
                                <Label>Civil Status *</Label>
                                <Select v-model="form.civil_status">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="status in civilStatusOptions" :key="status" :value="status">{{ status }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.civil_status" />
                            </div>
                            <div>
                                <Label>Citizenship *</Label>
                                <Select v-model="form.citizenship">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Filipino">Filipino</SelectItem>
                                        <SelectItem value="Dual Citizenship">Dual Citizenship</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.citizenship" />
                            </div>
                            <div v-if="form.citizenship === 'Dual Citizenship'">
                                <Label>Dual Country</Label>
                                <Input v-model="form.dual_country" />
                            </div>
                            <div v-if="form.citizenship === 'Dual Citizenship'">
                                <Label>Dual Citizenship Type</Label>
                                <Select v-model="form.dual_citizenship_type">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="By Birth">By Birth</SelectItem>
                                        <SelectItem value="By Naturalization">By Naturalization</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.dual_citizenship_type" />
                            </div>
                            <div>
                                <Label>Height (m)</Label>
                                <Input v-model="form.height" />
                            </div>
                            <div>
                                <Label>Weight (kg)</Label>
                                <Input v-model="form.weight" />
                            </div>
                            <div>
                                <Label>Blood Type</Label>
                                <Select v-model="form.blood_type">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in bloodTypes" :key="type" :value="type">{{ type }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div><Label>UMID ID</Label><Input v-model="form.umid_id" /></div>
                            <div><Label>Pag-IBIG ID</Label><Input v-model="form.pagibig_id" /></div>
                            <div><Label>PhilHealth No</Label><Input v-model="form.philhealth_no" /></div>
                            <div><Label>PhilSys No</Label><Input v-model="form.philsys_no" /></div>
                            <div><Label>TIN No</Label><Input v-model="form.tin_no" /></div>
                            <div><Label>Agency Employee No</Label><Input v-model="form.agency_employee_no" /></div>
                            <div><Label>Telephone No</Label><Input v-model="form.telephone_no" /></div>
                            <div><Label>Mobile No</Label><Input v-model="form.mobile_no" /></div>
                            <div><Label>Email</Label><Input v-model="form.email" type="email" /></div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <h3 class="md:col-span-3 text-base font-semibold text-gray-800">Residential Address</h3>
                            <div><Label>House/Lot</Label><Input v-model="form.res_house_no" /></div>
                            <div><Label>Street</Label><Input v-model="form.res_street" /></div>
                            <div><Label>Subdivision</Label><Input v-model="form.res_subdivision" /></div>
                            <div>
                                <Label>Region</Label>
                                <Select v-model="selectedResRegionCode">
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
                                <Label>Province</Label>
                                <Select v-model="selectedResProvinceCode" :disabled="!selectedResRegionCode || provinces.length === 0">
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
                                <Label>City/Municipality</Label>
                                <Select v-model="selectedResCityCode" :disabled="!selectedResProvinceCode || cities.length === 0">
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
                                <Label>Barangay</Label>
                                <Select v-model="form.res_barangay" :disabled="!selectedResCityCode || barangays.length === 0">
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
                            <div><Label>Zipcode</Label><Input v-model="form.res_zipcode" /></div>
                            <p v-if="loading" class="md:col-span-3 text-sm text-blue-600">Loading PSGC locations...</p>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="md:col-span-3 flex items-center gap-2">
                                <h3 class="text-base font-semibold text-gray-800">Permanent Address</h3>
                                <label class="flex items-center gap-2 text-sm text-gray-600">
                                    <input v-model="copyPermanentAddress" type="checkbox" class="h-4 w-4" />
                                    Same as residential
                                </label>
                            </div>
                            <div><Label>House/Lot</Label><Input v-model="form.perm_house_no" /></div>
                            <div><Label>Street</Label><Input v-model="form.perm_street" /></div>
                            <div><Label>Subdivision</Label><Input v-model="form.perm_subdivision" /></div>
                            <div>
                                <Label>Region</Label>
                                <Select v-model="selectedPermRegionCode">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select region" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="region in permRegions" :key="region.code" :value="region.code">
                                            {{ region.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Province</Label>
                                <Select v-model="selectedPermProvinceCode" :disabled="!selectedPermRegionCode || permProvinces.length === 0">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select province" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="province in permProvinces" :key="province.code" :value="province.code">
                                            {{ province.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>City/Municipality</Label>
                                <Select v-model="selectedPermCityCode" :disabled="!selectedPermProvinceCode || permCities.length === 0">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select city/municipality" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="city in permCities" :key="city.code" :value="city.code">
                                            {{ city.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Barangay</Label>
                                <Select v-model="form.perm_barangay" :disabled="!selectedPermCityCode || permBarangays.length === 0">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select barangay" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="barangay in permBarangays" :key="barangay.code" :value="barangay.name">
                                            {{ barangay.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><Label>Zipcode</Label><Input v-model="form.perm_zipcode" /></div>
                            <p v-if="permLoading" class="md:col-span-3 text-sm text-blue-600">Loading PSGC locations...</p>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Section 2: Family Background</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div><Label>Spouse Surname</Label><Input v-model="form.spouse_surname" /></div>
                            <div><Label>Spouse First Name</Label><Input v-model="form.spouse_firstname" /></div>
                            <div><Label>Spouse Middle Name</Label><Input v-model="form.spouse_middlename" /></div>
                            <div><Label>Spouse Extension</Label><Input v-model="form.spouse_extension" /></div>
                            <div><Label>Occupation</Label><Input v-model="form.spouse_occupation" /></div>
                            <div><Label>Employer</Label><Input v-model="form.spouse_employer" /></div>
                            <div><Label>Business Address</Label><Input v-model="form.spouse_business_addr" /></div>
                            <div><Label>Telephone</Label><Input v-model="form.spouse_telephone" /></div>
                        </div>

                        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                            <div><Label>Father Surname</Label><Input v-model="form.father_surname" /></div>
                            <div><Label>Father First Name</Label><Input v-model="form.father_firstname" /></div>
                            <div><Label>Father Middle Name</Label><Input v-model="form.father_middlename" /></div>
                            <div><Label>Father Extension</Label><Input v-model="form.father_extension" /></div>
                            <div><Label>Mother Surname</Label><Input v-model="form.mother_surname" /></div>
                            <div><Label>Mother First Name</Label><Input v-model="form.mother_firstname" /></div>
                            <div><Label>Mother Middle Name</Label><Input v-model="form.mother_middlename" /></div>
                        </div>

                        <div class="mt-6">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-base font-semibold text-gray-800">Children</h3>
                                <Button type="button" variant="outline" @click="form.children.push({ name: '', date_of_birth: '' })">Add Child</Button>
                            </div>
                            <div v-for="(child, index) in form.children" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                                <div class="md:col-span-2"><Label>Name</Label><Input v-model="child.name" /></div>
                                <div><Label>Date of Birth</Label><Input v-model="child.date_of_birth" type="date" /></div>
                                <div class="md:col-span-3">
                                    <Button type="button" variant="destructive" size="sm" @click="form.children.splice(index, 1)">Remove</Button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Section 3: Education</h2>
                        <div class="space-y-4">
                            <div v-for="(label, key) in {
                                elementary: 'Elementary',
                                secondary: 'Secondary',
                                vocational: 'Vocational',
                                college: 'College',
                                graduate: 'Graduate Studies'
                            }" :key="key" class="rounded-md border border-gray-200 p-4">
                                <h3 class="mb-3 font-semibold text-gray-800">{{ label }}</h3>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <div><Label>School Name</Label><Input v-model="form.education[key].school_name" /></div>
                                    <div><Label>Degree/Course</Label><Input v-model="form.education[key].degree" /></div>
                                    <div>
                                        <Label>From</Label>
                                        <Select v-model="form.education[key].from">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select year" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="year in yearOptions" :key="`from-${key}-${year}`" :value="year">
                                                    {{ year }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>To</Label>
                                        <Select v-model="form.education[key].to">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select year" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="year in yearOptions" :key="`to-${key}-${year}`" :value="year">
                                                    {{ year }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div><Label>Highest Level/Units</Label><Input v-model="form.education[key].units" /></div>
                                    <div>
                                        <Label>Year Graduated</Label>
                                        <Select v-model="form.education[key].year_graduated">
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select year" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem v-for="year in yearOptions" :key="`grad-${key}-${year}`" :value="year">
                                                    {{ year }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="md:col-span-3"><Label>Honors</Label><Input v-model="form.education[key].honors" /></div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-show="activeTab === 'c2'" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-8">
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Civil Service Eligibility</h2>
                            <Button type="button" variant="outline" @click="form.eligibility.push({ name: '', rating: '', exam_date: '', exam_place: '', license_number: '', license_validity: '' })">Add Row</Button>
                        </div>
                        <div v-for="(row, index) in form.eligibility" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div><Label>Name</Label><Input v-model="row.name" /></div>
                            <div><Label>Rating</Label><Input v-model="row.rating" /></div>
                            <div><Label>Exam Date</Label><Input v-model="row.exam_date" type="date" /></div>
                            <div><Label>Exam Place</Label><Input v-model="row.exam_place" /></div>
                            <div><Label>License Number</Label><Input v-model="row.license_number" /></div>
                            <div><Label>Validity</Label><Input v-model="row.license_validity" type="date" /></div>
                            <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="form.eligibility.splice(index, 1)">Remove</Button></div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Work Experience</h2>
                            <Button type="button" variant="outline" @click="form.work_experience.push({ date_from: '', date_to: '', position_title: '', dept_agency: '', monthly_salary: '', salary_grade: '', status_appointment: '', govt_service: '' })">Add Row</Button>
                        </div>
                        <div v-for="(row, index) in form.work_experience" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                            <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                            <div><Label>Position</Label><Input v-model="row.position_title" /></div>
                            <div><Label>Dept/Agency</Label><Input v-model="row.dept_agency" /></div>
                            <div><Label>Monthly Salary</Label><Input v-model="row.monthly_salary" /></div>
                            <div><Label>Salary Grade</Label><Input v-model="row.salary_grade" /></div>
                            <div><Label>Status</Label><Input v-model="row.status_appointment" /></div>
                            <div>
                                <Label>Govt Service</Label>
                                <Select v-model="row.govt_service">
                                    <SelectTrigger><SelectValue placeholder="Y/N" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Y">Y</SelectItem>
                                        <SelectItem value="N">N</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="md:col-span-4"><Button type="button" variant="destructive" size="sm" @click="form.work_experience.splice(index, 1)">Remove</Button></div>
                        </div>
                    </section>
                </div>

                <div v-show="activeTab === 'c3'" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-8">
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Voluntary Work</h2>
                            <Button type="button" variant="outline" @click="form.voluntary_work.push({ organization: '', date_from: '', date_to: '', hours: '', position: '' })">Add Row</Button>
                        </div>
                        <div v-for="(row, index) in form.voluntary_work" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="md:col-span-2"><Label>Organization</Label><Input v-model="row.organization" /></div>
                            <div><Label>Hours</Label><Input v-model="row.hours" /></div>
                            <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                            <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                            <div><Label>Position/Nature</Label><Input v-model="row.position" /></div>
                            <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="form.voluntary_work.splice(index, 1)">Remove</Button></div>
                        </div>
                    </section>

                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">Learning & Development</h2>
                            <Button type="button" variant="outline" @click="form.learning_development.push({ title: '', date_from: '', date_to: '', hours: '', type: '', conducted_by: '' })">Add Row</Button>
                        </div>
                        <div v-for="(row, index) in form.learning_development" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div class="md:col-span-2"><Label>Title</Label><Input v-model="row.title" /></div>
                            <div><Label>Hours</Label><Input v-model="row.hours" /></div>
                            <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                            <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                            <div>
                                <Label>Type</Label>
                                <Select v-model="row.type">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="type in lndTypes" :key="type" :value="type">{{ type }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><Label>Conducted By</Label><Input v-model="row.conducted_by" /></div>
                            <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="form.learning_development.splice(index, 1)">Remove</Button></div>
                        </div>
                    </section>
                </div>

                <div v-show="activeTab === 'c4'" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm space-y-8">
                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Other Information</h2>

                        <div class="mb-5">
                            <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Special Skills</h3><Button type="button" variant="outline" @click="addStringRow('special_skills')">Add</Button></div>
                            <div v-for="(row, index) in form.special_skills" :key="`skill-${index}`" class="mb-2 flex gap-2">
                                <Input v-model="form.special_skills[index]" />
                                <Button type="button" variant="destructive" size="sm" @click="removeStringRow('special_skills', index)">Remove</Button>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Recognitions</h3><Button type="button" variant="outline" @click="addStringRow('recognitions')">Add</Button></div>
                            <div v-for="(row, index) in form.recognitions" :key="`recognition-${index}`" class="mb-2 flex gap-2">
                                <Input v-model="form.recognitions[index]" />
                                <Button type="button" variant="destructive" size="sm" @click="removeStringRow('recognitions', index)">Remove</Button>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Memberships</h3><Button type="button" variant="outline" @click="addStringRow('memberships')">Add</Button></div>
                            <div v-for="(row, index) in form.memberships" :key="`membership-${index}`" class="mb-2 flex gap-2">
                                <Input v-model="form.memberships[index]" />
                                <Button type="button" variant="destructive" size="sm" @click="removeStringRow('memberships', index)">Remove</Button>
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Yes/No Questions</h2>
                        <div v-for="question in yesNoQuestions" :key="question.key" class="mb-4 rounded-md border border-gray-200 p-4">
                            <Label class="mb-2 block">{{ question.text }}</Label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2"><input type="radio" :name="question.key" value="Yes" v-model="form[question.key]" /> Yes</label>
                                <label class="flex items-center gap-2"><input type="radio" :name="question.key" value="No" v-model="form[question.key]" /> No</label>
                            </div>
                            <InputError :message="form.errors[question.key]" />
                            <div v-if="form[question.key] === 'Yes'" class="mt-3">
                                <Label>Details</Label>
                                <Textarea v-model="form[question.details]" rows="2" />
                            </div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">References</h2>
                        <div v-for="(row, index) in form.references" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            <div><Label>Name</Label><Input v-model="row.name" /></div>
                            <div><Label>Address</Label><Input v-model="row.address" /></div>
                            <div><Label>Tel No</Label><Input v-model="row.tel_no" /></div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Government ID</h2>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div><Label>Government-issued ID</Label><Input v-model="form.govt_id_type" /></div>
                            <div><Label>ID No</Label><Input v-model="form.govt_id_no" /></div>
                            <div><Label>Date of Issue</Label><Input v-model="form.id_issue_date" type="date" /></div>
                            <div><Label>Place of Issue</Label><Input v-model="form.id_issue_place" /></div>
                        </div>
                    </section>

                    <section>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">Signature</h2>
                        <div class="max-w-sm">
                            <Label>Date Signed</Label>
                            <Input v-model="form.signature_date" type="date" />
                        </div>
                    </section>
                </div>

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
