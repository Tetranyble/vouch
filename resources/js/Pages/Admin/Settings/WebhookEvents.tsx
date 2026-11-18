import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ArrowLeft, CheckCircle2, XCircle, Clock } from 'lucide-react';

const STATUS_CONFIG = {
    success: { icon: CheckCircle2, className: 'text-success', label: 'Success' },
    error: { icon: XCircle, className: 'text-destructive', label: 'Error' },
};

function EventRow({ event }) {
    const config = STATUS_CONFIG[event.status] ?? { icon: Clock, className: 'text-muted-foreground', label: event.status };
    const Icon = config.icon;

    return (
        <div className="flex items-center gap-3 py-3 border-b last:border-0">
            <Icon className={`h-4 w-4 shrink-0 ${config.className}`} />
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                    <span className="font-mono text-sm font-medium">{event.event_type}</span>
                    {event.response_code && (
                        <Badge variant={event.status === 'success' ? 'outline' : 'destructive'} className="text-xs">
                            HTTP {event.response_code}
                        </Badge>
                    )}
                </div>
                <p className="text-xs text-muted-foreground mt-0.5">
                    {new Date(event.created_at).toLocaleString()}
                </p>
            </div>
        </div>
    );
}

export default function WebhookEvents({ webhook, events, filters }) {
    const statusOptions = ['', 'success', 'error'];

    function buildUrl(status) {
        const params = new URLSearchParams();
        if (status) params.set('status', status);
        const qs = params.toString();
        return route('settings.webhooks.events', webhook.id) + (qs ? '?' + qs : '');
    }

    return (
        <AuthenticatedLayout>
            <Head title={`Webhook Events — ${webhook.url}`} />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex items-center gap-3">
                    <Link href={route('settings.webhooks.index')}>
                        <Button variant="ghost" size="icon">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <div>
                        <h1 className="ui-page-title">Webhook Events</h1>
                        <p className="text-sm text-muted-foreground font-mono truncate max-w-md">{webhook.url}</p>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    {statusOptions.map(status => (
                        <Link key={status || 'all'} href={buildUrl(status)}>
                            <Button
                                variant={filters.status === status ? 'default' : 'outline'}
                                size="sm"
                            >
                                {status === '' ? 'All' : status.charAt(0).toUpperCase() + status.slice(1)}
                            </Button>
                        </Link>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">
                            Event Log
                            <span className="ml-2 text-sm font-normal text-muted-foreground">
                                ({events.total} total)
                            </span>
                        </CardTitle>
                        <CardDescription>
                            Delivery attempts for this webhook endpoint.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {events.data.length === 0 ? (
                            <div className="py-12 text-center text-muted-foreground">
                                <p>No events found.</p>
                            </div>
                        ) : (
                            <div>
                                {events.data.map(event => (
                                    <EventRow key={event.id} event={event} />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {(events.prev_page_url || events.next_page_url) && (
                    <div className="flex justify-center gap-2">
                        {events.prev_page_url && (
                            <Link href={events.prev_page_url}>
                                <Button variant="outline" size="sm">Previous</Button>
                            </Link>
                        )}
                        {events.next_page_url && (
                            <Link href={events.next_page_url}>
                                <Button variant="outline" size="sm">Next</Button>
                            </Link>
                        )}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
