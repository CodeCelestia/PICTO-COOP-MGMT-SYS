<script setup lang="ts">
const props = defineProps<{ pds: Record<string, any> }>();

import { Head, Link, useForm } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";
import { FileText, ArrowLeft } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import AppLayout from "@/layouts/AppLayout.vue";
import {
    fetchBarangays, fetchCitiesMunicipalities, fetchProvinces, fetchRegions,
    type Barangay, type CityMunicipality, type Province, type Region,
} from "@/utils/psgc";
import type { BreadcrumbItem } from "@/types";
import { update as pdsUpdate } from "@/routes/super-admin/pds";
import { swalSuccess, swalError } from "@/composables/useSwal";

const breadcrumbs: BreadcrumbItem[] = [
    { title: "PDS Management", href: "/super-admin/pds" },
    { title: "Edit PDS", href: "#" },
];

const QUESTIONS = [
    { key: "q1",  label: "Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you?" },
    { key: "q2",  label: "Have you ever been found guilty of any administrative offense?" },
    { key: "q3",  label: "Have you been criminally charged before any court?" },
    { key: "q4",  label: "Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?" },
    { key: "q5",  label: "Have you ever been separated from the service (resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out)?" },
    { key: "q6",  label: "Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?" },
    { key: "q7",  label: "Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a candidate?" },
    { key: "q8",  label: "Have you acquired the status of an immigrant or permanent resident of another country?" },
    { key: "q9",  label: "Pursuant to one of the following laws, are you a person with disability?" },
    { key: "q10", label: "Have you ever been a member of any organization involving extremist ideology/policy/ambition that advocates violence or overthrow of the government?" },
    { key: "q11", label: "Have you been issued a clearance/certificate of no pending case(s) from the last employer?" },
];

const initQuestions = () => {
    const q: Record<string, { answer: string; details: string }> = {};
    QUESTIONS.forEach(({ key }) => { q[key] = { answer: "No", details: "" }; });
    return q;
};

const EDUCATION_LEVELS = ["Elementary", "Secondary", "Vocational/Trade Course", "College", "Graduate Studies"];

const pds = props.pds;
const INIT_EDU = () => EDUCATION_LEVELS.map((level) => { const e = (pds.education||[]).find((x)=>x.level===level); return e||{level,school_name:"",degree_course:"",attendance_from:"",attendance_to:"",units_earned:"",year_graduated:"",awards_honors:""}; });
const form = useForm({
    // Personal
    first_name: pds.first_name??"", middle_name: pds.middle_name??"", last_name: pds.last_name??"", name_extension: pds.name_extension??"",
    date_of_birth: pds.date_of_birth??"", place_of_birth: pds.place_of_birth??"", gender: pds.gender??"", civil_status: pds.civil_status??"",
    height: pds.height??"", weight: pds.weight??"", blood_type: pds.blood_type??"",
    citizenship: pds.citizenship??"Filipino", dual_citizenship_type: pds.dual_citizenship_type??"", dual_country: pds.dual_country??"",
    // IDs & contact
    gsis_id: pds.gsis_id??"", sss_no: pds.sss_no??"", philhealth_no: pds.philhealth_no??"", pagibig_no: pds.pagibig_no??"", tin_no: pds.tin_no??"",
    telephone_no: pds.telephone_no??"", phone_number: pds.phone_number??"", email: pds.email??"",
    // Residential
    region_code: pds.region_code??"", region_name: pds.region_name??"",
    province_code: pds.province_code??"", province_name: pds.province_name??"",
    city_municipality_code: pds.city_municipality_code??"", city_municipality_name: pds.city_municipality_name??"",
    barangay_code: pds.barangay_code??"", barangay_name: pds.barangay_name??"",
    street_address: pds.street_address??"", res_house: pds.res_house??"", res_subdivision: pds.res_subdivision??"", res_zip: pds.res_zip??"",
    // Permanent
    perm_same_as_res: pds.perm_same_as_res??true,
    perm_house: pds.perm_house??"", perm_street: pds.perm_street??"", perm_subdivision: pds.perm_subdivision??"", perm_zip: pds.perm_zip??"",
    perm_region_code: pds.perm_region_code??"", perm_region_name: pds.perm_region_name??"",
    perm_province_code: pds.perm_province_code??"", perm_province_name: pds.perm_province_name??"",
    perm_city_municipality_code: pds.perm_city_municipality_code??"", perm_city_municipality_name: pds.perm_city_municipality_name??"",
    perm_barangay_code: pds.perm_barangay_code??"", perm_barangay_name: pds.perm_barangay_name??"",
    // Family
    spouse_surname: pds.spouse_surname??"", spouse_first_name: pds.spouse_first_name??"", spouse_middle_name: pds.spouse_middle_name??"", spouse_name_extension: pds.spouse_name_extension??"",
    spouse_occupation: pds.spouse_occupation??"", spouse_employer: pds.spouse_employer??"", spouse_business_address: pds.spouse_business_address??"", spouse_telephone: pds.spouse_telephone??"",
    father_surname: pds.father_surname??"", father_first_name: pds.father_first_name??"", father_middle_name: pds.father_middle_name??"", father_name_extension: pds.father_name_extension??"",
    mother_surname: pds.mother_surname??"", mother_first_name: pds.mother_first_name??"", mother_middle_name: pds.mother_middle_name??"",
    // Arrays
    children: (pds.children??[]) as Array<{ name: string; date_of_birth: string }>,
    education: INIT_EDU(),
    eligibilities: (pds.eligibilities??[]) as Array<{ exam_name: string; rating: string; exam_date: string; exam_place: string; license_no: string; validity_date: string }>,
    work_experiences: (pds.work_experiences??[]) as Array<{ date_from: string; date_to: string; position_title: string; department: string; monthly_salary: string; salary_grade: string; appointment_status: string; is_government: boolean }>,
    voluntary_works: (pds.voluntary_works??[]) as Array<{ organization: string; date_from: string; date_to: string; hours: string; position: string }>,
    learning_developments: (pds.learning_developments??[]) as Array<{ title: string; date_from: string; date_to: string; hours: string; type: string; conducted_by: string }>,
    references_list: (pds.references_list?.length===3?pds.references_list:[{name:"",address:"",telephone:""},{name:"",address:"",telephone:""},{name:"",address:"",telephone:""}]) as Array<{ name: string; address: string; telephone: string }>,
    // Other
    special_skills: pds.special_skills??"", non_academic_distinctions: pds.non_academic_distinctions??"", memberships: pds.memberships??"",
    questions: (pds.questions&&Object.keys(pds.questions).length?pds.questions:initQuestions()) as Record<string, { answer: string; details: string }>,
    government_issued_id: pds.government_issued_id??"", id_no: pds.id_no??"", id_issue_place: pds.id_issue_place??"", date_accomplished: pds.date_accomplished??"",
});

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tabs = ["Personal Info", "Family", "Education", "Eligibility", "Work Exp.", "Vol. Work & L&D", "Other Info", "References"];
const activeTab = ref(0);

// ── Residential PSGC ─────────────────────────────────────────────────────────
const resRegions = ref<Region[]>([]);
const resProvinces = ref<Province[]>([]);
const resCities = ref<CityMunicipality[]>([]);
const resBarangays = ref<Barangay[]>([]);
const loadingResProvinces = ref(false);
const loadingResCities = ref(false);
const loadingResBarangays = ref(false);

const onResRegionChange = async (code: string) => {
    form.region_code = code;
    form.region_name = resRegions.value.find((r) => r.code === code)?.name ?? "";
    form.province_code = ""; form.province_name = "";
    form.city_municipality_code = ""; form.city_municipality_name = "";
    form.barangay_code = ""; form.barangay_name = "";
    resProvinces.value = []; resCities.value = []; resBarangays.value = [];
    if (code) { loadingResProvinces.value = true; resProvinces.value = (await fetchProvinces(code)).filter(p => p.code); loadingResProvinces.value = false; }
};
const onResProvinceChange = async (code: string) => {
    form.province_code = code;
    form.province_name = resProvinces.value.find((p) => p.code === code)?.name ?? "";
    form.city_municipality_code = ""; form.city_municipality_name = "";
    form.barangay_code = ""; form.barangay_name = "";
    resCities.value = []; resBarangays.value = [];
    if (code) { loadingResCities.value = true; resCities.value = (await fetchCitiesMunicipalities(code)).filter(c => c.code); loadingResCities.value = false; }
};
const onResCityChange = async (code: string) => {
    form.city_municipality_code = code;
    form.city_municipality_name = resCities.value.find((c) => c.code === code)?.name ?? "";
    form.barangay_code = ""; form.barangay_name = "";
    resBarangays.value = [];
    if (code) { loadingResBarangays.value = true; resBarangays.value = (await fetchBarangays(code)).filter(b => b.code); loadingResBarangays.value = false; }
};
const onResBarangayChange = (code: string) => {
    form.barangay_code = code;
    form.barangay_name = resBarangays.value.find((b) => b.code === code)?.name ?? "";
};

// ── Permanent PSGC ────────────────────────────────────────────────────────────
const permRegions = ref<Region[]>([]);
const permProvinces = ref<Province[]>([]);
const permCities = ref<CityMunicipality[]>([]);
const permBarangays = ref<Barangay[]>([]);
const loadingPermProvinces = ref(false);
const loadingPermCities = ref(false);
const loadingPermBarangays = ref(false);

const onPermRegionChange = async (code: string) => {
    form.perm_region_code = code;
    form.perm_region_name = permRegions.value.find((r) => r.code === code)?.name ?? "";
    form.perm_province_code = ""; form.perm_province_name = "";
    form.perm_city_municipality_code = ""; form.perm_city_municipality_name = "";
    form.perm_barangay_code = ""; form.perm_barangay_name = "";
    permProvinces.value = []; permCities.value = []; permBarangays.value = [];
    if (code) { loadingPermProvinces.value = true; permProvinces.value = (await fetchProvinces(code)).filter(p => p.code); loadingPermProvinces.value = false; }
};
const onPermProvinceChange = async (code: string) => {
    form.perm_province_code = code;
    form.perm_province_name = permProvinces.value.find((p) => p.code === code)?.name ?? "";
    form.perm_city_municipality_code = ""; form.perm_city_municipality_name = "";
    form.perm_barangay_code = ""; form.perm_barangay_name = "";
    permCities.value = []; permBarangays.value = [];
    if (code) { loadingPermCities.value = true; permCities.value = (await fetchCitiesMunicipalities(code)).filter(c => c.code); loadingPermCities.value = false; }
};
const onPermCityChange = async (code: string) => {
    form.perm_city_municipality_code = code;
    form.perm_city_municipality_name = permCities.value.find((c) => c.code === code)?.name ?? "";
    form.perm_barangay_code = ""; form.perm_barangay_name = "";
    permBarangays.value = [];
    if (code) { loadingPermBarangays.value = true; permBarangays.value = (await fetchBarangays(code)).filter(b => b.code); loadingPermBarangays.value = false; }
};
const onPermBarangayChange = (code: string) => {
    form.perm_barangay_code = code;
    form.perm_barangay_name = permBarangays.value.find((b) => b.code === code)?.name ?? "";
};

onMounted(async () => {
    // Load all region lists in parallel
    const [resR, permR] = await Promise.all([fetchRegions(), fetchRegions()]);
    resRegions.value = resR.filter(r => r.code);
    permRegions.value = permR.filter(r => r.code);

    // Pre-populate residential cascades based on existing PDS data
    if (pds.region_code) {
        resProvinces.value = (await fetchProvinces(pds.region_code)).filter(p => p.code);
    }
    if (pds.province_code) {
        resCities.value = (await fetchCitiesMunicipalities(pds.province_code)).filter(c => c.code);
    }
    if (pds.city_municipality_code) {
        resBarangays.value = (await fetchBarangays(pds.city_municipality_code)).filter(b => b.code);
    }

    // Pre-populate permanent address cascades based on existing PDS data
    if (pds.perm_region_code) {
        permProvinces.value = (await fetchProvinces(pds.perm_region_code)).filter(p => p.code);
    }
    if (pds.perm_province_code) {
        permCities.value = (await fetchCitiesMunicipalities(pds.perm_province_code)).filter(c => c.code);
    }
    if (pds.perm_city_municipality_code) {
        permBarangays.value = (await fetchBarangays(pds.perm_city_municipality_code)).filter(b => b.code);
    }
});

const submit = () => {
    if (!props.pds?.id) return;
    form.put(pdsUpdate(props.pds.id).url, {
        onSuccess: () => swalSuccess('PDS Updated!', 'Personal Data Sheet has been updated successfully.'),
        onError: () => swalError('Validation Error', 'Please check the highlighted fields and try again.'),
    });
};
</script>

<template>
    <Head title="Edit PDS" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-5xl mx-auto w-full">
            <!-- Header -->
            <div class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><FileText class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Edit Personal Data Sheet</h1>
                            <p class="text-sm text-amber-100">{{ pds.last_name }}, {{ pds.first_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="`/super-admin/pds/${pds.id}`"><Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> View</Button></Link>
                        <Button @click="submit" :disabled="form.processing" class="bg-white text-amber-700 hover:bg-amber-50 font-semibold shadow">
                            {{ form.processing ? "Saving…" : "Save Changes" }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Global error -->
            <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                Please fix the errors below before saving.
            </div>

            <!-- Tab Nav -->
            <div class="flex gap-1 bg-white rounded-xl border border-slate-200 shadow-sm p-1.5 overflow-x-auto">
                <button v-for="(tab, i) in tabs" :key="i" @click="activeTab = i"
                    :class="[
                        'px-4 py-2 text-xs font-semibold whitespace-nowrap rounded-lg transition-all shrink-0',
                        activeTab === i
                            ? 'bg-amber-500 text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100'
                    ]">
                    {{ tab }}
                </button>
            </div>

            <!-- Form content card -->
            <form @submit.prevent="submit">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">

                    <!-- ── TAB 0: Personal Information ── -->
                    <div v-show="activeTab === 0" class="space-y-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">I. Personal Information</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Surname <span class="text-red-500">*</span></label>
                                <Input v-model="form.last_name" required placeholder="e.g. Dela Cruz" />
                                <p v-if="form.errors.last_name" class="text-red-500 text-xs mt-1">{{ form.errors.last_name }}</p></div>
                            <div><label class="block text-sm font-medium mb-1">First Name <span class="text-red-500">*</span></label>
                                <Input v-model="form.first_name" required placeholder="e.g. Juan" />
                                <p v-if="form.errors.first_name" class="text-red-500 text-xs mt-1">{{ form.errors.first_name }}</p></div>
                            <div><label class="block text-sm font-medium mb-1">Middle Name</label><Input v-model="form.middle_name" /></div>
                            <div><label class="block text-sm font-medium mb-1">Name Extension</label>
                                <Select :modelValue="form.name_extension || '_none'" @update:modelValue="form.name_extension = $event === '_none' ? '' : $event">
                                    <SelectTrigger><SelectValue placeholder="N/A" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none">N/A</SelectItem>
                                        <SelectItem value="Jr.">Jr.</SelectItem><SelectItem value="Sr.">Sr.</SelectItem>
                                        <SelectItem value="II">II</SelectItem><SelectItem value="III">III</SelectItem><SelectItem value="IV">IV</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Date of Birth <span class="text-red-500">*</span></label>
                                <Input type="date" v-model="form.date_of_birth" required />
                                <p v-if="form.errors.date_of_birth" class="text-red-500 text-xs mt-1">{{ form.errors.date_of_birth }}</p></div>
                            <div><label class="block text-sm font-medium mb-1">Place of Birth</label><Input v-model="form.place_of_birth" /></div>
                            <div><label class="block text-sm font-medium mb-1">Sex <span class="text-red-500">*</span></label>
                                <Select v-bind:modelValue="form.gender" @update:modelValue="form.gender = $event">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Male">Male</SelectItem><SelectItem value="Female">Female</SelectItem><SelectItem value="Other">Other</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.gender" class="text-red-500 text-xs mt-1">{{ form.errors.gender }}</p></div>
                            <div><label class="block text-sm font-medium mb-1">Civil Status</label>
                                <Select v-bind:modelValue="form.civil_status" @update:modelValue="form.civil_status = $event">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Single">Single</SelectItem><SelectItem value="Married">Married</SelectItem>
                                        <SelectItem value="Widowed">Widowed</SelectItem><SelectItem value="Separated">Separated</SelectItem><SelectItem value="Others">Others</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Height (m)</label><Input type="number" step="0.01" v-model="form.height" placeholder="e.g. 1.65" /></div>
                            <div><label class="block text-sm font-medium mb-1">Weight (kg)</label><Input type="number" step="0.01" v-model="form.weight" placeholder="e.g. 60" /></div>
                            <div><label class="block text-sm font-medium mb-1">Blood Type</label>
                                <Select v-bind:modelValue="form.blood_type" @update:modelValue="form.blood_type = $event">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="bt in ['A+','A-','B+','B-','O+','O-','AB+','AB-']" :key="bt" :value="bt">{{ bt }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><label class="block text-sm font-medium mb-1">Citizenship <span class="text-red-500">*</span></label>
                                <Select v-bind:modelValue="form.citizenship" @update:modelValue="form.citizenship = $event">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Filipino">Filipino</SelectItem><SelectItem value="Dual Citizenship">Dual Citizenship</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <div v-if="form.citizenship === 'Dual Citizenship'" class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Dual Citizenship Type</label>
                                <Select v-bind:modelValue="form.dual_citizenship_type" @update:modelValue="form.dual_citizenship_type = $event">
                                    <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="by birth">by birth</SelectItem><SelectItem value="by naturalization">by naturalization</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><label class="block text-sm font-medium mb-1">Country</label><Input v-model="form.dual_country" /></div>
                        </div>

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-2">Government IDs</p>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            <div><label class="block text-sm font-medium mb-1">GSIS ID No.</label><Input v-model="form.gsis_id" /></div>
                            <div><label class="block text-sm font-medium mb-1">SSS No.</label><Input v-model="form.sss_no" /></div>
                            <div><label class="block text-sm font-medium mb-1">PhilHealth No.</label><Input v-model="form.philhealth_no" /></div>
                            <div><label class="block text-sm font-medium mb-1">Pag-IBIG No.</label><Input v-model="form.pagibig_no" /></div>
                            <div><label class="block text-sm font-medium mb-1">TIN No.</label><Input v-model="form.tin_no" /></div>
                        </div>

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-2">Contact Information</p>
                        <div class="grid grid-cols-3 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Telephone No.</label><Input v-model="form.telephone_no" /></div>
                            <div><label class="block text-sm font-medium mb-1">Mobile No.</label><Input v-model="form.phone_number" /></div>
                            <div><label class="block text-sm font-medium mb-1">Email Address <span class="text-red-500">*</span></label>
                                <Input type="email" v-model="form.email" required />
                                <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p></div>
                        </div>

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-2">Residential Address</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">House/Block/Lot No.</label><Input v-model="form.res_house" /></div>
                            <div><label class="block text-sm font-medium mb-1">Street</label><Input v-model="form.street_address" /></div>
                            <div><label class="block text-sm font-medium mb-1">Subdivision/Village</label><Input v-model="form.res_subdivision" /></div>
                            <div><label class="block text-sm font-medium mb-1">ZIP Code</label><Input v-model="form.res_zip" /></div>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div><label class="block text-sm font-medium mb-1">Region</label>
                                <Select v-bind:modelValue="form.region_code" @update:modelValue="onResRegionChange">
                                    <SelectTrigger><SelectValue placeholder="Select region" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="r in resRegions" :key="r.code" :value="r.code">{{ r.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><label class="block text-sm font-medium mb-1">Province</label>
                                <Select v-bind:modelValue="form.province_code" @update:modelValue="onResProvinceChange" :disabled="!form.region_code">
                                    <SelectTrigger><SelectValue placeholder="Select province" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="loadingResProvinces" value="_loading" disabled>Loading…</SelectItem>
                                        <SelectItem v-for="p in resProvinces" :key="p.code" :value="p.code">{{ p.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><label class="block text-sm font-medium mb-1">City/Municipality</label>
                                <Select v-bind:modelValue="form.city_municipality_code" @update:modelValue="onResCityChange" :disabled="!form.province_code">
                                    <SelectTrigger><SelectValue placeholder="Select city" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="loadingResCities" value="_loading" disabled>Loading…</SelectItem>
                                        <SelectItem v-for="c in resCities" :key="c.code" :value="c.code">{{ c.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div><label class="block text-sm font-medium mb-1">Barangay</label>
                                <Select v-bind:modelValue="form.barangay_code" @update:modelValue="onResBarangayChange" :disabled="!form.city_municipality_code">
                                    <SelectTrigger><SelectValue placeholder="Select barangay" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-if="loadingResBarangays" value="_loading" disabled>Loading…</SelectItem>
                                        <SelectItem v-for="b in resBarangays" :key="b.code" :value="b.code">{{ b.name }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <Checkbox id="perm_same" :checked="form.perm_same_as_res" @update:checked="form.perm_same_as_res = $event" />
                            <label for="perm_same" class="text-sm cursor-pointer select-none">Permanent address same as residential</label>
                        </div>

                        <div v-if="!form.perm_same_as_res" class="space-y-4">
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-1">Permanent Address</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div><label class="block text-sm font-medium mb-1">House/Block/Lot No.</label><Input v-model="form.perm_house" /></div>
                                <div><label class="block text-sm font-medium mb-1">Street</label><Input v-model="form.perm_street" /></div>
                                <div><label class="block text-sm font-medium mb-1">Subdivision/Village</label><Input v-model="form.perm_subdivision" /></div>
                                <div><label class="block text-sm font-medium mb-1">ZIP Code</label><Input v-model="form.perm_zip" /></div>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div><label class="block text-sm font-medium mb-1">Region</label>
                                    <Select v-bind:modelValue="form.perm_region_code" @update:modelValue="onPermRegionChange">
                                        <SelectTrigger><SelectValue placeholder="Select region" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="r in permRegions" :key="r.code" :value="r.code">{{ r.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div><label class="block text-sm font-medium mb-1">Province</label>
                                    <Select v-bind:modelValue="form.perm_province_code" @update:modelValue="onPermProvinceChange" :disabled="!form.perm_region_code">
                                        <SelectTrigger><SelectValue placeholder="Select province" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="loadingPermProvinces" value="_loading" disabled>Loading…</SelectItem>
                                            <SelectItem v-for="p in permProvinces" :key="p.code" :value="p.code">{{ p.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div><label class="block text-sm font-medium mb-1">City/Municipality</label>
                                    <Select v-bind:modelValue="form.perm_city_municipality_code" @update:modelValue="onPermCityChange" :disabled="!form.perm_province_code">
                                        <SelectTrigger><SelectValue placeholder="Select city" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="loadingPermCities" value="_loading" disabled>Loading…</SelectItem>
                                            <SelectItem v-for="c in permCities" :key="c.code" :value="c.code">{{ c.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div><label class="block text-sm font-medium mb-1">Barangay</label>
                                    <Select v-bind:modelValue="form.perm_barangay_code" @update:modelValue="onPermBarangayChange" :disabled="!form.perm_city_municipality_code">
                                        <SelectTrigger><SelectValue placeholder="Select barangay" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-if="loadingPermBarangays" value="_loading" disabled>Loading…</SelectItem>
                                            <SelectItem v-for="b in permBarangays" :key="b.code" :value="b.code">{{ b.name }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── TAB 1: Family Background ── -->
                    <div v-show="activeTab === 1" class="space-y-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">II. Family Background</p>

                        <div class="rounded-lg border bg-muted/20 p-4 space-y-3">
                            <p class="text-sm font-medium">Spouse (if married)</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div><label class="block text-sm font-medium mb-1">Surname</label><Input v-model="form.spouse_surname" /></div>
                                <div><label class="block text-sm font-medium mb-1">First Name</label><Input v-model="form.spouse_first_name" /></div>
                                <div><label class="block text-sm font-medium mb-1">Middle Name</label><Input v-model="form.spouse_middle_name" /></div>
                                <div><label class="block text-sm font-medium mb-1">Name Extension</label><Input v-model="form.spouse_name_extension" placeholder="Jr., Sr." /></div>
                                <div><label class="block text-sm font-medium mb-1">Occupation</label><Input v-model="form.spouse_occupation" /></div>
                                <div><label class="block text-sm font-medium mb-1">Employer/Business</label><Input v-model="form.spouse_employer" /></div>
                                <div><label class="block text-sm font-medium mb-1">Business Address</label><Input v-model="form.spouse_business_address" /></div>
                                <div><label class="block text-sm font-medium mb-1">Telephone No.</label><Input v-model="form.spouse_telephone" /></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="rounded-lg border bg-muted/20 p-4 space-y-3">
                                <p class="text-sm font-medium">Father's Name</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-sm font-medium mb-1">Surname</label><Input v-model="form.father_surname" /></div>
                                    <div><label class="block text-sm font-medium mb-1">First Name</label><Input v-model="form.father_first_name" /></div>
                                    <div><label class="block text-sm font-medium mb-1">Middle Name</label><Input v-model="form.father_middle_name" /></div>
                                    <div><label class="block text-sm font-medium mb-1">Extension</label><Input v-model="form.father_name_extension" placeholder="Jr., Sr." /></div>
                                </div>
                            </div>
                            <div class="rounded-lg border bg-muted/20 p-4 space-y-3">
                                <p class="text-sm font-medium">Mother's Maiden Name</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label class="block text-sm font-medium mb-1">Surname</label><Input v-model="form.mother_surname" /></div>
                                    <div><label class="block text-sm font-medium mb-1">First Name</label><Input v-model="form.mother_first_name" /></div>
                                    <div><label class="block text-sm font-medium mb-1">Middle Name</label><Input v-model="form.mother_middle_name" /></div>
                                </div>
                            </div>
                        </div>

                        <!-- Children -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-600">Children</p>
                                <Button type="button" variant="outline" size="sm"
                                    @click="form.children.push({ name: '', date_of_birth: '' })">
                                    + Add Child
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div v-for="(c, i) in form.children" :key="i" class="flex gap-3 items-center">
                                    <Input v-model="c.name" class="flex-1" placeholder="Full name" />
                                    <Input type="date" v-model="c.date_of_birth" class="w-44" />
                                    <Button type="button" variant="ghost" size="sm" class="text-red-500 hover:text-red-700 px-2"
                                        @click="form.children.splice(i, 1)">✕</Button>
                                </div>
                                <p v-if="!form.children.length" class="text-sm text-muted-foreground">No children added.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ── TAB 2: Educational Background ── -->
                    <div v-show="activeTab === 2" class="space-y-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">III. Educational Background</p>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="min-w-full text-xs">
                                <thead class="bg-muted/40 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-36">Level</th>
                                        <th class="px-3 py-2 text-left">School Name</th>
                                        <th class="px-3 py-2 text-left">Degree/Course</th>
                                        <th class="px-3 py-2 text-left w-20">From</th>
                                        <th class="px-3 py-2 text-left w-20">To</th>
                                        <th class="px-3 py-2 text-left w-20">Units</th>
                                        <th class="px-3 py-2 text-left w-20">Yr. Grad</th>
                                        <th class="px-3 py-2 text-left">Honors</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="row in form.education" :key="row.level" class="hover:bg-muted/20">
                                        <td class="px-3 py-2 font-medium text-muted-foreground">{{ row.level }}</td>
                                        <td class="px-3 py-2"><Input v-model="row.school_name" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.degree_course" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.attendance_from" class="h-7 text-xs" placeholder="YYYY" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.attendance_to" class="h-7 text-xs" placeholder="YYYY" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.units_earned" class="h-7 text-xs" placeholder="N/A" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.year_graduated" class="h-7 text-xs" placeholder="YYYY" /></td>
                                        <td class="px-3 py-2"><Input v-model="row.awards_honors" class="h-7 text-xs" /></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── TAB 3: Civil Service Eligibility ── -->
                    <div v-show="activeTab === 3" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600">IV. Civil Service Eligibility</p>
                            <Button type="button" variant="outline" size="sm"
                                @click="form.eligibilities.push({ exam_name:'',rating:'',exam_date:'',exam_place:'',license_no:'',validity_date:'' })">
                                + Add Row
                            </Button>
                        </div>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="min-w-full text-xs">
                                <thead class="bg-muted/40 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Career Service / RA / Board</th>
                                        <th class="px-3 py-2 text-left w-20">Rating</th>
                                        <th class="px-3 py-2 text-left w-28">Date of Exam</th>
                                        <th class="px-3 py-2 text-left">Place of Exam</th>
                                        <th class="px-3 py-2 text-left w-28">License No.</th>
                                        <th class="px-3 py-2 text-left w-28">Date of Validity</th>
                                        <th class="px-3 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="(e, i) in form.eligibilities" :key="i" class="hover:bg-muted/20">
                                        <td class="px-3 py-2"><Input v-model="e.exam_name" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="e.rating" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input type="date" v-model="e.exam_date" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="e.exam_place" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="e.license_no" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input type="date" v-model="e.validity_date" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="form.eligibilities.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button>
                                        </td>
                                    </tr>
                                    <tr v-if="!form.eligibilities.length">
                                        <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">No entries. Click + Add Row.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── TAB 4: Work Experience ── -->
                    <div v-show="activeTab === 4" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-wider text-amber-600">V. Work Experience</p>
                            <Button type="button" variant="outline" size="sm"
                                @click="form.work_experiences.push({ date_from:'',date_to:'',position_title:'',department:'',monthly_salary:'',salary_grade:'',appointment_status:'',is_government:false })">
                                + Add Row
                            </Button>
                        </div>
                        <div class="overflow-x-auto rounded-lg border">
                            <table class="min-w-full text-xs">
                                <thead class="bg-muted/40 border-b">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-24">From</th>
                                        <th class="px-3 py-2 text-left w-24">To</th>
                                        <th class="px-3 py-2 text-left">Position Title</th>
                                        <th class="px-3 py-2 text-left">Department/Agency</th>
                                        <th class="px-3 py-2 text-left w-24">Salary</th>
                                        <th class="px-3 py-2 text-left w-20">SG/Step</th>
                                        <th class="px-3 py-2 text-left w-28">Status</th>
                                        <th class="px-3 py-2 text-center w-14">Gov't</th>
                                        <th class="px-3 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    <tr v-for="(w, i) in form.work_experiences" :key="i" class="hover:bg-muted/20">
                                        <td class="px-3 py-2"><Input v-model="w.date_from" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                        <td class="px-3 py-2"><Input v-model="w.date_to" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                        <td class="px-3 py-2"><Input v-model="w.position_title" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="w.department" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input type="number" v-model="w.monthly_salary" class="h-7 text-xs" /></td>
                                        <td class="px-3 py-2"><Input v-model="w.salary_grade" class="h-7 text-xs" placeholder="15-1" /></td>
                                        <td class="px-3 py-2">
                                            <select v-model="w.appointment_status" class="h-7 w-full rounded-md border border-input bg-background px-2 text-xs">
                                                <option value="">Select</option>
                                                <option>permanent</option><option>temporary</option><option>casual</option><option>contractual</option><option>coterminous</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <Checkbox :checked="w.is_government" @update:checked="w.is_government = $event" />
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <button type="button" @click="form.work_experiences.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button>
                                        </td>
                                    </tr>
                                    <tr v-if="!form.work_experiences.length">
                                        <td colspan="9" class="px-4 py-6 text-center text-muted-foreground">No entries. Click + Add Row.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- ── TAB 5: Voluntary Work & L&D ── -->
                    <div v-show="activeTab === 5" class="space-y-6">
                        <!-- Voluntary Work -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-600">VI.A. Voluntary Work</p>
                                <Button type="button" variant="outline" size="sm"
                                    @click="form.voluntary_works.push({ organization:'',date_from:'',date_to:'',hours:'',position:'' })">
                                    + Add Row
                                </Button>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-muted/40 border-b">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Organization/Institution</th>
                                            <th class="px-3 py-2 text-left w-24">From</th>
                                            <th class="px-3 py-2 text-left w-24">To</th>
                                            <th class="px-3 py-2 text-left w-20">Hours</th>
                                            <th class="px-3 py-2 text-left">Position/Nature</th>
                                            <th class="px-3 py-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(v, i) in form.voluntary_works" :key="i" class="hover:bg-muted/20">
                                            <td class="px-3 py-2"><Input v-model="v.organization" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2"><Input v-model="v.date_from" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                            <td class="px-3 py-2"><Input v-model="v.date_to" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                            <td class="px-3 py-2"><Input type="number" v-model="v.hours" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2"><Input v-model="v.position" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="form.voluntary_works.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button>
                                            </td>
                                        </tr>
                                        <tr v-if="!form.voluntary_works.length">
                                            <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">No entries.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- L&D -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-600">VI.B. Learning &amp; Development Interventions</p>
                                <Button type="button" variant="outline" size="sm"
                                    @click="form.learning_developments.push({ title:'',date_from:'',date_to:'',hours:'',type:'',conducted_by:'' })">
                                    + Add Row
                                </Button>
                            </div>
                            <div class="overflow-x-auto rounded-lg border">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-muted/40 border-b">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Title of L&amp;D Intervention</th>
                                            <th class="px-3 py-2 text-left w-24">From</th>
                                            <th class="px-3 py-2 text-left w-24">To</th>
                                            <th class="px-3 py-2 text-left w-20">Hours</th>
                                            <th class="px-3 py-2 text-left w-28">Type</th>
                                            <th class="px-3 py-2 text-left">Conducted By</th>
                                            <th class="px-3 py-2 w-8"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="(l, i) in form.learning_developments" :key="i" class="hover:bg-muted/20">
                                            <td class="px-3 py-2"><Input v-model="l.title" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2"><Input v-model="l.date_from" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                            <td class="px-3 py-2"><Input v-model="l.date_to" class="h-7 text-xs" placeholder="MM/YYYY" /></td>
                                            <td class="px-3 py-2"><Input type="number" step="0.5" v-model="l.hours" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2">
                                                <select v-model="l.type" class="h-7 w-full rounded-md border border-input bg-background px-2 text-xs">
                                                    <option value="">Select</option>
                                                    <option>managerial</option><option>supervisory</option><option>technical</option><option>foundation</option>
                                                </select>
                                            </td>
                                            <td class="px-3 py-2"><Input v-model="l.conducted_by" class="h-7 text-xs" /></td>
                                            <td class="px-3 py-2 text-center">
                                                <button type="button" @click="form.learning_developments.splice(i,1)" class="text-red-400 hover:text-red-600">✕</button>
                                            </td>
                                        </tr>
                                        <tr v-if="!form.learning_developments.length">
                                            <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">No entries.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ── TAB 6: Other Information ── -->
                    <div v-show="activeTab === 6" class="space-y-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">VII. Other Information</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Special Skills / Hobbies</label>
                                <textarea v-model="form.special_skills" rows="4" class="w-full text-sm rounded-md border border-input bg-background px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring resize-none" placeholder="e.g. Computer Literacy, Photography…"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Non-Academic Distinctions / Recognition</label>
                                <textarea v-model="form.non_academic_distinctions" rows="4" class="w-full text-sm rounded-md border border-input bg-background px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring resize-none" placeholder="e.g. Outstanding Community Leader…"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Membership in Association/Organization</label>
                                <textarea v-model="form.memberships" rows="4" class="w-full text-sm rounded-md border border-input bg-background px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring resize-none" placeholder="e.g. Philippine Cooperative Movement…"></textarea>
                            </div>
                        </div>

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-2">Questions</p>
                        <div class="space-y-3">
                            <div v-for="q in QUESTIONS" :key="q.key" class="rounded-lg border bg-muted/20 p-3">
                                <p class="text-sm text-foreground font-medium mb-2">{{ q.label }}</p>
                                <div class="flex flex-wrap items-center gap-4">
                                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                        <input type="radio" :name="q.key" value="Yes" v-model="form.questions[q.key].answer" class="accent-primary" /> Yes
                                    </label>
                                    <label class="flex items-center gap-1.5 text-sm cursor-pointer">
                                        <input type="radio" :name="q.key" value="No" v-model="form.questions[q.key].answer" class="accent-primary" /> No
                                    </label>
                                    <Input v-if="form.questions[q.key].answer === 'Yes'"
                                        v-model="form.questions[q.key].details"
                                        class="flex-1 h-8 text-sm" placeholder="If yes, give details…" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── TAB 7: References & Declaration ── -->
                    <div v-show="activeTab === 7" class="space-y-6">
                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600">VIII. Character References (not relatives, not former supervisors)</p>
                        <div class="space-y-3">
                            <div v-for="(ref, i) in form.references_list" :key="i" class="grid grid-cols-3 gap-4 rounded-lg border bg-muted/20 p-4">
                                <div><label class="block text-sm font-medium mb-1">Name {{ i + 1 }}</label><Input v-model="ref.name" placeholder="Full Name" /></div>
                                <div><label class="block text-sm font-medium mb-1">Address</label><Input v-model="ref.address" /></div>
                                <div><label class="block text-sm font-medium mb-1">Tel. No.</label><Input v-model="ref.telephone" /></div>
                            </div>
                        </div>

                        <p class="text-xs font-bold uppercase tracking-wider text-amber-600 pt-2">Government-Issued ID</p>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium mb-1">Government-Issued ID (type)</label>
                                <Input v-model="form.government_issued_id" placeholder="e.g. PhilSys ID, Passport, Driver's License" />
                            </div>
                            <div><label class="block text-sm font-medium mb-1">ID / Document No.</label><Input v-model="form.id_no" /></div>
                            <div><label class="block text-sm font-medium mb-1">Date/Place of Issuance</label><Input v-model="form.id_issue_place" placeholder="City, Date" /></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Date Accomplished</label>
                            <Input type="date" v-model="form.date_accomplished" class="w-44" />
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm text-amber-800 leading-relaxed">
                                <strong>DECLARATION:</strong> I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency to verify/validate the contents stated herein.
                            </p>
                        </div>
                    </div>

                </div>

                <!-- Bottom nav -->
                <div class="flex items-center justify-between mt-4">
                    <Button type="button" variant="outline" :disabled="activeTab === 0" @click="activeTab--">← Previous</Button>
                    <span class="text-sm text-muted-foreground">Step {{ activeTab + 1 }} of {{ tabs.length }}</span>
                    <div class="flex gap-2">
                        <Button v-if="activeTab < tabs.length - 1" type="button" @click="activeTab++">Next →</Button>
                        <Button v-else type="submit" :disabled="form.processing">
                            {{ form.processing ? "Saving…" : "Save PDS" }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

