<x-vouch-mail.layout label="Your signed document" :preheader="'Your signed copy of '.$documentName.' is ready'">
    <p style="margin:0 0 12px; color:#29705a; font-size:13px; font-weight:700; line-height:1.4;">Signing complete</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">Keep your signed copy</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">Hi {{ $submitter->display_name }}, your document has been completed and recorded.</p>
    <x-vouch-mail.document :title="$documentName" meta="Completed document" status="Your copy is ready to download" tone="complete" />
    <x-vouch-mail.button :url="$downloadUrl">Download signed copy</x-vouch-mail.button>
    <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">A PDF is attached when its size permits. The secure download link expires in 7 days.</p>
</x-vouch-mail.layout>
