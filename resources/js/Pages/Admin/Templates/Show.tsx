import { Select, SelectTrigger, SelectContent, SelectValue, SelectItem } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import {
    Plus, Search, MoreVertical, Download, Eye, Archive, Clock, CheckCircle2,
    XCircle, List, Pencil, Share2, FileText, Send, ChevronLeft, Users, Code2,
    Settings, X,
} from 'lucide-react';
import { useState, useRef } from 'react';
import { Checkbox } from '@/Components/ui/checkbox';

const STATUS_CONFIG = {
    completed: { label: 'Completed', variant: 'success',     icon: CheckCircle2 },
    pending:   { label: 'Pending',   variant: 'pending',     icon: Clock },
    declined:  { label: 'Declined',  variant: 'destructive', icon: XCircle },
    expired:   { label: 'Expired',   variant: 'secondary',   icon: XCircle },
    sent:      { label: 'Sent',      variant: 'info',        icon: Send },
    opened:    { label: 'Opened',    variant: 'warning',     icon: Eye },
    awaiting:  { label: 'Awaiting',  variant: 'secondary',   icon: Clock },
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
    const isSingle = submitters.length === 1;

    function handleArchive() {
        router.delete(`/submissions/${submission.id}`, { preserveScroll: true });
    }

    return (
        <div className="rounded-md border bg-card">
            <div className="px-4 py-3 sm:px-5 sm:py-4">
                {isSingle ? (
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 sm:gap-4">
                        <div className="flex items-center gap-2 min-w-0 flex-1">
                            <StatusBadge status={submitters[0]?.status} />
                            <Link href={`/submissions/${submission.id}`} className="font-medium text-sm hover:underline truncate">
                                {submitters[0]?.display_name || submitters[0]?.email || 'Unknown'}
                            </Link>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                            {submitters[0]?.completed_at && (
                                <a href={`/s/${submitters[0].slug}/download`} target="_blank" rel="noopener noreferrer">
                                    <Button size="sm" className="gap-1.5 h-8 text-xs">
                                        <Download className="h-3.5 w-3.5" />Download
                                    </Button>
                                </a>
                            )}
                            {!submitters[0]?.completed_at && !submission.archived_at && (
                                <Button size="sm" variant="outline" className="h-8 gap-1.5 text-xs"
                                    onClick={() => navigator.clipboard?.writeText(`${window.location.origin}/s/${submitters[0]?.slug}`)}>
                                    <Share2 className="h-3.5 w-3.5" />
                                    <span className="hidden sm:inline">Copy Link</span>
                                </Button>
                            )}
                            <Button size="sm" variant="outline" className="h-8 text-xs" asChild>
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
                ) : (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between gap-2">
                            <StatusBadge status={submission.status} />
                            <div className="flex items-center gap-2 shrink-0">
                                <Button size="sm" variant="outline" className="h-8 text-xs" asChild>
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
                        {submitters.map((s) => (
                            <div key={s.id} className="flex items-center gap-2 text-sm flex-wrap">
                                <StatusBadge status={s.status} />
                                <span className="font-medium truncate">{s.display_name || s.email}</span>
                            </div>
                        ))}
                    </div>
                )}
                <p className="text-xs text-muted-foreground mt-2">
                    {new Date(submission.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                    {submission.created_by_user && ` · by ${submission.created_by_user.name}`}
                </p>
            </div>
        </div>
    );
}

export default function TemplatesShow({ template, submissions, counts, filters }) {
    const [search, setSearch] = useState(filters.q || '');
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const partyCount = template.submitters?.length || 1;
    const prefillableFields = template.prefillable_fields ?? [];
    const emptyParties = () => Array.from({ length: partyCount }, (_, i) => ({
        name: '', email: '', phone: '', values: {},
        auto_sign: template.submitters?.[i]?.auto_sign ?? false,
    }));
    const [parties, setParties] = useState(emptyParties);
    const [expireAt, setExpireAt] = useState('');
    const [preserveOrder, setPreserveOrder] = useState(false);
    const [bulkMode, setBulkMode] = useState(false);
    const [bulkText, setBulkText] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const submittingRef = useRef(false);

    const orderEnforced = template.preferences?.submitters_order === 'preserved'
        || template.submitters?.some((s) => s.order != null);
    const showOrderToggle = partyCount > 1 && !orderEnforced;

    function updateParty(index, field, value) {
        setParties((prev) => prev.map((p, i) => i === index ? { ...p, [field]: value } : p));
    }
    function updatePartyValue(partyIndex, fieldUuid, value) {
        setParties((prev) => prev.map((p, i) => i === partyIndex
            ? { ...p, values: { ...p.values, [fieldUuid]: value } }
            : p
        ));
    }

    const currentStatus = filters.status || '';
    const items = submissions?.data ?? [];

    const tabs = [
        { key: '',          label: 'All',       count: counts.all,       icon: List },
        { key: 'pending',   label: 'Pending',   count: counts.pending,   icon: Clock },
        { key: 'completed', label: 'Completed', count: counts.completed, icon: CheckCircle2 },
    ];

    function handleSearch(value) {
        setSearch(value);
        router.get(`/templates/${template.id}`, { q: value || undefined, status: currentStatus || undefined }, { preserveState: true, replace: true });
    }

    function handleTabChange(status) {
        router.get(`/templates/${template.id}`, { status: status || undefined, q: search || undefined }, { preserveState: true, replace: true });
    }

    function handleAddRecipient(e) {
        e.preventDefault();
        if (submittingRef.current) return;
        submittingRef.current = true;
        setSubmitting(true);

        const finish = () => { submittingRef.current = false; setSubmitting(false); };

        if (bulkMode) {
            const rows = bulkText.trim().split('\n').map((line) => {
                const [email, name] = line.split(',').map((s) => s.trim());
                return { email, name };
            }).filter((r) => r.email);
            router.post(`/templates/${template.id}/bulk-submissions`, { submitters: rows }, {
                onSuccess: () => { setShowAddDialog(false); setBulkText(''); setBulkMode(false); },
                onFinish: finish,
            });
        } else {
            router.post(`/templates/${template.id}/submissions`, {
                submitters: parties,
                preserve_order: orderEnforced ? true : preserveOrder,
                expire_at: expireAt || undefined,
            }, {
                onSuccess: () => { setShowAddDialog(false); setParties(emptyParties()); setExpireAt(''); setPreserveOrder(false); },
                onFinish: finish,
            });
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={template.name} />
            <div className="max-w-5xl mx-auto px-4 md:px-6 py-6 md:py-8">
                <Link href="/templates" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-4">
                    <ChevronLeft className="h-4 w-4" />Templates
                </Link>

                {/* Template header */}
                <div className="flex items-start justify-between gap-3 mb-6 md:mb-8">
                    <div className="flex items-center gap-3 min-w-0">
                        <div className="p-2 rounded-md bg-primary/10 shrink-0">
                            <FileText className="h-5 w-5 md:h-6 md:w-6 text-primary" />
                        </div>
                        <div className="min-w-0">
                            <h1 className="text-xl md:text-2xl font-bold truncate">{template.name}</h1>
                            <p className="text-sm text-muted-foreground truncate">by {template.author?.name || template.author?.email}</p>
                        </div>
                    </div>

                    {/* Desktop action bar */}
                    <div className="hidden md:flex items-center gap-2 shrink-0">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={`/templates/${template.id}/archived-submissions`} className="gap-1.5 text-muted-foreground">
                                <Archive className="h-4 w-4" />Archived
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/templates/${template.id}/form-preview`} className="gap-1.5" target="_blank" rel="noopener noreferrer">
                                <Eye className="h-4 w-4" />Preview
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/templates/${template.id}/embed`} className="gap-1.5">
                                <Code2 className="h-4 w-4" />Embed
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/templates/${template.id}/builder`} className="gap-1.5">
                                <Pencil className="h-4 w-4" />Edit
                            </Link>
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/templates/${template.id}/preferences`} className="gap-1.5">
                                <Settings className="h-4 w-4" />Preferences
                            </Link>
                        </Button>
                        <Button size="sm" className="gap-2" onClick={() => setShowAddDialog(true)}>
                            <Plus className="h-4 w-4" />Add Recipients
                        </Button>
                    </div>

                    {/* Mobile action bar: primary actions + overflow menu */}
                    <div className="md:hidden flex items-center gap-2 shrink-0">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/templates/${template.id}/builder`} className="gap-1.5">
                                <Pencil className="h-4 w-4" />
                                <span className="hidden xs:inline">Edit</span>
                            </Link>
                        </Button>
                        <Button size="sm" className="gap-1.5" onClick={() => setShowAddDialog(true)}>
                            <Plus className="h-4 w-4" />
                            <span>Send</span>
                        </Button>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="icon" className="h-8 w-8 shrink-0">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/form-preview`} target="_blank" rel="noopener noreferrer" className="flex items-center gap-2">
                                        <Eye className="h-4 w-4" />Preview Form
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/embed`} className="flex items-center gap-2">
                                        <Code2 className="h-4 w-4" />Embed
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/preferences`} className="flex items-center gap-2">
                                        <Settings className="h-4 w-4" />Preferences
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/archived-submissions`} className="flex items-center gap-2 text-muted-foreground">
                                        <Archive className="h-4 w-4" />Archived Submissions
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                {/* Submissions section */}
                <div className="flex items-center justify-between mb-4 gap-3">
                    <h2 className="text-lg md:text-xl font-bold shrink-0">Submissions</h2>
                    <div className="flex items-center gap-2">
                        {(items.length > 0 || filters.q) && (
                            <>
                                {/* Desktop search */}
                                <div className="relative hidden sm:block">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                    <Input placeholder="Search…" className="pl-9 w-44 h-9" value={search} onChange={(e) => handleSearch(e.target.value)} />
                                </div>
                                {/* Mobile search toggle */}
                                <Button variant="ghost" size="icon" className="sm:hidden h-9 w-9"
                                    onClick={() => setMobileSearchOpen((o) => !o)}>
                                    {mobileSearchOpen ? <X className="h-4 w-4" /> : <Search className="h-4 w-4" />}
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Mobile search bar */}
                {mobileSearchOpen && (
                    <div className="sm:hidden mb-3">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input autoFocus placeholder="Search submissions…" className="pl-9" value={search} onChange={(e) => handleSearch(e.target.value)} />
                        </div>
                    </div>
                )}

                {/* Status tabs — horizontally scrollable */}
                {counts.all > 0 && (
                    <div className="overflow-x-auto scrollbar-none -mx-4 px-4 md:mx-0 md:px-0 mb-4">
                        <div className="flex gap-2 w-max md:w-auto md:flex-wrap">
                            {tabs.map(({ key, label, count, icon: Icon }) => (
                                <Button variant="ghost" size="sm" key={key} onClick={() => handleTabChange(key)}
                                    className={`flex items-center gap-2 px-3 h-10 rounded-md border text-sm font-medium transition-colors whitespace-nowrap ${
                                        currentStatus === key
                                            ? 'border-foreground text-foreground bg-accent'
                                            : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'
                                    }`}>
                                    <Icon className="h-4 w-4 shrink-0" />
                                    {label}
                                    <span className="rounded-full border px-1.5 py-0.5 text-xs">{count}</span>
                                </Button>
                            ))}
                        </div>
                    </div>
                )}

                {items.length > 0 ? (
                    <div className="space-y-3">
                        {items.map((s) => <SubmissionRow key={s.id} submission={s} />)}
                    </div>
                ) : (
                    <div className="rounded-md border bg-muted/30 py-12 sm:py-16 text-center">
                        <Users className="h-10 w-10 mx-auto text-muted-foreground mb-3" />
                        <p className="text-lg font-semibold">No submissions yet</p>
                        {!template.archived_at && (
                            <div className="mt-4 flex justify-center">
                                <Button onClick={() => setShowAddDialog(true)} className="gap-2">
                                    <Plus className="h-4 w-4" />Send to Recipients
                                </Button>
                            </div>
                        )}
                    </div>
                )}

                {submissions?.last_page > 1 && (
                    <div className="flex items-center justify-between mt-6">
                        <p className="text-sm text-muted-foreground">{submissions.from}–{submissions.to} of {submissions.total}</p>
                        <div className="flex gap-2">
                            {submissions.current_page > 1 && <Button variant="outline" size="sm" asChild><Link href={submissions.prev_page_url}>Previous</Link></Button>}
                            {submissions.current_page < submissions.last_page && <Button variant="outline" size="sm" asChild><Link href={submissions.next_page_url}>Next</Link></Button>}
                        </div>
                    </div>
                )}
            </div>

            {/* Add Recipients dialog */}
            <AlertDialog open={showAddDialog} onOpenChange={(open) => { if (!submitting) setShowAddDialog(open); }}>
                <AlertDialogContent className="max-w-md w-[calc(100vw-2rem)] sm:w-full max-h-[90dvh] overflow-y-auto">
                    <AlertDialogHeader>
                        <AlertDialogTitle>Add Recipients</AlertDialogTitle>
                        <AlertDialogDescription>Send "{template.name}" to one or more recipients.</AlertDialogDescription>
                    </AlertDialogHeader>

                    {/* Mode toggle */}
                    <div className="flex rounded-md border overflow-hidden text-sm font-medium">
                        <Button variant="ghost" size="sm" type="button" onClick={() => setBulkMode(false)}
                            className={`flex-1 py-2 transition-colors ${!bulkMode ? 'bg-foreground text-background' : 'hover:bg-muted'}`}>
                            Single
                        </Button>
                        <Button variant="ghost" size="sm" type="button" onClick={() => setBulkMode(true)}
                            className={`flex-1 py-2 transition-colors ${bulkMode ? 'bg-foreground text-background' : 'hover:bg-muted'}`}>
                            Bulk
                        </Button>
                    </div>

                    <form onSubmit={handleAddRecipient} className="space-y-3">
                        {bulkMode ? (
                            <>
                                <Textarea
                                    className="w-full rounded-md border px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
                                    rows={6}
                                    placeholder={"One per line: email, name (optional)\njohn@example.com, John\njane@example.com"}
                                    value={bulkText}
                                    onChange={(e) => setBulkText(e.target.value)}
                                    required
                                />
                                <p className="text-xs text-muted-foreground">Format: email, name — one per line</p>
                            </>
                        ) : (
                            <>
                                {parties.map((party, i) => (
                                    <div key={i} className={`space-y-2 ${i > 0 ? 'pt-3 border-t' : ''}`}>
                                        {parties.length > 1 && (
                                            <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
                                                {template.submitters?.[i]?.name || `Signer ${i + 1}`}
                                            </p>
                                        )}
                                        <Input type="text" placeholder="Full name (optional)" value={party.name} onChange={(e) => updateParty(i, 'name', e.target.value)} />
                                        <Input type="email" placeholder="Email address" value={party.email} onChange={(e) => updateParty(i, 'email', e.target.value)} />
                                        <Input type="tel" placeholder="Phone number (optional)" value={party.phone} onChange={(e) => updateParty(i, 'phone', e.target.value)} />
                                        <Label className="flex items-center gap-2 cursor-pointer select-none">
                                            <Checkbox
                                                checked={party.auto_sign ?? false}
                                                onCheckedChange={(v) => updateParty(i, 'auto_sign', v)}
                                            />
                                            <span className="text-sm text-muted-foreground">Auto-sign (uses saved signature)</span>
                                        </Label>
                                        {prefillableFields
                                            .filter((f) => f.submitter_uuid === template.submitters?.[i]?.uuid)
                                            .map((f) => (
                                                <div key={f.uuid}>
                                                    <Label className="text-xs text-muted-foreground block mb-1">{f.name || f.type}</Label>
                                                    {f.type === 'select' ? (
                                                        <Select
                                                            value={String(party.values?.[f.uuid] || '__vouch_no_selection__')}
                                                            onValueChange={(value) => updatePartyValue(i, f.uuid, value === '__vouch_no_selection__' ? '' : value)}
                                                        >
                                                            <SelectTrigger className="w-full"><SelectValue placeholder="— select —" /></SelectTrigger>
                                                            <SelectContent>
                                                                <SelectItem value="__vouch_no_selection__">— select —</SelectItem>
                                                                {(f.options ?? []).filter((o) => o.value && o.value !== '__vouch_no_selection__').map((o) => (
                                                                    <SelectItem key={o.uuid} value={o.value}>{o.value}</SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    ) : f.type === 'checkbox' ? (
                                                        <Label className="flex items-center gap-2 text-sm cursor-pointer">
                                                            <Checkbox
                                                                checked={!!party.values?.[f.uuid]}
                                                                onCheckedChange={(v) => updatePartyValue(i, f.uuid, v)}
                                                            />
                                                            Pre-check
                                                        </Label>
                                                    ) : (
                                                        <Input
                                                            type={f.type === 'number' ? 'number' : f.type === 'date' ? 'date' : 'text'}
                                                            placeholder={`Prefill ${f.name || f.type} (optional)`}
                                                            value={party.values?.[f.uuid] ?? ''}
                                                            onChange={(e) => updatePartyValue(i, f.uuid, e.target.value)}
                                                        />
                                                    )}
                                                </div>
                                            ))
                                        }
                                    </div>
                                ))}
                                {showOrderToggle && (
                                    <Label className="flex items-center gap-2 cursor-pointer pt-1">
                                        <Checkbox
                                            checked={preserveOrder}
                                            onCheckedChange={setPreserveOrder}
                                        />
                                        <span className="text-sm font-medium">Preserve signing order</span>
                                        <span className="text-xs text-muted-foreground hidden sm:inline">(sequential, not all at once)</span>
                                    </Label>
                                )}
                                <div>
                                    <Label className="text-xs text-muted-foreground block mb-1">Expiration date (optional)</Label>
                                    <Input type="date" value={expireAt} onChange={(e) => setExpireAt(e.target.value)} />
                                </div>
                            </>
                        )}
                        <AlertDialogFooter className="flex-col-reverse sm:flex-row gap-2">
                            <AlertDialogCancel type="button" className="w-full sm:w-auto" disabled={submitting}>Cancel</AlertDialogCancel>
                            <Button type="submit" className="w-full sm:w-auto" disabled={submitting}>
                                {submitting ? 'Sending…' : `Send Invitation${bulkMode ? 's' : ''}`}
                            </Button>
                        </AlertDialogFooter>
                    </form>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
