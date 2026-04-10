import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all duration-150 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "border border-primary/80 bg-primary text-primary-foreground shadow-[0_14px_24px_-14px_hsl(var(--primary))] hover:-translate-y-px hover:bg-primary/90",
        destructive:
          "border border-destructive/80 bg-destructive text-white shadow-[0_14px_26px_-14px_hsl(var(--destructive))] hover:-translate-y-px hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "border border-border bg-background shadow-[0_12px_24px_-14px_rgba(15,23,42,0.62)] hover:-translate-y-px hover:bg-accent hover:text-accent-foreground dark:border-input dark:bg-input/40 dark:shadow-[0_14px_24px_-14px_rgba(0,0,0,0.82)] dark:hover:bg-input/58",
        secondary:
          "border border-border/75 bg-secondary text-secondary-foreground shadow-[0_12px_24px_-14px_rgba(15,23,42,0.56)] hover:-translate-y-px hover:bg-secondary/80 dark:shadow-[0_14px_24px_-14px_rgba(0,0,0,0.8)]",
        ghost:
          "border border-border bg-background/92 shadow-[0_12px_24px_-14px_rgba(15,23,42,0.6)] hover:-translate-y-px hover:bg-accent hover:text-accent-foreground dark:border-border/60 dark:bg-muted/34 dark:shadow-[0_14px_24px_-14px_rgba(0,0,0,0.82)] dark:hover:bg-accent/60",
        link: "bg-transparent text-primary underline-offset-4 shadow-none hover:underline",
      },
      size: {
        "default": "h-9 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5",
        "lg": "h-10 rounded-md px-6 has-[>svg]:px-4",
        "icon": "size-9",
        "icon-sm": "size-8",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
