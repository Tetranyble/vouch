<x-vouch-mail.layout label="Confirm your Vouch email address" preheader="Confirm your email address to activate your Vouch workspace">
    <p style="margin:0 0 12px; color:#0f5d9e; font-size:13px; font-weight:700; line-height:1.4;">Account setup</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">Confirm your email address</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">Hi {{ $name }}, confirm this address to activate your Vouch workspace and keep its documents protected.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 0; border-left:5px solid #0f5d9e; background:#edf5fb;">
        <tr><td style="padding:16px 18px; color:#084675; font-size:14px; font-weight:700; line-height:1.5;">One step remains before your workspace is ready</td></tr>
    </table>
    <x-vouch-mail.button :url="$verificationUrl">Confirm email address</x-vouch-mail.button>
    <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">This secure link expires in 60 minutes. If you did not create a Vouch account, you can safely ignore this email.</p>
</x-vouch-mail.layout>
