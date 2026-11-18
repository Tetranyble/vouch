import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ChevronLeft, FileText } from 'lucide-react';

export default function TemplatesCreate() {
    const { data, setData, post, processing, errors } = useForm({ name: '' });

    function handleSubmit(e) {
        e.preventDefault();
        post('/templates');
    }

    return (
        <AuthenticatedLayout>
            <Head title="New Template" />
            <div className="max-w-xl mx-auto px-4 py-12">
                <Link href="/templates" className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-6">
                    <ChevronLeft className="h-4 w-4" />Templates
                </Link>
                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-3 mb-1">
                            <div className="p-2 rounded-md bg-primary/10"><FileText className="h-5 w-5 text-primary" /></div>
                            <CardTitle>New Template</CardTitle>
                        </div>
                        <CardDescription>Create a new document template.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div className="space-y-2">
                                <Label htmlFor="name">Template Name</Label>
                                <Input id="name" type="text" placeholder="e.g. Non-Disclosure Agreement"
                                    value={data.name} onChange={(e) => setData('name', e.target.value)} autoFocus />
                                {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                            </div>
                            <div className="flex gap-3 pt-2">
                                <Button type="submit" disabled={processing} className="flex-1">
                                    {processing ? 'Creating…' : 'Create & Edit Template'}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/templates">Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
