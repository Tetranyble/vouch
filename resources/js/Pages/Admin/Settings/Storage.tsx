import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { HardDrive, LockKeyhole, Server, ShieldCheck } from 'lucide-react';

type Settings = {
    driver: string;
    quota_bytes: number;
    used_bytes: number;
    configured_centrally: boolean;
};

const formatBytes = (bytes: number): string => {
    if (bytes <= 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    return `${(bytes / 1024 ** index).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
};

export default function Storage({ settings }: { settings: Settings }) {
    const fraction = settings.quota_bytes > 0
        ? Math.min(100, Math.round((settings.used_bytes / settings.quota_bytes) * 100))
        : 0;

    return (
        <AuthenticatedLayout>
            <Head title="Storage Settings" />
            <div className="mx-auto max-w-3xl space-y-6 px-4 py-8 md:px-6">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-primary/10 p-3"><HardDrive className="h-6 w-6 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">Document storage</h1>
                        <p className="text-sm text-muted-foreground">Secure storage for templates, signatures, and signed documents.</p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base"><Server className="h-4 w-4" /> Active storage provider</CardTitle>
                        <CardDescription>Configured centrally through Tetranyble Storage. Provider credentials never appear in the browser.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3">
                        <span className="rounded-md border px-3 py-2 font-mono text-sm">{settings.driver}</span>
                        <span className="flex items-center gap-2 text-sm text-muted-foreground"><LockKeyhole className="h-4 w-4" /> Private by default</span>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Account storage usage</CardTitle>
                        <CardDescription>Package-managed files count toward this account's configured quota.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="flex items-baseline justify-between gap-2 text-sm">
                            <strong>{formatBytes(settings.used_bytes)} used</strong>
                            <span className="text-muted-foreground">of {formatBytes(settings.quota_bytes)}</span>
                        </div>
                        <div className="h-2 overflow-hidden rounded-full bg-muted" role="progressbar" aria-valuenow={fraction} aria-valuemin={0} aria-valuemax={100} aria-label="Storage utilization">
                            <div className="h-full rounded-full bg-primary transition-all" style={{ width: `${fraction}%` }} />
                        </div>
                        <p className="text-xs text-muted-foreground">Only files registered in centralized storage count toward this account’s usage.</p>
                    </CardContent>
                </Card>

                <div className="flex items-start gap-3 rounded-lg border p-4 text-sm text-muted-foreground">
                    <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0" />
                    <p>To change storage providers, configure the server's filesystem credentials and <code>STORAGE_DISK</code>, then deploy the change. Existing media needs a verified data migration before switching providers.</p>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
