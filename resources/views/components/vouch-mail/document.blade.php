@props(['title', 'meta', 'status', 'tone' => 'action'])
@php
    [$border, $ink, $surface] = match ($tone) {
        'complete' => ['#29705a', '#16513e', '#edf7f1'],
        'warning' => ['#a95d15', '#80420a', '#fff5e8'],
        default => ['#0f5d9e', '#084675', '#edf5fb'],
    };
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:24px 0 0; border:1px solid #ccd7df; border-left:5px solid {{ $border }}; background:#ffffff;">
    <tr>
        <td style="padding:18px 20px 8px; color:#718096; font-size:12px; line-height:1.4;">{{ $meta }}</td>
    </tr>
    <tr>
        <td style="padding:0 20px 18px; color:#102a43; font-size:19px; font-weight:700; line-height:1.35;">{{ $title }}</td>
    </tr>
    <tr>
        <td style="padding:11px 20px; border-top:1px solid #e4eaee; background:{{ $surface }}; color:{{ $ink }}; font-size:13px; font-weight:700; line-height:1.4;">{{ $status }}</td>
    </tr>
</table>
