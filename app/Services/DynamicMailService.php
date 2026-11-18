<?php

namespace App\Services;

use App\Models\AccountConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class DynamicMailService
{
    private const SMTP_KEY = 'email_smtp';

    public static function configureForAccount(int $accountId): void
    {
        $config = AccountConfig::where('account_id', $accountId)
            ->where('key', self::SMTP_KEY)
            ->first();

        if (! $config || empty($config->value['host'])) {
            return;
        }

        $settings = $config->value;

        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['host']);
        Config::set('mail.mailers.smtp.port', (int) ($settings['port'] ?? 587));
        Config::set('mail.mailers.smtp.username', $settings['username'] ?? null);
        Config::set('mail.mailers.smtp.password', $settings['password'] ?? null);
        Config::set('mail.mailers.smtp.encryption', match ($settings['encryption'] ?? 'tls') {
            'tls' => 'tls',
            'ssl' => 'ssl',
            default => null,
        });
        Config::set('mail.from.address', $settings['from_email']);
        Config::set('mail.from.name', $settings['from_name'] ?? config('app.name', 'Vouch'));
        Config::set('mail.default', 'smtp');

        Mail::forgetMailers();
    }
}
