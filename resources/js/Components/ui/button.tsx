import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap text-sm font-semibold transition-colors duration-ui ease-ui focus-visible:outline-none focus-visible:shadow-focus focus-visible:ring-2 focus-visible:ring-ring/60 disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default:     'rounded-control bg-primary text-primary-foreground shadow-soft hover:bg-primary-hover',
                destructive: 'rounded-control bg-destructive text-destructive-foreground hover:bg-destructive/90',
                outline:     'rounded-control border border-border bg-card text-foreground hover:bg-muted',
                secondary:   'rounded-control bg-secondary text-secondary-foreground hover:bg-secondary/70',
                ghost:       'rounded-control text-foreground hover:bg-muted hover:text-foreground',
                link:        'text-primary underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-control-md px-control-x py-control-y',
                sm:      'h-control-sm rounded-control px-3 text-xs',
                lg:      'h-control-lg rounded-control px-6',
                icon:    'h-control-md w-control-md rounded-control',
                'icon-sm': 'h-7 w-7 rounded-md p-0',
                compact: 'h-7 rounded-md px-2 py-1 text-xs',
                text: 'h-auto rounded-md px-1 py-0 text-sm',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    }
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(({ className, variant, size, asChild = false, type = 'button', ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return (
        <Comp className={cn(buttonVariants({ variant, size, className }))} ref={ref} {...(!asChild ? { type } : {})} {...props} />
    );
});
Button.displayName = 'Button';

export { Button, buttonVariants };
