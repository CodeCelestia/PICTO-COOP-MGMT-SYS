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

interface TrainingOption {
    id: number;
    title: string;
    coop_id: number;
}

interface MemberOption {
    id: number;
    name: string;
    coop_id: number;
}

interface OfficerOption {
    id: number;
    name: string | null;
    coop_id: number;
}

interface Props {
    trainings: TrainingOption[];
    members: MemberOption[];
    officers: OfficerOption[];
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    training_id: props.trainings[0]?.id?.toString() || '',
    member_id: '',
    officer_id: 'none',
    outcome: 'No Assessment',
    certificate_no: '',
    certificate_date: '',
    remarks: '',
});

const outcomeOptions = ['Passed', 'Failed', 'Incomplete', 'No Assessment'];

const filteredMembers = computed(() => {
    if (!form.training_id) return props.members;
    const training = props.trainings.find(item => item.id.toString() === form.training_id);
    if (!training) return props.members;
    return props.members.filter(member => member.coop_id === training.coop_id);
});

const filteredOfficers = computed(() => {
    if (!form.training_id) return props.officers;
    const training = props.trainings.find(item => item.id.toString() === form.training_id);
    if (!training) return props.officers;
    return props.officers.filter(officer => officer.coop_id === training.coop_id);
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        officer_id: data.officer_id === 'none' ? '' : data.officer_id,
    }));

    form.post('/training-participants', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/training-participants');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Add Training Participant</h1>
                <p class="mt-1 text-sm text-gray-500">Record participant details for a training session.</p>
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
                                <Label for="training_id">Training</Label>
                                <Select v-model="form.training_id" :disabled="isCoopAdmin && trainings.length === 1">
                                    <SelectTrigger id="training_id" :class="{ 'border-red-500': form.errors.training_id }">
                                        <SelectValue placeholder="Select training" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="training in trainings" :key="training.id" :value="training.id.toString()">
                                            {{ training.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.training_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.training_id }}
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
                                <Label for="officer_id">Officer (optional)</Label>
                                <Select v-model="form.officer_id">
                                    <SelectTrigger id="officer_id" :class="{ 'border-red-500': form.errors.officer_id }">
                                        <SelectValue placeholder="Select officer" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No officer</SelectItem>
                                        <SelectItem v-for="officer in filteredOfficers" :key="officer.id" :value="officer.id.toString()">
                                            {{ officer.name || 'Officer' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.officer_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.officer_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="outcome">Outcome</Label>
                                <Select v-model="form.outcome">
                                    <SelectTrigger id="outcome" :class="{ 'border-red-500': form.errors.outcome }">
                                        <SelectValue placeholder="Select outcome" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in outcomeOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.outcome" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.outcome }}
                                </p>
                            </div>

                            <div>
                                <Label for="certificate_no">Certificate No.</Label>
                                <Input id="certificate_no" v-model="form.certificate_no" placeholder="Certificate number" />
                                <p v-if="form.errors.certificate_no" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.certificate_no }}
                                </p>
                            </div>

                            <div>
                                <Label for="certificate_date">Certificate Date</Label>
                                <Input id="certificate_date" v-model="form.certificate_date" type="date" />
                                <p v-if="form.errors.certificate_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.certificate_date }}
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
