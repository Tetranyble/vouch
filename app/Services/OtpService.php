<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    private const TTL_MINUTES = 30;

    private const RATE_LIMIT_SECONDS = 60;

    public function generate(string $key): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($this->codeKey($key), $code, now()->addMinutes(self::TTL_MINUTES));

        return $code;
    }

    public function verify(string $key, string $code): bool
    {
        // Limit guesses, even when a bot bypasses browser-side controls.
        $attemptKey = 'otp_attempt:'.sha1($key);
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return false;
        }
        $stored = Cache::get($this->codeKey($key));
        if (! is_string($stored) || ! hash_equals($stored, $code)) {
            RateLimiter::hit($attemptKey, 600);

            return false;
        }
        Cache::forget($this->codeKey($key));
        RateLimiter::clear($attemptKey);

        return true;
    }

    public function isRateLimited(string $key): bool
    {
        return Cache::has($this->rateLimitKey($key));
    }

    public function setRateLimit(string $key): void
    {
        Cache::put($this->rateLimitKey($key), true, now()->addSeconds(self::RATE_LIMIT_SECONDS));
    }

    private function codeKey(string $key): string
    {
        return 'otp:'.sha1($key);
    }

    private function rateLimitKey(string $key): string
    {
        return 'otp_rl:'.sha1($key);
    }
}
