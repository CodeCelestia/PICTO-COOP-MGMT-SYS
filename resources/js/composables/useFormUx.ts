import { ref, watch, onMounted } from 'vue';
import Swal from 'sweetalert2';

export function useFormUx(form: any) {
    const isPreFilling = ref(true);
    const isDirty = ref(false);
    const showErrorShake = ref(false);

    const inputErrorClass = (field: string) => {
        if (form?.errors && form.errors[field]) {
            return 'border-red-500 ring-1 ring-red-300 dark:border-red-700 dark:ring-red-900/50';
        }
        return '';
    };

    const clearError = (field: string) => {
        if (form?.errors && form.errors[field]) {
            delete form.errors[field];
        }
    };

    const scrollToFirstError = () => {
        requestAnimationFrame(() => {
            const el = document.querySelector('.border-red-500');
            if (el && typeof (el as HTMLElement).scrollIntoView === 'function') {
                (el as HTMLElement).scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    };

    const triggerErrorShake = () => {
        showErrorShake.value = false;
        requestAnimationFrame(() => {
            showErrorShake.value = true;
            setTimeout(() => (showErrorShake.value = false), 600);
        });
    };

    watch(form, () => {
        if (isPreFilling.value) return;
        isDirty.value = true;
    }, { deep: true });

    onMounted(() => {
        // mark prefill done after mount
        requestAnimationFrame(() => {
            isPreFilling.value = false;
        });
    });

    const markClean = () => { isDirty.value = false; };

    const handleCancel = async (options?: { confirmTitle?: string; confirmText?: string; confirmButtonText?: string; cancelButtonText?: string; fallbackBack?: boolean; confirmAlways?: boolean }) => {
        const fallbackBack = options?.fallbackBack ?? true;
        if (isDirty.value || options?.confirmAlways) {
            const result = await Swal.fire({
                title: options?.confirmTitle ?? 'Discard changes?',
                text: options?.confirmText ?? 'You have unsaved changes. Are you sure you want to discard them?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: options?.confirmButtonText ?? 'Discard',
                cancelButtonText: options?.cancelButtonText ?? 'Keep editing',
                confirmButtonColor: '#0f172a',
                reverseButtons: true,
            });

            if (result.isConfirmed) {
                if (fallbackBack) window.history.back();
            }
            return result;
        }

        if (fallbackBack) window.history.back();
        // Return an object compatible with Swal result so callers can check .isConfirmed
        return { isConfirmed: true } as any;
    };

    return {
        isPreFilling,
        isDirty,
        showErrorShake,
        inputErrorClass,
        clearError,
        scrollToFirstError,
        triggerErrorShake,
        handleCancel,
        markClean,
    };
}
