import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Search, MoreVertical, Archive, Clock, CheckCircle2, XCircle, List, Send, Eye, FileText, Users, Download, X } from 'lucide-react';
import { useState } from 'react';

const STATUS_CONFIG = {
    completed: { label: 'Completed', variant: 'success' },
    pending:   { label: 'Pending',   variant: 'pending' },
    declined:  { label: 'Declined',  variant: 'destructive' },
    expired:   { label: 'Expired',   variant: 'secondary' },
    sent:      { label: 'Sent',      variant: 'info' },
    opened:    { label: 'Opened',    variant: 'warning' },
    awaiting:  { label: 'Awaiting',  variant: 'secondary' },
};

function StatusBadge({ status }) {
    const config = STATUS_CONFIG[status] || STATUS_CONFIG.awaiting;
    return (
        <Badge variant={config.variant} className="uppercase text-xs font-semibold tracking-wide shrink-0">
            {config.label}
        </Badge>
    );
}

function SubmissionRow({ submission }) {
    const submitters = submission.submitters ?? [];

    function handleArchive() {
        router.delete(`/submissions/${submission.id}`, { preserveScroll: true });
    }

    return (
        <div className="rounded-md border bg-card flex flex-col sm:flex-row">
            {/* Left: template info */}
            <Link
                href={submission.template_id ? `/templates/${submission.template_id}` : `/submissions/${submission.id}`}
                className="px-4 py-3 sm:px-5 sm:py-4 sm:w-48 md:w-52 flex-shrink-0 bg-muted/40 rounded-t-xl sm:rounded-l-xl sm:rounded-tr-none flex sm:flex-col justify-between gap-3 sm:gap-0 hover:bg-muted/60 transition-colors"
            >
                <div className="flex items-start gap-2 min-w-0">
                    <FileText className="h-4 w-4 mt-0.5 shrink-0 text-muted-foreground" />
                    <span className="text-sm font-medium line-clamp-2 leading-snug">
                        {submission.template_name || submission.name || 'Unnamed'}
                    </span>
                </div>
                <div className="text-xs text-muted-foreground shrink-0">
                    {new Date(submission.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                </div>
            </Link>

            {/* Right: submitters + actions */}
            <div className="flex-1 px-4 py-3 sm:px-5 sm:py-4 flex items-start sm:items-center justify-between gap-3 min-w-0">
                <div className="space-y-1.5 flex-1 min-w-0">
                    {submitters.map((s) => (
                        <div key={s.id} className="flex items-center gap-2 flex-wrap">
                            <StatusBadge status={s.status} />
                            <Link href={`/submissions/${submission.id}`} className="font-medium hover:underline text-sm truncate">
                                {s.display_name || s.email || 'Unknown'}
                            </Link>
                        </div>
                    ))}
                </div>
                <div className="flex items-center gap-1.5 shrink-0">
                    <Button size="sm" variant="outline" className="h-8 px-3 text-xs" asChild>
                        <Link href={`/submissions/${submission.id}`}>View</Link>
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                <MoreVertical className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem className="text-destructive focus:text-destructive" onClick={handleArchive}>
                                <Archive className="h-4 w-4 mr-2" />Archive
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>
    );
}

export default function SubmissionsIndex({ submissions, counts, filters }) {
    const [search, setSearch] = useState(filters.q || '');
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
    const currentStatus = filters.status || '';
    const items = submissions?.data ?? [];

    const tabs = [
        { key: '',          label: 'All',       count: counts.all,       icon: List },
        { key: 'pending',   label: 'Pending',   count: counts.pending,   icon: Clock },
        { key: 'completed', label: 'Completed', count: counts.completed, icon: CheckCircle2 },
        { key: 'declined',  label: 'Declined',  count: counts.declined,  icon: XCircle },
    ];

    function handleSearch(value) {
        setSearch(value);
        router.get('/submissions', { q: value || undefined, status: currentStatus || undefined }, { preserveState: true, replace: true });
    }

    function handleTabChange(status) {
        router.get('/submissions', { status: status || undefined, q: search || undefined }, { preserveState: true, replace: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Submissions" />
            <div className="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8">

                {/* Header */}
                <div className="flex items-center justify-between mb-4 md:mb-6 gap-3">
                    <div className="flex items-center gap-2 md:gap-3 min-w-0">
                        <h1 className="ui-page-title truncate">Submissions</h1>
                        {submissions?.total > 0 && (
                            <Badge variant="secondary" className="shrink-0">{submissions.total}</Badge>
                        )}
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {/* Desktop search */}
                        {(items.length > 0 || filters.q) && (
                            <div className="relative hidden sm:block">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search…"
                                    className="pl-9 w-48 md:w-56"
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                />
                            </div>
                        )}
                        {/* Mobile search toggle */}
                        {(items.length > 0 || filters.q) && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="sm:hidden h-9 w-9"
                                onClick={() => setMobileSearchOpen((o) => !o)}
                            >
                                {mobileSearchOpen ? <X className="h-4 w-4" /> : <Search className="h-4 w-4" />}
                            </Button>
                        )}
                        {items.length > 0 && (
                            <a
                                href={`/submissions/export${currentStatus ? `?status=${currentStatus}` : ''}`}
                                className="inline-flex items-center gap-1.5 h-9 px-3 rounded-md border text-sm font-medium hover:bg-muted transition-colors"
                            >
                                <Download className="h-4 w-4" />
                                <span className="hidden sm:inline">Export CSV</span>
                            </a>
                        )}
                    </div>
                </div>

                {/* Mobile search bar */}
                {mobileSearchOpen && (
                    <div className="sm:hidden mb-4">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                autoFocus
                                placeholder="Search submissions…"
                                className="pl-9"
                                value={search}
                                onChange={(e) => handleSearch(e.target.value)}
                            />
                        </div>
                    </div>
                )}

                {/* Status tabs — horizontally scrollable on mobile */}
                {counts.all > 0 && (
                    <div className="overflow-x-auto scrollbar-none -mx-4 px-4 md:mx-0 md:px-0 mb-5">
                        <div className="flex gap-2 w-max md:w-auto md:flex-wrap">
                            {tabs.map(({ key, label, count, icon: Icon }) => (
                                <Button variant="ghost" size="sm"
                                    key={key}
                                    onClick={() => handleTabChange(key)}
                                    className={`flex items-center gap-2 px-3 h-10 rounded-md border text-sm font-medium transition-colors whitespace-nowrap ${
                                        currentStatus === key
                                            ? 'border-foreground text-foreground bg-accent'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'
                                    }`}
                                >
                                    <Icon className="h-4 w-4 shrink-0" />
                                    <span>{label}</span>
                                    <span className="rounded-full border px-1.5 py-0.5 text-xs">{count}</span>
                                </Button>
                            ))}
                        </div>
                    </div>
                )}

                {items.length > 0 ? (
                    <>
                        <div className="space-y-3">
                            {items.map((s) => <SubmissionRow key={s.id} submission={s} />)}
                        </div>
                        {submissions?.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6">
                                <p className="text-sm text-muted-foreground">{submissions.from}–{submissions.to} of {submissions.total}</p>
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
                    <div className="border-2 border-dashed rounded-md py-16 text-center">
                        <Users className="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                        <p className="text-lg font-semibold">
                            {filters.q ? 'No submissions found' : 'No submissions yet'}
                        </p>
                    </div>
                )}

                <div className="mt-4 text-center">
                    <Link href="/submissions-archived" className="text-sm text-muted-foreground hover:text-foreground underline-offset-4 hover:underline">
                        View archived submissions
                    </Link>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
