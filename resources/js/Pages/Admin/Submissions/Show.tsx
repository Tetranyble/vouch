import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import { Clock, CheckCircle2, XCircle, Download, Share2, Eye, FileText, Mail, Phone, Archive, Send, Check, RotateCcw } from 'lucide-react';
import { useState } from 'react';

const STATUS_CONFIG = {
    completed: { label: 'Completed', variant: 'success',     icon: CheckCircle2, color: 'text-success' },
    pending:   { label: 'Pending',   variant: 'pending',     icon: Clock,        color: 'text-warning' },
    declined:  { label: 'Declined',  variant: 'destructive', icon: XCircle,      color: 'text-destructive' },
    expired:   { label: 'Expired',   variant: 'secondary',   icon: XCircle,      color: 'text-muted-foreground' },
    sent:      { label: 'Sent',      variant: 'info',        icon: Send,         color: 'text-muted-foreground' },
    opened:    { label: 'Opened',    variant: 'warning',     icon: Eye,          color: 'text-warning' },
    awaiting:  { label: 'Awaiting',  variant: 'secondary',   icon: Clock,        color: 'text-muted-foreground' },
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.awaiting;
    const Icon = config.icon;
    return <Badge variant={config.variant} className="gap-1.5 px-3 py-1 text-sm"><Icon className="h-3.5 w-3.5" />{config.label}</Badge>;
}

function CopyLinkButton({ slug }) {
    const [copied, setCopied] = useState(false);

    async function handleCopy() {
        const url = `${window.location.origin}/s/${slug}`;
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                // Fallback for non-secure contexts
                const el = document.createElement('textarea');
                el.value = url;
                el.style.position = 'fixed';
                el.style.opacity = '0';
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
            }
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch (err) {
            console.error('Copy failed', err);
            prompt('Copy this link:', url);
        }
    }

    return (
        <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={handleCopy}>
            {copied ? <><Check className="h-3.5 w-3.5 text-success" />Copied!</> : <><Share2 className="h-3.5 w-3.5" />Copy Link</>}
        </Button>
    );
}

function SubmitterCard({ submitter }) {
    const config = STATUS_CONFIG[submitter.status] || STATUS_CONFIG.awaiting;
    const Icon = config.icon;
    const [resending, setResending] = useState(false);
    const [resubmitting, setResubmitting] = useState(false);

    function handleResend() {
        setResending(true);
        router.post(`/submitters/${submitter.id}/send-email`, {}, {
            preserveScroll: true,
            onFinish: () => setResending(false),
        });
    }

    function handleResubmit() {
        if (!confirm('This will reset the form for this submitter and send a new invitation link. Continue?')) return;
        setResubmitting(true);
        router.post(`/submitters/${submitter.id}/resubmit`, {}, {
            preserveScroll: true,
            onFinish: () => setResubmitting(false),
        });
    }

    return (
        <div className="rounded-md border bg-card p-4 sm:p-5">
            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div className="flex items-center gap-3 min-w-0">
                    <div className={`p-2 rounded-full bg-muted shrink-0 ${config.color}`}><Icon className="h-5 w-5" /></div>
                    <div className="min-w-0">
                        {submitter.role_name && (
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-0.5">{submitter.role_name}</p>
                        )}
                        <p className="font-semibold text-base truncate">{submitter.display_name || submitter.email || submitter.role_name || 'Signer'}</p>
                        {submitter.email && submitter.display_name && (
                            <p className="text-sm text-muted-foreground flex items-center gap-1 truncate"><Mail className="h-3 w-3 shrink-0" />{submitter.email}</p>
                        )}
                        {submitter.phone && (
                            <p className="text-sm text-muted-foreground flex items-center gap-1"><Phone className="h-3 w-3 shrink-0" />{submitter.phone}</p>
                        )}
                    </div>
                </div>
                <div className="flex items-center gap-2 flex-wrap shrink-0">
                    <StatusBadge status={submitter.status} />
                    {submitter.completed_at && (
                        <a href={`/s/${submitter.slug}/download`} target="_blank" rel="noopener noreferrer">
                            <Button size="sm" variant="outline" className="h-8 gap-1.5"><Download className="h-3.5 w-3.5" />Download</Button>
                        </a>
                    )}
                    {!submitter.completed_at && !submitter.declined_at && (
                        <>
                            <CopyLinkButton slug={submitter.slug} />
                            {submitter.email && (
                                <Button
                                    size="sm" variant="outline" className="h-8 gap-1.5"
                                    disabled={resending}
                                    onClick={handleResend}
                                >
                                    <Send className="h-3.5 w-3.5" />
                                    {resending ? 'Sending…' : 'Resend'}
                                </Button>
                            )}
                        </>
                    )}
                    <Button
                        size="sm" variant="outline" className="h-8 gap-1.5"
                        disabled={resubmitting}
                        onClick={handleResubmit}
                    >
                        <RotateCcw className="h-3.5 w-3.5" />
                        {resubmitting ? 'Resetting…' : 'Resubmit'}
                    </Button>
                </div>
            </div>
            <div className="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-xs text-muted-foreground">
                {submitter.sent_at && <div><p className="font-medium text-foreground">Sent</p><p>{new Date(submitter.sent_at).toLocaleDateString()}</p></div>}
                {submitter.opened_at && <div><p className="font-medium text-foreground">Opened</p><p>{new Date(submitter.opened_at).toLocaleDateString()}</p></div>}
                {submitter.completed_at && <div><p className="font-medium text-foreground">Completed</p><p>{new Date(submitter.completed_at).toLocaleDateString()}</p></div>}
                {submitter.declined_at && <div><p className="font-medium text-foreground">Declined</p><p>{new Date(submitter.declined_at).toLocaleDateString()}</p></div>}
            </div>
        </div>
    );
}

const EVENT_LABELS = {
    'form.viewed':    { label: 'Viewed',    color: 'bg-muted text-muted-foreground' },
    'form.started':   { label: 'Started',   color: 'bg-warning-soft text-warning-foreground' },
    'form.completed': { label: 'Completed', color: 'bg-success-soft text-success' },
    'form.declined':  { label: 'Declined',  color: 'bg-destructive-soft text-destructive' },
};

function EventsLog({ events }) {
    if (!events?.length) return null;
    return (
        <div className="mt-8">
            <h2 className="text-lg font-semibold mb-3">Activity Log</h2>
            <div className="rounded-md border bg-card overflow-hidden">
                {events.map((ev, i) => {
                    const cfg = EVENT_LABELS[ev.event_type] ?? { label: ev.event_type, color: 'bg-muted text-muted-foreground' };
                    return (
                        <div key={ev.id} className={`flex items-center gap-3 px-4 py-3 text-sm ${i > 0 ? 'border-t' : ''}`}>
                            <span className={`px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap ${cfg.color}`}>{cfg.label}</span>
                            <span className="text-muted-foreground truncate">
                                {ev.submitter_name || ev.submitter_email || 'Submitter'}
                            </span>
                            <span className="ml-auto text-xs text-muted-foreground whitespace-nowrap">
                                {new Date(ev.event_timestamp).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}
                            </span>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export default function SubmissionsShow({ submission, events }) {
    function handleArchive() {
        router.delete(`/submissions/${submission.id}`, {
            onSuccess: () => router.visit(submission.template_id ? `/templates/${submission.template_id}` : '/submissions'),
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title={`Submission #${submission.id}`} />
            <div className="max-w-3xl mx-auto px-4 md:px-6 py-8">
                <div className="flex items-center gap-2 text-sm text-muted-foreground mb-6">
                    {submission.template_id ? (
                        <><Link href="/templates" className="hover:text-foreground">Templates</Link>
                        <span>/</span>
                        <Link href={`/templates/${submission.template_id}`} className="hover:text-foreground">{submission.template_name}</Link></>
                    ) : (
                        <Link href="/submissions" className="hover:text-foreground">Submissions</Link>
                    )}
                    <span>/</span><span className="text-foreground">#{submission.id}</span>
                </div>

                <div className="flex items-start justify-between gap-4 mb-8 flex-wrap">
                    <div>
                        <div className="flex items-center gap-3 mb-1 flex-wrap">
                            <h1 className="ui-page-title">{submission.template_name || submission.name || `Submission #${submission.id}`}</h1>
                            <StatusBadge status={submission.status} />
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Created {new Date(submission.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}
                            {submission.created_by_user && ` by ${submission.created_by_user.name}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <a
                            href={`/submissions/${submission.id}/preview`}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="outline" size="sm" className="gap-1.5">
                                <Eye className="h-4 w-4" />Preview PDF
                            </Button>
                        </a>
                    <AlertDialog>
                        <AlertDialogTrigger asChild>
                            <Button variant="outline" size="sm" className="gap-1.5 text-destructive border-destructive/30 hover:bg-destructive/5">
                                <Archive className="h-4 w-4" />Archive
                            </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>Archive this submission?</AlertDialogTitle>
                                <AlertDialogDescription>The submission will be hidden from the main view. You can restore it later.</AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction onClick={handleArchive}>Archive</AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                    </div>
                </div>

                <h2 className="text-lg font-semibold mb-3">Signers / Recipients</h2>
                <div className="space-y-3">
                    {submission.submitters?.map((s) => <SubmitterCard key={s.id} submitter={s} />)}
                </div>

                {submission.template && (
                    <Card className="mt-8">
                        <CardHeader><CardTitle className="text-base">Template</CardTitle></CardHeader>
                        <CardContent className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <FileText className="h-4 w-4 text-muted-foreground" />
                                <span className="font-medium">{submission.template.name}</span>
                            </div>
                            <Button variant="outline" size="sm" asChild>
                                <Link href={`/templates/${submission.template.id}`}>View Template</Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}

                <EventsLog events={events} />
            </div>
        </AuthenticatedLayout>
    );
}
