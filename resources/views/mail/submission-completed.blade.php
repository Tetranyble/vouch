<x-vouch-mail.layout label="Document signing update" :preheader="$submitter->display_name.' completed '.$templateName">
    <p style="margin:0 0 12px; color:#29705a; font-size:13px; font-weight:700; line-height:1.4;">Signature recorded</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">A signer has finished</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;"><strong>{{ $submitter->display_name }}</strong> completed their part of the document below.</p>
    <x-vouch-mail.document :title="$templateName" :meta="'Completed by '.$submitter->display_name" :status="$submission->submitters->every(fn($s) => $s->completed_at) ? 'All signatures are complete' : 'Waiting for the remaining recipients'" :tone="$submission->submitters->every(fn($s) => $s->completed_at) ? 'complete' : 'action'" />
    @if($submission->submitters->every(fn($s) => $s->completed_at))
        <p style="margin:24px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">All parties have signed. The completed document is ready for your records.</p>
        <x-vouch-mail.button :url="$downloadUrl">Download completed document</x-vouch-mail.button>
        <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">For your security, this download link expires in 7 days.</p>
    @else
        <p style="margin:24px 0 0; color:#64748b; font-size:13px; line-height:1.65;">The document is still awaiting signatures from the remaining recipients. We&rsquo;ll let you know when it is complete.</p>
    @endif
</x-vouch-mail.layout>
