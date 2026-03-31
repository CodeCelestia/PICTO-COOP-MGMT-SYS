<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
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

const props = defineProps<{
    form: any;
    regions: any[];
    provinces: any[];
    cities: any[];
    barangays: any[];
    permRegions: any[];
    permProvinces: any[];
    permCities: any[];
    permBarangays: any[];
    loading: boolean;
    permLoading: boolean;
    selectedResRegionCode: string;
    selectedResProvinceCode: string;
    selectedResCityCode: string;
    selectedPermRegionCode: string;
    selectedPermProvinceCode: string;
    selectedPermCityCode: string;
    copyPermanentAddress: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:selectedResRegionCode', value: string): void;
    (e: 'update:selectedResProvinceCode', value: string): void;
    (e: 'update:selectedResCityCode', value: string): void;
    (e: 'update:selectedPermRegionCode', value: string): void;
    (e: 'update:selectedPermProvinceCode', value: string): void;
    (e: 'update:selectedPermCityCode', value: string): void;
    (e: 'update:copyPermanentAddress', value: boolean): void;
}>();

const civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Solo Parent', 'Others'];
const bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
const educationLevels = [
    { key: 'elementary', label: 'Elementary' },
    { key: 'secondary', label: 'Secondary' },
    { key: 'vocational', label: 'Vocational' },
    { key: 'college', label: 'College' },
    { key: 'graduate', label: 'Graduate Studies' },
] as const;

const resRegionModel = computed({
    get: () => props.selectedResRegionCode,
    set: (value: string) => emit('update:selectedResRegionCode', value),
});

const resProvinceModel = computed({
    get: () => props.selectedResProvinceCode,
    set: (value: string) => emit('update:selectedResProvinceCode', value),
});

const resCityModel = computed({
    get: () => props.selectedResCityCode,
    set: (value: string) => emit('update:selectedResCityCode', value),
});

const permRegionModel = computed({
    get: () => props.selectedPermRegionCode,
    set: (value: string) => emit('update:selectedPermRegionCode', value),
});

const permProvinceModel = computed({
    get: () => props.selectedPermProvinceCode,
    set: (value: string) => emit('update:selectedPermProvinceCode', value),
});

const permCityModel = computed({
    get: () => props.selectedPermCityCode,
    set: (value: string) => emit('update:selectedPermCityCode', value),
});

const copyAddressModel = computed({
    get: () => props.copyPermanentAddress,
    set: (value: boolean) => emit('update:copyPermanentAddress', value),
});
</script>

<template>
    <div class="space-y-8 rounded-xl border border-border bg-card p-6 shadow-sm">
        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">Section 1: Personal Information</h2>
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
                <h3 class="md:col-span-3 text-base font-semibold text-foreground">Residential Address</h3>
                <div><Label>House/Lot</Label><Input v-model="form.res_house_no" /></div>
                <div><Label>Street</Label><Input v-model="form.res_street" /></div>
                <div><Label>Subdivision</Label><Input v-model="form.res_subdivision" /></div>
                <div>
                    <Label>Region</Label>
                    <Select v-model="resRegionModel">
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
                    <Select v-model="resProvinceModel" :disabled="!selectedResRegionCode || provinces.length === 0">
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
                    <Select v-model="resCityModel" :disabled="!selectedResProvinceCode || cities.length === 0">
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
                    <h3 class="text-base font-semibold text-foreground">Permanent Address</h3>
                    <label class="flex items-center gap-2 text-sm text-muted-foreground">
                        <input v-model="copyAddressModel" type="checkbox" class="h-4 w-4" />
                        Same as residential
                    </label>
                </div>
                <div><Label>House/Lot</Label><Input v-model="form.perm_house_no" /></div>
                <div><Label>Street</Label><Input v-model="form.perm_street" /></div>
                <div><Label>Subdivision</Label><Input v-model="form.perm_subdivision" /></div>
                <div>
                    <Label>Region</Label>
                    <Select v-model="permRegionModel">
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
                    <Select v-model="permProvinceModel" :disabled="!selectedPermRegionCode || permProvinces.length === 0">
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
                    <Select v-model="permCityModel" :disabled="!selectedPermProvinceCode || permCities.length === 0">
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
            <h2 class="mb-4 text-lg font-semibold text-foreground">Section 2: Family Background</h2>
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
                <div><Label>Mother's Maiden Surname</Label><Input v-model="form.mother_surname" /></div>
                <div><Label>Mother's First Name</Label><Input v-model="form.mother_firstname" /></div>
                <div><Label>Mother's Maiden Middle Name</Label><Input v-model="form.mother_middlename" /></div>
            </div>

            <div class="mt-6">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-foreground">Children</h3>
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
            <h2 class="mb-4 text-lg font-semibold text-foreground">Section 3: Education</h2>
            <div class="space-y-4">
                <div v-for="level in educationLevels" :key="level.key" class="rounded-md border border-border bg-muted/30 p-4">
                    <h3 class="mb-3 font-semibold text-foreground">{{ level.label }}</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div><Label>School Name</Label><Input v-model="form.education[level.key].school_name" /></div>
                        <div><Label>Degree/Course</Label><Input v-model="form.education[level.key].degree" /></div>
                        <div>
                            <Label>From</Label>
                            <Input
                                v-model="form.education[level.key].from"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{4}"
                                maxlength="4"
                                placeholder="YYYY"
                            />
                        </div>
                        <div>
                            <Label>To</Label>
                            <Input
                                v-model="form.education[level.key].to"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{4}"
                                maxlength="4"
                                placeholder="YYYY"
                            />
                        </div>
                        <div><Label>Highest Level/Units</Label><Input v-model="form.education[level.key].units" /></div>
                        <div>
                            <Label>Year Graduated</Label>
                            <Input
                                v-model="form.education[level.key].year_graduated"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{4}"
                                maxlength="4"
                                placeholder="YYYY"
                            />
                        </div>
                        <div class="md:col-span-3"><Label>Honors</Label><Input v-model="form.education[level.key].honors" /></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
