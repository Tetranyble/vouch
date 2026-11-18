import type { ReactNode } from 'react';
import type { AuthUser } from '@/types/vouch';
import { Link } from '@inertiajs/react';

function VouchMark({ dark = false }) {
    const color = dark ? 'border-background' : 'border-foreground';
    return (
        <span className="relative block h-9 w-12">
            <span className={`absolute left-0 top-0 h-4 w-5 rounded-tl-md border-l-[5px] border-t-[5px] ${color}`} />
            <span className={`absolute right-0 top-0 h-4 w-5 rounded-tr-md border-r-[5px] border-t-[5px] ${color}`} />
            <span className={`absolute bottom-0 left-0 h-4 w-5 rounded-bl-md border-b-[5px] border-l-[5px] ${color}`} />
            <span className={`absolute bottom-0 right-0 h-4 w-5 rounded-br-md border-b-[5px] border-r-[5px] ${color}`} />
        </span>
    );
}

export default function PublicLayout({ children, dark = false, activeLink = '', auth }: { children: ReactNode; dark?: boolean; activeLink?: string; auth?: { user: AuthUser | null } }) {
    const links = [
        { label: 'Home', href: route('home') },
        { label: 'Privacy', href: route('privacy') },
        { label: 'Terms', href: route('terms') },
        { label: 'Security', href: route('security-page') },
    ].filter(l => l.label !== activeLink);

    if (dark) {
        return (
            <div className="bg-foreground text-background antialiased">
                <main className="min-h-screen overflow-hidden">
                    <header className="border-b border-background/10 bg-foreground/85 backdrop-blur-xl">
                        <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                            <Link href={route('home')} className="flex items-center gap-3" aria-label="Vouch home">
                                <VouchMark dark />
                                <span className="text-xl font-semibold tracking-tight">Vouch</span>
                            </Link>
                            <nav className="hidden items-center gap-6 text-sm font-medium text-background/60 md:flex">
                                {links.map(l => (
                                    <Link key={l.label} href={l.href} className="hover:text-background transition-colors">{l.label}</Link>
                                ))}
                            </nav>
                            <Link
                                href={auth?.user ? route('templates.index') : route('register')}
                                className="rounded-md bg-background px-5 py-2.5 text-sm font-semibold text-primary shadow-soft transition hover:bg-primary-soft"
                            >
                                {auth?.user ? 'Dashboard' : 'Start free'}
                            </Link>
                        </div>
                    </header>

                    {children}

                    <footer className="border-t border-background/10 bg-foreground">
                        <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-background/55 md:flex-row md:items-center md:justify-between lg:px-8">
                            <p>© 2026 Vouch. Secure document signing. · Made with love at <a href="https://pensuh.com" className={dark ? 'hover:text-background transition-colors underline underline-offset-2' : 'hover:text-foreground transition-colors underline underline-offset-2'} target="_blank" rel="noopener noreferrer">Pensuh</a></p>
                            <div className="flex gap-5">
                                <Link href={route('privacy')} className={activeLink === 'Privacy' ? 'text-background' : 'hover:text-background transition-colors'}>Privacy</Link>
                                <Link href={route('terms')} className={activeLink === 'Terms' ? 'text-background' : 'hover:text-background transition-colors'}>Terms</Link>
                                <Link href={route('security-page')} className={activeLink === 'Security' ? 'text-background' : 'hover:text-background transition-colors'}>Security</Link>
                            </div>
                        </div>
                    </footer>
                </main>
            </div>
        );
    }

    return (
        <div className="bg-background text-foreground antialiased">
            <main className="min-h-screen">
                <header className="border-b border-border bg-background/80 backdrop-blur-xl">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                        <Link href={route('home')} className="flex items-center gap-3" aria-label="Vouch home">
                            <VouchMark />
                            <span className="text-xl font-semibold tracking-tight">Vouch</span>
                        </Link>
                        <nav className="hidden items-center gap-6 text-sm font-medium text-muted-foreground md:flex">
                            {links.map(l => (
                                <Link key={l.label} href={l.href} className="hover:text-foreground transition-colors">{l.label}</Link>
                            ))}
                        </nav>
                        <Link
                            href={auth?.user ? route('templates.index') : route('register')}
                            className="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-soft transition hover:bg-primary-hover"
                        >
                            {auth?.user ? 'Dashboard' : 'Start free'}
                        </Link>
                    </div>
                </header>

                {children}

                <footer className="border-t border-border bg-card/70">
                    <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between lg:px-8">
                        <p>© 2026 Vouch. Secure document signing.</p>
                        <div className="flex gap-5">
                            <Link href={route('privacy')} className={activeLink === 'Privacy' ? 'text-foreground font-medium' : 'hover:text-foreground transition-colors'}>Privacy</Link>
                            <Link href={route('terms')} className={activeLink === 'Terms' ? 'text-foreground font-medium' : 'hover:text-foreground transition-colors'}>Terms</Link>
                            <Link href={route('security-page')} className={activeLink === 'Security' ? 'text-foreground font-medium' : 'hover:text-foreground transition-colors'}>Security</Link>
                        </div>
                    </div>
                </footer>
            </main>
        </div>
    );
}
