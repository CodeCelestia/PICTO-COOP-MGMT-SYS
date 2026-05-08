import Swal from 'sweetalert2';

type ConfirmOptions = {
    title: string;
    text: string;
    confirmButtonText?: string;
    cancelButtonText?: string;
};

type NotifyErrorOptions = {
    title?: string;
    icon?: 'error' | 'warning' | 'info' | 'success' | 'question';
    timer?: number;
};

export const confirmAction = async ({
    title,
    text,
    confirmButtonText = 'Yes, continue',
    cancelButtonText = 'Cancel',
}: ConfirmOptions): Promise<boolean> => {
    const result = await Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText,
        cancelButtonText,
        confirmButtonColor: '#0f172a',
        reverseButtons: true,
    });

    return result.isConfirmed;
};

export const notifySuccess = (message: string) => {
    void Swal.fire({
        icon: 'success',
        title: 'Success',
        text: message,
        timer: 2500,
        timerProgressBar: true,
        showConfirmButton: false,
    });
};

export const notifyError = (message: string, options: NotifyErrorOptions = {}) => {
    void Swal.fire({
        icon: options.icon || 'error',
        title: options.title || 'Error',
        text: message,
        timer: options.timer ?? 3000,
        timerProgressBar: true,
        showConfirmButton: false,
    });
};
