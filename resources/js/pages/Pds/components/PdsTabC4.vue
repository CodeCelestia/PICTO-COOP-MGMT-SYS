<script setup lang="ts">
import { onUnmounted, reactive, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

const yesNoQuestions = [
    { key: 'q34', details: 'q34_details', text: '34. Have you ever been found guilty of any administrative offense?' },
    { key: 'q35', details: 'q35_details', text: '35. Have you been criminally charged before any court?' },
    { key: 'q36', details: 'q36_details', text: '36. Have you ever been convicted of any crime?' },
    { key: 'q37', details: 'q37_details', text: '37. Have you ever been separated from the service?' },
    { key: 'q38a', details: 'q38a_details', text: '38(a). Have you ever been a candidate in a national or local election?' },
    { key: 'q38b', details: 'q38b_details', text: '38(b). Have you resigned from government service during election period?' },
    { key: 'q39', details: 'q39_details', text: '39. Have you acquired status of immigrant or permanent resident of another country?' },
    { key: 'q40a', details: 'q40a_details', text: '40(a). Pursuant to Indigenous Peoples rights, are you a member of an indigenous group?' },
    { key: 'q40b', details: 'q40b_details', text: '40(b). Are you a person with disability?' },
    { key: 'q41', details: 'q41_details', text: '41. Are you a solo parent?' },
] as const;

const emit = defineEmits<{
    (e: 'update-field', key: string, value: any): void
    (e: 'add-special-skill'): void
    (e: 'remove-special-skill', index: number): void
    (e: 'add-recognition'): void
    (e: 'remove-recognition', index: number): void
    (e: 'add-membership'): void
    (e: 'remove-membership', index: number): void
}>()

const props = defineProps<{
    form: any;
}>();

const detailDraft = reactive<Record<string, string>>(
    Object.fromEntries(yesNoQuestions.map((question) => [question.details, props.form[question.details] || ''])),
);

const updateField = (key: string, value: any) => {
    emit('update-field', key, value);
};

const updateArrayAt = (key: string, index: number, value: any) => {
    const arr = Array.isArray(props.form[key]) ? [...props.form[key]] : [];
    arr[index] = value;
    emit('update-field', key, arr);
};

const syncDetail = (detailsKey: string, value: string) => {
    if (props.form[detailsKey] === value) {
        return;
    }

    emit('update-field', detailsKey, value);
};

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    detailDraft,
    (value) => {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
            yesNoQuestions.forEach((question) => {
                syncDetail(question.details, value[question.details] || '');
            });
        }, 120);
    },
    { deep: true },
);

watch(
    () => yesNoQuestions.map((question) => props.form[question.details]),
    (values) => {
        yesNoQuestions.forEach((question, index) => {
            const nextValue = values[index] || '';
            if (detailDraft[question.details] !== nextValue) {
                detailDraft[question.details] = nextValue;
            }
        });
    },
);

onUnmounted(() => {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }
});
</script>

<template>
    <div class="space-y-8 rounded-xl border border-border bg-card p-6 shadow-sm">
        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">Other Information</h2>

            <div class="mb-5">
                <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Special Skills</h3><Button type="button" variant="outline" @click="emit('add-special-skill')">Add</Button></div>
                <div v-for="(row, index) in props.form.special_skills" :key="`skill-${index}`" class="mb-2 flex gap-2">
                    <Input :model-value="row" @update:model-value="(val) => updateArrayAt('special_skills', index, val)" />
                    <Button type="button" variant="destructive" size="sm" @click="emit('remove-special-skill', index)">Remove</Button>
                </div>
            </div>

            <div class="mb-5">
                <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Recognitions</h3><Button type="button" variant="outline" @click="emit('add-recognition')">Add</Button></div>
                <div v-for="(row, index) in props.form.recognitions" :key="`recognition-${index}`" class="mb-2 flex gap-2">
                    <Input :model-value="row" @update:model-value="(val) => updateArrayAt('recognitions', index, val)" />
                    <Button type="button" variant="destructive" size="sm" @click="emit('remove-recognition', index)">Remove</Button>
                </div>
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between"><h3 class="font-semibold">Memberships</h3><Button type="button" variant="outline" @click="emit('add-membership')">Add</Button></div>
                <div v-for="(row, index) in props.form.memberships" :key="`membership-${index}`" class="mb-2 flex gap-2">
                    <Input :model-value="row" @update:model-value="(val) => updateArrayAt('memberships', index, val)" />
                    <Button type="button" variant="destructive" size="sm" @click="emit('remove-membership', index)">Remove</Button>
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">Yes/No Questions</h2>
            <div v-for="question in yesNoQuestions" :key="question.key" class="mb-4 rounded-md border border-border bg-muted/30 p-4">
                <Label class="mb-2 block">{{ question.text }}</Label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2"><input type="radio" :name="question.key" value="Yes" :checked="props.form[question.key] === 'Yes'" @change="() => updateField(question.key, 'Yes')" /> Yes</label>
                    <label class="flex items-center gap-2"><input type="radio" :name="question.key" value="No" :checked="props.form[question.key] === 'No'" @change="() => updateField(question.key, 'No')" /> No</label>
                </div>
                <InputError :message="form.errors[question.key]" />
                <div v-if="form[question.key] === 'Yes'" class="mt-3">
                    <Label>Details</Label>
                    <Textarea :model-value="detailDraft[question.details]" @update:model-value="(val) => { detailDraft[question.details] = val }" rows="2" />
                </div>
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">References</h2>
            <div v-for="(row, index) in form.references" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div><Label>Name</Label><Input v-model="row.name" /></div>
                <div><Label>Address</Label><Input v-model="row.address" /></div>
                <div><Label>Tel No</Label><Input v-model="row.tel_no" /></div>
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">Government ID</h2>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                <div><Label>Government-issued ID</Label><Input v-model="form.govt_id_type" /></div>
                <div><Label>ID No</Label><Input v-model="form.govt_id_no" /></div>
                <div><Label>Date of Issue</Label><Input v-model="form.id_issue_date" type="date" /></div>
                <div><Label>Place of Issue</Label><Input v-model="form.id_issue_place" /></div>
            </div>
        </section>

        <section>
            <h2 class="mb-4 text-lg font-semibold text-foreground">Signature</h2>
            <div class="max-w-sm">
                <Label>Date Signed</Label>
                <Input v-model="form.signature_date" type="date" />
            </div>
        </section>
    </div>
</template>
