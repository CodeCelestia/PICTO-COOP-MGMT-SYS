import { useMediaQuery, useStorage } from '@vueuse/core';
import { computed } from 'vue';
import { applyUiTestingTuningVars, UI_TESTING_TUNING } from '@/config/uiTestingTuning';
import { useAppearance } from '@/composables/useAppearance';

export type BackgroundBehaviorMode = 'auto' | 'on' | 'off';

const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');
const { resolvedAppearance } = useAppearance();
const backgroundMode = useStorage<BackgroundBehaviorMode>('app_background_mode', 'auto');
const appBackgroundPreference = useStorage('app_background_visible', true);

applyUiTestingTuningVars();

const autoBackgroundVisible = computed(() => resolvedAppearance.value === 'dark');
const isBackgroundAutoManaged = computed(() => backgroundMode.value === 'auto');
const isBackgroundButtonLocked = computed(() => backgroundMode.value !== 'on');

const isAppBackgroundVisible = computed(
    () =>
        UI_TESTING_TUNING.background.masterVisible &&
        (backgroundMode.value === 'off'
            ? false
            : backgroundMode.value === 'auto'
              ? autoBackgroundVisible.value
              : appBackgroundPreference.value) &&
        !prefersReducedMotion.value,
);

function setBackgroundMode(mode: BackgroundBehaviorMode) {
    backgroundMode.value = mode;

    if (mode === 'on') {
        // Seed manual mode from current auto state so transition is predictable.
        appBackgroundPreference.value = autoBackgroundVisible.value;
    }
}

function toggleAppBackground() {
    if (prefersReducedMotion.value || isBackgroundButtonLocked.value) {
        return;
    }

    appBackgroundPreference.value = !appBackgroundPreference.value;
}

export function useAppBackgroundPreference() {
    return {
        appBackgroundPreference,
        backgroundMode,
        setBackgroundMode,
        isBackgroundAutoManaged,
        isBackgroundButtonLocked,
        prefersReducedMotion,
        isAppBackgroundVisible,
        toggleAppBackground,
    };
}
