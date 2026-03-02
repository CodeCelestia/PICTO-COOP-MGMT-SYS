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
import { update as officesUpdate } from '@/routes/super-admin/offices';

type Office = {
    id: number; name: string; code: string;
    cooperative_type: string | null; registration_number: string | null; date_registered: string | null;
    asset_size: number | null; classification: string | null; status: string;
    key_services: string[] | null; year_of_latest_audit: number | null;
    chairperson: string | null; general_manager: string | null;
    region_code: string | null; region_name: string | null;
    province_code: string | null; province_name: string | null;
    city_municipality_code: string | null; city_municipality_name: string | null;
    barangay_code: string | null; barangay_name: string | null;
    contact_email: string | null; contact_phone: string | null;
};
const props = defineProps<{ office: Office }>();

const COOPERATIVE_TYPES = ['Credit', 'Agriculture', 'MPC (Multi-Purpose)', 'Transport', 'Electric', 'Water', 'Housing', 'Workers', 'Consumers', 'Producers', 'Service', 'Others'];
const CLASSIFICATIONS   = ['Micro', 'Small', 'Medium', 'Large', 'Billion'];
const STATUSES          = ['Active', 'Inactive', 'Under Rehabilitation'];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Offices', href: '/super-admin/offices' },
    { title: props.office.name, href: `/super-admin/offices/${props.office.id}` },
    { title: 'Edit', href: '' },
];

const o = props.office;
const form = useForm({
    name: o.name, code: o.code,
    cooperative_type: o.cooperative_type ?? '',
    registration_number: o.registration_number ?? '',
    date_registered: o.date_registered ?? '',
    asset_size: o.asset_size != null ? String(o.asset_size) : '',
    classification: o.classification ?? '',
    status: o.status ?? 'Active',
    key_services: o.key_services ?? [] as string[],
    year_of_latest_audit: o.year_of_latest_audit != null ? String(o.year_of_latest_audit) : '',
    chairperson: o.chairperson ?? '', general_manager: o.general_manager ?? '',
    region_code: o.region_code ?? '', region_name: o.region_name ?? '',
    province_code: o.province_code ?? '', province_name: o.province_name ?? '',
    city_municipality_code: o.city_municipality_code ?? '', city_municipality_name: o.city_municipality_name ?? '',
    barangay_code: o.barangay_code ?? '', barangay_name: o.barangay_name ?? '',
    contact_email: o.contact_email ?? '', contact_phone: o.contact_phone ?? '',
});

// ── PSGC ──────────────────────────────────────────────────────────────────────
const regions   = ref<Region[]>([]);
const provinces = ref<Province[]>([]);
const cities    = ref<CityMunicipality[]>([]);
const barangays = ref<Barangay[]>([]);
const loadingProvinces = ref(false);
const loadingCities    = ref(false);
const loadingBarangays = ref(false);

onMounted(async () => {
    regions.value = (await fetchRegions()).filter(r => r.code);
    if (o.region_code) {
        provinces.value = (await fetchProvinces(o.region_code)).filter(p => p.code);
        if (o.province_code) {
            cities.value = (await fetchCitiesMunicipalities(o.province_code)).filter(c => c.code);
            if (o.city_municipality_code) barangays.value = (await fetchBarangays(o.city_municipality_code)).filter(b => b.code);
        }
    }
});

const onRegionChange = async (code: string) => {
    form.region_code = code; form.region_name = regions.value.find(r => r.code === code)?.name ?? '';
    form.province_code = ''; form.province_name = ''; form.city_municipality_code = ''; form.city_municipality_name = ''; form.barangay_code = ''; form.barangay_name = '';
    provinces.value = []; cities.value = []; barangays.value = [];
    loadingProvinces.value = true; provinces.value = (await fetchProvinces(code)).filter(p => p.code); loadingProvinces.value = false;
};
const onProvinceChange = async (code: string) => {
    form.province_code = code; form.province_name = provinces.value.find(p => p.code === code)?.name ?? '';
    form.city_municipality_code = ''; form.city_municipality_name = ''; form.barangay_code = ''; form.barangay_name = '';
    cities.value = []; barangays.value = [];
    loadingCities.value = true; cities.value = (await fetchCitiesMunicipalities(code)).filter(c => c.code); loadingCities.value = false;
};
const onCityChange = async (code: string) => {
    form.city_municipality_code = code; form.city_municipality_name = cities.value.find(c => c.code === code)?.name ?? '';
    form.barangay_code = ''; form.barangay_name = ''; barangays.value = [];
    loadingBarangays.value = true; barangays.value = (await fetchBarangays(code)).filter(b => b.code); loadingBarangays.value = false;
};
const onBarangayChange = (code: string) => { form.barangay_code = code; form.barangay_name = barangays.value.find(b => b.code === code)?.name ?? ''; };

// ── Key Services ───────────────────────────────────────────────────────────────
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
    form.patch(officesUpdate(props.office.id).url, {
        onSuccess: () => swalSuccess('Cooperative Updated!', 'Changes have been saved.'),
        onError: () => swalError('Validation Error', 'Please check the fields.'),
    });
};
</script>

<template>
    <Head :title="`Edit ${office.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-4xl mx-auto w-full">

            <div class="rounded-2xl bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-5 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/20"><Building2 class="h-5 w-5" /></div>
                        <div>
                            <h1 class="text-xl font-bold">Edit Cooperative</h1>
                            <p class="text-sm text-amber-100">{{ office.name }}</p>
                        </div>
                    </div>
                    <Link :href="`/super-admin/offices/${office.id}`">
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
                            <Input v-model="form.name" required :class="{ 'border-red-400': form.errors.name }" />
                            <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Cooperative ID / Code <span class="text-red-500">*</span></label>
                            <Input v-model="form.code" required :class="{ 'border-red-400': form.errors.code }" />
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
                            <Input v-model="form.registration_number" placeholder="e.g., CDA-2025-XXXXX" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Date Registered</label>
                            <Input v-model="form.date_registered" type="date" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Status <span class="text-red-500">*</span></label>
                            <Select :modelValue="form.status" @update:modelValue="form.status = $event">
                                <SelectTrigger :class="{ 'border-red-400': form.errors.status }"><SelectValue /></SelectTrigger>
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
                            <Input v-model="form.asset_size" type="number" min="0" step="0.01" />
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Classification</label>
                            <Select :modelValue="form.classification || '_none'" @update:modelValue="form.classification = $event === '_none' ? '' : $event">
                                <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_none">— Select —</SelectItem>
                                    <SelectItem v-for="c in CLASSIFICATIONS" :key="c" :value="c">{{ c }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-sm font-semibold text-slate-700">Year of Latest Audit</label>
                            <Input v-model="form.year_of_latest_audit" type="number" min="1900" max="2100" />
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
                    <p class="text-xs font-bold uppercase tracking-wider text-indigo-600 mb-4">VI. Contact Information</p>
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
                    <Link :href="`/super-admin/offices/${office.id}`"><Button type="button" variant="outline">Cancel</Button></Link>
                    <Button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 shadow-sm">
                        <Save class="h-4 w-4" />
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
