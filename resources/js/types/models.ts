export interface CooperativeSummary {
    id: number;
    name: string;
}

export interface Member {
    id: number;
    coop_id: number;
    first_name: string;
    last_name: string;
    birth_date: string | null;
    gender: string | null;
    address: string | null;
    region: string | null;
    province: string | null;
    city_municipality: string | null;
    barangay: string | null;
    phone: string | null;
    email: string | null;
    date_joined: string | null;
    membership_type: string | null;
    membership_status: 'Active' | 'Suspended' | 'Resigned' | 'Deceased' | null;
    share_capital: string | null;
    educational_attainment: string | null;
    primary_livelihood: string | null;
    sector: string | null;
    full_name: string;
    age: number | null;
    active_officers_count?: number;
    user?: {
        id: number;
        email: string;
    } | null;
    roles?: Array<{
        id: number;
        name: string;
    }>;
    cooperative: CooperativeSummary;
}

export interface Officer {
    id: number;
    member_id: number;
    coop_id: number;
    position: string;
    committee: string | null;
    term_start: string | null;
    term_end: string | null;
    status: string;
    member: { id: number; full_name: string };
    cooperative: CooperativeSummary;
}

export interface CommitteeMember {
    id: number;
    member_id: number;
    coop_id: number;
    committee_name: string;
    role: string | null;
    date_assigned: string | null;
    date_removed: string | null;
    status: string;
    member: { id: number; full_name: string };
    cooperative: CooperativeSummary;
}
