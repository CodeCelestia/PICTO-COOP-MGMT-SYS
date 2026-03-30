<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { Users, Save, X } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

interface ActivityOption {
    id: number;
    title: string;
    coop_id: number;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface Props {
    activities: ActivityOption[];
    members: MemberOption[];
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    activity_id: props.activities[0]?.id?.toString() || '',
    member_id: '',
    role: '',
    date_joined: '',
    is_beneficiary: false,
    remarks: '',
});

const filteredMembers = computed(() => {
    if (!form.activity_id) return props.members;
    const activity = props.activities.find(item => item.id.toString() === form.activity_id);
    if (!activity) return props.members;
    return props.members.filter(member => member.coop_id === activity.coop_id);
});

const submit = () => {
    form.post('/activity-participants', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/activity-participants');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add Activity Participant</h1>
                <p class="mt-1 text-sm text-gray-500">Record participant details for an activity.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Participant Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="activity_id">Activity</Label>
                                <Select v-model="form.activity_id" :disabled="isCoopAdmin && activities.length === 1">
                                    <SelectTrigger id="activity_id" :class="{ 'border-red-500': form.errors.activity_id }">
                                        <SelectValue placeholder="Select activity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="activity in activities" :key="activity.id" :value="activity.id.toString()">
                                            {{ activity.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.activity_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.activity_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="member_id">Member</Label>
                                <Select v-model="form.member_id">
                                    <SelectTrigger id="member_id" :class="{ 'border-red-500': form.errors.member_id }">
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
                                <Label for="role">Role</Label>
                                <Input id="role" v-model="form.role" placeholder="Participant, Organizer" />
                                <p v-if="form.errors.role" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.role }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_joined">Date Joined</Label>
                                <Input id="date_joined" v-model="form.date_joined" type="date" />
                                <p v-if="form.errors.date_joined" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_joined }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="is_beneficiary" class="flex items-center gap-2 text-sm text-gray-700">
                                    <Checkbox id="is_beneficiary" v-model:checked="form.is_beneficiary" />
                                    <span>Is Beneficiary</span>
                                </Label>
                                <p v-if="form.errors.is_beneficiary" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.is_beneficiary }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="remarks">Remarks</Label>
                                <Textarea id="remarks" v-model="form.remarks" placeholder="Additional notes" />
                                <p v-if="form.errors.remarks" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.remarks }}
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
                            Save Participant
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
