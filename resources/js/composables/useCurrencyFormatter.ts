const philippinePesoFormatter = new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export const formatPhilippinePeso = (value: string | number | null | undefined): string => {
    const normalizedValue = typeof value === 'string' ? value.replaceAll(',', '').trim() : value;
    const parsedValue = Number(normalizedValue ?? 0);
    const safeValue = Number.isFinite(parsedValue) ? parsedValue : 0;

    return `₱ ${philippinePesoFormatter.format(safeValue)}`;
};
