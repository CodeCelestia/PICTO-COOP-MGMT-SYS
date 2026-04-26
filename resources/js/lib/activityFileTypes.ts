import {
    File,
    FileSpreadsheet,
    FileText,
    FileType,
    Film,
    ImageIcon,
    Layout,
    Monitor,
    type LucideIcon,
} from 'lucide-vue-next';

export type FileTypeGroup = 'Documents' | 'Spreadsheets' | 'Presentations' | 'Images' | 'Other';

export interface FileTypeConfig {
    extension: string;
    icon: LucideIcon;
    iconColorClass: string;
    badgeClass: string;
    group: FileTypeGroup;
    previewable: boolean;
}

export interface FileTypeLegendGroup {
    key: string;
    label: FileTypeGroup;
    icon: LucideIcon;
    iconColorClass: string;
    badgeClass: string;
    extensions: string[];
    badgeText: string;
}

const GROUP_CONFIGS = {
    pdf: {
        label: 'Documents' as const,
        icon: FileText,
        iconColorClass: 'text-red-500',
        badgeClass: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800',
        extensions: ['PDF'],
        previewable: true,
    },
    docs: {
        label: 'Documents' as const,
        icon: FileType,
        iconColorClass: 'text-blue-500',
        badgeClass: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400 dark:border-blue-800',
        extensions: ['DOC', 'DOCX'],
        previewable: false,
    },
    sheets: {
        label: 'Spreadsheets' as const,
        icon: FileSpreadsheet,
        iconColorClass: 'text-green-500',
        badgeClass: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800',
        extensions: ['XLS', 'XLSX'],
        previewable: false,
    },
    slides: {
        label: 'Presentations' as const,
        icon: Layout,
        iconColorClass: 'text-orange-500',
        badgeClass: 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400 dark:border-orange-800',
        extensions: ['PPT', 'PPTX'],
        previewable: false,
    },
    imagePrimary: {
        label: 'Images' as const,
        icon: ImageIcon,
        iconColorClass: 'text-purple-500',
        badgeClass: 'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400 dark:border-purple-800',
        extensions: ['JPG', 'JPEG', 'PNG'],
        previewable: true,
    },
    imageSecondary: {
        label: 'Images' as const,
        icon: Film,
        iconColorClass: 'text-pink-500',
        badgeClass: 'bg-pink-100 text-pink-700 border-pink-200 dark:bg-pink-900/20 dark:text-pink-400 dark:border-pink-800',
        extensions: ['GIF', 'WEBP'],
        previewable: true,
    },
    other: {
        label: 'Other' as const,
        icon: File,
        iconColorClass: 'text-gray-500',
        badgeClass: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-900/20 dark:text-gray-400 dark:border-gray-700',
        extensions: ['Other'],
        previewable: false,
    },
};

const EXTENSION_TO_GROUP: Record<string, keyof typeof GROUP_CONFIGS> = {
    PDF: 'pdf',
    DOC: 'docs',
    DOCX: 'docs',
    XLS: 'sheets',
    XLSX: 'sheets',
    PPT: 'slides',
    PPTX: 'slides',
    JPG: 'imagePrimary',
    JPEG: 'imagePrimary',
    PNG: 'imagePrimary',
    GIF: 'imageSecondary',
    WEBP: 'imageSecondary',
};

export const getFileExtension = (filename: string) => filename.split('.').pop()?.toUpperCase() || 'OTHER';

export const getFileTypeConfig = (filename: string): FileTypeConfig => {
    const extension = getFileExtension(filename);
    const groupKey = EXTENSION_TO_GROUP[extension] || 'other';
    const group = GROUP_CONFIGS[groupKey];

    return {
        extension,
        icon: group.icon,
        iconColorClass: group.iconColorClass,
        badgeClass: group.badgeClass,
        group: group.label,
        previewable: group.previewable,
    };
};

export const getLegendFileTypeGroups = (): FileTypeLegendGroup[] => {
    return [
        {
            key: 'pdf',
            label: GROUP_CONFIGS.pdf.label,
            icon: GROUP_CONFIGS.pdf.icon,
            iconColorClass: GROUP_CONFIGS.pdf.iconColorClass,
            badgeClass: GROUP_CONFIGS.pdf.badgeClass,
            extensions: GROUP_CONFIGS.pdf.extensions,
            badgeText: GROUP_CONFIGS.pdf.extensions.join(' / '),
        },
        {
            key: 'docs',
            label: GROUP_CONFIGS.docs.label,
            icon: GROUP_CONFIGS.docs.icon,
            iconColorClass: GROUP_CONFIGS.docs.iconColorClass,
            badgeClass: GROUP_CONFIGS.docs.badgeClass,
            extensions: GROUP_CONFIGS.docs.extensions,
            badgeText: GROUP_CONFIGS.docs.extensions.join(' / '),
        },
        {
            key: 'sheets',
            label: GROUP_CONFIGS.sheets.label,
            icon: GROUP_CONFIGS.sheets.icon,
            iconColorClass: GROUP_CONFIGS.sheets.iconColorClass,
            badgeClass: GROUP_CONFIGS.sheets.badgeClass,
            extensions: GROUP_CONFIGS.sheets.extensions,
            badgeText: GROUP_CONFIGS.sheets.extensions.join(' / '),
        },
        {
            key: 'slides',
            label: GROUP_CONFIGS.slides.label,
            icon: GROUP_CONFIGS.slides.icon,
            iconColorClass: GROUP_CONFIGS.slides.iconColorClass,
            badgeClass: GROUP_CONFIGS.slides.badgeClass,
            extensions: GROUP_CONFIGS.slides.extensions,
            badgeText: GROUP_CONFIGS.slides.extensions.join(' / '),
        },
        {
            key: 'imagePrimary',
            label: GROUP_CONFIGS.imagePrimary.label,
            icon: GROUP_CONFIGS.imagePrimary.icon,
            iconColorClass: GROUP_CONFIGS.imagePrimary.iconColorClass,
            badgeClass: GROUP_CONFIGS.imagePrimary.badgeClass,
            extensions: GROUP_CONFIGS.imagePrimary.extensions,
            badgeText: GROUP_CONFIGS.imagePrimary.extensions.join(' / '),
        },
        {
            key: 'imageSecondary',
            label: GROUP_CONFIGS.imageSecondary.label,
            icon: GROUP_CONFIGS.imageSecondary.icon,
            iconColorClass: GROUP_CONFIGS.imageSecondary.iconColorClass,
            badgeClass: GROUP_CONFIGS.imageSecondary.badgeClass,
            extensions: GROUP_CONFIGS.imageSecondary.extensions,
            badgeText: GROUP_CONFIGS.imageSecondary.extensions.join(' / '),
        },
        {
            key: 'other',
            label: GROUP_CONFIGS.other.label,
            icon: GROUP_CONFIGS.other.icon,
            iconColorClass: GROUP_CONFIGS.other.iconColorClass,
            badgeClass: GROUP_CONFIGS.other.badgeClass,
            extensions: GROUP_CONFIGS.other.extensions,
            badgeText: GROUP_CONFIGS.other.extensions.join(' / '),
        },
    ];
};

export const getPreviewSuggestion = (filename: string) => {
    const extension = getFileExtension(filename);

    if (extension === 'PDF') {
        return 'Adobe Acrobat or any modern browser';
    }

    if (extension === 'DOC' || extension === 'DOCX') {
        return 'Microsoft Word or Google Docs';
    }

    if (extension === 'XLS' || extension === 'XLSX') {
        return 'Microsoft Excel or Google Sheets';
    }

    if (extension === 'PPT' || extension === 'PPTX') {
        return 'Microsoft PowerPoint or Google Slides';
    }

    return 'An application that supports this file format';
};

export const getPreviewHintIcon = () => Monitor;
