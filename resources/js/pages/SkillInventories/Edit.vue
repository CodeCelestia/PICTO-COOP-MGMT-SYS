<script setup lang="ts">
import { computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Sparkles, Save, X } from 'lucide-vue-next';

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

interface SkillRecord {
    id: number;
    member_id: number;
    coop_id: number;
    skill_name: string;
    proficiency_level: string;
    training_id: number;
    date_acquired: string | null;
    remarks: string | null;
}

interface Props {
    skill: SkillRecord;
    trainings: TrainingOption[];
    members: MemberOption[];
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));

const form = useForm({
    coop_id: props.skill.coop_id.toString(),
    member_id: props.skill.member_id.toString(),
    skill_name: props.skill.skill_name,
    proficiency_level: props.skill.proficiency_level,
    training_id: props.skill.training_id.toString(),
    date_acquired: props.skill.date_acquired || '',
    remarks: props.skill.remarks || '',
});

const proficiencyOptions = ['Beginner', 'Intermediate', 'Advanced'];

const filteredMembers = computed(() => {
    if (!form.coop_id) return props.members;
    return props.members.filter(member => member.coop_id.toString() === form.coop_id);
});

const filteredTrainings = computed(() => {
    if (!form.coop_id) return props.trainings;
    return props.trainings.filter(training => training.coop_id.toString() === form.coop_id);
});

const submit = () => {
    form.put(`/skill-inventories/${props.skill.id}`, {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/skill-inventories');
};
</script>

<template>
    <AppLayout>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Skill Inventory</h1>
                <p class="mt-1 text-sm text-gray-500">Update skill details for a member.</p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <Sparkles class="h-5 w-5" />
                            Skill Details
                        </h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <Label for="coop_id">Cooperative</Label>
                                <Select v-model="form.coop_id" :disabled="isCoopAdmin">
                                    <SelectTrigger id="coop_id" :class="{ 'border-red-500': form.errors.coop_id }">
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
                                <Label for="skill_name">Skill Name</Label>
                                <Input id="skill_name" v-model="form.skill_name" placeholder="Skill name" />
                                <p v-if="form.errors.skill_name" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.skill_name }}
                                </p>
                            </div>

                            <div>
                                <Label for="proficiency_level">Proficiency Level</Label>
                                <Select v-model="form.proficiency_level">
                                    <SelectTrigger id="proficiency_level" :class="{ 'border-red-500': form.errors.proficiency_level }">
                                        <SelectValue placeholder="Select proficiency" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in proficiencyOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.proficiency_level" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.proficiency_level }}
                                </p>
                            </div>

                            <div>
                                <Label for="training_id">Training Source</Label>
                                <Select v-model="form.training_id">
                                    <SelectTrigger id="training_id" :class="{ 'border-red-500': form.errors.training_id }">
                                        <SelectValue placeholder="Select training" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="training in filteredTrainings" :key="training.id" :value="training.id.toString()">
                                            {{ training.title }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.training_id" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.training_id }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_acquired">Date Acquired</Label>
                                <Input id="date_acquired" v-model="form.date_acquired" type="date" />
                                <p v-if="form.errors.date_acquired" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_acquired }}
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
                            Update Skill
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
