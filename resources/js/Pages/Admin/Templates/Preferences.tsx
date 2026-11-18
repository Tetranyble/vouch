import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { ChevronLeft, CheckCircle } from 'lucide-react';

export default function Preferences({ template }) {
    const initPrefs = template.preferences ?? {};
    const [preferences, setPreferences] = useState({
        submitters_order: initPrefs.submitters_order ?? '',
        default_expire_at_duration: initPrefs.default_expire_at_duration ?? '',
        completed_redirect_url: initPrefs.completed_redirect_url ?? '',
        completed_message: initPrefs.completed_message ?? '',
        bcc_completed: initPrefs.bcc_completed ?? false,
        require_email_2fa: initPrefs.require_email_2fa ?? false,
        require_phone_2fa: initPrefs.require_phone_2fa ?? false,
    });

    const [submitters, setSubmitters] = useState(
        (template.submitters ?? []).map((s) => ({ uuid: s.uuid, name: s.name }))
    );

    const [saving, setSaving] = useState(false);
    const [savedRecently, setSavedRecently] = useState(false);

    function setPref(key, value) {
        setPreferences((prev) => ({ ...prev, [key]: value }));
    }

    function updateSubmitterName(uuid, name) {
        setSubmitters((prev) => prev.map((s) => s.uuid === uuid ? { ...s, name } : s));
    }

    function handleSubmit(e) {
        e.preventDefault();
        setSaving(true);

        // Strip empty strings so the backend array_filter passes correctly
        const filteredPrefs: Record<string, string | number | boolean> = Object.fromEntries(
            Object.entries(preferences).filter((entry): entry is [string, string | number | boolean] =>
                entry[1] !== '' && entry[1] !== null && ['string', 'number', 'boolean'].includes(typeof entry[1])
            )
        );

        router.patch(`/templates/${template.id}/preferences`, {
            preferences: filteredPrefs,
            submitters,
        }, {
            onSuccess: () => {
                setSaving(false);
                setSavedRecently(true);
                setTimeout(() => setSavedRecently(false), 2000);
            },
            onError: () => setSaving(false),
        });
    }

    return (
        <AuthenticatedLayout>
            <Head title={`Preferences — ${template.name}`} />

            <div className="max-w-3xl mx-auto px-4 md:px-6 py-8 space-y-6">
                {/* Breadcrumb / back link */}
                <div>
                    <Link
                        href={`/templates/${template.id}`}
                        className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-1"
                    >
                        <ChevronLeft className="h-4 w-4" />Templates / {template.name}
                    </Link>
                    <h1 className="ui-page-title">Template Preferences</h1>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Card 1: General Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>General Settings</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5">
                            {/* Signing order */}
                            <div className="space-y-2">
                                <Label htmlFor="submitters_order">Signing order</Label>
                                <Select
                                    value={preferences.submitters_order || 'random'}
                                    onValueChange={(v) => setPref('submitters_order', v)}
                                >
                                    <SelectTrigger id="submitters_order" className="w-64">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="random">Send to all at once</SelectItem>
                                        <SelectItem value="preserved">Send sequentially</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p className="text-xs text-muted-foreground">
                                    "Send sequentially" enforces a strict signing order among signers.
                                </p>
                            </div>

                            {/* Expiry */}
                            <div className="space-y-2">
                                <Label htmlFor="expire_duration">Expiry (days)</Label>
                                <Input
                                    id="expire_duration"
                                    type="number"
                                    min={1}
                                    placeholder="No expiry"
                                    className="w-40"
                                    value={preferences.default_expire_at_duration}
                                    onChange={(e) => setPref('default_expire_at_duration', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Leave blank to use no default expiry for new submissions.
                                </p>
                            </div>

                            {/* Redirect URL */}
                            <div className="space-y-2">
                                <Label htmlFor="redirect_url">Redirect URL after completion</Label>
                                <Input
                                    id="redirect_url"
                                    type="url"
                                    placeholder="https://example.com/thank-you"
                                    value={preferences.completed_redirect_url}
                                    onChange={(e) => setPref('completed_redirect_url', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Signers will be redirected here after completing the form.
                                </p>
                            </div>

                            {/* Completion message */}
                            <div className="space-y-2">
                                <Label htmlFor="completed_message">Completion message</Label>
                                <Textarea
                                    id="completed_message"
                                    rows={4}
                                    placeholder="Thank you for signing!"
                                    className="w-full rounded-md border px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
                                    value={preferences.completed_message}
                                    onChange={(e) => setPref('completed_message', e.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Custom message shown to signers on the completion screen.
                                </p>
                            </div>

                            {/* Boolean toggles */}
                            <div className="space-y-3 pt-2 border-t">
                                <p className="text-sm font-medium">Additional options</p>
                                <Label className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        className="h-4 w-4 rounded border-input accent-primary"
                                        checked={!!preferences.bcc_completed}
                                        onCheckedChange={(checked) => setPref('bcc_completed', checked)}
                                    />
                                    <span className="text-sm">BCC completed email</span>
                                </Label>
                                <Label className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        className="h-4 w-4 rounded border-input accent-primary"
                                        checked={!!preferences.require_email_2fa}
                                        onCheckedChange={(checked) => setPref('require_email_2fa', checked)}
                                    />
                                    <span className="text-sm">Require email 2FA</span>
                                </Label>
                                <Label className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        className="h-4 w-4 rounded border-input accent-primary"
                                        checked={!!preferences.require_phone_2fa}
                                        onCheckedChange={(checked) => setPref('require_phone_2fa', checked)}
                                    />
                                    <span className="text-sm">Require phone 2FA</span>
                                </Label>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Card 2: Signers / Role Names */}
                    {submitters.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Signers / Role Names</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm text-muted-foreground">
                                    Rename each signer role to better reflect your workflow (e.g. "First Party" → "Client").
                                </p>
                                {submitters.map((s, i) => (
                                    <div key={s.uuid} className="flex items-center gap-3">
                                        <span className="text-sm text-muted-foreground w-6 shrink-0">{i + 1}.</span>
                                        <Input
                                            value={s.name}
                                            onChange={(e) => updateSubmitterName(s.uuid, e.target.value)}
                                            placeholder={`Signer ${i + 1}`}
                                        />
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    )}

                    {/* Submit */}
                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={saving}>
                            {saving ? 'Saving…' : 'Save Preferences'}
                        </Button>
                        {savedRecently && (
                            <span className="flex items-center gap-1 text-sm text-success">
                                <CheckCircle className="h-4 w-4" />
                                Saved
                            </span>
                        )}
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
