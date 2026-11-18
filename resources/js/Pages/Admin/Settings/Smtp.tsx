import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { CheckCircle, Mail, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Smtp({ settings }) {
    const [clearOpen, setClearOpen] = useState(false);
    const form = useForm({
        host: settings.host,
        port: settings.port,
        username: settings.username,
        password: settings.password,
        from_email: settings.from_email,
        from_name: settings.from_name,
        encryption: settings.encryption,
    });

    function submit(e) {
        e.preventDefault();
        form.post(route('settings.smtp.store'));
    }

    function handleClear() {
        router.delete(route('settings.smtp.destroy'), {
            onSuccess: () => setClearOpen(false),
        });
    }

    const hasSettings = !!settings.host;

    return (
        <AuthenticatedLayout>
            <Head title="Email Settings" />

            <div className="max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 className="ui-page-title">Email (SMTP)</h1>
                    <p className="text-muted-foreground">
                        Configure a custom SMTP server for sending invitation and notification emails.
                    </p>
                </div>

                {!hasSettings && (
                    <div className="rounded-md border bg-primary-soft border-primary/20 p-4 text-sm text-primary">
                        <strong>Using default mail driver.</strong> Configure SMTP below to use your own email provider.
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <Mail className="h-5 w-5 text-muted-foreground" />
                            <CardTitle>SMTP Configuration</CardTitle>
                        </div>
                        <CardDescription>
                            Credentials for your outgoing mail server (e.g. SendGrid, Mailgun, SES).
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-2 space-y-2">
                                    <Label htmlFor="smtp_host">SMTP host</Label>
                                    <Input
                                        id="smtp_host"
                                        placeholder="smtp.sendgrid.net"
                                        value={form.data.host}
                                        onChange={(e) => form.setData('host', e.target.value)}
                                    />
                                    {form.errors.host && <p className="text-sm text-destructive">{form.errors.host}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_port">Port</Label>
                                    <Input
                                        id="smtp_port"
                                        type="number"
                                        placeholder="587"
                                        value={form.data.port}
                                        onChange={(e) => form.setData('port', e.target.value)}
                                    />
                                    {form.errors.port && <p className="text-sm text-destructive">{form.errors.port}</p>}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="smtp_encryption">Encryption</Label>
                                <Select
                                    value={form.data.encryption}
                                    onValueChange={(v) => form.setData('encryption', v)}
                                >
                                    <SelectTrigger id="smtp_encryption" className="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="tls">STARTTLS</SelectItem>
                                        <SelectItem value="ssl">SSL/TLS</SelectItem>
                                        <SelectItem value="none">None</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_username">Username</Label>
                                    <Input
                                        id="smtp_username"
                                        placeholder="apikey"
                                        value={form.data.username}
                                        onChange={(e) => form.setData('username', e.target.value)}
                                        autoComplete="off"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="smtp_password">Password</Label>
                                    <Input
                                        id="smtp_password"
                                        type="password"
                                        placeholder="••••••••"
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        autoComplete="new-password"
                                    />
                                </div>
                            </div>

                            <div className="pt-2 border-t">
                                <p className="text-sm font-medium mb-3">Sender address</p>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="from_email">From email</Label>
                                        <Input
                                            id="from_email"
                                            type="email"
                                            placeholder="noreply@company.com"
                                            value={form.data.from_email}
                                            onChange={(e) => form.setData('from_email', e.target.value)}
                                        />
                                        {form.errors.from_email && <p className="text-sm text-destructive">{form.errors.from_email}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="from_name">From name</Label>
                                        <Input
                                            id="from_name"
                                            placeholder="Vouch"
                                            value={form.data.from_name}
                                            onChange={(e) => form.setData('from_name', e.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center gap-3 pt-2">
                                <Button type="submit" disabled={form.processing}>
                                    {form.processing ? 'Saving…' : 'Save settings'}
                                </Button>
                                {form.recentlySuccessful && (
                                    <span className="flex items-center gap-1 text-sm text-success">
                                        <CheckCircle className="h-4 w-4" />
                                        Saved
                                    </span>
                                )}
                                {hasSettings && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        className="ml-auto text-destructive hover:text-destructive"
                                        onClick={() => setClearOpen(true)}
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Clear settings
                                    </Button>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <AlertDialog open={clearOpen} onOpenChange={setClearOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Clear SMTP settings?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Vouch will revert to the default mail driver. Emails may stop working.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleClear}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Clear settings
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
