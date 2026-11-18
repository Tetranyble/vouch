import { Label } from '@/Components/ui/label';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { MessageSquare, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Sms({ settings }) {
    const [form, setForm] = useState({
        account_sid: settings.account_sid ?? '',
        auth_token: '',
        from_number: settings.from_number ?? '',
    });

    function set(k, v) { setForm((p) => ({ ...p, [k]: v })); }

    function handleSubmit(e) {
        e.preventDefault();
        router.post('/settings/sms', form, { preserveScroll: true });
    }

    function handleRemove() {
        router.delete('/settings/sms', { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="SMS Settings" />
            <div className="max-w-2xl mx-auto px-4 md:px-6 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10"><MessageSquare className="h-5 w-5 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">SMS Settings</h1>
                        <p className="text-sm text-muted-foreground">Configure Twilio to send SMS invitations and 2FA codes.</p>
                    </div>
                </div>

                {settings.configured && (
                    <div className="rounded-md border bg-success-soft border-success/20 px-4 py-3 text-sm text-success flex items-center justify-between">
                        <span>SMS is configured and active.</span>
                        <Button variant="ghost" size="sm" className="text-destructive gap-1.5" onClick={handleRemove}>
                            <Trash2 className="h-3.5 w-3.5" />Remove
                        </Button>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="rounded-md border bg-card p-6 space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Get your credentials from the <a href="https://console.twilio.com" target="_blank" rel="noopener noreferrer" className="text-primary underline">Twilio Console</a>.
                    </p>
                    <div>
                        <Label className="text-sm font-medium block mb-1">Account SID</Label>
                        <Input placeholder="ACxxxxxxxxxxxxxxxx" value={form.account_sid} onChange={(e) => set('account_sid', e.target.value)} required />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">Auth Token</Label>
                        <Input type="password" placeholder={settings.configured ? '(saved — enter to update)' : 'Your auth token'} value={form.auth_token} onChange={(e) => set('auth_token', e.target.value)} required={!settings.configured} />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">From Phone Number</Label>
                        <Input placeholder="+15551234567" value={form.from_number} onChange={(e) => set('from_number', e.target.value)} required />
                    </div>
                    <Button type="submit">Save SMS Settings</Button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
