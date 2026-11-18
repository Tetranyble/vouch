<x-mail::message>
# Signature requested

{{ $senderName }} has requested your signature on:

**{{ $templateName }}**

Review the document and sign when you are ready. This secure link is unique to you.

<x-mail::button :url="$formUrl">
Review & Sign Document
</x-mail::button>

If you were not expecting this request, you can safely ignore this email.

Thanks,
Vouch
</x-mail::message>
