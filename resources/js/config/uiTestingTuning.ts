type Opacity = number;

const clampOpacity = (value: Opacity): string => {
    if (Number.isNaN(value)) {
        return '0';
    }

    return Math.min(1, Math.max(0, value)).toFixed(2);
};

export const UI_TESTING_TUNING = {
    background: {
        // Master kill switch for app Three.js background (independent from header toggle)
        masterVisible: true,
        // Overall Three.js background strength
        strength: 0.88,
        // Grid overlay strength inside the Three.js layer
        gridStrength: 0.28,
        // Content readability veil over the animated background
        contentOverlayLight: 0.64,
        contentOverlayDark: 0.46,
    },
    header: {
        light: 0.58,
        dark: 0.25,
    },
    sidebar: {
        staticLight: 0.78,
        staticDark: 0.64,
        mobileLight: 0.84,
        mobileDark: 0.72,
        panelLight: 0.74,
        panelDark: 0.62,
    },
    card: {
        light: 0.94,
        dark: 0.82,
        popoverLight: 0.78,
        popoverDark: 0.68,
        mutedLight: 0.72,
        mutedDark: 0.62,
    },
} as const;

let hasAppliedUiTestingVars = false;

export const applyUiTestingTuningVars = () => {
    if (hasAppliedUiTestingVars || typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;

    root.style.setProperty('--app-bg-opacity', clampOpacity(UI_TESTING_TUNING.background.strength));
    root.style.setProperty('--app-bg-grid-opacity', clampOpacity(UI_TESTING_TUNING.background.gridStrength));
    root.style.setProperty('--app-bg-overlay-light', clampOpacity(UI_TESTING_TUNING.background.contentOverlayLight));
    root.style.setProperty('--app-bg-overlay-dark', clampOpacity(UI_TESTING_TUNING.background.contentOverlayDark));

    root.style.setProperty('--app-header-opacity-light', clampOpacity(UI_TESTING_TUNING.header.light));
    root.style.setProperty('--app-header-opacity-dark', clampOpacity(UI_TESTING_TUNING.header.dark));

    root.style.setProperty('--app-sidebar-static-light', clampOpacity(UI_TESTING_TUNING.sidebar.staticLight));
    root.style.setProperty('--app-sidebar-static-dark', clampOpacity(UI_TESTING_TUNING.sidebar.staticDark));
    root.style.setProperty('--app-sidebar-mobile-light', clampOpacity(UI_TESTING_TUNING.sidebar.mobileLight));
    root.style.setProperty('--app-sidebar-mobile-dark', clampOpacity(UI_TESTING_TUNING.sidebar.mobileDark));
    root.style.setProperty('--app-sidebar-panel-light', clampOpacity(UI_TESTING_TUNING.sidebar.panelLight));
    root.style.setProperty('--app-sidebar-panel-dark', clampOpacity(UI_TESTING_TUNING.sidebar.panelDark));

    root.style.setProperty('--app-card-opacity-light', clampOpacity(UI_TESTING_TUNING.card.light));
    root.style.setProperty('--app-card-opacity-dark', clampOpacity(UI_TESTING_TUNING.card.dark));
    root.style.setProperty('--app-popover-opacity-light', clampOpacity(UI_TESTING_TUNING.card.popoverLight));
    root.style.setProperty('--app-popover-opacity-dark', clampOpacity(UI_TESTING_TUNING.card.popoverDark));
    root.style.setProperty('--app-muted-opacity-light', clampOpacity(UI_TESTING_TUNING.card.mutedLight));
    root.style.setProperty('--app-muted-opacity-dark', clampOpacity(UI_TESTING_TUNING.card.mutedDark));

    hasAppliedUiTestingVars = true;
};
