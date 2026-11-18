import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Bell } from 'lucide-react';
import { useState } from 'react';

export default function Notifications({ settings }) {
    const [form, setForm] = useState({
        send_completion_email: settings.send_completion_email ?? true,
        send_reminder_email: settings.send_reminder_email ?? false,
        reminder_after_days: settings.reminder_after_days ?? 3,
        bcc_emails: settings.bcc_emails ?? '',
        completion_redirect_url: settings.completion_redirect_url ?? '',
        allow_decline: settings.allow_decline ?? true,
    });

    function set(k, v) { setForm((p) => ({ ...p, [k]: v })); }

    function handleSubmit(e) {
        e.preventDefault();
        router.patch('/settings/notifications', form, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Notification Settings" />
            <div className="max-w-2xl mx-auto px-4 md:px-6 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10"><Bell className="h-5 w-5 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">Notification Settings</h1>
                        <p className="text-sm text-muted-foreground">Control what emails get sent and when.</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="rounded-md border bg-card p-6 space-y-5">
                    {[
                        { key: 'send_completion_email', label: 'Send completion email to submitter', desc: 'Notify the signer when their form is complete.' },
                        { key: 'send_reminder_email', label: 'Send reminder emails', desc: 'Automatically remind pending signers.' },
                        { key: 'allow_decline', label: 'Allow signers to decline', desc: 'Show a Decline button on the signing page.' },
                    ].map(({ key, label, desc }) => (
                        <Label key={key} className="flex items-start justify-between gap-4 cursor-pointer">
                            <div>
                                <p className="text-sm font-medium">{label}</p>
                                <p className="text-xs text-muted-foreground">{desc}</p>
                            </div>
                            <Checkbox checked={!!form[key]} onCheckedChange={(checked) => set(key, checked)} className="mt-1 h-4 w-4 accent-primary" />
                        </Label>
                    ))}

                    {form.send_reminder_email && (
                        <div>
                            <Label className="text-sm font-medium block mb-1">Remind after (days)</Label>
                            <Input type="number" min={1} max={30} value={form.reminder_after_days}
                                onChange={(e) => set('reminder_after_days', e.target.value)} className="w-32" />
                        </div>
                    )}

                    <div>
                        <Label className="text-sm font-medium block mb-1">BCC email addresses</Label>
                        <Input placeholder="admin@example.com, legal@example.com" value={form.bcc_emails}
                            onChange={(e) => set('bcc_emails', e.target.value)} />
                        <p className="text-xs text-muted-foreground mt-1">Separate multiple addresses with commas.</p>
                    </div>

                    <div>
                        <Label className="text-sm font-medium block mb-1">Completion redirect URL</Label>
                        <Input type="url" placeholder="https://yoursite.com/thank-you" value={form.completion_redirect_url}
                            onChange={(e) => set('completion_redirect_url', e.target.value)} />
                        <p className="text-xs text-muted-foreground mt-1">Redirect the signer to this URL after completion.</p>
                    </div>

                    <Button type="submit">Save Settings</Button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
