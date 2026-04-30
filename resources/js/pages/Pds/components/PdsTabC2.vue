<script setup lang="ts">
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

const emit = defineEmits<{
    (e: 'add-eligibility'): void
    (e: 'remove-eligibility', index: number): void
    (e: 'add-work-experience'): void
    (e: 'remove-work-experience', index: number): void
}>()

defineProps<{
    form: any;
}>();
</script>

<template>
    <div class="space-y-8 rounded-xl border border-border bg-card p-6 shadow-sm">
        <section>
                <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">Civil Service Eligibility</h2>
                <Button type="button" variant="outline" @click="emit('add-eligibility')">Add Row</Button>
            </div>
            <div v-for="(row, index) in form.eligibility" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div><Label>Name</Label><Input v-model="row.name" /></div>
                <div><Label>Rating</Label><Input v-model="row.rating" /></div>
                <div><Label>Exam Date</Label><Input v-model="row.exam_date" type="date" /></div>
                <div><Label>Exam Place</Label><Input v-model="row.exam_place" /></div>
                <div><Label>License Number</Label><Input v-model="row.license_number" /></div>
                <div><Label>Validity</Label><Input v-model="row.license_validity" type="date" /></div>
                <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="emit('remove-eligibility', index)">Remove</Button></div>
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">Work Experience</h2>
                <Button type="button" variant="outline" @click="emit('add-work-experience')">Add Row</Button>
            </div>
            <div v-for="(row, index) in form.work_experience" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                <div><Label>Position</Label><Input v-model="row.position_title" /></div>
                <div><Label>Dept/Agency</Label><Input v-model="row.dept_agency" /></div>
                <div><Label>Monthly Salary</Label><Input v-model="row.monthly_salary" /></div>
                <div><Label>Salary Grade</Label><Input v-model="row.salary_grade" /></div>
                <div><Label>Status</Label><Input v-model="row.status_appointment" /></div>
                <div>
                    <Label>Govt Service</Label>
                    <Select v-model="row.govt_service">
                        <SelectTrigger><SelectValue placeholder="Y/N" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="Y">Y</SelectItem>
                            <SelectItem value="N">N</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="md:col-span-4"><Button type="button" variant="destructive" size="sm" @click="emit('remove-work-experience', index)">Remove</Button></div>
            </div>
        </section>
    </div>
</template>
