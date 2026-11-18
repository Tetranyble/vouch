import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import DeleteUserForm from './Partials/DeleteUserForm';
import UserSignatureForm from './Partials/UserSignatureForm';
import { ShieldCheck, ShieldOff } from 'lucide-react';
import { Button } from '@/Components/ui/button';

function TwoFactorSection({ mfaEnabled }) {
    return (
        <section className="rounded-md border bg-card p-6 space-y-4">
            <h2 className="text-lg font-semibold">Two-Factor Authentication</h2>

            {mfaEnabled ? (
                <div className="flex items-center gap-2 text-sm text-success">
                    <ShieldCheck className="h-4 w-4 shrink-0" />
                    <span>2FA is enabled on your account.</span>
                </div>
            ) : (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    <ShieldOff className="h-4 w-4 shrink-0" />
                    <span>2FA is not configured</span>
                </div>
            )}

            <Button className="w-full" variant={mfaEnabled ? 'outline' : 'default'} asChild>
                <Link href={route('settings.mfa.show')}>
                    {mfaEnabled ? 'Manage 2FA' : '🔒 Set up 2FA'}
                </Link>
            </Button>
        </section>
    );
}

export default function Edit({ mustVerifyEmail, status, userSignature, userInitials, mfaEnabled }) {
    return (
        <AuthenticatedLayout>
            <Head title="Profile" />

            <div className="max-w-3xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Profile</h1>
                    <p className="text-muted-foreground">Manage your account settings and personal information.</p>
                </div>

                <UpdateProfileInformationForm mustVerifyEmail={mustVerifyEmail} status={status} />
                <UserSignatureForm userSignature={userSignature} userInitials={userInitials} />
                <UpdatePasswordForm />
                <TwoFactorSection mfaEnabled={!!mfaEnabled} />
                <DeleteUserForm />
            </div>
        </AuthenticatedLayout>
    );
}
