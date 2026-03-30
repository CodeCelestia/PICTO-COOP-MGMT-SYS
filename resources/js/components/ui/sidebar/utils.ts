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
export const SIDEBAR_WIDTH = "16rem"
export const SIDEBAR_WIDTH_LARGE = "19rem"
export const SIDEBAR_WIDTH_MOBILE = "18rem"
export const SIDEBAR_WIDTH_ICON = "3rem"
export const SIDEBAR_WIDTH_ICON_LARGE = "3.75rem"
export const SIDEBAR_KEYBOARD_SHORTCUT = "b"
export const SIDEBAR_LARGE_MODE_STORAGE_KEY = "sidebar_large_mode"

export const [useSidebar, provideSidebarContext] = createContext<SidebarContext>("Sidebar")
