import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { CheckCircle, Copy, Key, RefreshCw, ShieldCheck, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { ApiTokenInfo } from '@/types/vouch';

type Props = { token: ApiTokenInfo | null; newToken: string | null };

export default function ApiTokens({ token, newToken }: Props) {
    const [copied, setCopied] = useState(false);
    const [revokeOpen, setRevokeOpen] = useState(false);
    const [regenerateOpen, setRegenerateOpen] = useState(false);

    const generateToken = () => {
        setRegenerateOpen(false);
        router.post(route('settings.api-tokens.store'));
    };

    const revokeToken = () => {
        router.delete(route('settings.api-tokens.destroy'), {
            onSuccess: () => setRevokeOpen(false),
        });
    };

    const copyToken = async () => {
        if (!newToken) return;
        try {
            await navigator.clipboard.writeText(newToken);
            setCopied(true);
        } catch {
            setCopied(false);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="API Tokens" />
            <div className="mx-auto max-w-3xl space-y-6">
                <div>
                    <h1 className="ui-page-title">API access</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Manage access to the Vouch API. Tokens can only be copied immediately after generation.
                    </p>
                </div>

                {newToken && (
                    <Card className="border-primary/30">
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <ShieldCheck className="h-5 w-5" />
                                <CardTitle className="text-base">Save your new API token</CardTitle>
                            </div>
                            <CardDescription>
                                This is the only time the token is displayed. Store it in a password manager or secret vault.
                                It cannot be retrieved again.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center gap-2 rounded-lg border bg-muted/40 p-3">
                                <code className="min-w-0 flex-1 break-all text-xs select-all">{newToken}</code>
                                <Button size="sm" variant="outline" onClick={copyToken} type="button" aria-label="Copy API token">
                                    {copied ? <CheckCircle className="mr-1 h-4 w-4" /> : <Copy className="mr-1 h-4 w-4" />}
                                    {copied ? 'Copied' : 'Copy'}
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">It will disappear when you leave or refresh this page.</p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Key className="h-5 w-5" />
                            <CardTitle className="text-base">Personal API token</CardTitle>
                        </div>
                        <CardDescription>Requests made with this token have the permissions of your user account.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {token ? (
                            <>
                                <div className="rounded-lg bg-muted px-3 py-2 font-mono text-sm" aria-label="Masked token">
                                    {token.masked}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Created {new Date(token.created_at).toLocaleDateString()}. The stored token is one-way hashed.
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    <Button size="sm" variant="outline" onClick={() => setRegenerateOpen(true)}>
                                        <RefreshCw className="mr-2 h-4 w-4" /> Regenerate
                                    </Button>
                                    <Button size="sm" variant="outline" className="text-destructive" onClick={() => setRevokeOpen(true)}>
                                        <Trash2 className="mr-2 h-4 w-4" /> Revoke
                                    </Button>
                                </div>
                            </>
                        ) : (
                            <div className="space-y-3 py-6 text-center">
                                <Key className="mx-auto h-8 w-8 text-muted-foreground" />
                                <p className="text-sm text-muted-foreground">No API token is active.</p>
                                <Button onClick={generateToken}>Generate an API token</Button>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle className="text-base">Authenticate API requests</CardTitle></CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-muted-foreground">Use the <code>Authorization</code> header. Never place tokens in URLs or frontend code.</p>
                        <pre className="overflow-x-auto rounded-lg bg-muted p-4 text-xs">{'curl -H "Authorization: Bearer YOUR_API_TOKEN" \\\n  https://your-domain.com/api/templates'}</pre>
                    </CardContent>
                </Card>
            </div>

            <AlertDialog open={regenerateOpen} onOpenChange={setRegenerateOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Regenerate API token?</AlertDialogTitle>
                        <AlertDialogDescription>The previous token will stop working immediately. Update any connected clients.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={generateToken}>Regenerate token</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
            <AlertDialog open={revokeOpen} onOpenChange={setRevokeOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Revoke API token?</AlertDialogTitle>
                        <AlertDialogDescription>Clients using this token will immediately lose access.</AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction className="bg-destructive text-destructive-foreground" onClick={revokeToken}>Revoke token</AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
