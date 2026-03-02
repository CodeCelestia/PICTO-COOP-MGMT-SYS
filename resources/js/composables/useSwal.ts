import Swal from 'sweetalert2';

export const swalSuccess = (title: string, text?: string) =>
    Swal.fire({
        icon: 'success',
        title,
        text,
        timer: 2200,
        timerProgressBar: true,
        showConfirmButton: false,
        toast: false,
        customClass: { popup: 'rounded-2xl' },
    });

export const swalError = (title: string, text?: string) =>
    Swal.fire({
        icon: 'error',
        title,
        text,
        customClass: { popup: 'rounded-2xl' },
    });

export const swalConfirmDelete = (itemName = 'this record') =>
    Swal.fire({
        title: 'Delete ' + itemName + '?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'rounded-2xl' },
    });

export const swalConfirm = (title: string, text?: string) =>
    Swal.fire({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'rounded-2xl' },
    });
