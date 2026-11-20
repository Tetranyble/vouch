@props(['url'])
<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:28px 0;">
    <tr>
        <td style="background:#0f5d9e; border-bottom:3px solid #084675;">
            <a href="{{ $url }}" style="display:inline-block; padding:15px 22px; color:#ffffff; font-size:15px; font-weight:700; line-height:1; text-decoration:none;">{{ $slot }} <span style="padding-left:8px;">&rarr;</span></a>
        </td>
    </tr>
</table>
