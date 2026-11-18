import type { AuthUser } from '@/types/vouch';
import { Head } from '@inertiajs/react';
import PublicLayout from '@/Layouts/PublicLayout';

export default function Terms({ auth }: { auth?: { user: AuthUser | null } }) {
    return (
        <PublicLayout auth={auth} activeLink="Terms">
            <Head title="Terms of Service — Vouch" />

            <section className="mx-auto grid max-w-7xl gap-12 px-6 py-20 lg:grid-cols-[0.72fr_1.28fr] lg:px-8 lg:py-28">
                <aside className="lg:sticky lg:top-8 lg:self-start">
                    <p className="text-sm font-semibold uppercase tracking-[0.24em] text-primary">Terms</p>
                    <h1 className="mt-4 text-5xl font-semibold tracking-[-0.05em] sm:text-6xl">Terms of Service</h1>
                    <p className="mt-6 text-lg leading-8 text-muted-foreground">The rules and responsibilities for using Vouch to send, sign, store, and manage documents.</p>
                    <div className="mt-8 rounded-md border border-border bg-card p-5 text-sm leading-6 text-muted-foreground shadow-soft">
                        <p className="font-semibold text-foreground">Last updated</p>
                        <p className="mt-1">May 24, 2026</p>
                    </div>
                </aside>

                <article className="rounded-md border border-border bg-card p-6 shadow-soft sm:p-10">
                    <div className="space-y-8 text-base leading-8 text-muted-foreground">
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">1. Acceptance of terms</h2>
                            <p className="mt-3">By accessing or using Vouch, you agree to these Terms of Service and any policies referenced within them.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">2. Use of the service</h2>
                            <p className="mt-3">You may use Vouch to prepare, send, sign, track, and store documents. You are responsible for ensuring that your use of the service and your documents comply with applicable laws.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">3. Accounts and security</h2>
                            <p className="mt-3">You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">4. Electronic signatures</h2>
                            <p className="mt-3">Vouch provides tools for electronic signing and audit records. You are responsible for determining whether a document or transaction is suitable for electronic signature in your jurisdiction or use case.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">5. Customer content</h2>
                            <p className="mt-3">You retain ownership of documents and content uploaded to Vouch. You grant Vouch permission to process that content only as needed to provide and secure the service.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">6. Prohibited use</h2>
                            <p className="mt-3">You may not use Vouch for unlawful activity, fraudulent documents, unauthorized access, abuse of recipients, malware distribution, or attempts to disrupt the platform.</p>
                        </section>
                        <section>
                            <h2 className="text-2xl font-semibold tracking-[-0.02em] text-foreground">7. Service changes</h2>
                            <p className="mt-3">We may update, improve, suspend, or discontinue features from time to time. Where practical, we will provide reasonable notice of material changes.</p>
                        </section>
                        <section className="rounded-md bg-background p-6">
                            <h2 className="text-xl font-semibold text-foreground">Legal review</h2>
                            <p className="mt-2">This is starter website copy and should be reviewed by a qualified legal professional before production use.</p>
                        </section>
                    </div>
                </article>
            </section>
        </PublicLayout>
    );
}
