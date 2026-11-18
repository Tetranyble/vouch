import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import {
    Archive, ChevronLeft, Clock, CheckCircle2, XCircle, Send, Eye, RotateCcw, Trash2, Users,
} from 'lucide-react';
import { useState } from 'react';

const STATUS_CONFIG = {
    completed: { label: 'Completed', variant: 'success', icon: CheckCircle2 },
    pending: { label: 'Pending', variant: 'pending', icon: Clock },
    declined: { label: 'Declined', variant: 'destructive', icon: XCircle },
    expired: { label: 'Expired', variant: 'secondary', icon: XCircle },
    sent: { label: 'Sent', variant: 'info', icon: Send },
    opened: { label: 'Opened', variant: 'warning', icon: Eye },
    awaiting: { label: 'Awaiting', variant: 'secondary', icon: Clock },
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.awaiting;
    return (
        <Badge variant={config.variant} className="uppercase text-xs font-semibold tracking-wide">
            {config.label}
        </Badge>
    );
}

function ArchivedSubmissionCard({ submission, templateId }) {
    const [confirmDeleteId, setConfirmDeleteId] = useState<number | string | null>(null);
    const submitters = submission.submitters ?? [];
    const isSingle = submitters.length === 1;

    function handleRestore() {
        router.put(`/templates/${templateId}/archived-submissions/${submission.id}/restore`, {}, { preserveScroll: true });
    }

    function handleDelete() {
        router.delete(`/templates/${templateId}/archived-submissions/${submission.id}`, {
            preserveScroll: true,
            onSuccess: () => setConfirmDeleteId(null),
        });
    }

    return (
        <>
            <div className="rounded-md border bg-card flex flex-col sm:flex-row">
                <div className="flex-1 px-5 py-4">
                    {isSingle ? (
                        <div className="flex items-center justify-between gap-4 flex-wrap">
                            <div className="flex items-center gap-3">
                                <StatusBadge status={submitters[0]?.status} />
                                <span className="font-medium text-base">
                                    {submitters[0]?.display_name || submitters[0]?.email || 'Unknown'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={handleRestore}>
                                    <RotateCcw className="h-3.5 w-3.5" />Restore
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    className="h-8 gap-1.5 text-destructive border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                    onClick={() => setConfirmDeleteId(submission.id)}
                                >
                                    <Trash2 className="h-3.5 w-3.5" />Delete
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <StatusBadge status={submission.status} />
                                <div className="flex items-center gap-2">
                                    <Button size="sm" variant="outline" className="h-8 gap-1.5" onClick={handleRestore}>
                                        <RotateCcw className="h-3.5 w-3.5" />Restore
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        className="h-8 gap-1.5 text-destructive border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
                                        onClick={() => setConfirmDeleteId(submission.id)}
                                    >
                                        <Trash2 className="h-3.5 w-3.5" />Delete
                                    </Button>
                                </div>
                            </div>
                            {submitters.map((s) => (
                                <div key={s.id} className="flex items-center gap-2 text-sm">
                                    <StatusBadge status={s.status} />
                                    <span className="font-medium">{s.display_name || s.email}</span>
                                </div>
                            ))}
                        </div>
                    )}
                    <p className="text-xs text-muted-foreground mt-2">
                        Archived {submission.archived_at
                            ? new Date(submission.archived_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                            : '—'}
                        {' · '}Created {new Date(submission.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                        {submission.created_by_user && ` · by ${submission.created_by_user.name}`}
                    </p>
                </div>
            </div>

            <AlertDialog open={confirmDeleteId === submission.id} onOpenChange={(open) => !open && setConfirmDeleteId(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete submission?</AlertDialogTitle>
                        <AlertDialogDescription>
                            This will permanently delete the submission. This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            onClick={handleDelete}
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

export default function TemplateArchivedSubmissions({ template, submissions }) {
    const items = submissions?.data ?? [];

    return (
        <AuthenticatedLayout>
            <Head title={`Archived Submissions — ${template.name}`} />
            <div className="max-w-5xl mx-auto px-4 md:px-6 py-8">
                <Link
                    href={`/templates/${template.id}`}
                    className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-4"
                >
                    <ChevronLeft className="h-4 w-4" />{template.name}
                </Link>

                <div className="flex items-center gap-3 mb-8">
                    <div className="p-2 rounded-md bg-muted">
                        <Archive className="h-6 w-6 text-muted-foreground" />
                    </div>
                    <div>
                        <h1 className="ui-page-title">Archived Submissions</h1>
                        <p className="text-sm text-muted-foreground">{template.name}</p>
                    </div>
                    {submissions?.total > 0 && (
                        <Badge variant="secondary" className="ml-1">{submissions.total}</Badge>
                    )}
                </div>

                {items.length > 0 ? (
                    <>
                        <div className="space-y-3">
                            {items.map((s) => (
                                <ArchivedSubmissionCard key={s.id} submission={s} templateId={template.id} />
                            ))}
                        </div>

                        {submissions?.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <p className="text-sm text-muted-foreground">
                                    {submissions.from}–{submissions.to} of {submissions.total}
                                </p>
                                <div className="flex gap-2">
                                    {submissions.current_page > 1 && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={submissions.prev_page_url}>Previous</Link>
                                        </Button>
                                    )}
                                    {submissions.current_page < submissions.last_page && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={submissions.next_page_url}>Next</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <div className="border-2 border-dashed rounded-md py-20 text-center">
                        <Users className="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                        <p className="text-lg font-semibold">No archived submissions</p>
                        <p className="text-muted-foreground text-sm mt-1">Archived submissions for this template will appear here.</p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
