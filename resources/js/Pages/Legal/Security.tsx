import type { AuthUser } from '@/types/vouch';
import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Security({ auth }: { auth?: { user: AuthUser | null } }) {
    return (
        <PublicLayout auth={auth} dark activeLink="Security">
            <Head title="Security — Vouch" />

            <section className="relative mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-28">
                <div className="absolute -right-32 top-10 h-96 w-96 rounded-full bg-primary/40 blur-3xl" />
                <div className="absolute -bottom-28 left-10 h-80 w-80 rounded-full bg-accent/10 blur-3xl" />

                <div className="relative grid gap-12 lg:grid-cols-[0.78fr_1.22fr] lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.24em] text-accent">Security</p>
                        <h1 className="mt-4 text-5xl font-semibold tracking-[-0.05em] sm:text-6xl">Security at Vouch</h1>
                        <p className="mt-6 text-lg leading-8 text-background/70">Vouch is designed for sensitive agreements, controlled signing, and clear document accountability.</p>
                    </div>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <article className="rounded-md border border-background/10 bg-background/5 p-6 shadow-soft backdrop-blur">
                            <h2 className="text-xl font-semibold">Encrypted storage</h2>
                            <p className="mt-3 text-sm leading-7 text-background/65">Documents should be protected at rest and in transit using modern encryption standards and secure transport.</p>
                        </article>
                        <article className="rounded-md border border-background/10 bg-background/5 p-6 shadow-soft backdrop-blur">
                            <h2 className="text-xl font-semibold">Audit trail</h2>
                            <p className="mt-3 text-sm leading-7 text-background/65">Important actions such as views, signatures, completions, and changes are tracked for accountability.</p>
                        </article>
                        <article className="rounded-md border border-background/10 bg-background/5 p-6 shadow-soft backdrop-blur">
                            <h2 className="text-xl font-semibold">Access controls</h2>
                            <p className="mt-3 text-sm leading-7 text-background/65">Teams can control who can create, send, view, manage, and archive signing workflows.</p>
                        </article>
                        <article className="rounded-md border border-background/10 bg-background/5 p-6 shadow-soft backdrop-blur">
                            <h2 className="text-xl font-semibold">Secure signing links</h2>
                            <p className="mt-3 text-sm leading-7 text-background/65">Signing sessions should be protected with time-aware links, recipient controls, and event monitoring.</p>
                        </article>
                    </div>
                </div>

                <div className="relative mt-16 rounded-md border border-background/10 bg-background/[0.06] p-6 shadow-elegant backdrop-blur sm:p-10">
                    <div className="grid gap-8 lg:grid-cols-3">
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-accent">01</p>
                            <h3 className="mt-3 text-2xl font-semibold">Protect documents</h3>
                            <p className="mt-3 text-sm leading-7 text-background/65">Security starts from upload, storage, signer access, and final document delivery.</p>
                        </div>
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-accent">02</p>
                            <h3 className="mt-3 text-2xl font-semibold">Control workflows</h3>
                            <p className="mt-3 text-sm leading-7 text-background/65">Organizations can structure signing flows with roles, templates, and recipient order.</p>
                        </div>
                        <div>
                            <p className="text-sm font-semibold uppercase tracking-[0.2em] text-accent">03</p>
                            <h3 className="mt-3 text-2xl font-semibold">Preserve evidence</h3>
                            <p className="mt-3 text-sm leading-7 text-background/65">Activity records help teams understand what happened, when it happened, and who was involved.</p>
                        </div>
                    </div>
                </div>

                <div className="relative mt-12 rounded-md bg-primary p-8 shadow-elegant sm:p-10">
                    <div className="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                        <div>
                            <h2 className="text-3xl font-semibold tracking-[-0.035em]">Have a security question?</h2>
                            <p className="mt-3 max-w-2xl text-primary-foreground/70">Contact our security team for responsible disclosure, compliance requests, or enterprise security reviews.</p>
                        </div>
                        <a
                            href="mailto:security@vouch.example"
                            className="inline-flex justify-center rounded-md bg-background px-7 py-4 text-sm font-semibold text-primary transition hover:bg-primary-soft"
                        >
                            Contact security
                        </a>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
