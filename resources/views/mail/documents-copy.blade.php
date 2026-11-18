<x-mail::message>
# Your signed document

Thank you for completing:

**{{ $documentName }}**

We have included a copy when the file size allows. You can always download your completed document from the secure link below.

<x-mail::button :url="$downloadUrl">
Download document
</x-mail::button>

For security, this download link expires in 7 days. Please keep this email for your records.

Thanks,
Vouch
</x-mail::message>
