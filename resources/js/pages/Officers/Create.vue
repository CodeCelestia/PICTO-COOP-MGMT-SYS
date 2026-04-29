<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Users, Save, X } from 'lucide-vue-next';
import { computed } from 'vue';
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
import { Textarea } from '@/components/ui/textarea';
import { useCreateBack } from '@/composables/useCreateBack';
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
    role_names: string[];
}

interface Props {
    cooperatives: Cooperative[];
    members: MemberOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateOfficer = computed(() => permissions.value.includes('create officers-&-committees'));

const queryParams = computed(() => new URLSearchParams((page.url || '').split('?')[1] || ''));
const initialCoopId = computed(() => queryParams.value.get('coop_id') || props.cooperatives[0]?.id?.toString() || '');
const initialPosition = computed(() => queryParams.value.get('position') || '');

const form = useForm({
    coop_id: initialCoopId.value,
    member_id: '',
    position: initialPosition.value,
    committee: '',
    term_start: '',
    term_end: '',
    status: 'Active',
    reason_for_change: '',
    election_year: '',
});

const filteredMembers = computed(() => {
    if (!form.coop_id) return props.members;
    return props.members.filter(member => member.coop_id.toString() === form.coop_id);
});

const submit = () => {
    if (!canCreateOfficer.value) return;
    form.transform((data) => ({
        ...data,
        return_to: returnToHref.value,
    })).post('/officers', {
        preserveScroll: true,
    });
};

const cancel = () => {
    goBack();
};

const { goBack, returnToHref } = useCreateBack({
    fallbackHref: '/officers',
    cooperativeId: computed(() => form.coop_id),
    cooperativeTab: 'officers',
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Officer</h1>
                    <p class="text-sm text-muted-foreground">Assign a member to an officer role.</p>
                </div>
                <Button variant="outline" size="sm" class="gap-2" type="button" @click="goBack">
                    <ArrowLeft class="h-4 w-4" />
                    Back
                </Button>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <Users class="h-5 w-5" />
                            Officer Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative</Label>
                                <Select v-model="form.coop_id" :disabled="isCoopAdmin">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.coop_id }">
                                        <SelectValue placeholder="Select cooperative" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="coop in cooperatives" :key="coop.id" :value="coop.id.toString()">
                                            {{ coop.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.coop_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.coop_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="member_id">Member</Label>
                                <Select v-model="form.member_id">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.member_id }">
                                        <SelectValue placeholder="Select member" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="member in filteredMembers" :key="member.id" :value="member.id.toString()">
                                            <div class="flex flex-col gap-0.5 text-sm">
                                                <span>{{ member.name }}</span>
                                                <span class="text-xs text-muted-foreground">{{ member.role_names.join(', ') }}</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="filteredMembers.length === 0" class="mt-1 text-sm text-muted-foreground">
                                    No members with Officer, Chairperson, or General Manager role found. Please assign one of those roles to a member first before adding them as an officer.
                                </p>
                                <p v-if="form.errors.member_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.member_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="position">Position</Label>
                                <Input id="position" v-model="form.position" placeholder="Chairperson, Treasurer, etc." />
                                <p v-if="form.errors.position" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.position }}
                                </p>
                            </div>

                            <div>
                                <Label for="committee">Committee</Label>
                                <Input id="committee" v-model="form.committee" placeholder="Audit, Education, Credit" />
                                <p v-if="form.errors.committee" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.committee }}
                                </p>
                            </div>

                            <div>
                                <Label for="term_start">Term Start</Label>
                                <Input id="term_start" v-model="form.term_start" type="date" />
                                <p v-if="form.errors.term_start" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.term_start }}
                                </p>
                            </div>

                            <div>
                                <Label for="term_end">Term End</Label>
                                <Input id="term_end" v-model="form.term_end" type="date" />
                                <p v-if="form.errors.term_end" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.term_end }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Active">Active</SelectItem>
                                        <SelectItem value="Retired">Retired</SelectItem>
                                        <SelectItem value="Removed">Removed</SelectItem>
                                        <SelectItem value="Resigned">Resigned</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="reason_for_change">Reason for Change</Label>
                                <Textarea
                                    id="reason_for_change"
                                    v-model="form.reason_for_change"
                                    placeholder="Election, appointment, resignation, removal, etc."
                                />
                                <p v-if="form.errors.reason_for_change" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.reason_for_change }}
                                </p>
                            </div>

                            <div>
                                <Label for="election_year">Election Year</Label>
                                <Input
                                    id="election_year"
                                    v-model="form.election_year"
                                    type="number"
                                    min="1900"
                                    max="2100"
                                    placeholder="2026"
                                />
                                <p v-if="form.errors.election_year" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.election_year }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t border-border pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateOfficer" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Officer
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
