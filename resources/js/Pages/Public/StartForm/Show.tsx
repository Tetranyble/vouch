import { Head, useForm, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { FileText, ArrowRight, Users } from 'lucide-react';

export default function StartFormShow({ template, submitters }) {
    const { data, setData, put, processing, errors } = useForm({
        submitters: (submitters || []).map((s) => ({ uuid: s.uuid, name: '', email: '', phone: '' })),
    });

    function handleSubmit(e) {
        e.preventDefault();
        put(`/d/${template?.slug}`);
    }

    function updateSubmitter(index, field, value) {
        const updated = [...data.submitters];
        updated[index] = { ...updated[index], [field]: value };
        setData('submitters', updated);
    }

    return (
        <div className="min-h-screen bg-muted/30 flex items-center justify-center px-4 py-12">
            <Head title={template?.name || 'Start Document'} />
            <div className="w-full max-w-md">
                <div className="text-center mb-6">
                    <div className="inline-flex p-3 rounded-md bg-primary/10 mb-3">
                        <FileText className="h-8 w-8 text-primary" />
                    </div>
                    <h1 className="text-2xl font-bold">{template?.name || 'Start Document'}</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Fill in the details to start the signing process</p>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSubmit} className="space-y-5">
                            {(submitters || []).map((submitter, index) => (
                                <div key={submitter.uuid} className="space-y-3">
                                    {submitters.length > 1 && (
                                        <div className="flex items-center gap-2 text-sm font-medium">
                                            <div className="h-5 w-5 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs">
                                                {index + 1}
                                            </div>
                                            {submitter.name || `Signer ${index + 1}`}
                                        </div>
                                    )}
                                    <div className="space-y-3">
                                        <Input
                                            type="text"
                                            placeholder="Full name (optional)"
                                            value={data.submitters[index]?.name || ''}
                                            onChange={(e) => updateSubmitter(index, 'name', e.target.value)}
                                        />
                                        <Input
                                            type="email"
                                            placeholder="Email address"
                                            value={data.submitters[index]?.email || ''}
                                            onChange={(e) => updateSubmitter(index, 'email', e.target.value)}
                                        />
                                    </div>
                                </div>
                            ))}

                            <Button type="submit" size="lg" className="w-full gap-2" disabled={processing}>
                                {processing ? 'Starting…' : <><ArrowRight className="h-4 w-4" />Start Signing</>}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
