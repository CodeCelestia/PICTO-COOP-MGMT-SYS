import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all duration-150 ease-in-out disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-offset-background aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "border border-primary bg-primary text-primary-foreground shadow-sm hover:border-primary hover:bg-primary/90 focus-visible:ring-primary/35 dark:focus-visible:ring-primary/45",
        destructive:
          "border border-red-300 bg-white text-red-600 shadow-sm hover:border-red-500 hover:bg-red-50 hover:text-red-700 focus-visible:ring-red-300/70 dark:border-red-700 dark:bg-red-950/30 dark:text-red-400 dark:hover:border-red-500 dark:hover:bg-red-950 dark:hover:text-red-300 dark:focus-visible:ring-red-500/60",
        outline:
          "border border-gray-400 bg-white text-gray-700 shadow-sm hover:border-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-gray-300 dark:border-zinc-500 dark:bg-zinc-800 dark:text-gray-200 dark:hover:border-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:ring-zinc-600",
        secondary:
          "border border-gray-300 bg-gray-100 text-gray-800 shadow-sm hover:border-gray-400 hover:bg-gray-200 dark:border-zinc-600 dark:bg-zinc-800 dark:text-gray-100 dark:hover:border-zinc-500 dark:hover:bg-zinc-700",
        ghost:
          "border border-gray-400 bg-gray-50 text-gray-700 shadow-sm hover:border-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-gray-300 dark:border-zinc-500 dark:bg-zinc-800/80 dark:text-gray-200 dark:hover:border-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:ring-zinc-600",
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
