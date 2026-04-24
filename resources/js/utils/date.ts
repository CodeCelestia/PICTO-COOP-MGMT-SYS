export function parseDateLocal(dateString: string | null | undefined): Date | null {
    if (!dateString) return null;

    const datePart = String(dateString).substring(0, 10);
    const [year, month, day] = datePart.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day);
}

export function dateInputValue(dateString: string | null | undefined): string {
    if (!dateString) return '';

    return String(dateString).substring(0, 10);
}