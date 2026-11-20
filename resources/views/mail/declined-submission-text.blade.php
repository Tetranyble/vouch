{{ $submitter->name ?: $submitter->email ?: $submitter->phone ?: 'A recipient' }} declined to sign: {{ $documentName }}

@if(filled($declineReason))
Reason provided: {{ $declineReason }}

@endif
View the submission:
{{ $submissionUrl }}

You are receiving this email because you own or manage this document.

Vouch
