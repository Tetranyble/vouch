@props(['preheader' => 'Secure document workflows', 'label' => 'Vouch notification'])
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $label }}</title>
</head>
<body style="margin:0; padding:0; background:#e9eef2; color:#172033; font-family:Arial, Helvetica, sans-serif;">
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; opacity:0; color:transparent;">{{ $preheader }}</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#e9eef2;">
        <tr>
            <td align="center" style="padding:36px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:640px; background:#fffefa; border:1px solid #cfd9e0;">
                    <tr>
                        <td style="padding:22px 36px; border-bottom:1px solid #d8e0e5; background:#fffefa;">
                            <img src="{{ asset('images/vouch-mark.svg') }}" width="26" height="26" alt="Vouch" style="display:inline-block; width:26px; height:26px; margin:0 9px -7px 0; border:0; vertical-align:middle;" />
                            <span style="color:#102a43; font-size:22px; font-weight:700; letter-spacing:-0.4px; vertical-align:middle;">Vouch</span>
                            <span style="float:right; padding-top:6px; color:#718096; font-size:12px;">Document control</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:40px 36px 36px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px; border-top:1px solid #d8e0e5; color:#718096; font-size:12px; line-height:1.6;">
                            Sent securely through Vouch &middot; Please do not reply to this automated email
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
