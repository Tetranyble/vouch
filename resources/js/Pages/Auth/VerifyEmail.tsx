import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { Mail } from 'lucide-react';
import VouchLogo from '@/Components/VouchLogo';

export default function VerifyEmail({ status }: { status?: string | null }) {
    const { post, processing } = useForm({});

    function submit(e) {
        e.preventDefault();
        post(route('verification.send'));
    }

    return (
        <div className="min-h-screen bg-muted/30 flex items-center justify-center px-4">
            <Head title="Verify Email" />
            <div className="w-full max-w-md">
                <div className="flex justify-center mb-8"><VouchLogo size={40} /></div>

                <Card>
                    <CardHeader className="text-center pb-2">
                        <div className="flex justify-center mb-3">
                            <div className="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center">
                                <Mail className="h-7 w-7 text-primary" />
                            </div>
                        </div>
                        <CardTitle className="text-2xl">Check your email</CardTitle>
                        <CardDescription>
                            Thanks for signing up! Please verify your email address by clicking the link we sent you.
                            If you didn't receive it, we can send another.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-4 space-y-4">
                        {status === 'verification-link-sent' && (
                            <div className="text-sm font-medium text-success bg-success-soft p-3 rounded-md text-center">
                                A new verification link has been sent to your email address.
                            </div>
                        )}

                        <form onSubmit={submit}>
                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Sending…' : 'Resend verification email'}
                            </Button>
                        </form>

                        <div className="text-center">
                            <Link
                                href={route('logout')}
                                method="post"
                                as="button"
                                className="text-sm text-muted-foreground hover:text-foreground underline"
                            >
                                Log out
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
