import { Checkbox } from '@/Components/ui/checkbox';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter,
} from '@/Components/ui/dialog';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { Webhook, Plus, Pencil, Trash2, ExternalLink, Copy, CheckCircle, RefreshCw, KeyRound, Zap } from 'lucide-react';
import { useState } from 'react';

function WebhookForm({ webhook = null, availableEvents, onSubmit, processing, errors }: {
    webhook?: { url: string; events: string[] } | null;
    availableEvents: string[];
    onSubmit: (data: { url: string; events: string[] }) => void;
    processing: boolean;
    errors?: Record<string, string>;
}) {
    const isEditing = !!webhook;
    const [url, setUrl] = useState(webhook?.url ?? '');
    const [selectedEvents, setSelectedEvents] = useState(webhook?.events ?? []);

    function toggleEvent(event) {
        setSelectedEvents(prev =>
            prev.includes(event) ? prev.filter(e => e !== event) : [...prev, event]
        );
    }

    function handleSubmit(e) {
        e.preventDefault();
        onSubmit({ url, events: selectedEvents });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
                <Label htmlFor="webhook_url">Endpoint URL</Label>
                <Input
                    id="webhook_url"
                    type="url"
                    placeholder="https://your-app.com/webhooks/vouch"
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                    autoFocus
                />
                {errors?.url && <p className="text-sm text-destructive">{errors.url}</p>}
            </div>

            <div className="space-y-2">
                <Label>Events to subscribe</Label>
                <p className="text-xs text-muted-foreground">Leave empty to receive all events.</p>
                <div className="grid grid-cols-2 gap-2">
                    {availableEvents.map(event => (
                        <Label key={event} className="flex items-center gap-2 cursor-pointer group">
                            <Checkbox
                                checked={selectedEvents.includes(event)}
                                onCheckedChange={() => toggleEvent(event)}
                                className="rounded border-border"
                            />
                            <span className="text-sm font-mono group-hover:text-foreground text-muted-foreground">
                                {event}
                            </span>
                        </Label>
                    ))}
                </div>
            </div>

            <DialogFooter>
                <Button type="submit" disabled={processing}>
                    {isEditing ? 'Update webhook' : 'Add webhook'}
                </Button>
            </DialogFooter>
        </form>
    );
}

export default function Webhooks({ webhooks, available_events }) {
    const [addOpen, setAddOpen] = useState(false);
    const [editWebhook, setEditWebhook] = useState(null);
    const [deleteWebhook, setDeleteWebhook] = useState(null);
    const [copiedSecret, setCopiedSecret] = useState<string | null>(null);
    const [secretLoading, setSecretLoading] = useState<number | string | null>(null);
    const [testLoading, setTestLoading] = useState<number | string | null>(null);

    const { post, patch, delete: destroy, processing, errors, reset } = useForm({});

    function handleAdd({ url, events }) {
        router.post(route('settings.webhooks.store'), { url, events }, {
            onSuccess: () => { setAddOpen(false); },
        });
    }

    function handleEdit({ url, events }) {
        router.patch(route('settings.webhooks.update', editWebhook.id), { url, events }, {
            onSuccess: () => setEditWebhook(null),
        });
    }

    function handleDelete() {
        router.delete(route('settings.webhooks.destroy', deleteWebhook.id), {
            onSuccess: () => setDeleteWebhook(null),
        });
    }

    function copySecret(webhook) {
        navigator.clipboard.writeText(webhook.secret);
        setCopiedSecret(webhook.id);
        setTimeout(() => setCopiedSecret(null), 2000);
    }

    function regenerateSecret(webhook) {
        setSecretLoading(webhook.id);
        router.post(route('settings.webhooks.secret.store', webhook.id), {}, {
            preserveScroll: true,
            onFinish: () => setSecretLoading(null),
        });
    }

    function removeSecret(webhook) {
        setSecretLoading(webhook.id);
        router.delete(route('settings.webhooks.secret.destroy', webhook.id), {
            preserveScroll: true,
            onFinish: () => setSecretLoading(null),
        });
    }

    function sendTest(webhook) {
        setTestLoading(webhook.id);
        router.post(`/settings/webhooks/${webhook.id}/test`, {}, {
            preserveScroll: true,
            onFinish: () => setTestLoading(null),
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Webhooks" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="ui-page-title">Webhooks</h1>
                        <p className="text-muted-foreground">
                            Receive real-time notifications when events happen in Vouch.
                        </p>
                    </div>
                    <Button onClick={() => setAddOpen(true)}>
                        <Plus className="h-4 w-4 mr-2" />
                        Add webhook
                    </Button>
                </div>

                {webhooks.length === 0 ? (
                    <Card>
                        <CardContent className="py-16 text-center">
                            <div className="h-12 w-12 rounded-full bg-muted flex items-center justify-center mx-auto mb-3">
                                <Webhook className="h-6 w-6 text-muted-foreground" />
                            </div>
                            <p className="font-medium mb-1">No webhooks configured</p>
                            <p className="text-sm text-muted-foreground mb-4">
                                Add a webhook endpoint to receive real-time event notifications.
                            </p>
                            <Button onClick={() => setAddOpen(true)}>
                                <Plus className="h-4 w-4 mr-2" />
                                Add your first webhook
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-3">
                        {webhooks.map(webhook => (
                            <Card key={webhook.id}>
                                <CardContent className="py-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 space-y-2">
                                            <div className="flex items-center gap-2">
                                                <a
                                                    href={webhook.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="font-mono text-sm truncate hover:underline flex items-center gap-1"
                                                >
                                                    {webhook.url}
                                                    <ExternalLink className="h-3 w-3 shrink-0" />
                                                </a>
                                            </div>

                                            <div className="flex flex-wrap gap-1">
                                                {webhook.events.length === 0 ? (
                                                    <Badge variant="secondary">All events</Badge>
                                                ) : webhook.events.map(event => (
                                                    <Badge key={event} variant="outline" className="font-mono text-xs">
                                                        {event}
                                                    </Badge>
                                                ))}
                                            </div>

                                            <div className="flex items-center gap-2 text-xs text-muted-foreground flex-wrap">
                                                {webhook.secret ? (
                                                    <>
                                                        <span>Secret:</span>
                                                        <code className="font-mono bg-muted px-1.5 py-0.5 rounded">
                                                            {webhook.secret.substring(0, 8)}••••
                                                        </code>
                                                        <Button variant="ghost" size="sm"
                                                            onClick={() => copySecret(webhook)}
                                                            className="hover:text-foreground transition-colors"
                                                            title="Copy secret"
                                                        >
                                                            {copiedSecret === webhook.id
                                                                ? <CheckCircle className="h-3 w-3 text-success" />
                                                                : <Copy className="h-3 w-3" />
                                                            }
                                                        </Button>
                                                        <Button variant="ghost" size="sm"
                                                            onClick={() => regenerateSecret(webhook)}
                                                            disabled={secretLoading === webhook.id}
                                                            className="hover:text-foreground transition-colors disabled:opacity-50"
                                                            title="Regenerate secret"
                                                        >
                                                            <RefreshCw className={`h-3 w-3 ${secretLoading === webhook.id ? 'animate-spin' : ''}`} />
                                                        </Button>
                                                        <Button variant="ghost" size="sm"
                                                            onClick={() => removeSecret(webhook)}
                                                            disabled={secretLoading === webhook.id}
                                                            className="hover:text-destructive transition-colors disabled:opacity-50"
                                                            title="Remove secret"
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    </>
                                                ) : (
                                                    <Button variant="ghost" size="sm"
                                                        onClick={() => regenerateSecret(webhook)}
                                                        disabled={secretLoading === webhook.id}
                                                        className="flex items-center gap-1 hover:text-foreground transition-colors disabled:opacity-50"
                                                    >
                                                        <KeyRound className="h-3 w-3" />
                                                        Add signing secret
                                                    </Button>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-1 shrink-0">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => sendTest(webhook)}
                                                disabled={testLoading === webhook.id}
                                                title="Send test webhook"
                                            >
                                                <Zap className={`h-3.5 w-3.5 mr-1 ${testLoading === webhook.id ? 'animate-pulse' : ''}`} />
                                                Test
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => setEditWebhook(webhook)}
                                                title="Edit"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => setDeleteWebhook(webhook)}
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Verifying webhook signatures</CardTitle>
                    </CardHeader>
                    <CardContent className="text-sm text-muted-foreground space-y-2">
                        <p>Each webhook request includes an <code className="bg-muted px-1 py-0.5 rounded text-foreground">X-Vouch-Signature</code> header. Verify it using your webhook secret:</p>
                        <pre className="bg-muted rounded-md p-3 font-mono text-xs overflow-x-auto">
{`$signature = hash_hmac('sha256', $payload, $secret);
hash_equals($signature, $request->header('X-Vouch-Signature'));`}
                        </pre>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={addOpen} onOpenChange={setAddOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Webhook</DialogTitle>
                        <DialogDescription>Configure an endpoint to receive Vouch events.</DialogDescription>
                    </DialogHeader>
                    <WebhookForm
                        availableEvents={available_events}
                        onSubmit={handleAdd}
                        processing={processing}
                        errors={errors}
                    />
                </DialogContent>
            </Dialog>

            <Dialog open={!!editWebhook} onOpenChange={(v) => !v && setEditWebhook(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Webhook</DialogTitle>
                        <DialogDescription>Update the endpoint URL or subscribed events.</DialogDescription>
                    </DialogHeader>
                    {editWebhook && (
                        <WebhookForm
                            webhook={editWebhook}
                            availableEvents={available_events}
                            onSubmit={handleEdit}
                            processing={processing}
                            errors={errors}
                        />
                    )}
                </DialogContent>
            </Dialog>

            <AlertDialog open={!!deleteWebhook} onOpenChange={(v) => !v && setDeleteWebhook(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete webhook?</AlertDialogTitle>
                        <AlertDialogDescription>
                            The endpoint <strong>{deleteWebhook?.url}</strong> will stop receiving events.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDelete}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
