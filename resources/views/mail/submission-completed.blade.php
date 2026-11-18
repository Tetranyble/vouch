<x-mail::message>
# Document completed

{{ $submitter->display_name }} has completed their part of:

**{{ $templateName }}**

@if($submission->submitters->every(fn($s) => $s->completed_at))
All parties have signed. Your completed document is ready to download.

<x-mail::button :url="$downloadUrl">
Download Document
</x-mail::button>

For security, this download link expires in 7 days.
@else
The document is still awaiting signatures from the remaining parties.
@endif

Thanks,
Vouch
</x-mail::message>
