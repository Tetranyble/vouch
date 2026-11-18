import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { FileText, Send, Clock, CheckCircle2, Plus, ArrowRight } from 'lucide-react';

function StatCard({ title, value, icon: Icon, href, color }: { title: string; value: number; icon: typeof FileText; href?: string; color: string }) {
    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardContent className="pt-6">
                <div className="flex items-center justify-between">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">{title}</p>
                        <p className="text-3xl font-bold mt-1">{value}</p>
                    </div>
                    <div className={`p-3 rounded-md ${color}`}>
                        <Icon className="h-6 w-6" />
                    </div>
                </div>
                {href && (
                    <Link href={href} className="mt-4 text-xs text-muted-foreground hover:text-foreground flex items-center gap-1 group">
                        View all <ArrowRight className="h-3 w-3 group-hover:translate-x-0.5 transition-transform" />
                    </Link>
                )}
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ stats }: { stats: { templates: number; submissions: number; pending: number; completed: number } }) {
    return (
        <AuthenticatedLayout>
            <Head title="Dashboard" />
            <div className="max-w-7xl mx-auto px-4 md:px-6 py-8">
                <div className="flex items-center justify-between mb-8">
                    <div>
                        <h1 className="ui-page-title">Dashboard</h1>
                        <p className="text-muted-foreground mt-1">Overview of your document activity</p>
                    </div>
                    <Link href="/templates/create">
                        <Button className="gap-2"><Plus className="h-4 w-4" />New Template</Button>
                    </Link>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
                    <StatCard title="Templates" value={stats.templates} icon={FileText} href="/templates" color="bg-primary-soft text-primary" />
                    <StatCard title="Submissions" value={stats.submissions} icon={Send} href="/submissions" color="bg-muted text-muted-foreground" />
                    <StatCard title="Pending" value={stats.pending} icon={Clock} href="/submissions?status=pending" color="bg-warning-soft text-warning-foreground" />
                    <StatCard title="Completed" value={stats.completed} icon={CheckCircle2} href="/submissions?status=completed" color="bg-success-soft text-success" />
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <Link href="/templates/create" className="flex items-center justify-between p-3 rounded-md hover:bg-muted transition-colors group">
                                <div className="flex items-center gap-3">
                                    <div className="p-2 rounded-md bg-primary/10"><FileText className="h-4 w-4 text-primary" /></div>
                                    <div>
                                        <p className="font-medium text-sm">Create Template</p>
                                        <p className="text-xs text-muted-foreground">Build a new document template</p>
                                    </div>
                                </div>
                                <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Link>
                            <Link href="/submissions" className="flex items-center justify-between p-3 rounded-md hover:bg-muted transition-colors group">
                                <div className="flex items-center gap-3">
                                    <div className="p-2 rounded-md bg-muted"><Send className="h-4 w-4 text-muted-foreground" /></div>
                                    <div>
                                        <p className="font-medium text-sm">View Submissions</p>
                                        <p className="text-xs text-muted-foreground">Manage all document submissions</p>
                                    </div>
                                </div>
                                <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:translate-x-0.5 transition-transform" />
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
