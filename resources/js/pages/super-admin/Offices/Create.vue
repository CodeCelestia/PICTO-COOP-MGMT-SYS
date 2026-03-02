<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { ArrowLeft, Save, Building2, X, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { fetchBarangays, fetchCitiesMunicipalities, fetchProvinces, fetchRegions, type Barangay, type CityMunicipality, type Province, type Region } from '@/utils/psgc';
import { swalSuccess, swalError } from '@/composables/useSwal';
import type { BreadcrumbItem } from '@/types';
import { store as officesStore } from '@/routes/super-admin/offices';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/super-admin/dashboard' },
    { title: 'Offices', href: '/super-admin/offices' },
    { title: 'Create', href: '/super-admin/offices/create' },
];

const COOPERATIVE_TYPES = ['Credit', 'Agriculture', 'MPC (Multi-Purpose)', 'Transport', 'Electric', 'Water', 'Housing', 'Workers', 'Consumers', 'Producers', 'Service', 'Others'];
const CLASSIFICATIONS   = ['Micro', 'Small', 'Medium', 'Large', 'Billion'];
const STATUSES          = ['Active', 'Inactive', 'Under Rehabilitation'];

const form = useForm({
    name: '', code: '',
    cooperative_type: '', registration_number: '', date_registered: '',
    asset_size: '', classification: '', status: 'Active',
    key_services: [] as string[], year_of_latest_audit: '',
    chairperson: '', general_manager: '',
    region_code: '', region_name: '',
    province_code: '', province_name: '',
    city_municipality_code: '', city_municipality_name: '',
    barangay_code: '', barangay_name: '',
    contact_email: '', contact_phone: '',
});

// ── PSGC ──────────────────────────────────────────────────────────────────────
const regions   = ref<Region[]>([]);
const provinces = ref<Province[]>([]);
const cities    = ref<CityMunicipality[]>([]);
const barangays = ref<Barangay[]>([]);
const loadingProvinces = ref(false);
const loadingCities    = ref(false);
const loadingBarangays = ref(false);

onMounted(async () => { regions.value = (await fetchRegions()).filter(r => r.code); });

const onRegionChange = async (code: string) => {
    form.region_code = code;
    form.region_name = regions.value.find(r => r.code === code)?.name ?? '';
    form.province_code = ''; form.province_name = '';
    form.city_municipality_code = ''; form.city_municipality_name = '';
    form.barangay_code = ''; form.barangay_name = '';
    provinces.value = []; cities.value = []; barangays.value = [];
    loadingProvinces.value = true;
    provinces.value = (await fetchProvinces(code)).filter(p => p.code);
    loadingProvinces.value = false;
};
const onProvinceChange = async (code: string) => {
    form.province_code = code;
    form.province_name = provinces.value.find(p => p.code === code)?.name ?? '';
    form.city_municipality_code = ''; form.city_municipality_name = '';
    form.barangay_code = ''; form.barangay_name = '';
    cities.value = []; barangays.value = [];
    loadingCities.value = true;
    cities.value = (await fetchCitiesMunicipalities(code)).filter(c => c.code);
    loadingCities.value = false;
};
const onCityChange = async (code: string) => {
    form.city_municipality_code = code;
    form.city_municipality_name = cities.value.find(c => c.code === code)?.name ?? '';
    form.barangay_code = ''; form.barangay_name = '';
    barangays.value = [];
    loadingBarangays.value = true;
    barangays.value = (await fetchBarangays(code)).filter(b => b.code);
    loadingBarangays.value = false;
};
const onBarangayChange = (code: string) => {
    form.barangay_code = code;
    form.barangay_name = barangays.value.find(b => b.code === code)?.name ?? '';
};

// ── Key Services tag input ─────────────────────────────────────────────────────
const serviceInput = ref('');
const addService = () => {
    const val = serviceInput.value.trim();
    if (val && !form.key_services.includes(val)) form.key_services = [...form.key_services, val];
    serviceInput.value = '';
};
const removeService = (i: number) => { form.key_services = form.key_services.filter((_, idx) => idx !== i); };
const onServiceKeydown = (e: KeyboardEvent) => { if (e.key === 'Enter') { e.preventDefault(); addService(); } };

// ── Submit ─────────────────────────────────────────────────────────────────────
const submit = () => {
    form.post(officesStore().url, {
        onSuccess: () => swalSuccess('Cooperative Registered!', `"${form.name}" has been registered successfully.`),
        onError: () => swalError('Validation Error', 'Please check the fields and try again.'),
    });
};
</script>

<template>
    <Head title="Create Office" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-4xl mx-auto w-full">

            <!-- Header -->
            <div class="rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Building2 class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Register New Cooperative</h1>
                            <p class="text-sm text-indigo-200">Fill in the details below</p>
                        </div>
                    </div>
                    <Link href="/super-admin/offices">
                        <Button variant="ghost" class="border border-white/30 text-white hover:bg-white/20 gap-2"><ArrowLeft class="h-4 w-4" /> Back</Button>
                    </Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

                <!-- I. Cooperative Information -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">I. Cooperative Information</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Cooperative Name <span class="text-red-500">*</span></label>
                            <Input v-model="form.name" required placeholder="e.g., PICTO Multi-Purpose Cooperative" :class="{ 'border-red-400': form.errors.name }" />
                            <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Cooperative ID / Code <span class="text-red-500">*</span></label>
                            <Input v-model="form.code" placeholder="e.g., COOP-001" required :class="{ 'border-red-400': form.errors.code }" />
                            <p class="text-xs text-slate-400">Unique identifier for this cooperative</p>
                            <span v-if="form.errors.code" class="text-xs text-red-600">{{ form.errors.code }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Type</label>
                            <Select :modelValue="form.cooperative_type || '_none'" @update:modelValue="form.cooperative_type = $event === '_none' ? '' : $event">
                                <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select type —</SelectItem>
                                    <SelectItem v-for="t in COOPERATIVE_TYPES" :key="t" :value="t">{{ t }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">CDA Registration Number</label>
                            <Input v-model="form.registration_number" placeholder="e.g., CDA-2025-XXXXX" :class="{ 'border-red-400': form.errors.registration_number }" />
                            <span v-if="form.errors.registration_number" class="text-xs text-red-600">{{ form.errors.registration_number }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Date Registered</label>
                            <Input v-model="form.date_registered" type="date" :class="{ 'border-red-400': form.errors.date_registered }" />
                            <span v-if="form.errors.date_registered" class="text-xs text-red-600">{{ form.errors.date_registered }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Status <span class="text-red-500">*</span></label>
                            <Select :modelValue="form.status" @update:modelValue="form.status = $event">
                                <SelectTrigger :class="{ 'border-red-400': form.errors.status }"><SelectValue placeholder="Select status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in STATUSES" :key="s" :value="s">{{ s }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.status" class="text-xs text-red-600">{{ form.errors.status }}</span>
                        </div>
                    </div>
                </div>

                <!-- II. Financial & Classification -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">II. Financial &amp; Classification</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Asset Size (₱)</label>
                            <Input v-model="form.asset_size" type="number" min="0" step="0.01" placeholder="e.g., 5000000.00" :class="{ 'border-red-400': form.errors.asset_size }" />
                            <span v-if="form.errors.asset_size" class="text-xs text-red-600">{{ form.errors.asset_size }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Classification</label>
                            <Select :modelValue="form.classification || '_none'" @update:modelValue="form.classification = $event === '_none' ? '' : $event">
                                <SelectTrigger :class="{ 'border-red-400': form.errors.classification }"><SelectValue placeholder="Select" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select —</SelectItem>
                                    <SelectItem v-for="c in CLASSIFICATIONS" :key="c" :value="c">{{ c }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <span v-if="form.errors.classification" class="text-xs text-red-600">{{ form.errors.classification }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Year of Latest Audit</label>
                            <Input v-model="form.year_of_latest_audit" type="number" min="1900" max="2100" placeholder="e.g., 2024" :class="{ 'border-red-400': form.errors.year_of_latest_audit }" />
                            <span v-if="form.errors.year_of_latest_audit" class="text-xs text-red-600">{{ form.errors.year_of_latest_audit }}</span>
                        </div>
                    </div>
                </div>

                <!-- III. Key Services -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">III. Key Services Offered</p>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <Input v-model="serviceInput" @keydown="onServiceKeydown" placeholder="Type a service and press Enter or click Add" class="flex-1" />
                            <Button type="button" @click="addService" variant="outline" class="gap-1.5 shrink-0"><Plus class="h-4 w-4" /> Add</Button>
                        </div>
                        <div v-if="form.key_services.length" class="flex flex-wrap gap-2">
                            <span v-for="(svc, i) in form.key_services" :key="i"
                                class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200">
                                {{ svc }}
                                <button type="button" @click="removeService(i)" class="hover:text-red-500 transition-colors"><X class="h-3 w-3" /></button>
                            </span>
                        </div>
                        <p v-else class="text-xs text-slate-400 italic">No services added yet.</p>
                    </div>
                </div>

                <!-- IV. Leadership -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">IV. Leadership</p>
                    <p class="text-xs text-slate-400 mb-4">Optional — can be assigned later</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Chairperson</label>
                            <Input v-model="form.chairperson" placeholder="Full name (optional)" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">General Manager</label>
                            <Input v-model="form.general_manager" placeholder="Full name (optional)" />
                        </div>
                    </div>
                </div>

                <!-- V. Location -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">V. Location (PSGC)</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Region</label>
                            <Select :modelValue="form.region_code || '_none'" @update:modelValue="v => v !== '_none' && onRegionChange(v)">
                                <SelectTrigger><SelectValue placeholder="Select region" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select region —</SelectItem>
                                    <SelectItem v-for="r in regions" :key="r.code" :value="r.code">{{ r.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Province</label>
                            <Select :modelValue="form.province_code || '_none'" @update:modelValue="v => v !== '_none' && onProvinceChange(v)" :disabled="!form.region_code">
                                <SelectTrigger><SelectValue placeholder="Select province" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select province —</SelectItem>
                                    <SelectItem v-if="loadingProvinces" value="_l" disabled>Loading…</SelectItem>
                                    <SelectItem v-for="p in provinces" :key="p.code" :value="p.code">{{ p.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">City / Municipality</label>
                            <Select :modelValue="form.city_municipality_code || '_none'" @update:modelValue="v => v !== '_none' && onCityChange(v)" :disabled="!form.province_code">
                                <SelectTrigger><SelectValue placeholder="Select city" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select city —</SelectItem>
                                    <SelectItem v-if="loadingCities" value="_l" disabled>Loading…</SelectItem>
                                    <SelectItem v-for="c in cities" :key="c.code" :value="c.code">{{ c.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Barangay</label>
                            <Select :modelValue="form.barangay_code || '_none'" @update:modelValue="v => v !== '_none' && onBarangayChange(v)" :disabled="!form.city_municipality_code">
                                <SelectTrigger><SelectValue placeholder="Select barangay" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select barangay —</SelectItem>
                                    <SelectItem v-if="loadingBarangays" value="_l" disabled>Loading…</SelectItem>
                                    <SelectItem v-for="b in barangays" :key="b.code" :value="b.code">{{ b.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- VI. Contact -->
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">VI. Contact Information</p>
                    <p class="text-xs text-slate-400 mb-4">Optional — can be updated later</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Contact Email</label>
                            <Input v-model="form.contact_email" type="email" placeholder="coop@example.com" :class="{ 'border-red-400': form.errors.contact_email }" />
                            <span v-if="form.errors.contact_email" class="text-xs text-red-600">{{ form.errors.contact_email }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Contact Phone</label>
                            <Input v-model="form.contact_phone" placeholder="+63 9XX XXX XXXX" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pb-6">
                    <Link href="/super-admin/offices"><Button type="button" variant="outline">Cancel</Button></Link>
                    <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Registering…' : 'Register Cooperative' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
