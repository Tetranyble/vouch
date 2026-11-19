import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Home } from 'lucide-react';
import VouchLogo from '@/Components/VouchLogo';
import { Button } from '@/Components/ui/button';

type ErrorPageProps = {
    status: 403 | 404 | 500 | 503;
};

const copy = {
    403: {
        eyebrow: 'Access restricted',
        title: 'You do not have permission to view this page.',
        description: 'If you believe this is a mistake, ask the account administrator for access.',
    },
    404: {
        eyebrow: 'Page not found',
        title: 'This document trail ends here.',
        description: 'The link may be incomplete, expired, or point to a page that no longer exists.',
    },
    500: {
        eyebrow: 'Something went wrong',
        title: 'We could not complete that request.',
        description: 'Our team has been notified. Please try again in a moment.',
    },
    503: {
        eyebrow: 'Temporarily unavailable',
        title: 'Vouch is undergoing maintenance.',
        description: 'We are making a few improvements. Please check back shortly.',
    },
} as const;

export default function Error({ status }: ErrorPageProps) {
    const content = copy[status] ?? copy[500];

    return (
        <div className="relative min-h-screen overflow-hidden bg-background text-foreground antialiased">
            <div className="pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-primary-soft blur-3xl" />
            <div className="pointer-events-none absolute -bottom-24 -left-20 h-72 w-72 rounded-full bg-muted blur-3xl" />

            <header className="relative border-b border-border bg-background/80 backdrop-blur-xl">
                <div className="mx-auto flex max-w-7xl items-center px-6 py-6 lg:px-8">
                    <Link href={route('home')} aria-label="Vouch home">
                        <VouchLogo size={34} />
                    </Link>
                </div>
            </header>

            <main className="relative mx-auto flex min-h-[calc(100vh-101px)] max-w-7xl items-center px-6 py-16 lg:px-8">
                <section className="max-w-2xl">
                    <p className="text-sm font-semibold text-primary">{content.eyebrow}</p>
                    <div className="mt-5 flex items-start gap-5">
                        <span className="font-mono text-6xl font-semibold tracking-[-0.08em] text-primary sm:text-8xl">{status}</span>
                        <span className="mt-3 h-px flex-1 bg-border sm:mt-6" />
                    </div>
                    <h1 className="mt-7 text-3xl font-semibold tracking-[-0.04em] sm:text-5xl">{content.title}</h1>
                    <p className="mt-5 max-w-xl text-base leading-7 text-muted-foreground sm:text-lg">{content.description}</p>

                    <div className="mt-9 flex flex-wrap gap-3">
                        <Button asChild size="lg">
                            <Link href={route('home')}><Home /> Return home</Link>
                        </Button>
                        <Button variant="outline" size="lg" onClick={() => window.history.back()}>
                            <ArrowLeft /> Go back
                        </Button>
                    </div>
                </section>
            </main>
        </div>
    );
}
