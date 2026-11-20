<x-vouch-mail.layout label="Signature requested" :preheader="$senderName.' needs your signature on '.$templateName">
    <p style="margin:0 0 12px; color:#0f5d9e; font-size:13px; font-weight:700; line-height:1.4;">Action required</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">A document is ready for your signature</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">Hi {{ $submitter->display_name }}, {{ $senderName }} has sent you a document to review and sign.</p>
    <x-vouch-mail.document :title="$templateName" :meta="'Requested by '.$senderName" status="Your signature is needed" />
    <x-vouch-mail.button :url="$formUrl">Open document</x-vouch-mail.button>
    <p style="margin:0; color:#64748b; font-size:13px; line-height:1.65;">This private link is for you. You do not need a Vouch account to sign. If you do not recognise this request, you can safely ignore it.</p>
</x-vouch-mail.layout>
