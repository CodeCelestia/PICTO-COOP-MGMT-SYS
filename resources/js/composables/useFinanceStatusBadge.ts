export const getFinanceStatusBadgeClass = (status: string | null | undefined): string => {
    const normalizedStatus = String(status || '').trim().toLowerCase();

    const positiveStatuses = ['paid', 'active', 'approved', 'completed', 'released'];
    const warningStatuses = ['pending', 'in progress', 'under review', 'dormant'];
    const negativeStatuses = ['overdue', 'rejected', 'cancelled', 'defaulted'];
    const neutralStatuses = ['inactive', 'closed'];

    if (positiveStatuses.includes(normalizedStatus)) {
        return 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-200';
    }

    if (warningStatuses.includes(normalizedStatus)) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-200';
    }

    if (negativeStatuses.includes(normalizedStatus)) {
        return 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-200';
    }

    if (neutralStatuses.includes(normalizedStatus)) {
        return 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200';
    }

    return 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200';
};
