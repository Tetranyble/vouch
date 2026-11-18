import { Label } from '@/Components/ui/label';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Shield, Trash2, Upload } from 'lucide-react';
import { useState, useRef } from 'react';

export default function Esign({ settings }) {
    const [certName, setCertName] = useState('');
    const [timestampServer, setTimestampServer] = useState(settings.timestamp_server ?? '');
    const fileRef = useRef<HTMLInputElement>(null);

    function handleSubmit(e) {
        e.preventDefault();
        const data = new FormData(e.target);
        router.post('/settings/esign', data, {
            preserveScroll: true,
            forceFormData: true,
        });
    }

    function handleRemove() {
        router.delete('/settings/esign', { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="E-Sign Certificate" />
            <div className="max-w-2xl mx-auto px-4 md:px-6 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10"><Shield className="h-5 w-5 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">E-Sign Certificate</h1>
                        <p className="text-sm text-muted-foreground">Upload a PKCS#12 (.p12/.pfx) certificate for digital PDF signatures.</p>
                    </div>
                </div>

                {settings.has_certificate && (
                    <div className="rounded-md border bg-success-soft border-success/20 px-4 py-3 text-sm text-success flex items-center justify-between">
                        <span>Certificate active: <strong>{settings.certificate_name}</strong></span>
                        <Button variant="ghost" size="sm" className="text-destructive gap-1.5" onClick={handleRemove}>
                            <Trash2 className="h-3.5 w-3.5" />Remove
                        </Button>
                    </div>
                )}

                <form onSubmit={handleSubmit} className="rounded-md border bg-card p-6 space-y-4" encType="multipart/form-data">
                    <div>
                        <Label className="text-sm font-medium block mb-1">Certificate file (.p12 / .pfx)</Label>
                        <div
                            className="border-2 border-dashed rounded-md h-20 flex items-center justify-center cursor-pointer hover:bg-muted/30"
                            onClick={() => fileRef.current?.click()}
                        >
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Upload className="h-4 w-4" />Click to select certificate file
                            </div>
                        </div>
                        <Input ref={fileRef} type="file" name="certificate" accept=".p12,.pfx" className="hidden" required={!settings.has_certificate} />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">Certificate name (optional)</Label>
                        <Input name="certificate_name" placeholder="My Company Certificate" value={certName} onChange={(e) => setCertName(e.target.value)} />
                    </div>
                    <div>
                        <Label className="text-sm font-medium block mb-1">Timestamp server URL (optional)</Label>
                        <Input name="timestamp_server" type="url" placeholder="http://timestamp.sectigo.com" value={timestampServer} onChange={(e) => setTimestampServer(e.target.value)} />
                    </div>
                    <Button type="submit">Upload Certificate</Button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
