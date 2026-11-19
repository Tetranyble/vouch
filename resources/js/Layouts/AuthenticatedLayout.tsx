import { useState, type ReactNode, type FormEvent } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    FileText, Send, Settings, LogOut, User, Users, ChevronDown, Menu,
    Plus, Bell, Palette, MessageSquare, Shield, HardDrive, Lock,
    ShieldCheck, Key, Globe, Webhook, Plug2,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger, SheetClose } from '@/Components/ui/sheet';
import VouchLogo from '@/Components/VouchLogo';
import VouchEcosystemLinks from '@/Components/VouchEcosystemLinks';

const navItems = [
    { label: 'Templates', href: '/templates', icon: FileText, match: '/templates' },
    { label: 'Submissions', href: '/submissions', icon: Send, match: '/submissions' },
];

const settingsNav = [
    { label: 'Account', href: '/settings/account', icon: User },
    { label: 'Team', href: '/settings/teams', icon: Users },
    { label: 'Integrations', href: '/settings/integrations', icon: Plug2 },
    { label: 'API Tokens', href: '/settings/api-tokens', icon: Key },
    { label: 'Webhooks', href: '/settings/webhooks', icon: Webhook },
    { label: 'SMTP', href: '/settings/smtp', icon: Globe },
    { label: 'Notifications', href: '/settings/notifications', icon: Bell },
    { label: 'Personalization', href: '/settings/personalization', icon: Palette },
    { label: 'SMS', href: '/settings/sms', icon: MessageSquare },
    { label: 'E-Sign', href: '/settings/esign', icon: Shield },
    { label: 'Storage', href: '/settings/storage', icon: HardDrive },
    { label: 'SSO', href: '/settings/sso', icon: Lock },
    { label: 'MFA', href: '/settings/mfa', icon: ShieldCheck },
];

function UserAvatar({ user }: { user: AuthenticatedUser }) {
    const initials = [user.first_name?.[0], user.last_name?.[0]].filter(Boolean).join('').toUpperCase()
        || user.email?.[0]?.toUpperCase() || 'U';
    return (
        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-xs font-semibold shrink-0">
            {initials}
        </div>
    );
}

function SettingsSidebar({ currentPath }: { currentPath: string }) {
    return (
        <aside className="w-56 shrink-0 border-r bg-muted/20 py-6 px-3 hidden md:block">
            <p className="px-3 mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Settings</p>
            <nav className="space-y-0.5">
                {settingsNav.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                            currentPath.startsWith(item.href)
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        )}
                    >
                        <item.icon className="h-4 w-4 shrink-0" />
                        {item.label}
                    </Link>
                ))}
            </nav>
        </aside>
    );
}

function SettingsMobileNav({ currentPath }: { currentPath: string }) {
    return (
        <div className="md:hidden border-b bg-muted/10 overflow-x-auto scrollbar-none">
            <nav className="flex px-3 py-2 gap-1 w-max min-w-full">
                {settingsNav.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium whitespace-nowrap transition-colors shrink-0',
                            currentPath.startsWith(item.href)
                                ? 'bg-accent text-accent-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                        )}
                    >
                        <item.icon className="h-3.5 w-3.5 shrink-0" />
                        {item.label}
                    </Link>
                ))}
            </nav>
        </div>
    );
}

function MobileNav({ user, currentPath, onLogout }: { user: AuthenticatedUser; currentPath: string; onLogout: (event: FormEvent) => void }) {
    const [settingsOpen, setSettingsOpen] = useState(false);

    function isActive(match: string) {
        return currentPath.startsWith(match);
    }

    return (
        <div className="flex flex-col h-full">
            {/* User info */}
            <div className="px-4 py-4 border-b flex items-center gap-3">
                <UserAvatar user={user} />
                <div className="min-w-0">
                    <p className="text-sm font-medium truncate">
                        {[user.first_name, user.last_name].filter(Boolean).join(' ') || 'User'}
                    </p>
                    <p className="text-xs text-muted-foreground truncate">{user.email}</p>
                </div>
            </div>

            {/* Nav links */}
            <nav className="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">
                {navItems.map((item) => (
                    <SheetClose key={item.href} asChild>
                        <Link
                            href={item.href}
                            className={cn(
                                'flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium transition-colors',
                                isActive(item.match)
                                    ? 'bg-accent text-accent-foreground'
                                    : 'text-foreground hover:bg-accent hover:text-accent-foreground'
                            )}
                        >
                            <item.icon className="h-4 w-4 shrink-0" />
                            {item.label}
                        </Link>
                    </SheetClose>
                ))}

                <SheetClose asChild>
                    <Link
                        href="/templates/create"
                        className="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                    >
                        <Plus className="h-4 w-4 shrink-0" />
                        New Template
                    </Link>
                </SheetClose>

                <div className="pt-1">
                    <Button variant="ghost" size="sm"
                        onClick={() => setSettingsOpen((o) => !o)}
                        className="flex items-center gap-3 w-full rounded-md px-3 py-2.5 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
                    >
                        <Settings className="h-4 w-4 shrink-0" />
                        Settings
                        <ChevronDown className={cn('h-4 w-4 ml-auto transition-transform duration-200', settingsOpen && 'rotate-180')} />
                    </Button>
                    {settingsOpen && (
                        <div className="mt-0.5 pl-4 border-l ml-5 space-y-0.5">
                            {settingsNav.map((item) => (
                                <SheetClose key={item.href} asChild>
                                    <Link
                                        href={item.href}
                                        className={cn(
                                            'flex items-center gap-2.5 rounded-md px-3 py-2 text-sm transition-colors',
                                            currentPath.startsWith(item.href)
                                                ? 'bg-accent text-accent-foreground font-medium'
                                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                                        )}
                                    >
                                        <item.icon className="h-3.5 w-3.5 shrink-0" />
                                        {item.label}
                                    </Link>
                                </SheetClose>
                            ))}
                        </div>
                    )}
                </div>
            </nav>

            {/* Log out */}
            <div className="px-3 py-3 border-t">
                <Button variant="destructive"
                    onClick={onLogout}
                    className="flex items-center gap-3 w-full rounded-md px-3 py-2.5 text-sm font-medium text-destructive-foreground transition-colors"
                >
                    <LogOut className="h-4 w-4 shrink-0" />
                    Log out
                </Button>
            </div>
        </div>
    );
}

type AuthenticatedUser = { id: number; first_name?: string | null; last_name?: string | null; email: string; role?: string };
type LayoutPageProps = { auth: { user: AuthenticatedUser }; contact: { support_email: string }; flash?: { success?: string; error?: string } } & Record<string, unknown>;

export default function AuthenticatedLayout({ children }: { children: ReactNode; header?: ReactNode }) {
    const { auth, contact, flash } = usePage<LayoutPageProps>().props;
    const user = auth.user;

    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';
    const isSettingsPage = currentPath.startsWith('/settings');

    function isActive(match: string) {
        return currentPath.startsWith(match);
    }

    function handleLogout(e: FormEvent) {
        e.preventDefault();
        router.post('/logout');
    }

    return (
        <div className="min-h-screen bg-background flex flex-col">
            {/* Top navbar */}
            <header className="sticky top-0 z-40 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                <div className="flex h-14 items-center px-4 md:px-6">
                    {/* Logo */}
                    <Link href="/templates" className="mr-4 md:mr-6 shrink-0">
                        <VouchLogo size={32} />
                    </Link>

                    {/* Desktop nav */}
                    <nav className="hidden md:flex items-center gap-1 flex-1">
                        {navItems.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={cn(
                                    'flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                                    isActive(item.match)
                                        ? 'bg-accent text-accent-foreground'
                                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                                )}
                            >
                                <item.icon className="h-4 w-4" />
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    {/* Spacer on mobile */}
                    <div className="flex-1 md:hidden" />

                    {/* Right side */}
                    <div className="flex items-center gap-1.5">
                        <Link href="/templates/create" className="hidden sm:block">
                            <Button size="sm" className="gap-2">
                                <Plus className="h-4 w-4" />
                                <span className="hidden md:inline">New Template</span>
                                <span className="md:hidden">New</span>
                            </Button>
                        </Link>

                        <Link href="/settings/account" className="hidden md:flex">
                            <Button variant="ghost" size="icon" className={cn(isSettingsPage && 'bg-accent')}>
                                <Settings className="h-4 w-4" />
                            </Button>
                        </Link>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="flex items-center gap-2 px-2 h-9">
                                    <UserAvatar user={user} />
                                    <span className="hidden md:block text-sm font-medium max-w-[120px] truncate">
                                        {user.first_name || user.email}
                                    </span>
                                    <ChevronDown className="h-4 w-4 opacity-50 hidden md:block" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel className="font-normal">
                                    <div className="flex flex-col space-y-1">
                                        <p className="text-sm font-medium leading-none">
                                            {[user.first_name, user.last_name].filter(Boolean).join(' ') || 'User'}
                                        </p>
                                        <p className="text-xs leading-none text-muted-foreground">{user.email}</p>
                                    </div>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/profile" className="flex items-center gap-2 cursor-pointer">
                                        <User className="h-4 w-4" />Profile
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href="/settings/account" className="flex items-center gap-2 cursor-pointer">
                                        <Settings className="h-4 w-4" />Settings
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="text-destructive focus:text-destructive cursor-pointer" onClick={handleLogout}>
                                    <LogOut className="h-4 w-4 mr-2" />Log out
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        {/* Mobile hamburger → Sheet */}
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button variant="ghost" size="icon" className="md:hidden h-9 w-9">
                                    <Menu className="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="p-0 w-72">
                                <SheetHeader className="sr-only">
                                    <SheetTitle>Navigation</SheetTitle>
                                </SheetHeader>
                                <MobileNav user={user} currentPath={currentPath} onLogout={handleLogout} />
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>

            {/* Flash messages */}
            {flash?.success && (
                <div className="bg-success-soft border-b border-success/20 px-4 py-3 text-sm text-success flex items-center gap-2">
                    <svg className="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clipRule="evenodd" />
                    </svg>
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="bg-destructive-soft border-b border-destructive/20 px-4 py-3 text-sm text-destructive">
                    {flash.error}
                </div>
            )}

            {/* Settings layout: sidebar on md+, horizontal tabs on mobile */}
            {isSettingsPage ? (
                <>
                    <SettingsMobileNav currentPath={currentPath} />
                    <div className="flex flex-1 min-h-0">
                        <SettingsSidebar currentPath={currentPath} />
                        <main className="flex-1 min-w-0 overflow-auto">
                            {children}
                        </main>
                    </div>
                </>
            ) : (
                <main className="flex-1">
                    {children}
                </main>
            )}

            <footer className="border-t bg-card/70 px-4 py-5 md:px-6">
                <div className="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between">
                    <p>© 2026 Vouch. Secure document signing.</p>
                    <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                        <a href={`mailto:${contact.support_email}`} className="text-xs hover:text-foreground hover:underline underline-offset-4">Support</a>
                        <VouchEcosystemLinks />
                    </div>
                </div>
            </footer>
        </div>
    );
}
