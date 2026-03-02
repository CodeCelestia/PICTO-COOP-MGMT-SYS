<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { MapPin, Pencil, FileText } from "lucide-vue-next";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import AppLayout from "@/layouts/AppLayout.vue";
import type { BreadcrumbItem } from "@/types";
import { edit as pdsEdit } from "@/routes/super-admin/pds";

const props = defineProps<{ pds: Record<string, any>; canCreateUser?: boolean }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: "PDS Management", href: "/super-admin/pds" },
    { title: `${props.pds.last_name}, ${props.pds.first_name}`, href: "#" },
];

const fullName = `${props.pds.first_name ?? ""} ${props.pds.middle_name ? props.pds.middle_name + " " : ""}${props.pds.last_name ?? ""}`.trim();
const address = [props.pds.barangay_name, props.pds.city_municipality_name, props.pds.province_name, props.pds.region_name].filter(Boolean).join(", ");

const val = (v: any, fallback = "—") => (v !== null && v !== undefined && v !== "") ? v : fallback;

const QUESTIONS = [
    { key: "q1",  label: "Related by consanguinity or affinity to appointing/recommending authority?" },
    { key: "q2",  label: "Found guilty of any administrative offense?" },
    { key: "q3",  label: "Criminally charged before any court?" },
    { key: "q4",  label: "Convicted of any crime or violation of any law?" },
    { key: "q5",  label: "Separated from the service?" },
    { key: "q6",  label: "Candidate in a national or local election (last year)?" },
    { key: "q7",  label: "Resigned from government service 3 months before last election to campaign?" },
    { key: "q8",  label: "Immigrant or permanent resident of another country?" },
    { key: "q9",  label: "Person with disability?" },
    { key: "q10", label: "Member of organization with extremist ideology?" },
    { key: "q11", label: "Issued a clearance/certificate of no pending cases from last employer?" },
];
</script>

<template>
    <Head :title="fullName" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 max-w-5xl mx-auto w-full">
            <!-- Gradient header -->
            <div class="rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5 text-white shadow-lg">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/20 text-xl font-black uppercase">
                            {{ pds.first_name?.[0] ?? '' }}{{ pds.last_name?.[0] ?? '' }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold">{{ fullName }}</h1>
                            <p class="mt-0.5 text-sm text-blue-200">{{ pds.email }}</p>
                            <p v-if="address" class="mt-1 flex items-center gap-1 text-sm text-blue-200">
                                <MapPin class="h-3.5 w-3.5" /> {{ address }}
                            </p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span v-if="pds.user" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-emerald-500/30 text-emerald-100 ring-1 ring-inset ring-emerald-400/30">✓ Linked to account</span>
                                <span v-else class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-amber-500/30 text-amber-100 ring-1 ring-inset ring-amber-400/30">No Account</span>
                            </div>
                        </div>
                    </div>
                    <Link :href="pdsEdit(pds.id).url">
                        <Button class="bg-white/20 hover:bg-white/30 text-white border border-white/30 gap-2"><Pencil class="h-4 w-4" /> Edit</Button>
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600">I. Personal Information</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-3 text-sm">
                    <div><p class="text-xs text-muted-foreground font-medium">Surname</p><p>{{ val(pds.last_name) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">First Name</p><p>{{ val(pds.first_name) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Middle Name</p><p>{{ val(pds.middle_name) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Extension</p><p>{{ val(pds.name_extension) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Date of Birth</p><p>{{ val(pds.date_of_birth) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Place of Birth</p><p>{{ val(pds.place_of_birth) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Sex</p><p>{{ val(pds.gender) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Civil Status</p><p>{{ val(pds.civil_status) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Height</p><p>{{ pds.height ? pds.height + ' m' : '—' }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Weight</p><p>{{ pds.weight ? pds.weight + ' kg' : '—' }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Blood Type</p><p>{{ val(pds.blood_type) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Citizenship</p><p>{{ val(pds.citizenship) }}{{ pds.dual_country ? ' (' + pds.dual_country + ')' : '' }}</p></div>
                </div>
                <div class="pt-2 border-t grid grid-cols-2 md:grid-cols-5 gap-x-6 gap-y-3 text-sm">
                    <div><p class="text-xs text-muted-foreground font-medium">GSIS ID</p><p>{{ val(pds.gsis_id) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">SSS No.</p><p>{{ val(pds.sss_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">PhilHealth</p><p>{{ val(pds.philhealth_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Pag-IBIG</p><p>{{ val(pds.pagibig_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">TIN</p><p>{{ val(pds.tin_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Telephone</p><p>{{ val(pds.telephone_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Mobile</p><p>{{ val(pds.phone_number) }}</p></div>
                    <div class="md:col-span-3"><p class="text-xs text-muted-foreground font-medium">Email</p><p>{{ val(pds.email) }}</p></div>
                </div>
                <div class="pt-2 border-t space-y-2 text-sm">
                    <div><p class="text-xs text-muted-foreground font-medium">Residential Address</p>
                        <p>{{ [pds.res_house,pds.street_address,pds.res_subdivision,pds.barangay_name,pds.city_municipality_name,pds.province_name,pds.region_name].filter(Boolean).join(', ')||'—' }} {{ pds.res_zip ? '('+pds.res_zip+')' : '' }}</p>
                    </div>
                    <div><p class="text-xs text-muted-foreground font-medium">Permanent Address</p>
                        <p v-if="pds.perm_same_as_res" class="text-muted-foreground italic">Same as residential</p>
                        <p v-else>{{ [pds.perm_house,pds.perm_street,pds.perm_subdivision,pds.perm_barangay_name,pds.perm_city_municipality_name,pds.perm_province_name,pds.perm_region_name].filter(Boolean).join(', ')||'—' }} {{ pds.perm_zip ? '('+pds.perm_zip+')' : '' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600">II. Family Background</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="rounded-lg border bg-muted/20 p-3">
                        <p class="text-xs text-muted-foreground font-medium mb-1">Spouse</p>
                        <p>{{ [pds.spouse_surname,pds.spouse_first_name,pds.spouse_middle_name,pds.spouse_name_extension].filter(Boolean).join(' ')||'—' }}</p>
                        <p v-if="pds.spouse_occupation" class="text-muted-foreground mt-1 text-xs">{{ pds.spouse_occupation }} · {{ pds.spouse_employer }}</p>
                    </div>
                    <div class="rounded-lg border bg-muted/20 p-3">
                        <p class="text-xs text-muted-foreground font-medium mb-1">Father</p>
                        <p>{{ [pds.father_surname,pds.father_first_name,pds.father_middle_name,pds.father_name_extension].filter(Boolean).join(' ')||'—' }}</p>
                    </div>
                    <div class="rounded-lg border bg-muted/20 p-3">
                        <p class="text-xs text-muted-foreground font-medium mb-1">Mother (Maiden Name)</p>
                        <p>{{ [pds.mother_surname,pds.mother_first_name,pds.mother_middle_name].filter(Boolean).join(' ')||'—' }}</p>
                    </div>
                </div>
                <div v-if="pds.children && pds.children.length">
                    <p class="text-xs text-muted-foreground font-medium mb-2">Children</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div v-for="(c,i) in pds.children" :key="i" class="rounded-md border bg-muted/10 px-3 py-2 text-sm">
                            <p class="font-medium">{{ c.name }}</p><p class="text-xs text-muted-foreground">{{ c.date_of_birth }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="pds.education && pds.education.some((e) => e.school_name)" class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-4">III. Educational Background</h2>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-xs">
                        <thead class="bg-muted/40 border-b"><tr>
                            <th class="px-3 py-2 text-left">Level</th><th class="px-3 py-2 text-left">School</th>
                            <th class="px-3 py-2 text-left">Degree/Course</th><th class="px-3 py-2 text-left">Attendance</th>
                            <th class="px-3 py-2 text-left">Yr. Grad</th><th class="px-3 py-2 text-left">Honors</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="row in pds.education.filter((e) => e.school_name)" :key="row.level" class="hover:bg-muted/10">
                                <td class="px-3 py-2 font-medium">{{ row.level }}</td>
                                <td class="px-3 py-2">{{ row.school_name }}</td>
                                <td class="px-3 py-2">{{ row.degree_course||'—' }}</td>
                                <td class="px-3 py-2">{{ row.attendance_from&&row.attendance_to ? row.attendance_from+' – '+row.attendance_to : row.attendance_from||'—' }}</td>
                                <td class="px-3 py-2">{{ row.year_graduated||'—' }}</td>
                                <td class="px-3 py-2">{{ row.awards_honors||'—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="pds.eligibilities && pds.eligibilities.length" class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-4">IV. Civil Service Eligibility</h2>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-xs">
                        <thead class="bg-muted/40 border-b"><tr>
                            <th class="px-3 py-2 text-left">Examination</th><th class="px-3 py-2 text-left">Rating</th>
                            <th class="px-3 py-2 text-left">Date</th><th class="px-3 py-2 text-left">Place</th>
                            <th class="px-3 py-2 text-left">License No.</th><th class="px-3 py-2 text-left">Validity</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="(e,i) in pds.eligibilities" :key="i" class="hover:bg-muted/10">
                                <td class="px-3 py-2">{{ e.exam_name }}</td><td class="px-3 py-2">{{ e.rating||'—' }}</td>
                                <td class="px-3 py-2">{{ e.exam_date||'—' }}</td><td class="px-3 py-2">{{ e.exam_place||'—' }}</td>
                                <td class="px-3 py-2">{{ e.license_no||'—' }}</td><td class="px-3 py-2">{{ e.validity_date||'—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="pds.work_experiences && pds.work_experiences.length" class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-4">V. Work Experience</h2>
                <div class="overflow-x-auto rounded-lg border">
                    <table class="min-w-full text-xs">
                        <thead class="bg-muted/40 border-b"><tr>
                            <th class="px-3 py-2 text-left">Period</th><th class="px-3 py-2 text-left">Position Title</th>
                            <th class="px-3 py-2 text-left">Department/Agency</th><th class="px-3 py-2 text-left">Salary</th>
                            <th class="px-3 py-2 text-left">SG</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-center">Gov</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="(w,i) in pds.work_experiences" :key="i" class="hover:bg-muted/10">
                                <td class="px-3 py-2 whitespace-nowrap">{{ w.date_from }} – {{ w.date_to||'present' }}</td>
                                <td class="px-3 py-2">{{ w.position_title }}</td><td class="px-3 py-2">{{ w.department }}</td>
                                <td class="px-3 py-2">{{ w.monthly_salary ? '₱'+Number(w.monthly_salary).toLocaleString() : '—' }}</td>
                                <td class="px-3 py-2">{{ w.salary_grade||'—' }}</td><td class="px-3 py-2">{{ w.appointment_status||'—' }}</td>
                                <td class="px-3 py-2 text-center">{{ w.is_government ? '✓' : '' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="(pds.voluntary_works&&pds.voluntary_works.length)||(pds.learning_developments&&pds.learning_developments.length)"
                class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600">VI. Voluntary Work &amp; L&amp;D</h2>
                <div v-if="pds.voluntary_works&&pds.voluntary_works.length">
                    <p class="text-xs text-muted-foreground font-medium mb-2">Voluntary Work</p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full text-xs">
                            <thead class="bg-muted/40 border-b"><tr>
                                <th class="px-3 py-2 text-left">Organization</th><th class="px-3 py-2 text-left">Period</th>
                                <th class="px-3 py-2 text-left">Hours</th><th class="px-3 py-2 text-left">Position</th>
                            </tr></thead>
                            <tbody class="divide-y">
                                <tr v-for="(v,i) in pds.voluntary_works" :key="i" class="hover:bg-muted/10">
                                    <td class="px-3 py-2">{{ v.organization }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ v.date_from }} – {{ v.date_to }}</td>
                                    <td class="px-3 py-2">{{ v.hours }}</td><td class="px-3 py-2">{{ v.position }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="pds.learning_developments&&pds.learning_developments.length">
                    <p class="text-xs text-muted-foreground font-medium mb-2">L&amp;D Interventions</p>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="min-w-full text-xs">
                            <thead class="bg-muted/40 border-b"><tr>
                                <th class="px-3 py-2 text-left">Title</th><th class="px-3 py-2 text-left">Period</th>
                                <th class="px-3 py-2 text-left">Hours</th><th class="px-3 py-2 text-left">Type</th>
                                <th class="px-3 py-2 text-left">Conducted By</th>
                            </tr></thead>
                            <tbody class="divide-y">
                                <tr v-for="(l,i) in pds.learning_developments" :key="i" class="hover:bg-muted/10">
                                    <td class="px-3 py-2">{{ l.title }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ l.date_from }} – {{ l.date_to }}</td>
                                    <td class="px-3 py-2">{{ l.hours }}</td><td class="px-3 py-2">{{ l.type }}</td>
                                    <td class="px-3 py-2">{{ l.conducted_by }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600">VII. Other Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="text-xs text-muted-foreground font-medium">Special Skills</p><p class="whitespace-pre-line">{{ val(pds.special_skills) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Non-Academic Distinctions</p><p class="whitespace-pre-line">{{ val(pds.non_academic_distinctions) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Memberships</p><p class="whitespace-pre-line">{{ val(pds.memberships) }}</p></div>
                </div>
                <div v-if="pds.questions" class="pt-3 border-t space-y-2">
                    <p class="text-xs text-muted-foreground font-medium mb-2">Questions</p>
                    <div v-for="q in QUESTIONS" :key="q.key" class="flex flex-wrap gap-3 items-start text-sm py-1 border-b last:border-0">
                        <span class="text-muted-foreground flex-1 min-w-48">{{ q.label }}</span>
                        <Badge :class="pds.questions[q.key]?.answer==='Yes' ? 'bg-red-100 text-red-800 border-red-200' : 'bg-green-100 text-green-800 border-green-200'">
                            {{ pds.questions[q.key]?.answer??'No' }}
                        </Badge>
                        <span v-if="pds.questions[q.key]?.details" class="text-muted-foreground italic text-xs">{{ pds.questions[q.key].details }}</span>
                    </div>
                </div>
            </div>

            <div v-if="pds.references_list&&pds.references_list.length" class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600 mb-4">VIII. Character References</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div v-for="(r,i) in pds.references_list" :key="i" class="rounded-lg border bg-muted/20 p-3 text-sm">
                        <p class="font-medium">{{ r.name||'—' }}</p>
                        <p class="text-muted-foreground text-xs mt-0.5">{{ r.address }}</p>
                        <p class="text-xs">{{ r.telephone }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5 space-y-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-blue-600">Declaration</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="md:col-span-2"><p class="text-xs text-muted-foreground font-medium">Government-Issued ID</p><p>{{ val(pds.government_issued_id) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">ID No.</p><p>{{ val(pds.id_no) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Date/Place of Issuance</p><p>{{ val(pds.id_issue_place) }}</p></div>
                    <div><p class="text-xs text-muted-foreground font-medium">Date Accomplished</p><p>{{ val(pds.date_accomplished) }}</p></div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>

