{{ $submitter->display_name }} has completed their part of: {{ $templateName }}

@if($submission->submitters->every(fn($s) => $s->completed_at))
All parties have signed. Download the completed document securely:
{{ $downloadUrl }}

For your security, this download link expires in 7 days.
@else
The document is still awaiting signatures from the remaining recipients. We will let you know when it is complete.
@endif

Vouch
