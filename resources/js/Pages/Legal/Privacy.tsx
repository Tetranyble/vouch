import type { AuthUser } from '@/types/vouch';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Privacy({ auth }: { auth?: { user: AuthUser | null } }) {
    return (
        <PublicLayout auth={auth} activeLink="Privacy">
            <Head title="Privacy Policy — Vouch" />

            <section className="mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-[0.72fr_1.28fr] lg:px-8 lg:py-28">
                <aside className="lg:sticky lg:top-8 lg:self-start">
                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-primary">Privacy</p>
                    <h1 className="mt-4 text-5xl font-semibold tracking-[-0.05em] sm:text-6xl">Privacy Policy</h1>
                    <p className="mt-6 text-lg leading-8 text-muted-foreground">How Vouch collects, uses, protects, and manages information across document signing workflows.</p>
                    <div className="mt-8 rounded-md border border-border bg-card p-5 text-sm leading-6 text-muted-foreground shadow-soft">
                        <p className="font-semibold text-foreground">Last updated</p>
                        <p className="mt-1">May 24, 2026</p>
                    </div>
                </aside>

                <article className="rounded-md border border-border bg-card p-6 shadow-soft sm:p-10">
                    <div className="space-y-8 text-base leading-8 text-muted-foreground">
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">1. Information we collect</h2>
                            <p className="mt-3">We may collect account details, contact information, document metadata, signer activity, audit events, device information, and usage data needed to provide the Vouch service.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">2. Documents and signing data</h2>
                            <p className="mt-3">Documents uploaded to Vouch are processed only to support signing, review, storage, delivery, authentication, and audit trail generation. We do not sell document content or use it for advertising.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">3. How we use information</h2>
                            <p className="mt-3">We use information to create accounts, send signature requests, notify recipients, maintain document history, prevent misuse, improve reliability, provide support, and comply with applicable obligations.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">4. Sharing and disclosure</h2>
                            <p className="mt-3">We share information only with authorized users, signers, service providers, legal or compliance requests where required, and parties involved in a document workflow.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">5. Data retention</h2>
                            <p className="mt-3">We retain account, document, and audit data for as long as needed to provide the service, meet legal requirements, resolve disputes, enforce agreements, or support customer recordkeeping.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">6. Your choices</h2>
                            <p className="mt-3">Users may request access, correction, export, or deletion of eligible personal information, subject to security, legal, and contractual requirements.</p>
                        </section>
                        <section className="rounded-md bg-background p-6">
                            <h2 className="text-xl font-semibold text-foreground">Contact</h2>
                            <p className="mt-2">For privacy questions, contact <span className="font-semibold text-primary">privacy@vouch.example</span>.</p>
                        </section>
                    </div>
                </article>
            </section>
        </PublicLayout>
    );
}
