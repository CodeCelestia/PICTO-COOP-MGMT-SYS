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
import { Textarea } from '@/components/ui/textarea';
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

interface Props {
    cooperatives: Cooperative[];
    members: MemberOption[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    member_id: '',
    position: '',
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
    form.post('/officers', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/officers');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add Officer</h1>
                <p class="mt-1 text-sm text-gray-500">Assign a member to an officer role.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
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
                                            {{ member.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
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

                    <div class="flex justify-end gap-3 border-t pt-6">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Officer
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
