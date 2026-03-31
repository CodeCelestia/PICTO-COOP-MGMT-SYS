import { createContext } from "reka-ui"
import type { ComputedRef, Ref } from 'vue';

export type SidebarContext = {
    state: ComputedRef<'expanded' | 'collapsed'>;
    open: Ref<boolean>;
    setOpen: (value: boolean) => void;
    isMobile: Ref<boolean>;
    openMobile: Ref<boolean>;
    setOpenMobile: (value: boolean) => void;
    toggleSidebar: () => void;
    isLargeMode: Ref<boolean>;
    setLargeMode: (value: boolean) => void;
    toggleLargeMode: () => void;
};

export const SIDEBAR_COOKIE_NAME = "sidebar_state"
export const SIDEBAR_COOKIE_MAX_AGE = 60 * 60 * 24 * 7
export const SIDEBAR_WIDTH = "296px"
export const SIDEBAR_WIDTH_LARGE = "328px"
export const SIDEBAR_WIDTH_MOBILE = "288px"
export const SIDEBAR_WIDTH_ICON = "48px"
export const SIDEBAR_WIDTH_ICON_LARGE = "58px"
export const SIDEBAR_KEYBOARD_SHORTCUT = "b"
export const SIDEBAR_LARGE_MODE_STORAGE_KEY = "sidebar_large_mode"

export const [useSidebar, provideSidebarContext] = createContext<SidebarContext>("Sidebar")
