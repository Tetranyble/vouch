import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Check, Copy, Code2 } from 'lucide-react';
import { useState } from 'react';

function CopyButton({ text, label }) {
    const [copied, setCopied] = useState(false);
    async function copy() {
        try {
            await navigator.clipboard.writeText(text);
        } catch {
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }
    return (
        <Button variant="outline" size="sm" className="gap-1.5 shrink-0" onClick={copy}>
            {copied ? <><Check className="h-3.5 w-3.5 text-success" />Copied!</> : <><Copy className="h-3.5 w-3.5" />{label}</>}
        </Button>
    );
}

export default function TemplateEmbed({ template, shareUrl }) {
    const iframeSnippet = `<iframe
  src="${shareUrl}"
  width="100%"
  height="800"
  style="border:none;border-radius:8px;"
  allow="camera *"
></iframe>`;

    const jsSnippet = `<div id="vouch-form"></div>
<script>
  (function() {
    var iframe = document.createElement('iframe');
    iframe.src = '${shareUrl}';
    iframe.style.width = '100%';
    iframe.style.height = '800px';
    iframe.style.border = 'none';
    iframe.style.borderRadius = '8px';
    document.getElementById('vouch-form').appendChild(iframe);
  })();
</script>`;

    const linkSnippet = shareUrl;

    return (
        <AuthenticatedLayout>
            <Head title={`Embed — ${template.name}`} />
            <div className="max-w-3xl mx-auto px-4 md:px-6 py-8">
                <div className="flex items-center gap-2 text-sm text-muted-foreground mb-6">
                    <Link href="/templates" className="hover:text-foreground">Templates</Link>
                    <span>/</span>
                    <Link href={`/templates/${template.id}`} className="hover:text-foreground">{template.name}</Link>
                    <span>/</span>
                    <span className="text-foreground">Embed</span>
                </div>

                <div className="flex items-center gap-3 mb-8">
                    <div className="p-2 rounded-md bg-primary/10">
                        <Code2 className="h-5 w-5 text-primary" />
                    </div>
                    <div>
                        <h1 className="ui-page-title">Embed Form</h1>
                        <p className="text-sm text-muted-foreground">{template.name}</p>
                    </div>
                </div>

                <div className="space-y-6">
                    {/* Direct link */}
                    <section className="rounded-md border bg-card p-6">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="font-semibold">Direct Link</h2>
                            <CopyButton text={linkSnippet} label="Copy link" />
                        </div>
                        <p className="text-sm text-muted-foreground mb-3">
                            Share this link directly with signers.
                        </p>
                        <div className="bg-muted rounded-md px-3 py-2 font-mono text-xs break-all">
                            {linkSnippet}
                        </div>
                    </section>

                    {/* iFrame embed */}
                    <section className="rounded-md border bg-card p-6">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="font-semibold">iFrame Embed</h2>
                            <CopyButton text={iframeSnippet} label="Copy code" />
                        </div>
                        <p className="text-sm text-muted-foreground mb-3">
                            Paste this HTML into your webpage to embed the signing form inline.
                        </p>
                        <pre className="bg-muted rounded-md p-4 text-xs overflow-x-auto whitespace-pre-wrap break-all">
                            {iframeSnippet}
                        </pre>
                    </section>

                    {/* JavaScript embed */}
                    <section className="rounded-md border bg-card p-6">
                        <div className="flex items-center justify-between mb-3">
                            <h2 className="font-semibold">JavaScript Embed</h2>
                            <CopyButton text={jsSnippet} label="Copy code" />
                        </div>
                        <p className="text-sm text-muted-foreground mb-3">
                            Dynamic embed — add a container div and the script will inject the form.
                        </p>
                        <pre className="bg-muted rounded-md p-4 text-xs overflow-x-auto whitespace-pre-wrap break-all">
                            {jsSnippet}
                        </pre>
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
