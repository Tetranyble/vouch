import { documentColors, resolvedCanvasColor } from '@/lib/design-tokens';
import type * as React from 'react';
import type { VouchPageProps } from '@/types/vouch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle,
} from '@/Components/ui/dialog';
import { CheckCircle, ShieldCheck, ShieldOff, Check, UploadCloud } from 'lucide-react';
import { useRef, useState, useEffect, useCallback } from 'react';
import { cn } from '@/lib/utils';
import SignaturePad from 'signature_pad';

const LOCALES = [
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Spanish' },
    { value: 'fr', label: 'French' },
    { value: 'de', label: 'German' },
    { value: 'pt', label: 'Portuguese' },
    { value: 'it', label: 'Italian' },
    { value: 'nl', label: 'Dutch' },
    { value: 'pl', label: 'Polish' },
];

/* ─── Crop transparent edges and export PNG ──────────────────────────────────── */
function cropCanvasAndExportToPNG(canvas: HTMLCanvasElement): string | null {
    const ctx = canvas.getContext('2d');
    const { width, height } = canvas;
    if (!ctx) return null;
    const data = ctx.getImageData(0, 0, width, height).data;
    let top = height, bottom = 0, left = width, right = 0;
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            if (data[(y * width + x) * 4 + 3] > 0) {
                if (y < top) top = y;
                if (y > bottom) bottom = y;
                if (x < left) left = x;
                if (x > right) right = x;
            }
        }
    }
    const cw = right - left + 1, ch = bottom - top + 1;
    if (top >= bottom || left >= right || cw < 20 || ch < 20) return null;
    const tmp = document.createElement('canvas');
    tmp.width = cw; tmp.height = ch;
    const destContext = tmp.getContext('2d');
    if (!destContext) return null;
    destContext.drawImage(canvas, left, top, cw, ch, 0, 0, cw, ch);
    return tmp.toDataURL('image/png');
}

/* ─── Draw tab (signature_pad) ───────────────────────────────────────────────── */
function DrawTab({ onSave, saving }: { onSave: (dataUrl: string) => void; saving: boolean }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const padRef    = useRef<SignaturePad>(null);
    const [hasContent, setHasContent] = useState(false);

    useEffect(() => {
        const canvas = canvasRef.current;
        if (!canvas) return;
        const ratio = Math.max(window.devicePixelRatio || 1, 3);
        canvas.width  = canvas.offsetWidth  * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        const context = canvas.getContext('2d');
        if (!context) return;
        context.scale(ratio, ratio);

        padRef.current = new SignaturePad(canvas, {
            backgroundColor: 'transparent',
            penColor: resolvedCanvasColor('signature-ink'),
        });
        const pad = padRef.current;
        const onEnd = () => setHasContent(!pad.isEmpty());
        pad.addEventListener('endStroke', onEnd);
        return () => { pad.off(); padRef.current = null; };
    }, []);

    function clear() { padRef.current?.clear(); setHasContent(false); }

    function save() {
        const dataUrl = canvasRef.current ? cropCanvasAndExportToPNG(canvasRef.current) : null;
        if (dataUrl) onSave(dataUrl);
    }

    return (
        <div className="space-y-3">
            <div className="relative border-2 border-dashed rounded-md overflow-hidden ui-document-paper"
                style={{ touchAction: 'none', height: '160px' }}>
                <canvas ref={canvasRef} className="w-full h-full cursor-crosshair block" />
                {!hasContent && (
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <p className="text-muted-foreground text-sm select-none">Draw here</p>
                    </div>
                )}
                <div className="absolute bottom-5 left-6 right-6 border-t border-dashed border-border" />
                <Button variant="ghost" size="compact" type="button" onClick={clear}
                    className="absolute top-2 right-2 text-xs text-muted-foreground hover:text-foreground transition-colors">
                    Clear
                </Button>
            </div>
            <Button className="w-full" disabled={!hasContent || saving} onClick={save}>
                <Check className="h-4 w-4 mr-1.5" />
                {saving ? 'Saving…' : 'Save'}
            </Button>
        </div>
    );
}

/* ─── Upload tab (auto-saves on file select) ─────────────────────────────────── */
function UploadTab({ onSave, saving }: { onSave: (dataUrl: string) => void; saving: boolean }) {
    const [dragging, setDragging] = useState(false);

    function readFile(file: File | undefined) {
        if (!file || !file.type.startsWith('image/')) return;
        const reader = new FileReader();
        reader.onload = () => { if (typeof reader.result === 'string') onSave(reader.result); };
        reader.readAsDataURL(file);
    }

    const onDragOver  = useCallback((e: React.DragEvent<HTMLElement>) => { e.preventDefault(); setDragging(true); }, []);
    const onDragLeave = useCallback(() => setDragging(false), []);
    const onDrop      = useCallback((e: React.DragEvent<HTMLElement>) => { e.preventDefault(); setDragging(false); readFile(e.dataTransfer.files[0]); }, []);

    return (
        <Label
            className={cn(
                'flex flex-col items-center justify-center h-40 w-full rounded-md border-2 border-dashed cursor-pointer transition-colors',
                dragging ? 'border-primary bg-primary/5' : 'border-border bg-muted/20 hover:bg-muted/40'
            )}
            onDragOver={onDragOver} onDragLeave={onDragLeave} onDrop={onDrop}
        >
            {saving ? (
                <p className="text-sm text-muted-foreground">Saving…</p>
            ) : (
                <>
                    <UploadCloud className={cn('h-8 w-8 mb-2', dragging ? 'text-primary' : 'text-muted-foreground')} />
                    <span className="text-sm font-medium">Upload Signature</span>
                    <span className="text-xs text-muted-foreground mt-0.5">click to upload or drag and drop</span>
                </>
            )}
            <Input type="file" accept="image/png,image/jpeg,image/jpg" className="sr-only"
                onChange={e => readFile(e.target.files?.[0])} disabled={saving} />
        </Label>
    );
}

/* ─── Signature modal ───────────────────────────────────────────────────────── */
function SignatureModal({ open, onClose, onSave, label, saving }: { open: boolean; onClose: () => void; onSave: (dataUrl: string) => void; label: string; saving: boolean }) {
    const [tab, setTab] = useState('draw');

    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen) { onClose(); setTab('draw'); }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Update {label}</DialogTitle>
                </DialogHeader>

                <div className="flex justify-center mt-1">
                    <div className="inline-flex rounded-full border bg-muted p-0.5 gap-0.5">
                        {['draw', 'upload'].map(t => (
                            <Button variant="ghost" size="sm" key={t} type="button" onClick={() => setTab(t)}
                                className={cn(
                                    'px-5 py-1.5 rounded-full text-sm font-medium transition-colors capitalize',
                                    tab === t ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'
                                )}>
                                {t === 'draw' ? 'Draw' : 'Upload'}
                            </Button>
                        ))}
                    </div>
                </div>

                <div className="mt-2">
                    {tab === 'draw'
                        ? <DrawTab key={open + 'draw'} onSave={onSave} saving={saving} />
                        : <UploadTab key={open + 'upload'} onSave={onSave} saving={saving} />
                    }
                </div>
            </DialogContent>
        </Dialog>
    );
}

/* ─── Signature / Initials section ──────────────────────────────────────────── */
function SignatureSection({ label, field, value }: { label: string; field: "signature" | "initials"; value?: string | null }) {
    const [open, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);

    function save(dataUrl: string) {
        setSaving(true);
        router.post(route('user.signatures.store'), { [field]: dataUrl }, {
            preserveScroll: true,
            onSuccess: () => { setSaving(false); setOpen(false); },
            onError:   () => setSaving(false),
        });
    }

    function remove() {
        router.delete(route('user.signatures.destroy', field), { preserveScroll: true });
    }

    return (
        <>
            {value && (
                <div className="flex justify-center mb-4 relative">
                    <Button variant="ghost" size="sm" type="button" onClick={remove}
                        className="right-0 top-0 absolute text-sm text-muted-foreground hover:text-destructive transition-colors">
                        Remove
                    </Button>
                    <img src={value} alt={label} style={{ maxHeight: '200px', width: 'auto' }} />
                </div>
            )}
            <Button className="w-full" onClick={() => setOpen(true)}>
                Update {label}
            </Button>

            <SignatureModal open={open} onClose={() => setOpen(false)} onSave={save} label={label} saving={saving} />
        </>
    );
}

/* ─── Prefill signature toggle ───────────────────────────────────────────────── */
function PrefillToggle({ defaultChecked }: { defaultChecked?: boolean }) {
    const [checked, setChecked] = useState(defaultChecked ?? true);
    const [saving, setSaving]   = useState(false);

    function toggle() {
        const value = !checked;
        setChecked(value);
        setSaving(true);
        router.patch(route('settings.account.preferences.update'), { prefill_signature: value }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    }

    return (
        <Button variant="ghost" size="sm"
            type="button"
            onClick={toggle}
            disabled={saving}
            className="flex items-center gap-3 text-left disabled:opacity-60 group"
        >
            {/* Toggle track */}
            <span
                className={cn(
                    'inline-flex h-5 w-9 shrink-0 items-center rounded-full border-2 border-transparent transition-colors',
                    checked ? 'bg-primary' : 'bg-input'
                )}
            >
                {/* Toggle thumb */}
                <span
                    className={cn(
                        'block h-4 w-4 rounded-full bg-background shadow-sm transition-transform',
                        checked ? 'translate-x-4' : 'translate-x-0'
                    )}
                />
            </span>
            <span className="text-sm group-disabled:cursor-not-allowed">
                Automatically pre-fill my saved signature when signing documents
            </span>
        </Button>
    );
}

export default function Account({
    account, timezones,
    mustVerifyEmail, status,
    userSignature, userInitials, mfaEnabled,
    prefillSignature,
}: {
    account: { name: string; locale: string; timezone: string };
    timezones: string[];
    mustVerifyEmail?: boolean;
    status?: string | null;
    userSignature?: string | null;
    userInitials?: string | null;
    mfaEnabled?: boolean;
    prefillSignature?: boolean;
}) {
    const user = usePage<VouchPageProps>().props.auth.user;
    if (!user) throw new Error('Sign in is required to access account settings');

    /* Account details form */
    const accountForm = useForm({
        name:     account.name,
        locale:   account.locale,
        timezone: account.timezone,
    });

    /* Profile info form */
    const profileForm = useForm({
        first_name: user.first_name ?? '',
        last_name:  user.last_name  ?? '',
        email:      user.email      ?? '',
    });

    /* Password form */
    const passwordRef   = useRef<HTMLInputElement>(null);
    const currentPwRef  = useRef<HTMLInputElement>(null);
    const passwordForm  = useForm({ current_password: '', password: '', password_confirmation: '' });

    function submitAccount(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        accountForm.patch(route('settings.account.update'));
    }

    function submitProfile(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        profileForm.patch(route('profile.update'));
    }

    function submitPassword(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        passwordForm.put(route('password.update'), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
            onError: (errs) => {
                if (errs.password) { passwordForm.reset('password', 'password_confirmation'); passwordRef.current?.focus(); }
                if (errs.current_password) { passwordForm.reset('current_password'); currentPwRef.current?.focus(); }
            },
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Account Settings" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 className="ui-page-title">Account Settings</h1>
                    <p className="text-muted-foreground">Manage your profile, account details, and team.</p>
                </div>

                {/* ── Profile Information ── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Profile Information</CardTitle>
                        <CardDescription>Update your personal name and email address.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitProfile} className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="p_first_name">First name</Label>
                                    <Input id="p_first_name" value={profileForm.data.first_name}
                                        onChange={e => profileForm.setData('first_name', e.target.value)}
                                        autoComplete="given-name" />
                                    {profileForm.errors.first_name && <p className="text-sm text-destructive">{profileForm.errors.first_name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="p_last_name">Last name</Label>
                                    <Input id="p_last_name" value={profileForm.data.last_name}
                                        onChange={e => profileForm.setData('last_name', e.target.value)}
                                        autoComplete="family-name" />
                                    {profileForm.errors.last_name && <p className="text-sm text-destructive">{profileForm.errors.last_name}</p>}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="p_email">Email address</Label>
                                <Input id="p_email" type="email" value={profileForm.data.email}
                                    onChange={e => profileForm.setData('email', e.target.value)}
                                    autoComplete="email" />
                                {profileForm.errors.email && <p className="text-sm text-destructive">{profileForm.errors.email}</p>}
                            </div>
                            {mustVerifyEmail && !user.email_verified_at && (
                                <p className="text-sm text-warning-foreground bg-warning-soft px-3 py-2 rounded-md">
                                    Your email address is unverified.
                                </p>
                            )}
                            <div className="flex items-center gap-3 pt-1">
                                <Button type="submit" disabled={profileForm.processing}>Save changes</Button>
                                {profileForm.recentlySuccessful && (
                                    <span className="flex items-center gap-1 text-sm text-success">
                                        <CheckCircle className="h-4 w-4" />Saved
                                    </span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* ── Signature & Initials ── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Signature &amp; Initials</CardTitle>
                        <CardDescription>Saved for use when you sign documents.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-1.5">
                            <Label>Signature</Label>
                            <SignatureSection label="Signature" field="signature" value={userSignature} />
                        </div>
                        <div className="border-t border-border" />
                        <div className="space-y-1.5">
                            <Label>Initials</Label>
                            <SignatureSection label="Initials" field="initials" value={userInitials} />
                        </div>
                        <div className="border-t border-border" />
                        <PrefillToggle defaultChecked={prefillSignature} />
                    </CardContent>
                </Card>

                {/* ── Change Password ── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Change Password</CardTitle>
                        <CardDescription>Use a long, random password to keep your account secure.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitPassword} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="current_pw">Current password</Label>
                                <Input id="current_pw" ref={currentPwRef} type="password" placeholder="••••••••"
                                    value={passwordForm.data.current_password}
                                    onChange={e => passwordForm.setData('current_password', e.target.value)}
                                    autoComplete="current-password" />
                                {passwordForm.errors.current_password && <p className="text-sm text-destructive">{passwordForm.errors.current_password}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="new_pw">New password</Label>
                                <Input id="new_pw" ref={passwordRef} type="password" placeholder="••••••••"
                                    value={passwordForm.data.password}
                                    onChange={e => passwordForm.setData('password', e.target.value)}
                                    autoComplete="new-password" />
                                {passwordForm.errors.password && <p className="text-sm text-destructive">{passwordForm.errors.password}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="confirm_pw">Confirm new password</Label>
                                <Input id="confirm_pw" type="password" placeholder="••••••••"
                                    value={passwordForm.data.password_confirmation}
                                    onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                                    autoComplete="new-password" />
                                {passwordForm.errors.password_confirmation && <p className="text-sm text-destructive">{passwordForm.errors.password_confirmation}</p>}
                            </div>
                            <div className="flex items-center gap-3 pt-1">
                                <Button type="submit" disabled={passwordForm.processing}>Update password</Button>
                                {passwordForm.recentlySuccessful && (
                                    <span className="flex items-center gap-1 text-sm text-success">
                                        <CheckCircle className="h-4 w-4" />Updated
                                    </span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* ── Two-Factor Authentication ── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Two-Factor Authentication</CardTitle>
                        <CardDescription>Add an extra layer of security to your account.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {mfaEnabled ? (
                            <div className="flex items-center gap-2 text-sm text-success">
                                <ShieldCheck className="h-4 w-4 shrink-0" />
                                <span>2FA is enabled on your account.</span>
                            </div>
                        ) : (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <ShieldOff className="h-4 w-4 shrink-0" />
                                <span>2FA is not configured</span>
                            </div>
                        )}
                        <Button variant={mfaEnabled ? 'outline' : 'default'} asChild>
                            <Link href={route('settings.mfa.show')}>
                                {mfaEnabled ? 'Manage 2FA' : '🔒 Set up 2FA'}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                {/* ── Account Details ── */}
                <Card>
                    <CardHeader>
                        <CardTitle>Account Details</CardTitle>
                        <CardDescription>Update your account name and preferences.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submitAccount} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="account_name">Account name</Label>
                                <Input id="account_name" value={accountForm.data.name}
                                    onChange={e => accountForm.setData('name', e.target.value)} />
                                {accountForm.errors.name && <p className="text-sm text-destructive">{accountForm.errors.name}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Language</Label>
                                    <Select value={accountForm.data.locale} onValueChange={v => accountForm.setData('locale', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            {LOCALES.map(l => <SelectItem key={l.value} value={l.value}>{l.label}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Timezone</Label>
                                    <Select value={accountForm.data.timezone} onValueChange={v => accountForm.setData('timezone', v)}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent className="max-h-60">
                                            {timezones.map(tz => <SelectItem key={tz} value={tz}>{tz}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="flex items-center gap-3 pt-1">
                                <Button type="submit" disabled={accountForm.processing}>Save changes</Button>
                                {accountForm.recentlySuccessful && (
                                    <span className="flex items-center gap-1 text-sm text-success">
                                        <CheckCircle className="h-4 w-4" />Saved
                                    </span>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>

            </div>
        </AuthenticatedLayout>
    );
}
