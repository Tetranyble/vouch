import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { ShieldCheck, ShieldOff, Trash2, Copy, Check } from 'lucide-react';
import { useState } from 'react';

export default function Mfa({ mfa, qrCodeUrl, secret }) {
    const { flash } = usePage<{ flash?: { success?: string; error?: string } }>().props;
    const [code, setCode] = useState('');
    const [error, setError] = useState('');
    const [copied, setCopied] = useState(false);

    function handleEnable(e) {
        e.preventDefault();
        setError('');
        router.post(route('settings.mfa.store'), { code }, {
            preserveScroll: true,
            onError: (errors) => setError(errors.code || 'Verification failed.'),
        });
    }

    function handleDisable() {
        if (confirm('Disable two-factor authentication? This will remove TOTP from your account.')) {
            router.delete(route('settings.mfa.destroy'), { preserveScroll: true });
        }
    }

    function copySecret() {
        navigator.clipboard?.writeText(secret);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <AuthenticatedLayout>
            <Head title="Two-Factor Authentication" />
            <div className="max-w-lg mx-auto px-4 md:px-6 py-8 space-y-6">

                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10">
                        <ShieldCheck className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="ui-page-title">Two-Factor Authentication</h1>
                        <p className="text-sm text-muted-foreground">Add an extra layer of security to your account.</p>
                    </div>
                </div>

                {flash?.success && (
                    <div className="rounded-md bg-success-soft border border-success/20 px-4 py-3 text-sm text-success">
                        {flash.success}
                    </div>
                )}

                {mfa?.enabled ? (
                    <div className="rounded-md border bg-card p-6 space-y-5">
                        <div className="flex items-center gap-2 text-success">
                            <ShieldCheck className="h-5 w-5 shrink-0" />
                            <span className="font-medium">Two-factor authentication is enabled.</span>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Your account is protected. You'll be asked for a verification code each time you log in.
                        </p>
                        <Button variant="outline" className="gap-2 text-destructive border-destructive/30 hover:bg-destructive-soft hover:text-destructive" onClick={handleDisable}>
                            <Trash2 className="h-4 w-4" />
                            Disable 2FA
                        </Button>
                    </div>
                ) : (
                    <div className="rounded-md border bg-card p-6 space-y-6">
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <ShieldOff className="h-4 w-4 shrink-0" />
                            <span className="text-sm">2FA is not configured</span>
                        </div>

                        <div className="space-y-2">
                            <p className="text-sm font-medium">Step 1 — Scan with your authenticator app</p>
                            <p className="text-sm text-muted-foreground">
                                Use Google Authenticator, Authy, or any TOTP-compatible app to scan the QR code below.
                            </p>
                        </div>

                        {qrCodeUrl && (
                            <div className="flex justify-center">
                                <div className="border rounded-md p-3 bg-document inline-flex">
                                    <img
                                        src={qrCodeUrl}
                                        alt="Scan this QR code with your authenticator app"
                                        className="w-48 h-48 block"
                                    />
                                </div>
                            </div>
                        )}

                        {secret && (
                            <div className="space-y-1">
                                <p className="text-xs text-muted-foreground">Can't scan? Enter this key manually:</p>
                                <div className="flex items-center gap-2">
                                    <code className="flex-1 text-sm font-mono bg-muted px-3 py-2 rounded-md tracking-widest break-all">
                                        {secret}
                                    </code>
                                    <Button variant="ghost" size="sm"
                                        type="button"
                                        onClick={copySecret}
                                        className="p-2 rounded-md hover:bg-muted text-muted-foreground hover:text-foreground transition-colors shrink-0"
                                        title="Copy secret"
                                    >
                                        {copied ? <Check className="h-4 w-4 text-success" /> : <Copy className="h-4 w-4" />}
                                    </Button>
                                </div>
                            </div>
                        )}

                        <form onSubmit={handleEnable} className="space-y-4 pt-2 border-t border-border">
                            <div className="space-y-2">
                                <p className="text-sm font-medium">Step 2 — Enter the 6-digit code</p>
                                <Input
                                    value={code}
                                    onChange={(e) => { setCode(e.target.value); setError(''); }}
                                    placeholder="000000"
                                    maxLength={6}
                                    inputMode="numeric"
                                    pattern="\d{6}"
                                    className="w-40 font-mono tracking-widest text-center text-lg"
                                    required
                                    autoComplete="one-time-code"
                                />
                                {error && <p className="text-sm text-destructive">{error}</p>}
                            </div>
                            <Button type="submit" className="w-full" disabled={code.length !== 6}>
                                Enable 2FA
                            </Button>
                        </form>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
