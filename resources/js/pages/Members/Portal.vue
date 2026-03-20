<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { BreadcrumbItem } from '@/types';

interface Member {
    id: number;
    first_name: string;
    last_name: string;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    region: string | null;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    phone: string | null;
    email: string | null;
}

interface Cooperative {
    id: number;
    name: string;
    registration_number: string | null;
    coop_type: string | null;
    address: string | null;
    province: string | null;
    region: string | null;
    city_municipality: string | null;
    barangay: string | null;
    email: string | null;
    phone: string | null;
    status: string | null;
    accreditation_status: string | null;
}

interface MemberSectorHistory {
    id: number;
    previous_sector: string | null;
    new_sector: string | null;
    previous_livelihood: string | null;
    new_livelihood: string | null;
    change_reason: string | null;
    changed_by: string | null;
    changed_at: string | null;
}

const props = defineProps<{
    member: Member;
    cooperative: Cooperative | null;
    sectorHistory: MemberSectorHistory[];
    servicesCount: number;
    activitiesCount: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Profile', href: '/member-portal' },
];

const form = useForm({
    first_name: props.member.first_name,
    last_name: props.member.last_name,
    birth_date: props.member.birth_date ?? '',
    gender: props.member.gender ?? '',
    address: props.member.address ?? '',
    region: props.member.region ?? '',
    province: props.member.province ?? '',
    city_municipality: props.member.city_municipality ?? '',
    barangay: props.member.barangay ?? '',
    phone: props.member.phone ?? '',
    email: props.member.email ?? '',
    profile_photo: null as File | null,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user as { name?: string; avatar?: string } | undefined);
const { getInitials } = useInitials();
const photoPreview = ref<string | null>(null);

const onPhotoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] || null;
    form.profile_photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : null;
};

const formatDateTime = (date: string | null) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

</script>

<template>
    <Head title="My Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold text-slate-900">My Profile</h1>
                    <p class="text-sm text-slate-600">
                        Update your personal details and review your cooperative information.
                    </p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm lg:col-span-2">
                    <h2 class="text-lg font-semibold text-slate-900">Personal Information</h2>
                    <p class="text-sm text-slate-500">Keep your record accurate and up to date.</p>

                    <form
                        class="mt-6 space-y-4"
                        @submit.prevent="form.put('/member-portal', { forceFormData: true })"
                    >
                        <div class="flex flex-col gap-4 rounded-lg border border-slate-200/70 bg-slate-50/60 p-4 sm:flex-row sm:items-center">
                            <Avatar class="h-16 w-16 overflow-hidden rounded-full">
                                <AvatarImage
                                    v-if="photoPreview || authUser?.avatar"
                                    :src="photoPreview || authUser?.avatar || ''"
                                    :alt="authUser?.name || 'Profile photo'"
                                />
                                <AvatarFallback class="rounded-full text-lg font-semibold">
                                    {{ getInitials(authUser?.name || 'User') }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <Label for="profile_photo">Profile Photo</Label>
                                <Input
                                    id="profile_photo"
                                    type="file"
                                    accept="image/*"
                                    @change="onPhotoChange"
                                />
                                <InputError :message="form.errors.profile_photo" />
                            </div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_name">First name</Label>
                                <Input id="first_name" v-model="form.first_name" />
                                <InputError :message="form.errors.first_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="last_name">Last name</Label>
                                <Input id="last_name" v-model="form.last_name" />
                                <InputError :message="form.errors.last_name" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="birth_date">Birth date</Label>
                                <Input id="birth_date" type="date" v-model="form.birth_date" />
                                <InputError :message="form.errors.birth_date" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="gender">Gender</Label>
                                <select
                                    id="gender"
                                    v-model="form.gender"
                                    class="h-10 rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-900"
                                >
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError :message="form.errors.gender" />
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="email">Email</Label>
                                <Input id="email" type="email" v-model="form.email" />
                                <InputError :message="form.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="phone">Phone</Label>
                                <Input id="phone" v-model="form.phone" />
                                <InputError :message="form.errors.phone" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="address">Address</Label>
                            <Input id="address" v-model="form.address" />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label for="region">Region</Label>
                                <Input id="region" v-model="form.region" />
                                <InputError :message="form.errors.region" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="province">Province</Label>
                                <Input id="province" v-model="form.province" />
                                <InputError :message="form.errors.province" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="city_municipality">City/Municipality</Label>
                                <Input id="city_municipality" v-model="form.city_municipality" />
                                <InputError :message="form.errors.city_municipality" />
                            </div>
                        </div>

                        <div class="grid gap-2">
                            <Label for="barangay">Barangay</Label>
                            <Input id="barangay" v-model="form.barangay" />
                            <InputError :message="form.errors.barangay" />
                        </div>

                        <div class="flex items-center justify-end">
                            <Button :disabled="form.processing">Save Changes</Button>
                        </div>
                    </form>
                </div>

                <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-slate-900">My Cooperative</h2>
                    <p class="text-sm text-slate-500">Your cooperative assignment and details.</p>

                    <div v-if="cooperative" class="mt-6 space-y-3 text-sm text-slate-700">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Name</div>
                            <div class="font-semibold text-slate-900">{{ cooperative.name }}</div>
                        </div>
                        <div class="grid gap-3">
                            <div v-if="cooperative.registration_number">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Registration No.</div>
                                <div>{{ cooperative.registration_number }}</div>
                            </div>
                            <div v-if="cooperative.coop_type">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Type</div>
                                <div>{{ cooperative.coop_type }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Location</div>
                                <div>
                                    {{ cooperative.city_municipality || 'N/A' }}
                                    {{ cooperative.province ? `, ${cooperative.province}` : '' }}
                                </div>
                            </div>
                            <div v-if="cooperative.status">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Status</div>
                                <div>{{ cooperative.status }}</div>
                            </div>
                            <div v-if="cooperative.accreditation_status">
                                <div class="text-xs font-semibold uppercase tracking-widest text-slate-500">Accreditation</div>
                                <div>{{ cooperative.accreditation_status }}</div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-6 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        No cooperative is assigned to your account yet. Please contact your cooperative admin.
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">My Services & Activities</h2>
                <p class="text-sm text-slate-500">View your service availments and participation history.</p>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="text-sm text-slate-500">Services Availed</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900">{{ servicesCount }}</div>
                        <Link href="/member-portal/services" class="mt-4 inline-flex">
                            <Button variant="outline">View Services</Button>
                        </Link>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="text-sm text-slate-500">Activity Participation</div>
                        <div class="mt-2 text-2xl font-semibold text-slate-900">{{ activitiesCount }}</div>
                        <Link href="/member-portal/activities" class="mt-4 inline-flex">
                            <Button variant="outline">View Activities</Button>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200/70 bg-white/90 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">Member Sector History</h2>
                <p class="text-sm text-slate-500">Updates to your sector and livelihood classification.</p>

                <div class="mt-4 rounded-lg border border-slate-200 bg-white">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Changed At</TableHead>
                                <TableHead>Sector</TableHead>
                                <TableHead>Livelihood</TableHead>
                                <TableHead>Changed By</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="sectorHistory.length === 0">
                                <TableCell colspan="4" class="text-center text-slate-500">
                                    No sector history recorded yet.
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="history in sectorHistory" :key="history.id">
                                <TableCell class="text-sm text-slate-600">
                                    {{ formatDateTime(history.changed_at) }}
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-slate-900">{{ history.new_sector || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">from {{ history.previous_sector || 'N/A' }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="text-sm text-slate-900">{{ history.new_livelihood || 'N/A' }}</div>
                                    <div class="text-xs text-slate-500">from {{ history.previous_livelihood || 'N/A' }}</div>
                                </TableCell>
                                <TableCell class="text-sm text-slate-600">{{ history.changed_by || 'N/A' }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
