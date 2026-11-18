import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { RotateCcw, FileText, Archive } from 'lucide-react';

export default function TemplatesArchived({ templates }) {
    function handleRestore(id) {
        router.put(`/templates/${id}/restore`, {}, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout>
            <Head title="Archived Templates" />
            <div className="max-w-4xl mx-auto px-4 md:px-6 py-8">
                <div className="flex items-center gap-3 mb-6">
                    <div className="p-2 rounded-md bg-muted">
                        <Archive className="h-5 w-5 text-muted-foreground" />
                    </div>
                    <div>
                        <h1 className="ui-page-title">Archived Templates</h1>
                        <p className="text-sm text-muted-foreground">Templates removed from the active list. Restore to use them again.</p>
                    </div>
                    <Link href="/templates" className="ml-auto text-sm text-primary hover:underline">← Back to Templates</Link>
                </div>

                {templates.data?.length === 0 ? (
                    <div className="rounded-md border bg-card p-12 text-center">
                        <Archive className="h-10 w-10 text-muted-foreground mx-auto mb-3 opacity-40" />
                        <p className="font-medium">No archived templates</p>
                        <p className="text-sm text-muted-foreground mt-1">Templates you archive will appear here.</p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {templates.data?.map((template) => (
                            <div key={template.id} className="rounded-md border bg-card px-4 py-3 flex items-center gap-3">
                                <FileText className="h-4 w-4 text-muted-foreground shrink-0" />
                                <div className="flex-1 min-w-0">
                                    <p className="font-medium truncate">{template.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {template.folder_name} · Archived {new Date(template.archived_at).toLocaleDateString()}
                                    </p>
                                </div>
                                <Button variant="outline" size="sm" className="gap-1.5 shrink-0" onClick={() => handleRestore(template.id)}>
                                    <RotateCcw className="h-3.5 w-3.5" />Restore
                                </Button>
                            </div>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {(templates.prev_page_url || templates.next_page_url) && (
                    <div className="flex justify-between mt-6">
                        {templates.prev_page_url ? (
                            <Link href={templates.prev_page_url}><Button variant="outline" size="sm">← Previous</Button></Link>
                        ) : <span />}
                        {templates.next_page_url && (
                            <Link href={templates.next_page_url}><Button variant="outline" size="sm">Next →</Button></Link>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
