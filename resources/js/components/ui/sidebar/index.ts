import type { VariantProps } from "class-variance-authority"
import type { HTMLAttributes } from "vue"
import { cva } from "class-variance-authority"

export interface SidebarProps {
  side?: "left" | "right"
  variant?: "sidebar" | "floating" | "inset"
  collapsible?: "offcanvas" | "icon" | "none"
  class?: HTMLAttributes["class"]
}

export { default as Sidebar } from "./Sidebar.vue"
export { default as SidebarContent } from "./SidebarContent.vue"
export { default as SidebarFooter } from "./SidebarFooter.vue"
export { default as SidebarGroup } from "./SidebarGroup.vue"
export { default as SidebarGroupAction } from "./SidebarGroupAction.vue"
export { default as SidebarGroupContent } from "./SidebarGroupContent.vue"
export { default as SidebarGroupLabel } from "./SidebarGroupLabel.vue"
export { default as SidebarHeader } from "./SidebarHeader.vue"
export { default as SidebarInput } from "./SidebarInput.vue"
export { default as SidebarInset } from "./SidebarInset.vue"
export { default as SidebarMenu } from "./SidebarMenu.vue"
export { default as SidebarMenuAction } from "./SidebarMenuAction.vue"
export { default as SidebarMenuBadge } from "./SidebarMenuBadge.vue"
export { default as SidebarMenuButton } from "./SidebarMenuButton.vue"
export { default as SidebarMenuItem } from "./SidebarMenuItem.vue"
export { default as SidebarMenuSkeleton } from "./SidebarMenuSkeleton.vue"
export { default as SidebarMenuSub } from "./SidebarMenuSub.vue"
export { default as SidebarMenuSubButton } from "./SidebarMenuSubButton.vue"
export { default as SidebarMenuSubItem } from "./SidebarMenuSubItem.vue"
export { default as SidebarProvider } from "./SidebarProvider.vue"
export { default as SidebarRail } from "./SidebarRail.vue"
export { default as SidebarSeparator } from "./SidebarSeparator.vue"
export { default as SidebarTrigger } from "./SidebarTrigger.vue"

export { useSidebar } from "./utils"

export const sidebarMenuButtonVariants = cva(
  "peer/menu-button flex w-full items-center gap-[var(--sidebar-menu-item-gap)] overflow-hidden rounded-md p-2 text-left outline-hidden ring-sidebar-ring transition-[width,height,padding,gap,font-size] duration-300 ease-out hover:bg-sidebar-primary/16 hover:text-sidebar-foreground focus-visible:ring-2 focus-visible:ring-sidebar-ring active:bg-sidebar-primary/20 active:text-sidebar-foreground disabled:pointer-events-none disabled:opacity-50 group-has-data-[sidebar=menu-action]/menu-item:pr-8 aria-disabled:pointer-events-none aria-disabled:opacity-50 data-[active=true]:bg-sidebar-primary/30 data-[active=true]:font-semibold data-[active=true]:text-sidebar-foreground data-[active=true]:ring-1 data-[active=true]:ring-sidebar-primary/42 data-[active=true]:shadow-[0_10px_20px_-16px_hsl(var(--sidebar-primary)/0.95)] dark:data-[active=true]:bg-sidebar-primary/26 dark:data-[active=true]:font-bold dark:data-[active=true]:text-sidebar-primary-foreground dark:data-[active=true]:ring-sidebar-primary/35 dark:data-[active=true]:shadow-[0_12px_24px_-18px_hsl(var(--sidebar-primary)/0.9)] data-[state=open]:hover:bg-sidebar-primary/18 data-[state=open]:hover:text-sidebar-foreground group-data-[collapsible=icon]:size-[var(--sidebar-menu-collapsed-size)]! group-data-[collapsible=icon]:p-[var(--sidebar-menu-collapsed-padding)]! [&>span:last-child]:truncate [&>svg]:size-[var(--sidebar-menu-icon-size)] [&>svg]:shrink-0 [&>svg]:transition-[width,height] [&>svg]:duration-300 [&>svg]:ease-out",
  {
    variants: {
      variant: {
        default: "hover:bg-sidebar-primary/16 hover:text-sidebar-foreground",
        outline:
          "bg-background shadow-[0_0_0_1px_hsl(var(--sidebar-border))] hover:bg-sidebar-primary/16 hover:text-sidebar-foreground hover:shadow-[0_0_0_1px_hsl(var(--sidebar-primary)/0.45)]",
      },
      size: {
        default: "h-[var(--sidebar-menu-item-height)] text-[length:var(--sidebar-menu-font-size)]",
        sm: "h-8 text-sm",
        lg: "h-[var(--sidebar-menu-item-height-lg)] text-[length:var(--sidebar-menu-font-size)] group-data-[collapsible=icon]:p-0!",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)

export type SidebarMenuButtonVariants = VariantProps<typeof sidebarMenuButtonVariants>
