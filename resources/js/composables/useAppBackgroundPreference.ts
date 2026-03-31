import { useMediaQuery, useStorage } from '@vueuse/core';
import { computed } from 'vue';
import { applyUiTestingTuningVars, UI_TESTING_TUNING } from '@/config/uiTestingTuning';

const appBackgroundPreference = useStorage('app_background_visible', true);
const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');

applyUiTestingTuningVars();

const isAppBackgroundVisible = computed(
    () =>
        UI_TESTING_TUNING.background.masterVisible &&
        appBackgroundPreference.value &&
        !prefersReducedMotion.value,
);

function toggleAppBackground() {
    if (prefersReducedMotion.value) {
        appBackgroundPreference.value = false;
        return;
    }

    appBackgroundPreference.value = !appBackgroundPreference.value;
}

export function useAppBackgroundPreference() {
    return {
        appBackgroundPreference,
        prefersReducedMotion,
        isAppBackgroundVisible,
        toggleAppBackground,
    };
}
