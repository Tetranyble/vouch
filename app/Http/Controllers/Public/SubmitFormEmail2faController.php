<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\OtpVerificationMail;
use App\Models\Submitter;
use App\Services\OtpService;
use App\Services\SubmissionFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SubmitFormEmail2faController extends Controller
{
    public function store(
        Request $request,
        OtpService $otp,
        SubmissionFlowService $flowService,
        string $slug
    ): JsonResponse {
        $submitter = Submitter::where('slug', $slug)->with('submission.template')->firstOrFail();

        if (!$submitter->email) {
            return response()->json(['error' => 'No email address on file.'], 422);
        }

        if ($otp->isRateLimited($submitter->slug)) {
            return response()->json(['error' => 'Please wait before requesting another code.'], 429);
        }

        $code         = $otp->generate($submitter->slug);
        $documentName = $submitter->submission?->template?->name ?? 'Document';

        Mail::to($submitter->email)->send(new OtpVerificationMail($code, $documentName));
        $otp->setRateLimit($submitter->slug);

        $flowService->sendEmail2fa($submitter->submission, $submitter, ['ip' => $request->ip()]);

        return response()->json(['slug' => $slug, 'status' => '2fa_sent']);
    }

    public function update(
        Request $request,
        OtpService $otp,
        SubmissionFlowService $flowService,
        string $slug
    ): JsonResponse {
        $request->validate(['code' => 'required|string|size:6']);

        $submitter = Submitter::where('slug', $slug)->with('submission')->firstOrFail();

        if (!$otp->verify($submitter->slug, $request->input('code'))) {
            return response()->json(['error' => 'Invalid or expired verification code.'], 422);
        }

        $flowService->verifyEmail2fa($submitter->submission, $submitter, ['ip' => $request->ip()]);

        // Store verified state in session so the form can proceed
        session()->put('email_2fa_verified:' . $slug, true);

        return response()->json(['slug' => $slug, 'status' => '2fa_verified']);
    }
}
