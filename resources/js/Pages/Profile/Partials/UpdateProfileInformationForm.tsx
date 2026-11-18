import type * as React from 'react';
import type { VouchPageProps } from '@/types/vouch';
import { Link, useForm, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { CheckCircle } from 'lucide-react';

export default function UpdateProfileInformationForm({ mustVerifyEmail, status }: { mustVerifyEmail?: boolean; status?: string | null }) {
    const user = usePage<VouchPageProps>().props.auth.user;
    if (!user) throw new Error('Sign in is required to edit profile information');

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        first_name: user.first_name ?? '',
        last_name: user.last_name ?? '',
        email: user.email ?? '',
    });

    function submit(e: React.FormEvent<HTMLFormElement>) {
        e.preventDefault();
        patch(route('profile.update'));
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle>Profile Information</CardTitle>
                <CardDescription>Update your name and email address.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="first_name">First name</Label>
                            <Input
                                id="first_name"
                                value={data.first_name}
                                onChange={(e) => setData('first_name', e.target.value)}
                                autoFocus
                                autoComplete="given-name"
                            />
                            {errors.first_name && <p className="text-sm text-destructive">{errors.first_name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="last_name">Last name</Label>
                            <Input
                                id="last_name"
                                value={data.last_name}
                                onChange={(e) => setData('last_name', e.target.value)}
                                autoComplete="family-name"
                            />
                            {errors.last_name && <p className="text-sm text-destructive">{errors.last_name}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            autoComplete="email"
                        />
                        {errors.email && <p className="text-sm text-destructive">{errors.email}</p>}
                    </div>

                    {mustVerifyEmail && !user.email_verified_at && (
                        <div className="text-sm text-warning-foreground bg-warning-soft p-3 rounded-md">
                            Your email address is unverified.{' '}
                            <Link
                                href={route('verification.send')}
                                method="post"
                                as="button"
                                className="underline font-medium hover:text-warning"
                            >
                                Resend verification email
                            </Link>
                            {status === 'verification-link-sent' && (
                                <span className="block mt-1 text-success">Verification link sent!</span>
                            )}
                        </div>
                    )}

                    <div className="flex items-center gap-3 pt-2">
                        <Button type="submit" disabled={processing}>Save changes</Button>
                        {recentlySuccessful && (
                            <span className="flex items-center gap-1 text-sm text-success">
                                <CheckCircle className="h-4 w-4" />
                                Saved
                            </span>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
