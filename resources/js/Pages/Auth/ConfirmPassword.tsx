import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';
import { ShieldCheck } from 'lucide-react';
import VouchLogo from '@/Components/VouchLogo';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({ password: '' });

    function submit(e) {
        e.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    }

    return (
        <div className="min-h-screen bg-muted/30 flex items-center justify-center px-4">
            <Head title="Confirm Password" />
            <div className="w-full max-w-md">
                <div className="flex justify-center mb-8"><VouchLogo size={40} /></div>

                <Card>
                    <CardHeader className="text-center pb-2">
                        <div className="flex justify-center mb-3">
                            <div className="h-14 w-14 rounded-full bg-primary/10 flex items-center justify-center">
                                <ShieldCheck className="h-7 w-7 text-primary" />
                            </div>
                        </div>
                        <CardTitle className="text-2xl">Confirm your password</CardTitle>
                        <CardDescription>
                            This is a secure area. Please confirm your password before continuing.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    placeholder="••••••••"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    autoFocus
                                    autoComplete="current-password"
                                />
                                {errors.password && <p className="text-sm text-destructive">{errors.password}</p>}
                            </div>

                            <Button type="submit" className="w-full" disabled={processing}>
                                {processing ? 'Confirming…' : 'Confirm password'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
