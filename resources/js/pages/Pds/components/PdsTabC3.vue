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

const lndTypes = ['Managerial', 'Supervisory', 'Technical', 'Clerical', 'Others'];

defineProps<{
    form: any;
}>();
</script>

<template>
    <div class="space-y-8 rounded-xl border border-border bg-card p-6 shadow-sm">
        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">Voluntary Work</h2>
                <Button type="button" variant="outline" @click="form.voluntary_work.push({ organization: '', date_from: '', date_to: '', hours: '', position: '' })">Add Row</Button>
            </div>
            <div v-for="(row, index) in form.voluntary_work" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-2"><Label>Organization</Label><Input v-model="row.organization" /></div>
                <div><Label>Hours</Label><Input v-model="row.hours" /></div>
                <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                <div><Label>Position/Nature</Label><Input v-model="row.position" /></div>
                <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="form.voluntary_work.splice(index, 1)">Remove</Button></div>
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-foreground">Learning & Development</h2>
                <Button type="button" variant="outline" @click="form.learning_development.push({ title: '', date_from: '', date_to: '', hours: '', type: '', conducted_by: '' })">Add Row</Button>
            </div>
            <div v-for="(row, index) in form.learning_development" :key="index" class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="md:col-span-2"><Label>Title</Label><Input v-model="row.title" /></div>
                <div><Label>Hours</Label><Input v-model="row.hours" /></div>
                <div><Label>Date From</Label><Input v-model="row.date_from" type="date" /></div>
                <div><Label>Date To</Label><Input v-model="row.date_to" type="date" /></div>
                <div>
                    <Label>Type</Label>
                    <Select v-model="row.type">
                        <SelectTrigger><SelectValue placeholder="Select" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="type in lndTypes" :key="type" :value="type">{{ type }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div><Label>Conducted By</Label><Input v-model="row.conducted_by" /></div>
                <div class="md:col-span-3"><Button type="button" variant="destructive" size="sm" @click="form.learning_development.splice(index, 1)">Remove</Button></div>
            </div>
        </section>
    </div>
</template>
