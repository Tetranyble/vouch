import type { AuthUser } from '@/types/vouch';
import { Head, Link } from '@inertiajs/react';
import VouchEcosystemLinks from '@/Components/VouchEcosystemLinks';

function VouchMark({ className = '' }) {
    return (
        <span className={`relative block h-9 w-12 ${className}`}>
            <span className="absolute left-0 top-0 h-4 w-5 rounded-tl-md border-l-[5px] border-t-[5px] border-foreground" />
            <span className="absolute right-0 top-0 h-4 w-5 rounded-tr-md border-r-[5px] border-t-[5px] border-foreground" />
            <span className="absolute bottom-0 left-0 h-4 w-5 rounded-bl-md border-b-[5px] border-l-[5px] border-foreground" />
            <span className="absolute bottom-0 right-0 h-4 w-5 rounded-br-md border-b-[5px] border-r-[5px] border-foreground" />
        </span>
    );
}

function VouchMarkWhite() {
    return (
        <span className="relative block h-4 w-5">
            <span className="absolute left-0 top-0 h-2 w-2 border-l-2 border-t-2 border-signer-foreground" />
            <span className="absolute right-0 top-0 h-2 w-2 border-r-2 border-t-2 border-signer-foreground" />
            <span className="absolute bottom-0 left-0 h-2 w-2 border-b-2 border-l-2 border-signer-foreground" />
            <span className="absolute bottom-0 right-0 h-2 w-2 border-b-2 border-r-2 border-signer-foreground" />
        </span>
    );
}

export default function Welcome({ auth, contact }: { auth?: { user: AuthUser | null }; contact: { support_email: string } }) {
    return (
        <>
            <Head title="Vouch — Secure Document Signing" />
            <div className="bg-background text-foreground antialiased">
                <main className="min-h-screen overflow-hidden">

                    {/* Navigation */}
                    <header className="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                        <Link href="/" className="flex items-center gap-3" aria-label="Vouch home">
                            <VouchMark />
                            <span className="text-xl font-semibold tracking-tight">Vouch</span>
                        </Link>

                        <nav className="hidden items-center gap-8 text-sm font-medium text-muted-foreground lg:flex">
                            <a href="#product" className="hover:text-foreground transition-colors">Product</a>
                            <a href="#security" className="hover:text-foreground transition-colors">Security</a>
                            <a href="#workflow" className="hover:text-foreground transition-colors">Workflow</a>
                            <a href="#pricing" className="hover:text-foreground transition-colors">Pricing</a>
                        </nav>

                        <div className="flex items-center gap-3">
                            {auth?.user ? (
                                <Link
                                    href={route('templates.index')}
                                    className="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-soft transition hover:bg-primary-hover"
                                >
                                    Go to dashboard
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={route('login')}
                                        className="hidden rounded-md px-4 py-2 text-sm font-semibold text-foreground hover:bg-card transition-colors md:inline-flex"
                                    >
                                        Log in
                                    </Link>
                                    <Link
                                        href={route('register')}
                                        className="rounded-md bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-soft transition hover:bg-primary-hover"
                                    >
                                        Start free
                                    </Link>
                                </>
                            )}
                        </div>
                    </header>

                    {/* Hero */}
                    <section className="relative mx-auto grid max-w-7xl items-center gap-14 px-6 pb-20 pt-10 lg:grid-cols-[1.02fr_0.98fr] lg:px-8 lg:pb-28 lg:pt-16">
                        <div className="absolute -right-32 top-20 h-80 w-80 rounded-full bg-primary-soft blur-3xl opacity-70" />
                        <div className="absolute left-12 top-52 h-40 w-40 rounded-full bg-accent/20 blur-3xl" />

                        <div className="relative z-10 max-w-3xl">
                            <div className="mb-8 inline-flex items-center gap-2 rounded-full border border-border bg-card/70 px-4 py-2 text-sm font-medium text-primary shadow-sm">
                                <span className="h-2 w-2 rounded-full bg-primary" />
                                Secure agreements for modern teams
                            </div>

                            <h1 className="max-w-4xl text-5xl font-semibold tracking-[-0.055em] text-foreground sm:text-6xl lg:text-7xl">
                                Sign documents with quiet confidence.
                            </h1>

                            <p className="mt-7 max-w-2xl text-lg leading-8 text-muted-foreground">
                                Vouch helps you send, sign, track, and protect important agreements in a simple workspace built for trust, speed, and professional control.
                            </p>

                            <div className="mt-9 flex flex-col gap-4 sm:flex-row">
                                <Link
                                    href={auth?.user ? route('templates.index') : route('register')}
                                    className="inline-flex items-center justify-center rounded-md bg-primary px-7 py-4 text-sm font-semibold text-primary-foreground shadow-elegant transition hover:bg-primary-hover"
                                >
                                    Send for signature
                                    <span className="ml-2">→</span>
                                </Link>
                                <a
                                    href="#workflow"
                                    className="inline-flex items-center justify-center rounded-md border border-border bg-card px-7 py-4 text-sm font-semibold text-foreground transition hover:border-primary/30 hover:bg-primary-soft/60"
                                >
                                    See how it works
                                </a>
                            </div>

                            <div className="mt-10 grid max-w-xl grid-cols-3 gap-6 border-t border-border pt-8">
                                <div>
                                    <p className="text-2xl font-semibold tracking-tight">99.9%</p>
                                    <p className="mt-1 text-sm text-muted-foreground">uptime focus</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold tracking-tight">AES</p>
                                    <p className="mt-1 text-sm text-muted-foreground">secure storage</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold tracking-tight">4-step</p>
                                    <p className="mt-1 text-sm text-muted-foreground">simple flow</p>
                                </div>
                            </div>
                        </div>

                        {/* Product visual */}
                        <div className="relative z-10">
                            <div className="rounded-md border border-border bg-card p-4 shadow-elegant">
                                <div className="rounded-md bg-gradient-to-br from-card via-background to-primary-soft/70 p-5">
                                    <div className="mb-5 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary text-primary-foreground">
                                                <VouchMarkWhite />
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold">Master Service Agreement</p>
                                                <p className="text-xs text-muted-foreground">Sent to 3 recipients</p>
                                            </div>
                                        </div>
                                        <span className="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-primary">In progress</span>
                                    </div>

                                    <div className="grid gap-4 lg:grid-cols-[1fr_0.76fr]">
                                        <div className="rounded-md border border-border bg-card p-5 shadow-soft">
                                            <div className="mb-5 flex items-center justify-between">
                                                <div>
                                                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-muted-foreground">Document</p>
                                                    <h3 className="mt-2 text-xl font-semibold tracking-tight">Client Agreement</h3>
                                                </div>
                                                <span className="rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-foreground">PDF</span>
                                            </div>

                                            <div className="space-y-3">
                                                <div className="h-3 w-full rounded-full bg-border/70" />
                                                <div className="h-3 w-11/12 rounded-full bg-border/70" />
                                                <div className="h-3 w-8/12 rounded-full bg-border/70" />
                                                <div className="mt-6 h-20 rounded-md border border-dashed border-primary/25 bg-primary-soft/60" />
                                                <div className="h-3 w-10/12 rounded-full bg-border/70" />
                                                <div className="h-3 w-7/12 rounded-full bg-border/70" />
                                            </div>

                                            <div className="mt-8 rounded-md bg-primary p-4 text-primary-foreground">
                                                <p className="text-xs text-primary-foreground/70">Next action</p>
                                                <p className="mt-1 text-sm font-semibold">Awaiting Sarah's signature</p>
                                            </div>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="rounded-md border border-border bg-card p-5 shadow-soft">
                                                <p className="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">Signers</p>
                                                <div className="mt-5 space-y-4">
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div className="h-9 w-9 rounded-full bg-primary-soft" />
                                                            <div>
                                                                <p className="text-sm font-semibold">Jane Doe</p>
                                                                <p className="text-xs text-muted-foreground">Signed</p>
                                                            </div>
                                                        </div>
                                                        <span className="text-sm text-primary">✓</span>
                                                    </div>
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div className="h-9 w-9 rounded-full bg-accent/25" />
                                                            <div>
                                                                <p className="text-sm font-semibold">Sarah Miller</p>
                                                                <p className="text-xs text-muted-foreground">Pending</p>
                                                            </div>
                                                        </div>
                                                        <span className="text-sm text-accent">●</span>
                                                    </div>
                                                    <div className="flex items-center justify-between">
                                                        <div className="flex items-center gap-3">
                                                            <div className="h-9 w-9 rounded-full bg-border" />
                                                            <div>
                                                                <p className="text-sm font-semibold">Robert King</p>
                                                                <p className="text-xs text-muted-foreground">Viewed</p>
                                                            </div>
                                                        </div>
                                                        <span className="text-sm text-muted-foreground">•</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="rounded-md border border-border bg-card p-5 shadow-soft">
                                                <div className="flex items-center justify-between">
                                                    <p className="text-sm font-semibold">Completion</p>
                                                    <p className="text-sm font-semibold text-primary">67%</p>
                                                </div>
                                                <div className="mt-4 h-2 rounded-full bg-border">
                                                    <div className="h-2 w-2/3 rounded-full bg-primary" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Trust strip */}
                    <section className="border-y border-border bg-card/70">
                        <div className="mx-auto grid max-w-7xl gap-4 px-6 py-6 sm:grid-cols-2 lg:grid-cols-4 lg:px-8">
                            <div className="rounded-md px-4 py-3">
                                <p className="font-semibold">Protected by design</p>
                                <p className="mt-1 text-sm text-muted-foreground">Security-first signing flow.</p>
                            </div>
                            <div className="rounded-md px-4 py-3">
                                <p className="font-semibold">Audit trail</p>
                                <p className="mt-1 text-sm text-muted-foreground">Track every important action.</p>
                            </div>
                            <div className="rounded-md px-4 py-3">
                                <p className="font-semibold">Team ready</p>
                                <p className="mt-1 text-sm text-muted-foreground">Templates, roles, and controls.</p>
                            </div>
                            <div className="rounded-md px-4 py-3">
                                <p className="font-semibold">Minimal experience</p>
                                <p className="mt-1 text-sm text-muted-foreground">No clutter. No confusion.</p>
                            </div>
                        </div>
                    </section>

                    {/* Workflow */}
                    <section id="workflow" className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                        <div className="mx-auto max-w-2xl text-center">
                            <p className="text-sm font-semibold uppercase tracking-[0.24em] text-primary">Workflow</p>
                            <h2 className="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">From draft to signed in minutes.</h2>
                            <p className="mt-5 text-lg leading-8 text-muted-foreground">A calm signing process for businesses that need speed without sacrificing trust.</p>
                        </div>

                        <div className="mt-16 grid gap-5 md:grid-cols-4">
                            <div className="rounded-md border border-border bg-card p-6 shadow-soft">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">1</span>
                                <h3 className="mt-6 text-lg font-semibold">Upload</h3>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">Add your agreement, offer letter, NDA, or contract.</p>
                            </div>
                            <div className="rounded-md border border-border bg-card p-6 shadow-soft">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">2</span>
                                <h3 className="mt-6 text-lg font-semibold">Prepare</h3>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">Place fields, define signers, and set the signing order.</p>
                            </div>
                            <div className="rounded-md border border-border bg-card p-6 shadow-soft">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-primary-soft text-sm font-bold text-primary">3</span>
                                <h3 className="mt-6 text-lg font-semibold">Send</h3>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">Share a secure signing link with recipients instantly.</p>
                            </div>
                            <div className="rounded-md bg-primary p-6 text-primary-foreground shadow-elegant">
                                <span className="flex h-10 w-10 items-center justify-center rounded-full bg-signer-foreground/15 text-sm font-bold">4</span>
                                <h3 className="mt-6 text-lg font-semibold">Complete</h3>
                                <p className="mt-2 text-sm leading-6 text-primary-foreground/75">Receive the completed document and audit record.</p>
                            </div>
                        </div>
                    </section>

                    {/* Features */}
                    <section id="product" className="bg-card py-24">
                        <div className="mx-auto max-w-7xl px-6 lg:px-8">
                            <div className="grid gap-14 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-primary">Product</p>
                                    <h2 className="mt-4 text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">Everything needed to manage signing professionally.</h2>
                                    <p className="mt-5 text-lg leading-8 text-muted-foreground">Vouch keeps the experience simple for signers while giving teams the structure they need behind the scenes.</p>
                                </div>

                                <div className="grid gap-5 sm:grid-cols-2">
                                    <article className="rounded-md border border-border bg-background p-6">
                                        <h3 className="text-lg font-semibold">Reusable templates</h3>
                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">Create templates for common agreements and send faster every time.</p>
                                    </article>
                                    <article className="rounded-md border border-border bg-background p-6">
                                        <h3 className="text-lg font-semibold">Audit trails</h3>
                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">Capture timeline, signer activity, and document status with clarity.</p>
                                    </article>
                                    <article className="rounded-md border border-border bg-background p-6">
                                        <h3 className="text-lg font-semibold">Secure access</h3>
                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">Protect sensitive documents with thoughtful access and role controls.</p>
                                    </article>
                                    <article className="rounded-md border border-border bg-background p-6">
                                        <h3 className="text-lg font-semibold">Status tracking</h3>
                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">Know who has viewed, signed, completed, or delayed the process.</p>
                                    </article>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Security */}
                    <section id="security" className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
                        <div className="rounded-md bg-foreground p-8 text-background shadow-elegant lg:p-14">
                            <div className="grid gap-10 lg:grid-cols-[1fr_0.9fr] lg:items-center">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-accent">Security</p>
                                    <h2 className="mt-4 max-w-2xl text-4xl font-semibold tracking-[-0.04em] sm:text-5xl">Built for agreements that matter.</h2>
                                    <p className="mt-5 max-w-2xl text-lg leading-8 text-background/70">Every interaction should feel protected. Vouch presents security with calm, professional clarity instead of visual noise.</p>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="rounded-md border border-background/10 bg-background/5 p-5">
                                        <p className="font-semibold">Encrypted files</p>
                                        <p className="mt-2 text-sm leading-6 text-background/60">Keep sensitive documents protected.</p>
                                    </div>
                                    <div className="rounded-md border border-background/10 bg-background/5 p-5">
                                        <p className="font-semibold">Signer identity</p>
                                        <p className="mt-2 text-sm leading-6 text-background/60">Verify and track recipient actions.</p>
                                    </div>
                                    <div className="rounded-md border border-background/10 bg-background/5 p-5">
                                        <p className="font-semibold">Access controls</p>
                                        <p className="mt-2 text-sm leading-6 text-background/60">Manage who can view and send.</p>
                                    </div>
                                    <div className="rounded-md border border-background/10 bg-background/5 p-5">
                                        <p className="font-semibold">Complete history</p>
                                        <p className="mt-2 text-sm leading-6 text-background/60">Review activity with confidence.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Pricing */}
                    <section id="pricing" className="mx-auto max-w-7xl px-6 pb-24 lg:px-8">
                        <div className="rounded-md border border-border bg-card p-8 shadow-soft lg:p-12">
                            <div className="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                                <div>
                                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-primary">Get started</p>
                                    <h2 className="mt-4 text-3xl font-semibold tracking-[-0.035em] sm:text-4xl">Launch your first signing flow today.</h2>
                                    <p className="mt-4 max-w-2xl text-muted-foreground">Use Vouch for NDAs, contracts, service agreements, offer letters, onboarding documents, and internal approvals.</p>
                                </div>
                                <div className="flex flex-col gap-3 sm:flex-row lg:flex-col">
                                    <Link
                                        href={auth?.user ? route('templates.index') : route('register')}
                                        className="inline-flex justify-center rounded-md bg-primary px-7 py-4 text-sm font-semibold text-primary-foreground shadow-elegant hover:bg-primary-hover transition-colors"
                                    >
                                        Start free trial
                                    </Link>
                                    <a
                                        href="#workflow"
                                        className="inline-flex justify-center rounded-md border border-border px-7 py-4 text-sm font-semibold hover:bg-background transition-colors"
                                    >
                                        Book demo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </section>

                    <footer className="border-t border-border bg-card/70">
                        <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-muted-foreground lg:px-8">
                            <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <p>© 2026 Vouch. Secure document signing.</p>
                                <VouchEcosystemLinks />
                            </div>
                            <div className="flex gap-5">
                                <a href={`mailto:${contact.support_email}`} className="hover:text-foreground transition-colors">Support</a>
                                <Link href={route('privacy')} className="hover:text-foreground transition-colors">Privacy</Link>
                                <Link href={route('terms')} className="hover:text-foreground transition-colors">Terms</Link>
                                <Link href={route('security-page')} className="hover:text-foreground transition-colors">Security</Link>
                            </div>
                        </div>
                    </footer>

                </main>
            </div>
        </>
    );
}
