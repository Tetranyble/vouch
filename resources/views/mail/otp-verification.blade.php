<x-vouch-mail.layout label="Your Vouch verification code" :preheader="'Use this code to continue signing '.$documentName">
    <p style="margin:0 0 12px; color:#0f5d9e; font-size:13px; font-weight:700; line-height:1.4;">Identity check</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">Use this code to continue</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">Enter this code to verify your identity before signing <strong>{{ $documentName }}</strong>.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 0; border:1px solid #ccd7df; background:#f6f9fb;">
        <tr><td style="padding:22px; color:#102a43; font-family:Consolas, Monaco, monospace; font-size:32px; font-weight:700; letter-spacing:8px; line-height:1.2; text-align:center;">{{ $otpCode }}</td></tr>
        <tr><td style="padding:11px 18px; border-top:1px solid #dce5ea; color:#64748b; font-size:13px; line-height:1.5; text-align:center;">Code valid for 30 minutes</td></tr>
    </table>
    <p style="margin:24px 0 0; color:#64748b; font-size:13px; line-height:1.65;">Never share this code with anyone, including Vouch support. If you did not request it, you can safely ignore this email.</p>
</x-vouch-mail.layout>
