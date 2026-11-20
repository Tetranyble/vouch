<x-vouch-mail.layout label="Reset your Vouch password" preheader="Use this secure link to reset your Vouch password">
    <p style="margin:0 0 12px; color:#0f5d9e; font-size:13px; font-weight:700; line-height:1.4;">Account security</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">Reset your password</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">Hi {{ $name }}, we received a request to reset your Vouch password.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 0; border-left:5px solid #0f5d9e; background:#edf5fb;">
        <tr><td style="padding:16px 18px; color:#084675; font-size:14px; font-weight:700; line-height:1.5;">Use the secure link below to choose a new password</td></tr>
    </table>
    <x-vouch-mail.button :url="$resetUrl">Reset password</x-vouch-mail.button>
    <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">This link expires in {{ $expiresIn }} minutes. If you did not request a reset, ignore this email and your password will remain unchanged.</p>
</x-vouch-mail.layout>
