<x-vouch-mail.layout label="Signature declined" :preheader="'A recipient declined to sign '.$documentName">
    <p style="margin:0 0 12px; color:#a95d15; font-size:13px; font-weight:700; line-height:1.4;">Signature declined</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">This document needs your attention</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;"><strong>{{ $submitter->name ?: $submitter->email ?: $submitter->phone ?: 'A recipient' }}</strong> declined to sign the document below.</p>
    <x-vouch-mail.document :title="$documentName" :meta="'Recipient: '.($submitter->name ?: $submitter->email ?: $submitter->phone ?: 'Unknown')" status="A signature was declined" tone="warning" />
    @if(filled($declineReason))
        <p style="margin:24px 0 8px; color:#718096; font-size:13px; font-weight:700; line-height:1.4;">Reason provided</p>
        <div style="padding:16px 18px; border-left:3px solid #d9e1e6; background:#f6f8f9; color:#4b5d70; font-size:15px; line-height:1.65;">{{ $declineReason }}</div>
    @endif
    <x-vouch-mail.button :url="$submissionUrl">View submission</x-vouch-mail.button>
    <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">You are receiving this email because you own or manage this document.</p>
</x-vouch-mail.layout>
