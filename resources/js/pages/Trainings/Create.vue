<script setup lang="ts">
import { useForm, router, usePage } from '@inertiajs/vue3';
import { GraduationCap, Save, X } from 'lucide-vue-next';
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

interface Props {
    cooperatives: Cooperative[];
}

const props = defineProps<Props>();

const page = usePage();
const auth = computed(() => page.props.auth as { isCoopAdmin?: boolean; permissions?: string[] } | undefined);
const isCoopAdmin = computed(() => Boolean(auth.value?.isCoopAdmin));
const permissions = computed<string[]>(() => auth.value?.permissions || []);
const canCreateTraining = computed(() => permissions.value.includes('create training-&-capacity'));

const form = useForm({
    coop_id: props.cooperatives[0]?.id?.toString() || '',
    title: '',
    date_conducted: '',
    facilitator: '',
    skills_targeted: '',
    venue: '',
    target_group: 'All Members',
    no_of_participants: '',
    follow_up_needed: false,
    follow_up_date: '',
    follow_up_remarks: '',
    status: 'Planned',
});

const targetGroups = ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'Fishfolk', 'New Members', 'Other'];
const statusOptions = ['Planned', 'Completed', 'Cancelled', 'Follow-Up Pending'];

const submit = () => {
    if (!canCreateTraining.value) return;
    form.post('/trainings', {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get('/trainings');
};
</script>

<template>
    <AppLayout>
        <div class="space-y-6 p-4 sm:p-6">
            <div class="space-y-1">
                <h1 class="text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">Add Training</h1>
                <p class="text-sm text-muted-foreground">Record a training or capacity building session.</p>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-sm sm:p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <h2 class="mb-4 flex items-center gap-2 text-lg font-semibold text-foreground">
                            <GraduationCap class="h-5 w-5" />
                            Training Details
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
                                <Label for="title">Title</Label>
                                <Input id="title" v-model="form.title" placeholder="Training title" />
                                <p v-if="form.errors.title" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <div>
                                <Label for="date_conducted">Date Conducted</Label>
                                <Input id="date_conducted" v-model="form.date_conducted" type="date" />
                                <p v-if="form.errors.date_conducted" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.date_conducted }}
                                </p>
                            </div>

                            <div>
                                <Label for="facilitator">Facilitator</Label>
                                <Input id="facilitator" v-model="form.facilitator" placeholder="Provider or facilitator" />
                                <p v-if="form.errors.facilitator" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.facilitator }}
                                </p>
                            </div>

                            <div>
                                <Label for="venue">Venue</Label>
                                <Input id="venue" v-model="form.venue" placeholder="Training venue" />
                                <p v-if="form.errors.venue" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.venue }}
                                </p>
                            </div>

                            <div>
                                <Label for="target_group">Target Group</Label>
                                <Select v-model="form.target_group">
                                    <SelectTrigger id="target_group" :class="{ 'border-red-500': form.errors.target_group }">
                                        <SelectValue placeholder="Select target group" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in targetGroups" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.target_group" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.target_group }}
                                </p>
                            </div>

                            <div>
                                <Label for="no_of_participants">No. of Participants</Label>
                                <Input id="no_of_participants" v-model="form.no_of_participants" type="number" min="0" />
                                <p v-if="form.errors.no_of_participants" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.no_of_participants }}
                                </p>
                            </div>

                            <div>
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status" :class="{ 'border-red-500': form.errors.status }">
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="option in statusOptions" :key="option" :value="option">
                                            {{ option }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.status }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="skills_targeted">Skills Targeted</Label>
                                <Textarea id="skills_targeted" v-model="form.skills_targeted" placeholder="Skills targeted" />
                                <p v-if="form.errors.skills_targeted" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.skills_targeted }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <Label for="follow_up_needed" class="flex items-center gap-2 text-sm text-foreground/80">
                                    <Checkbox id="follow_up_needed" v-model:checked="form.follow_up_needed" />
                                    <span>Follow-up needed</span>
                                </Label>
                                <p v-if="form.errors.follow_up_needed" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_needed }}
                                </p>
                            </div>

                            <div>
                                <Label for="follow_up_date">Follow-up Date</Label>
                                <Input id="follow_up_date" v-model="form.follow_up_date" type="date" />
                                <p v-if="form.errors.follow_up_date" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_date }}
                                </p>
                            </div>

                            <div>
                                <Label for="follow_up_remarks">Follow-up Remarks</Label>
                                <Input id="follow_up_remarks" v-model="form.follow_up_remarks" placeholder="Follow-up remarks" />
                                <p v-if="form.errors.follow_up_remarks" class="mt-1 text-sm text-red-500">
                                    {{ form.errors.follow_up_remarks }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:justify-end">
                        <Button @click="cancel" type="button" variant="outline" class="gap-2">
                            <X class="h-4 w-4" />
                            Cancel
                        </Button>
                        <Button v-if="canCreateTraining" type="submit" :disabled="form.processing" class="gap-2">
                            <Save class="h-4 w-4" />
                            Save Training
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
