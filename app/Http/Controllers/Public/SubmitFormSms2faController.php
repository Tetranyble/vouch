<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Submitter;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmitFormSms2faController extends Controller
{
    public function store(Request $request, string $slug, SmsService $sms, OtpService $otp): JsonResponse
    {
        $submitter = Submitter::where('slug', $slug)->firstOrFail();
        if (! $submitter->phone) {
            return response()->json(['error' => 'No phone number on file.'], 422);
        }
        if ($submitter->completed_at || $submitter->declined_at) {
            return response()->json(['error' => 'Form is already closed.'], 422);
        }
        if (! $sms->isConfigured()) {
            return response()->json(['error' => 'SMS verification is unavailable.'], 503);
        }
        $key = 'sms2fa:'.$slug;
        if ($otp->isRateLimited($key)) {
            return response()->json(['error' => 'Please wait before requesting another code.'], 429);
        }

        $code = $otp->generate($key);
        if (! $sms->send($submitter->phone, "Your Vouch verification code is: {$code}")) {
            return response()->json(['error' => 'Unable to deliver verification code.'], 502);
        }
        $otp->setRateLimit($key);

        return response()->json(['sent' => true]);
    }

    public function update(Request $request, string $slug, OtpService $otp): JsonResponse
    {
        $validated = $request->validate(['code' => ['required', 'string', 'size:6']]);
        Submitter::where('slug', $slug)->firstOrFail();
        if (! $otp->verify('sms2fa:'.$slug, $validated['code'])) {
            return response()->json(['error' => 'Invalid or expired code.'], 422);
        }
        $request->session()->put('sms2fa_verified:'.$slug, true);

        return response()->json(['verified' => true]);
    }
}
