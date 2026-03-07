<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, CheckCircle2, Link2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface DuplicateMatch {
    type: 'exact_id' | 'exact_email' | 'fuzzy_name_dob';
    confidence: 'high' | 'medium';
    pds_id: number;
    full_name: string;
    email: string;
    same_office: boolean;
}

interface ExistingPds {
    first_name: string;
    last_name: string;
    middle_name?: string;
    date_of_birth?: string;
    gender?: string;
    citizenship?: string;
    email?: string;
    phone_number?: string;
    gsis_id?: string;
    sss_no?: string;
    philhealth_no?: string;
    pagibig_no?: string;
    tin_no?: string;
}

interface Props {
    existingPds: ExistingPds | null;
    user: { id: number; name: string; email: string; status: string; pds_id: number | null };
    office: { id: number; name: string; requires_approval: boolean } | null;
}

const props = defineProps<Props>();

const page = usePage();
const duplicateMatches = (page.props.flash as any)?.duplicate_matches as DuplicateMatch[] | undefined;
const showDuplicateWarning = ref(!!duplicateMatches?.length);
const confirmedNoDuplicate = ref(false);
const chosenLinkPdsId = ref<number | null>(null);
const activeTab = ref('personal');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Complete Your PDS', href: '/member/complete-pds' },
];

const form = useForm({
    // ── Personal ─────────────────────────────
    first_name: props.existingPds?.first_name ?? '',
    middle_name: props.existingPds?.middle_name ?? '',
    last_name: props.existingPds?.last_name ?? '',
    name_extension: '',
    date_of_birth: props.existingPds?.date_of_birth ?? '',
    gender: props.existingPds?.gender ?? '',
    civil_status: '',
    citizenship: props.existingPds?.citizenship ?? '',
    place_of_birth: '',
    height: '',
    weight: '',
    blood_type: '',
    // ── Contact ──────────────────────────────
    email: props.existingPds?.email ?? props.user.email,
    phone_number: props.existingPds?.phone_number ?? '',
    city_municipality_name: '',
    province_name: '',
    // ── Government IDs ───────────────────────
    gsis_id: props.existingPds?.gsis_id ?? '',
    sss_no: props.existingPds?.sss_no ?? '',
    philhealth_no: props.existingPds?.philhealth_no ?? '',
    pagibig_no: props.existingPds?.pagibig_no ?? '',
    tin_no: props.existingPds?.tin_no ?? '',
    // ── Duplicate resolution ─────────────────
    confirmed_no_duplicate: false,
    link_to_pds_id: null as number | null,
});

const genderOptions = ['Male', 'Female', 'Other'];
const civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Annulled'];
const bloodTypeOptions = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

const submit = () => {
    form.confirmed_no_duplicate = confirmedNoDuplicate.value;
    form.link_to_pds_id = chosenLinkPdsId.value;
    form.post('/member/complete-pds');
};

const linkToPds = (pdsId: number) => {
    chosenLinkPdsId.value = pdsId;
    form.link_to_pds_id = pdsId;
    form.post('/member/complete-pds');
};

const matchTypeLabelMap: Record<string, string> = {
    exact_id:       'Matching Government ID',
    exact_email:    'Matching Email Address',
    fuzzy_name_dob: 'Same Name & Date of Birth',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Complete Your PDS" />

        <div class="mx-auto max-w-4xl p-6 space-y-6">

            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Complete Your Personal Data Sheet</h1>
                <p class="text-muted-foreground mt-1">
                    Please fill in your details below. This is required before you can access your member dashboard.
                    <span v-if="office"> Your application will be submitted to <strong>{{ office.name }}</strong>.</span>
                </p>
            </div>

            <!-- Approval notice -->
            <Alert v-if="office && !office.requires_approval" variant="default">
                <CheckCircle2 class="h-4 w-4" />
                <AlertTitle>Self-Registration Enabled</AlertTitle>
                <AlertDescription>
                    Your office allows self-registration. Your account will be activated immediately after submitting your PDS.
                </AlertDescription>
            </Alert>

            <!-- Duplicate warning -->
            <Alert v-if="showDuplicateWarning && duplicateMatches?.length" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertTitle>Possible Duplicate Records Found</AlertTitle>
                <AlertDescription class="space-y-3">
                    <p>We found existing records that may match your details. Please review and choose an action:</p>
                    <ul class="space-y-2">
                        <li
                            v-for="match in duplicateMatches"
                            :key="match.pds_id"
                            class="flex items-center justify-between rounded-md border border-destructive/30 bg-destructive/5 p-3 text-sm"
                        >
                            <div>
                                <p class="font-medium">{{ match.full_name }}</p>
                                <p class="text-muted-foreground">{{ match.email }}</p>
                                <p class="text-xs mt-0.5">
                                    Match: <strong>{{ matchTypeLabelMap[match.type] ?? match.type }}</strong>
                                    · Confidence: <strong>{{ match.confidence }}</strong>
                                    <span v-if="match.same_office"> · Same office</span>
                                </p>
                            </div>
                            <Button size="sm" variant="outline" @click="linkToPds(match.pds_id)">
                                <Link2 class="mr-1 h-3 w-3" />
                                Link to this record
                            </Button>
                        </li>
                    </ul>
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="confirmed_no_duplicate"
                            v-model="confirmedNoDuplicate"
                            type="checkbox"
                            class="rounded border border-input"
                        />
                        <Label for="confirmed_no_duplicate">
                            None of these are me — create a new PDS record
                        </Label>
                    </div>
                </AlertDescription>
            </Alert>

            <!-- PDS Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Manual tab navigation -->
                <div>
                    <div class="flex gap-1 border-b">
                        <button
                            v-for="tab in ['personal', 'contact', 'gov-ids']"
                            :key="tab"
                            type="button"
                            :class="[
                                'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                                activeTab === tab
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground',
                            ]"
                            @click="activeTab = tab"
                        >
                            {{ tab === 'personal' ? 'Personal' : tab === 'contact' ? 'Contact & Address' : 'Government IDs' }}
                        </button>
                    </div>

                    <!-- Tab 1: Personal Info -->
                    <div v-show="activeTab === 'personal'" class="space-y-4 pt-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label for="last_name">Last Name <span class="text-destructive">*</span></Label>
                                <Input id="last_name" v-model="form.last_name" required />
                                <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="first_name">First Name <span class="text-destructive">*</span></Label>
                                <Input id="first_name" v-model="form.first_name" required />
                                <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="middle_name">Middle Name</Label>
                                <Input id="middle_name" v-model="form.middle_name" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="date_of_birth">Date of Birth <span class="text-destructive">*</span></Label>
                                <Input id="date_of_birth" v-model="form.date_of_birth" type="date" required />
                                <p v-if="form.errors.date_of_birth" class="text-xs text-destructive">{{ form.errors.date_of_birth }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="place_of_birth">Place of Birth</Label>
                                <Input id="place_of_birth" v-model="form.place_of_birth" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label>Gender <span class="text-destructive">*</span></Label>
                                <Select
                                    :model-value="form.gender || '_none'"
                                    @update:model-value="(v) => { form.gender = v === '_none' ? '' : String(v ?? '') }"
                                >
                                    <SelectTrigger><SelectValue placeholder="Select gender" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none" disabled>Select gender</SelectItem>
                                        <SelectItem v-for="g in genderOptions" :key="g" :value="g">{{ g }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.gender" class="text-xs text-destructive">{{ form.errors.gender }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Civil Status</Label>
                                <Select
                                    :model-value="form.civil_status || '_none'"
                                    @update:model-value="(v) => { form.civil_status = v === '_none' ? '' : String(v ?? '') }"
                                >
                                    <SelectTrigger><SelectValue placeholder="Select civil status" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none" disabled>Select civil status</SelectItem>
                                        <SelectItem v-for="cs in civilStatusOptions" :key="cs" :value="cs">{{ cs }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="citizenship">Citizenship <span class="text-destructive">*</span></Label>
                                <Input id="citizenship" v-model="form.citizenship" required />
                                <p v-if="form.errors.citizenship" class="text-xs text-destructive">{{ form.errors.citizenship }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="space-y-1.5">
                                <Label for="height">Height (m)</Label>
                                <Input id="height" v-model="form.height" type="number" step="0.01" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="weight">Weight (kg)</Label>
                                <Input id="weight" v-model="form.weight" type="number" step="0.1" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Blood Type</Label>
                                <Select
                                    :model-value="form.blood_type || '_none'"
                                    @update:model-value="(v) => { form.blood_type = v === '_none' ? '' : String(v ?? '') }"
                                >
                                    <SelectTrigger><SelectValue placeholder="Select blood type" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none" disabled>Select blood type</SelectItem>
                                        <SelectItem v-for="bt in bloodTypeOptions" :key="bt" :value="bt">{{ bt }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Contact & Address -->
                    <div v-show="activeTab === 'contact'" class="space-y-4 pt-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="email">Email <span class="text-destructive">*</span></Label>
                                <Input id="email" v-model="form.email" type="email" required />
                                <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                            </div>
                            <div class="space-y-1.5">
                                <Label for="phone_number">Phone Number</Label>
                                <Input id="phone_number" v-model="form.phone_number" />
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="city_municipality_name">City / Municipality</Label>
                                <Input id="city_municipality_name" v-model="form.city_municipality_name" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="province_name">Province</Label>
                                <Input id="province_name" v-model="form.province_name" />
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Government IDs -->
                    <div v-show="activeTab === 'gov-ids'" class="space-y-4 pt-4">
                        <p class="text-sm text-muted-foreground">At least one government ID helps prevent duplicates.</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="gsis_id">GSIS ID</Label>
                                <Input id="gsis_id" v-model="form.gsis_id" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="sss_no">SSS No.</Label>
                                <Input id="sss_no" v-model="form.sss_no" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="philhealth_no">PhilHealth No.</Label>
                                <Input id="philhealth_no" v-model="form.philhealth_no" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="pagibig_no">Pag-IBIG No.</Label>
                                <Input id="pagibig_no" v-model="form.pagibig_no" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="tin_no">TIN</Label>
                                <Input id="tin_no" v-model="form.tin_no" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3 border-t pt-4">
                    <Button
                        type="submit"
                        :disabled="form.processing || (showDuplicateWarning && !confirmedNoDuplicate && !chosenLinkPdsId)"
                    >
                        <span v-if="form.processing">Saving…</span>
                        <span v-else>Submit PDS</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
