import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Lock, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Sso({ settings }) {
    const [form, setForm] = useState({
        entity_id: settings.entity_id ?? '',
        sso_url: settings.sso_url ?? '',
        certificate: settings.certificate ?? '',
        force_sso: settings.force_sso ?? false,
    });

    function set(k, v) { setForm((p) => ({ ...p, [k]: v })); }

    function handleSubmit(e) {
        e.preventDefault();
        router.post('/settings/sso', form, { preserveScroll: true });
    }

    function handleRemove() {
        router.delete('/settings/sso', { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="SSO Settings" />
            <div className="max-w-2xl mx-auto px-4 md:px-6 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10"><Lock className="h-5 w-5 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">Single Sign-On (SSO)</h1>
                        <p className="text-sm text-muted-foreground">Configure SAML 2.0 SSO to let users log in with your identity provider.</p>
                    </div>
                </div>

                {settings.configured && (
                    <div className="rounded-md border bg-success-soft border-success/20 px-4 py-3 text-sm text-success flex items-center justify-between">
                        <span>SSO is configured and active.</span>
                        <Button variant="ghost" size="sm" className="text-destructive gap-1.5" onClick={handleRemove}>
                            <Trash2 className="h-3.5 w-3.5" />Remove
                        </Button>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="rounded-md border bg-card p-6 space-y-4">
                    <div>
                        <Label className="text-sm font-medium block mb-1">IDP Entity ID</Label>
                        <Input value={form.entity_id} onChange={(e) => set('entity_id', e.target.value)}
                            placeholder="https://idp.example.com/saml2/metadata" required />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">SSO URL</Label>
                        <Input type="url" value={form.sso_url} onChange={(e) => set('sso_url', e.target.value)}
                            placeholder="https://idp.example.com/saml2/sso" required />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">IDP Certificate (PEM/X.509)</Label>
                        <Textarea
                            value={form.certificate}
                            onChange={(e) => set('certificate', e.target.value)}
                            rows={6}
                            placeholder="-----BEGIN CERTIFICATE-----&#10;…&#10;-----END CERTIFICATE-----"
                            className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none font-mono"
                            required
                        />
                    </div>

                    <Label className="flex items-center justify-between gap-4 cursor-pointer">
                        <div>
                            <p className="text-sm font-medium">Force SSO login</p>
                            <p className="text-xs text-muted-foreground">Redirect all login attempts to the SSO provider (disables password login).</p>
                        </div>
                        <Checkbox checked={!!form.force_sso} onCheckedChange={(checked) => set('force_sso', checked)} className="h-4 w-4 accent-primary" />
                    </Label>

                    {settings.sp_metadata_url && (
                        <div className="rounded-md border bg-muted/30 p-3 text-sm space-y-1">
                            <p className="font-medium text-xs text-muted-foreground uppercase tracking-wide">Service Provider Info</p>
                            <p>Metadata URL: <a href={settings.sp_metadata_url} className="text-primary underline" target="_blank" rel="noopener noreferrer">{settings.sp_metadata_url}</a></p>
                            {settings.sp_acs_url && <p>ACS URL: <span className="font-mono text-xs">{settings.sp_acs_url}</span></p>}
                        </div>
                    )}

                    <Button type="submit">Save SSO Settings</Button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
