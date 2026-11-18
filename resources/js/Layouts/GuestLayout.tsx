import type { ReactNode } from 'react';
import VouchLogo from '@/Components/VouchLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-background pt-6 sm:justify-center sm:pt-0">
            <Link href="/">
                <VouchLogo size={40} showText={false} />
            </Link>
            <div className="mt-6 w-full overflow-hidden bg-card px-6 py-6 shadow-soft border border-border sm:max-w-md sm:rounded-md">
                {children}
            </div>
        </div>
    );
}
