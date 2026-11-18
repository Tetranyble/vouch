import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { FileText, RotateCcw, Users } from 'lucide-react';

const STATUS_CONFIG = {
    completed: { label: 'Completed', variant: 'success' },
    pending: { label: 'Pending', variant: 'pending' },
    declined: { label: 'Declined', variant: 'destructive' },
    awaiting: { label: 'Awaiting', variant: 'secondary' },
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.awaiting;
    return <Badge variant={config.variant} className="uppercase text-xs font-semibold tracking-wide">{config.label}</Badge>;
}

export default function SubmissionsArchived({ submissions }) {
    const items = submissions?.data ?? [];

    function handleRestore(id) {
        router.put(`/submissions/${id}/restore`, {}, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Archived Submissions" />
            <div className="max-w-5xl mx-auto px-4 md:px-6 py-8">
                <div className="flex items-center gap-3 mb-6">
                    <Link href="/submissions" className="text-sm text-muted-foreground hover:text-foreground">
                        ← Back to Submissions
                    </Link>
                </div>

                <div className="flex items-center gap-3 mb-6">
                    <h1 className="ui-page-title">Archived Submissions</h1>
                    {submissions?.total > 0 && (
                        <Badge variant="secondary">{submissions.total}</Badge>
                    )}
                </div>

                {items.length > 0 ? (
                    <>
                        <div className="space-y-3">
                            {items.map((submission) => (
                                <div key={submission.id} className="rounded-md border bg-card flex flex-col sm:flex-row">
                                    <div className="px-5 py-4 sm:w-52 flex-shrink-0 bg-muted/40 rounded-t-xl sm:rounded-l-xl sm:rounded-tr-none flex sm:flex-col justify-between gap-4 sm:gap-0">
                                        <div className="flex items-start gap-2">
                                            <FileText className="h-4 w-4 mt-0.5 flex-shrink-0 text-muted-foreground" />
                                            <span className="text-sm font-medium line-clamp-2">
                                                {submission.template_name || 'Unnamed'}
                                            </span>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            Archived {submission.archived_at
                                                ? new Date(submission.archived_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
                                                : '—'}
                                        </div>
                                    </div>

                                    <div className="flex-1 px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                                        <div className="space-y-1.5 flex-1">
                                            {(submission.submitters ?? []).map((s) => (
                                                <div key={s.id} className="flex items-center gap-2 flex-wrap">
                                                    <StatusBadge status={s.status} />
                                                    <span className="font-medium">{s.display_name || s.email || 'Unknown'}</span>
                                                </div>
                                            ))}
                                        </div>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            className="h-8 gap-1.5"
                                            onClick={() => handleRestore(submission.id)}
                                        >
                                            <RotateCcw className="h-3.5 w-3.5" />Restore
                                        </Button>
                                    </div>
                                </div>
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
                        <p className="text-muted-foreground text-sm mt-1">Archived submissions will appear here.</p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
