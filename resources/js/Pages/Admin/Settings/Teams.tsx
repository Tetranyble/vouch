import type { VouchPageProps } from '@/types/vouch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { UserPlus, MoreHorizontal, Trash2, RefreshCw, Search, Pencil, ArchiveRestore, Puzzle } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';
import type * as React from 'react';
import { cn } from '@/lib/utils';

interface TeamMember {
    id: number;
    first_name: string | null;
    last_name: string | null;
    email: string;
    role: string;
    is_pending?: boolean;
    last_active_at?: string | null;
}

const ROLES = [
    { value: 'admin',  label: 'Admin',  description: 'Full access' },
    { value: 'user',   label: 'Member', description: 'Can create and manage' },
    { value: 'viewer', label: 'Viewer', description: 'Read-only access' },
];

function userInitials(first?: string | null, last?: string | null, email?: string | null) {
    const s = ((first?.[0] ?? '') + (last?.[0] ?? '')).toUpperCase();
    return s || (email?.[0]?.toUpperCase() ?? '?');
}

function relativeTime(iso?: string | null) {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1)  return 'Just now';
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24)  return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30) return `${days}d ago`;
    return new Date(iso).toLocaleDateString();
}

/* ─── Shared column header ──────────────────────────────────────────────────── */
function TableHeader() {
    return (
        <div className="hidden sm:flex items-center gap-3 px-4 py-2.5 border-b bg-muted/30 rounded-t-md text-xs font-medium text-muted-foreground uppercase tracking-wide">
            <div className="w-9 shrink-0" />
            <div className="flex-1">Member</div>
            <div className="w-24 shrink-0">Status</div>
            <div className="hidden lg:block w-24 shrink-0 text-right">Last session</div>
            <div className="w-32 shrink-0">Role</div>
            <div className="w-8 shrink-0" />
        </div>
    );
}

/* ─── Active member row ─────────────────────────────────────────────────────── */
function ActiveRow({ u, currentUserId, isAdmin, onEdit, onArchive }: { u: TeamMember; currentUserId: number; isAdmin: boolean; onEdit: (user: TeamMember) => void; onArchive: (user: TeamMember) => void }) {
    const isSelf = u.id === currentUserId;
    const roleLabel = ROLES.find(r => r.value === u.role)?.label ?? u.role;

    function handleRoleChange(newRole: string) {
        router.patch(route('settings.teams.role.update', u.id), { role: newRole }, {
            preserveScroll: true,
        });
    }

    function handleResend() {
        router.post(route('settings.teams.resend', u.id), {}, { preserveScroll: true });
    }

    return (
        <div className="flex items-center gap-3 py-3 px-1 group">
            <div className="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center text-sm font-semibold text-primary shrink-0 select-none">
                {userInitials(u.first_name, u.last_name, u.email)}
            </div>

            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="text-sm font-medium leading-none">
                        {[u.first_name, u.last_name].filter(Boolean).join(' ') || u.email}
                    </span>
                    {isSelf && <span className="text-xs text-muted-foreground">(you)</span>}
                </div>
                <p className="text-xs text-muted-foreground mt-0.5 truncate">{u.email}</p>
            </div>

            <div className="hidden sm:flex w-24 shrink-0">
                {u.is_pending ? (
                    <Badge variant="warning" className="text-xs">
                        Pending
                    </Badge>
                ) : (
                    <Badge variant="success" className="text-xs">
                        Active
                    </Badge>
                )}
            </div>

            <div className="hidden lg:block w-24 text-xs text-muted-foreground shrink-0 text-right">
                {relativeTime(u.last_active_at)}
            </div>

            <div className="w-32 shrink-0">
                {isAdmin && !isSelf ? (
                    <Select value={u.role} onValueChange={handleRoleChange}>
                        <SelectTrigger className="h-8 text-xs">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {ROLES.map(r => (
                                <SelectItem key={r.value} value={r.value} className="text-xs">{r.label}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <span className="text-xs text-muted-foreground">{roleLabel}</span>
                )}
            </div>

            <div className="w-8 shrink-0">
                {!isSelf && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon"
                                className="h-8 w-8 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity">
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            <DropdownMenuItem onClick={() => onEdit(u)}>
                                <Pencil className="h-4 w-4 mr-2" />Edit user
                            </DropdownMenuItem>
                            {u.is_pending && (
                                <DropdownMenuItem onClick={handleResend}>
                                    <RefreshCw className="h-4 w-4 mr-2" />Resend invitation
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                                onClick={() => onArchive(u)}
                                className="text-destructive focus:text-destructive"
                            >
                                <Trash2 className="h-4 w-4 mr-2" />Archive user
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>
        </div>
    );
}

/* ─── Archived member row ───────────────────────────────────────────────────── */
function ArchivedRow({ u, onUnarchive }: { u: TeamMember; onUnarchive: (user: TeamMember) => void }) {
    const roleLabel = ROLES.find(r => r.value === u.role)?.label ?? u.role;

    return (
        <div className="flex items-center gap-3 py-3 px-1 opacity-70">
            <div className="h-9 w-9 rounded-full bg-muted flex items-center justify-center text-sm font-semibold text-muted-foreground shrink-0 select-none">
                {userInitials(u.first_name, u.last_name, u.email)}
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium leading-none line-through text-muted-foreground truncate">
                    {[u.first_name, u.last_name].filter(Boolean).join(' ') || u.email}
                </p>
                <p className="text-xs text-muted-foreground mt-0.5 truncate">{u.email}</p>
            </div>
            <div className="hidden sm:flex w-24 shrink-0">
                <Badge variant="outline" className="text-xs text-muted-foreground">Archived</Badge>
            </div>
            <div className="hidden lg:block w-24 shrink-0" />
            <div className="w-32 shrink-0">
                <span className="text-xs text-muted-foreground">{roleLabel}</span>
            </div>
            <div className="w-8 shrink-0">
                <Button variant="ghost" size="icon" className="h-8 w-8" title="Restore user"
                    onClick={() => onUnarchive(u)}>
                    <ArchiveRestore className="h-4 w-4 text-muted-foreground hover:text-foreground" />
                </Button>
            </div>
        </div>
    );
}

/* ─── Integration (embedding) user row — read-only ─────────────────────────── */
function IntegrationRow({ u, onArchive }: { u: TeamMember; onArchive: (user: TeamMember) => void }) {
    // Strip the +integration_name part from the displayed email (matches Ruby mailer logic)
    const displayEmail = u.email.replace(/\+[^@]+@/, '@');
    const displayName  = [u.first_name, u.last_name].filter(Boolean).join(' ') || displayEmail;

    return (
        <div className="flex items-center gap-3 py-3 px-1 group">
            <div className="h-9 w-9 rounded-full bg-info-soft flex items-center justify-center shrink-0">
                <Puzzle className="h-4 w-4 text-info-soft-foreground" />
            </div>
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium leading-none truncate">{displayName}</p>
                <p className="text-xs text-muted-foreground mt-0.5 truncate">{displayEmail}</p>
            </div>
            <div className="hidden sm:flex w-24 shrink-0">
                <Badge variant="info" className="text-xs">
                    Embedding
                </Badge>
            </div>
            <div className="hidden lg:block w-24 text-xs text-muted-foreground shrink-0 text-right">
                {relativeTime(u.last_active_at)}
            </div>
            <div className="w-32 shrink-0">
                <span className="text-xs text-muted-foreground">Integration</span>
            </div>
            <div className="w-8 shrink-0">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon"
                            className="h-8 w-8 opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity">
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuItem
                            onClick={() => onArchive(u)}
                            className="text-destructive focus:text-destructive"
                        >
                            <Trash2 className="h-4 w-4 mr-2" />Archive
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}

/* ─── Edit user dialog ──────────────────────────────────────────────────────── */
function EditUserDialog({ user, currentUserId, open, onClose }: { user: TeamMember | null; currentUserId: number; open: boolean; onClose: () => void }) {
    const isSelf = user?.id === currentUserId;
    const form = useForm({
        first_name: '',
        last_name:  '',
        email:      '',
        role:       'user',
    });

    // Synchronize only when selecting another user; never update React state during render.
    useEffect(() => {
        if (user) {
            form.setData({
                first_name: user.first_name ?? '',
                last_name: user.last_name ?? '',
                email: user.email,
                role: user.role ?? 'user',
            });
        }
    }, [user?.id]);

    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        if (!user) return;
        form.patch(route('settings.teams.update', user.id), { onSuccess: onClose });
    }

    function sendPasswordReset(e: React.MouseEvent<HTMLButtonElement>) {
        e.preventDefault();
        if (!user) return;
        router.post(route('settings.teams.resend', user.id), {}, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    }

    return (
        <Dialog open={open} onOpenChange={v => !v && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Edit user</DialogTitle>
                    <DialogDescription>Update {user?.email}'s details.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="edit_first">First name</Label>
                            <Input id="edit_first" value={form.data.first_name}
                                onChange={e => form.setData('first_name', e.target.value)} />
                            {form.errors.first_name && <p className="text-sm text-destructive">{form.errors.first_name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="edit_last">Last name</Label>
                            <Input id="edit_last" value={form.data.last_name}
                                onChange={e => form.setData('last_name', e.target.value)} />
                            {form.errors.last_name && <p className="text-sm text-destructive">{form.errors.last_name}</p>}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="edit_email">Email address</Label>
                        <Input id="edit_email" type="email" value={form.data.email}
                            onChange={e => form.setData('email', e.target.value)} />
                        {form.errors.email && <p className="text-sm text-destructive">{form.errors.email}</p>}
                    </div>
                    {!isSelf && (
                        <div className="space-y-2">
                            <Label>Role</Label>
                            <Select value={form.data.role} onValueChange={v => form.setData('role', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {ROLES.map(r => (
                                        <SelectItem key={r.value} value={r.value}>
                                            <span className="font-medium">{r.label}</span>
                                            <span className="text-muted-foreground ml-1.5">— {r.description}</span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}
                    <DialogFooter className="gap-2 sm:gap-0">
                        <Button type="button" variant="ghost" size="sm"
                            className="mr-auto text-muted-foreground text-xs"
                            onClick={sendPasswordReset}>
                            Send password reset email
                        </Button>
                        <Button type="button" variant="outline" onClick={onClose}>Cancel</Button>
                        <Button type="submit" disabled={form.processing}>Save changes</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

/* ─── Page ──────────────────────────────────────────────────────────────────── */
export default function Teams({ users, status, archivedCount, integrationCount }: { users: TeamMember[]; status?: 'archived' | 'integration' | 'active'; archivedCount: number; integrationCount: number }) {
    const { auth } = usePage<VouchPageProps>().props;
    if (!auth.user) throw new Error('Sign in required');
    const currentUserId = auth.user.id;
    const isAdmin = auth.user.role === 'admin';

    const [inviteOpen,    setInviteOpen]    = useState(false);
    const [editTarget,    setEditTarget]    = useState<TeamMember | null>(null);
    const [archiveTarget, setArchiveTarget] = useState<TeamMember | null>(null);
    const [search,        setSearch]        = useState('');

    const inviteForm = useForm({ first_name: '', last_name: '', email: '', role: 'user' });

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return users;
        return users.filter(u =>
            `${u.first_name ?? ''} ${u.last_name ?? ''} ${u.email}`.toLowerCase().includes(q)
        );
    }, [users, search]);

    const activeMembers  = filtered.filter(u => !u.is_pending);
    const pendingMembers = filtered.filter(u => u.is_pending);

    function submitInvite(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        inviteForm.post(route('settings.teams.invite'), {
            onSuccess: () => { setInviteOpen(false); inviteForm.reset(); },
        });
    }

    function handleArchiveConfirm() {
        if (!archiveTarget) return;
        router.delete(route('settings.teams.destroy', archiveTarget.id), {
            onSuccess: () => setArchiveTarget(null),
        });
    }

    function handleUnarchive(u: TeamMember) {
        router.put(route('settings.teams.unarchive', u.id));
    }

    const isArchived    = status === 'archived';
    const isIntegration = status === 'integration';
    const isActive      = !isArchived && !isIntegration;

    const pageTitle = isArchived ? 'Archived Users' : isIntegration ? 'Embedding Users' : 'Team';
    const pageDesc  = isArchived
        ? 'Users who have been archived and no longer have access.'
        : isIntegration
        ? 'Accounts created automatically for embedding and API integrations.'
        : 'Manage who has access to your account and their permissions.';

    return (
        <AuthenticatedLayout>
            <Head title={pageTitle} />

            <div className="max-w-4xl mx-auto px-4 md:px-6 py-8 space-y-6">

                {/* Header */}
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="ui-page-title">{pageTitle}</h1>
                        <p className="text-sm text-muted-foreground mt-1">{pageDesc}</p>
                    </div>
                    {isAdmin && isActive && (
                        <Button onClick={() => setInviteOpen(true)} className="shrink-0">
                            <UserPlus className="h-4 w-4 mr-2" />Invite member
                        </Button>
                    )}
                </div>

                {/* Search */}
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    <Input
                        placeholder="Search by name or email…"
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        className="pl-9"
                    />
                </div>

                {/* Table */}
                <div className="rounded-md border bg-card">
                    <TableHeader />
                    <div className="divide-y px-3">
                        {isActive && (
                            <>
                                {activeMembers.map(u => (
                                    <ActiveRow key={u.id} u={u} currentUserId={currentUserId}
                                        isAdmin={isAdmin} onEdit={setEditTarget} onArchive={setArchiveTarget} />
                                ))}
                                {pendingMembers.length > 0 && (
                                    <>
                                        {activeMembers.length > 0 && (
                                            <div className="py-2 px-1">
                                                <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                    Pending invitations ({pendingMembers.length})
                                                </p>
                                            </div>
                                        )}
                                        {pendingMembers.map(u => (
                                            <ActiveRow key={u.id} u={u} currentUserId={currentUserId}
                                                isAdmin={isAdmin} onEdit={setEditTarget} onArchive={setArchiveTarget} />
                                        ))}
                                    </>
                                )}
                            </>
                        )}

                        {isArchived && filtered.map(u => (
                            <ArchivedRow key={u.id} u={u} onUnarchive={handleUnarchive} />
                        ))}

                        {isIntegration && filtered.map(u => (
                            <IntegrationRow key={u.id} u={u} onArchive={setArchiveTarget} />
                        ))}

                        {filtered.length === 0 && (
                            <div className="py-12 text-center text-sm text-muted-foreground">
                                {search
                                    ? 'No members match your search.'
                                    : isArchived ? 'No archived users.' : isIntegration ? 'No embedding users.' : 'No team members yet.'}
                            </div>
                        )}
                    </div>
                </div>

                {/* Bottom navigation — mirrors Ruby's _bottom_links partial */}
                <div className="flex items-center gap-3 text-sm flex-wrap">
                    {(isArchived || isIntegration) && (
                        <Link href={route('settings.teams.index')} className="text-primary hover:underline">
                            View active users
                        </Link>
                    )}

                    {isActive && (
                        <>
                            <span className="text-xs text-muted-foreground">
                                {users.length} member{users.length !== 1 ? 's' : ''}
                                {pendingMembers.length > 0 && `, ${pendingMembers.length} pending`}
                            </span>

                            {integrationCount > 0 && (
                                <>
                                    <span className="text-muted-foreground/40">·</span>
                                    <Link
                                        href={route('settings.teams.index', { status: 'integration' })}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        View {integrationCount} embedding {integrationCount === 1 ? 'user' : 'users'}
                                    </Link>
                                </>
                            )}

                            {archivedCount > 0 && (
                                <>
                                    <span className="text-muted-foreground/40">·</span>
                                    <Link
                                        href={route('settings.teams.index', { status: 'archived' })}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        View {archivedCount} archived {archivedCount === 1 ? 'user' : 'users'}
                                    </Link>
                                </>
                            )}
                        </>
                    )}
                </div>
            </div>

            {/* Invite dialog */}
            <Dialog open={inviteOpen} onOpenChange={setInviteOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Invite team member</DialogTitle>
                        <DialogDescription>
                            They'll receive an email to set their password and join your account.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitInvite} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="inv_first">First name</Label>
                                <Input id="inv_first" value={inviteForm.data.first_name}
                                    onChange={e => inviteForm.setData('first_name', e.target.value)} autoFocus />
                                {inviteForm.errors.first_name && <p className="text-sm text-destructive">{inviteForm.errors.first_name}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="inv_last">Last name</Label>
                                <Input id="inv_last" value={inviteForm.data.last_name}
                                    onChange={e => inviteForm.setData('last_name', e.target.value)} />
                                {inviteForm.errors.last_name && <p className="text-sm text-destructive">{inviteForm.errors.last_name}</p>}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="inv_email">Email address</Label>
                            <Input id="inv_email" type="email" placeholder="colleague@company.com"
                                value={inviteForm.data.email}
                                onChange={e => inviteForm.setData('email', e.target.value)} />
                            {inviteForm.errors.email && <p className="text-sm text-destructive">{inviteForm.errors.email}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label>Role</Label>
                            <Select value={inviteForm.data.role} onValueChange={v => inviteForm.setData('role', v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    {ROLES.map(r => (
                                        <SelectItem key={r.value} value={r.value}>
                                            <span className="font-medium">{r.label}</span>
                                            <span className="text-muted-foreground ml-1.5">— {r.description}</span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setInviteOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={inviteForm.processing}>
                                {inviteForm.processing ? 'Sending…' : 'Send invitation'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit user dialog */}
            <EditUserDialog
                user={editTarget}
                currentUserId={currentUserId}
                open={!!editTarget}
                onClose={() => setEditTarget(null)}
            />

            {/* Archive confirmation */}
            <AlertDialog open={!!archiveTarget} onOpenChange={v => !v && setArchiveTarget(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Archive user?</AlertDialogTitle>
                        <AlertDialogDescription>
                            <strong>
                                {[archiveTarget?.first_name, archiveTarget?.last_name].filter(Boolean).join(' ') || archiveTarget?.email}
                            </strong>
                            {' '}will lose access immediately. You can restore them from the archived users list.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleArchiveConfirm}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Archive
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
