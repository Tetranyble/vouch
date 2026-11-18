import { signerColor } from '@/lib/design-tokens';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ChevronLeft, Pencil } from 'lucide-react';
import { useRef, useEffect, useState } from 'react';
import * as pdfjsLib from 'pdfjs-dist';

pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.js';


function submitterColor(submitters, submitterUuid) {
    const idx = submitters.findIndex((s) => s.uuid === submitterUuid);
    return signerColor(idx < 0 ? 0 : idx);
}

// ─── PDF page canvas ─────────────────────────────────────────────────────────

function PdfPage({ url, pageNumber }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        let cancelled = false;
        let renderTask = null;

        (async () => {
            try {
                const pdf  = await pdfjsLib.getDocument({ url }).promise;
                const page = await pdf.getPage(pageNumber);
                if (cancelled) return;

                const viewport = page.getViewport({ scale: 1.5 });
                const canvas   = canvasRef.current;
                if (!canvas) return;

                canvas.width  = viewport.width;
                canvas.height = viewport.height;

                const canvasContext = canvas.getContext('2d');
                if (!canvasContext) return;
                renderTask = page.render({ canvas, canvasContext, viewport });
                await renderTask.promise;
            } catch {
                // ignore
            }
        })();

        return () => {
            cancelled = true;
            renderTask?.cancel?.();
        };
    }, [url, pageNumber]);

    return <canvas ref={canvasRef} className="w-full block" />;
}

// ─── Read-only field overlay ──────────────────────────────────────────────────

function FieldOverlay({ field, area, pageW, pageH, color, label }) {
    if (!pageW || !pageH) return null;

    const style = {
        left:   `${area.x * 100}%`,
        top:    `${area.y * 100}%`,
        width:  `${area.w * 100}%`,
        height: `${area.h * 100}%`,
        borderColor: color,
        backgroundColor: color + '22',
    };

    return (
        <div
            className="absolute border-2 rounded flex items-center overflow-hidden px-1"
            style={style}
        >
            <span
                className="text-xs font-medium truncate leading-none"
                style={{ color, fontSize: '10px' }}
            >
                {label || field.type}
            </span>
        </div>
    );
}

// ─── One document page with overlays ─────────────────────────────────────────

function PreviewPage({ doc, pageIndex, fields, submitters }) {
    const wrapRef  = useRef<HTMLDivElement>(null);
    const [dims, setDims] = useState({ w: 0, h: 0 });
    const isPdf = doc.content_type === 'application/pdf';

    useEffect(() => {
        if (!wrapRef.current) return;
        const observer = new ResizeObserver(() => {
            if (wrapRef.current) {
                setDims({ w: wrapRef.current.offsetWidth, h: wrapRef.current.offsetHeight });
            }
        });
        observer.observe(wrapRef.current);
        return () => observer.disconnect();
    }, []);

    const areasOnPage = [];
    fields.forEach((field) => {
        (field.areas ?? []).forEach((area) => {
            if (area.attachment_uuid === doc.uuid && area.page === pageIndex) {
                areasOnPage.push({ field, area });
            }
        });
    });

    return (
        <div ref={wrapRef} className="relative" style={{ lineHeight: 0 }}>
            {isPdf ? (
                <PdfPage url={doc.url} pageNumber={pageIndex + 1} />
            ) : (
                <img src={doc.url} alt={doc.filename} className="w-full block" />
            )}
            {areasOnPage.map(({ field, area }) => (
                <FieldOverlay
                    key={`${field.uuid}:${area.page}`}
                    field={field}
                    area={area}
                    pageW={dims.w}
                    pageH={dims.h}
                    color={submitterColor(submitters, field.submitter_uuid)}
                    label={field.name || field.type}
                />
            ))}
        </div>
    );
}

// ─── Main export ──────────────────────────────────────────────────────────────

export default function TemplatePreview({ template }) {
    const { id, name, fields = [], submitters = [], documents = [] } = template;

    return (
        <AuthenticatedLayout>
            <Head title={`Preview — ${name}`} />
            <div className="max-w-4xl mx-auto px-4 md:px-6 py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-3">
                        <Link href={`/templates/${id}`}>
                            <Button variant="ghost" size="icon" className="h-8 w-8">
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-xl font-bold">{name}</h1>
                            <p className="text-xs text-muted-foreground">Read-only preview</p>
                        </div>
                    </div>
                    <Link href={`/templates/${id}/builder`}>
                        <Button size="sm" className="gap-1.5">
                            <Pencil className="h-3.5 w-3.5" />Edit
                        </Button>
                    </Link>
                </div>

                {/* Submitter legend */}
                {submitters.length > 0 && (
                    <div className="flex items-center gap-2 mb-6 flex-wrap">
                        {submitters.map((s, i) => (
                            <Badge
                                key={s.uuid}
                                variant="outline"
                                className="gap-1.5"
                                style={{ borderColor: signerColor(i), color: signerColor(i) }}
                            >
                                <span
                                    className="inline-block h-2 w-2 rounded-full"
                                    style={{ background: signerColor(i) }}
                                />
                                {s.name || `Signer ${i + 1}`}
                            </Badge>
                        ))}
                    </div>
                )}

                {/* No documents */}
                {documents.length === 0 && (
                    <div className="border-2 border-dashed rounded-md py-20 text-center text-muted-foreground">
                        <p className="text-lg font-medium">No documents uploaded</p>
                        <p className="text-sm mt-1">Add a PDF in the builder to see the preview.</p>
                        <Link href={`/templates/${id}/builder`} className="mt-4 inline-block">
                            <Button size="sm">Open Builder</Button>
                        </Link>
                    </div>
                )}

                {/* Document pages */}
                <div className="space-y-8">
                    {documents.map((doc) => (
                        <div key={doc.uuid} className="rounded-md overflow-hidden border shadow-sm ui-document-paper">
                            <div className="flex items-center gap-2 px-4 py-2 bg-muted border-b text-sm">
                                <span className="font-medium truncate flex-1">{doc.filename}</span>
                                <span className="text-muted-foreground text-xs">
                                    {doc.page_count} {doc.page_count === 1 ? 'page' : 'pages'}
                                </span>
                            </div>
                            {Array.from({ length: doc.page_count }, (_, i) => (
                                <div key={i} className={i < doc.page_count - 1 ? 'border-b' : ''}>
                                    <PreviewPage
                                        doc={doc}
                                        pageIndex={i}
                                        fields={fields}
                                        submitters={submitters}
                                    />
                                </div>
                            ))}
                        </div>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
