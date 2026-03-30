<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { Users, Save, X } from 'lucide-vue-next';
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
import AppLayout from '@/layouts/AppLayout.vue';

interface Cooperative {
    id: number;
    name: string;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface CommitteeMember {
    id: number;
    member_id: number;
    coop_id: number;
    committee_name: string;
    role: string | null;
    date_assigned: string | null;
    date_removed: string | null;
    status: string;
}

interface Props {
    committeeMember: CommitteeMember;
    cooperatives: Cooperative[];
    members: MemberOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    coop_id: props.committeeMember.coop_id.toString(),
    member_id: props.committeeMember.member_id.toString(),
    committee_name: props.committeeMember.committee_name,
    role: props.committeeMember.role || '',
    date_assigned: props.committeeMember.date_assigned || '',
    date_removed: props.committeeMember.date_removed || '',
    status: props.committeeMember.status || 'Active',
});

const filteredMembers = computed(() => {
    if (!form.coop_id) return props.members;
    return props.members.filter(member => member.coop_id.toString() === form.coop_id);
});

const submit = () => {
    form.put(`/committee-members/${props.committeeMember.id}`, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/committee-members');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Committee Member</h1>
                <p class="mt-1 text-sm text-gray-500">Update committee assignment.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Committee Details
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
                                            {{ member.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.member_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.member_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="committee_name">Committee Name</Label>
                                <Input id="committee_name" v-model="form.committee_name" placeholder="Audit, Education, Credit" />
                                <p v-if="form.errors.committee_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.committee_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="role">Role</Label>
                                <Input id="role" v-model="form.role" placeholder="Chairperson, Member" />
                                <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_assigned">Date Assigned</Label>
                                <Input id="date_assigned" v-model="form.date_assigned" type="date" />
                                <p v-if="form.errors.date_assigned" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_assigned }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_removed">Date Removed</Label>
                                <Input id="date_removed" v-model="form.date_removed" type="date" />
                                <p v-if="form.errors.date_removed" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_removed }}
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
                                        <SelectItem value="Inactive">Inactive</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 border-t pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Update Committee Member
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
