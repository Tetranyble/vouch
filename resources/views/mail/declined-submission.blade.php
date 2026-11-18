<x-mail::message>
# Signature declined

{{ $submitter->name ?: $submitter->email ?: $submitter->phone ?: 'A recipient' }} declined to sign:

**{{ $documentName }}**

@if(filled($declineReason))
**Reason provided**

{{ $declineReason }}
@endif

<x-mail::button :url="$submissionUrl">
View submission
</x-mail::button>

You are receiving this email because you own or manage this document.

Thanks,
Vouch
</x-mail::message>
