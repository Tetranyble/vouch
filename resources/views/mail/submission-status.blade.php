@php($isComplete = $type === 'submission_completed')
<x-vouch-mail.layout :label="$isComplete ? 'Document signing complete' : 'Document review requested'" :preheader="$message">
    <p style="margin:0 0 12px; color:{{ $isComplete ? '#29705a' : '#0f5d9e' }}; font-size:13px; font-weight:700; line-height:1.4;">{{ $isComplete ? 'Signing complete' : 'Document review' }}</p>
    <h1 style="margin:0; color:#102a43; font-size:29px; font-weight:600; line-height:1.22;">{{ $isComplete ? 'A document has been completed' : 'A document is ready for review' }}</h1>
    <p style="margin:20px 0 0; color:#4b5d70; font-size:16px; line-height:1.65;">{{ $message }}</p>
    <x-vouch-mail.document :title="$documentName" :meta="$isComplete ? 'Completed document' : 'Document shared with you'" :status="$isComplete ? 'Signing is complete' : 'Review requested'" :tone="$isComplete ? 'complete' : 'action'" />
    <p style="margin:24px 0 0; color:#64748b; font-size:13px; line-height:1.65;">This notification was sent securely through Vouch.</p>
</x-vouch-mail.layout>
