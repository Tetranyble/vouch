import type * as React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import {
    Key, Webhook, Mail, MessageSquare, HardDrive,
    ShieldCheck, Code2, CheckCircle2, XCircle, ArrowRight,
} from 'lucide-react';
import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';

// ─── Status badge ─────────────────────────────────────────────────────────────

function StatusBadge({ configured, label = 'Configured' }: { configured: boolean; label?: string }) {
    return configured ? (
        <span className="inline-flex items-center gap-1 text-xs font-medium text-success">
            <CheckCircle2 className="h-3.5 w-3.5" />
            {label ?? 'Configured'}
        </span>
    ) : (
        <span className="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground">
            <XCircle className="h-3.5 w-3.5" />
            Not configured
        </span>
    );
}

// ─── Integration card ─────────────────────────────────────────────────────────

function IntegrationCard({ icon: Icon, iconColor, title, description, status, href }: { icon: typeof Key; iconColor: string; title: string; description: string; status: React.ReactNode; href: string }) {
    return (
        <Link
            href={href}
            className="group flex flex-col gap-4 rounded-lg border bg-card p-5 hover:border-primary/50 hover:shadow-sm transition-all"
        >
            <div className="flex items-start justify-between gap-3">
                <div
                    className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    style={{ backgroundColor: `${iconColor}18` }}
                >
                    <Icon className="h-5 w-5" style={{ color: iconColor }} />
                </div>
                <ArrowRight className="h-4 w-4 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity mt-0.5" />
            </div>

            <div className="flex-1 space-y-1">
                <p className="font-semibold text-sm">{title}</p>
                <p className="text-xs text-muted-foreground leading-relaxed">{description}</p>
            </div>

            <div>{status}</div>
        </Link>
    );
}

// ─── Section ──────────────────────────────────────────────────────────────────

function Section({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div>
            <h2 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground mb-3">{title}</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {children}
            </div>
        </div>
    );
}

// ─── Page ─────────────────────────────────────────────────────────────────────

export default function Integrations({ integrations }: { integrations: { api: { configured: boolean }; webhooks: { count: number }; smtp: { configured: boolean; host: string }; sms: { configured: boolean }; storage: { driver: string; configured?: boolean }; esign: { configured: boolean; name?: string } } }) {
    const { api, webhooks, smtp, sms, storage, esign } = integrations;

    const storageLabels: Record<string, string> = {
        local:  'Local disk',
        s3:     'Amazon S3',
        gcs:    'Google Cloud',
        azure:  'Azure Blob',
    };
    const storageLabel = storageLabels[String(storage.driver)] ?? String(storage.driver);

    return (
        <AuthenticatedLayout>
            <Head title="Integrations" />

            <div className="max-w-4xl mx-auto space-y-8">
                <div>
                    <h1 className="ui-page-title">Integrations</h1>
                    <p className="text-muted-foreground mt-1">
                        Connect Vouch with the tools and services your team uses.
                    </p>
                </div>

                {/* Automation & Data */}
                <Section title="Automation &amp; Data">
                    <IntegrationCard
                        icon={Key}
                        iconColor="hsl(var(--info))"
                        title="API"
                        description="Access the REST API programmatically to create and manage templates, submissions, and signers."
                        href="/settings/api-tokens"
                        status={
                            <StatusBadge configured={api.configured} label="Token generated" />
                        }
                    />
                    <IntegrationCard
                        icon={Webhook}
                        iconColor="hsl(var(--warning))"
                        title="Webhooks"
                        description="Receive real-time HTTP notifications when documents are viewed, signed, completed, or declined."
                        href="/settings/webhooks"
                        status={
                            webhooks.count > 0
                                ? <StatusBadge configured label={`${webhooks.count} endpoint${webhooks.count !== 1 ? 's' : ''}`} />
                                : <StatusBadge configured={false} />
                        }
                    />
                    <IntegrationCard
                        icon={Code2}
                        iconColor="hsl(var(--accent))"
                        title="Embedding"
                        description="Embed signing forms directly into your website or application using an iframe or JavaScript snippet."
                        href="/settings/api-tokens"
                        status={<StatusBadge configured label="Always available" />}
                    />
                </Section>

                {/* Communication */}
                <Section title="Communication">
                    <IntegrationCard
                        icon={Mail}
                        iconColor="hsl(var(--info))"
                        title="Email (SMTP)"
                        description="Send document invitations and notifications through your own email provider instead of the default mailer."
                        href="/settings/smtp"
                        status={
                            smtp.configured
                                ? <StatusBadge configured label={smtp.host} />
                                : <StatusBadge configured={false} />
                        }
                    />
                    <IntegrationCard
                        icon={MessageSquare}
                        iconColor="hsl(var(--success))"
                        title="SMS (Twilio)"
                        description="Send signing invitations and reminders via SMS using your Twilio account credentials."
                        href="/settings/sms"
                        status={<StatusBadge configured={sms.configured} label="Twilio connected" />}
                    />
                </Section>

                {/* Storage & Security */}
                <Section title="Storage &amp; Security">
                    <IntegrationCard
                        icon={HardDrive}
                        iconColor="hsl(var(--muted-foreground))"
                        title="Storage"
                        description="Store signed documents and attachments on Amazon S3, Google Cloud Storage, or Azure Blob instead of local disk."
                        href="/settings/storage"
                        status={
                            storage.configured
                                ? <StatusBadge configured label={storageLabel} />
                                : <StatusBadge configured={false} label={storageLabel} />
                        }
                    />
                    <IntegrationCard
                        icon={ShieldCheck}
                        iconColor="hsl(var(--destructive))"
                        title="E-Sign Certificate"
                        description="Upload a PKCS#12 certificate to apply a verifiable digital signature to all completed PDF documents."
                        href="/settings/esign"
                        status={
                            esign.configured
                                ? <StatusBadge configured label={esign.name ?? 'Certificate uploaded'} />
                                : <StatusBadge configured={false} />
                        }
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
