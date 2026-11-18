import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Palette, Upload, X } from 'lucide-react';
import { useState, useRef } from 'react';

export default function Personalization({ settings }) {
    const [form, setForm] = useState({
        logo: settings.logo ?? '',
        primary_color: settings.primary_color ?? '#3b82f6',
        completed_message: settings.completed_message ?? '',
        completed_button_label: settings.completed_button_label ?? '',
        completed_button_url: settings.completed_button_url ?? '',
    });
    const logoInputRef = useRef<HTMLInputElement>(null);

    function set(k, v) { setForm((p) => ({ ...p, [k]: v })); }

    function handleLogoUpload(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => set('logo', ev.target.result);
        reader.readAsDataURL(file);
    }

    function handleSubmit(e) {
        e.preventDefault();
        router.patch('/settings/personalization', form, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Personalization" />
            <div className="max-w-2xl mx-auto px-4 md:px-6 py-8 space-y-6">
                <div className="flex items-center gap-3">
                    <div className="p-2 rounded-md bg-primary/10"><Palette className="h-5 w-5 text-primary" /></div>
                    <div>
                        <h1 className="ui-page-title">Personalization</h1>
                        <p className="text-sm text-muted-foreground">Customize the branding shown to signers.</p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="rounded-md border bg-card p-6 space-y-5">
                    {/* Logo */}
                    <div>
                        <Label className="text-sm font-medium block mb-2">Logo</Label>
                        {form.logo ? (
                            <div className="flex items-center gap-3">
                                <img src={form.logo} alt="Logo" className="h-12 object-contain border rounded-md p-1 bg-document" />
                                <Button variant="ghost" size="sm" type="button" onClick={() => set('logo', '')} className="text-xs text-destructive hover:underline flex items-center gap-1">
                                    <X className="h-3 w-3" />Remove
                                </Button>
                            </div>
                        ) : (
                            <div
                                className="border-2 border-dashed rounded-md h-20 flex items-center justify-center cursor-pointer hover:bg-muted/30"
                                onClick={() => logoInputRef.current?.click()}
                            >
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Upload className="h-4 w-4" />Upload logo
                                </div>
                            </div>
                        )}
                        <Input ref={logoInputRef} type="file" accept="image/*" className="hidden" onChange={handleLogoUpload} />
                    </div>

                    {/* Brand color */}
                    <div>
                        <Label className="text-sm font-medium block mb-1">Primary color</Label>
                        <div className="flex items-center gap-3">
                            <Input type="color" value={form.primary_color} onChange={(e) => set('primary_color', e.target.value)} className="h-9 w-14 rounded border cursor-pointer" />
                            <Input value={form.primary_color} onChange={(e) => set('primary_color', e.target.value)} className="w-32 font-mono text-sm" />
                        </div>
                    </div>

                    {/* Completion message */}
                    <div>
                        <Label className="text-sm font-medium block mb-1">Completion message</Label>
                        <Textarea
                            value={form.completed_message}
                            onChange={(e) => set('completed_message', e.target.value)}
                            rows={3}
                            placeholder="Thank you for signing!"
                            className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                        />
                    </div>

                    {/* Completion button */}
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label className="text-sm font-medium block mb-1">Button label</Label>
                            <Input placeholder="Go to website" value={form.completed_button_label} onChange={(e) => set('completed_button_label', e.target.value)} />
                        </div>
                        <div>
                            <Label className="text-sm font-medium block mb-1">Button URL</Label>
                            <Input type="url" placeholder="https://…" value={form.completed_button_url} onChange={(e) => set('completed_button_url', e.target.value)} />
                        </div>
                    </div>

                    <Button type="submit">Save Settings</Button>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
