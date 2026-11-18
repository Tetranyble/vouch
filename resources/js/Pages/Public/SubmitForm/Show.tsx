import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/Components/ui/dialog';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/Components/ui/tabs';
import { Select, SelectTrigger, SelectContent, SelectValue, SelectItem } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Head, router } from '@inertiajs/react';
import { useState, useRef, useEffect, useCallback } from 'react';
import type * as React from 'react';
import { documentColors, resolvedCanvasColor, signerColor } from '@/lib/design-tokens';
import { Button } from '@/Components/ui/button';
import {
    CheckCircle2, FileText, X, Check, ChevronDown, ChevronRight, XCircle,
    Clock, Send, Eye, Users, Upload, Image,
} from 'lucide-react';
import * as pdfjsLib from 'pdfjs-dist';
import { evaluateFormula } from '@/lib/formula';

type SignerValue = string | number | boolean | null;
type SignerValues = Record<string, SignerValue>;
interface SignerField {
    uuid: string;
    name: string;
    type: string;
    required?: boolean;
    content?: string;
    formula?: string;
    preferences?: { formula?: string };
    conditions?: Array<{ field_uuid: string; operator: string; value?: string }>;
    options?: Array<{ uuid?: string; value: string }>;
    areas?: Array<{ attachment_uuid: string; page: number; x: number; y: number; w: number; h: number }>;
}
type SignerArea = NonNullable<SignerField['areas']>[number];
interface SignerDocument { uuid: string; url: string; filename: string; content_type: string; page_count: number }
interface SignerPerson {
    uuid: string;
    slug: string;
    name?: string;
    email?: string;
    status: string;
    order?: number;
    is_current?: boolean;
}
interface SigningPageProps {
    submitter: SignerPerson;
    fields: SignerField[];
    documents: SignerDocument[];
    schema?: unknown;
    template?: { name: string } | null;
    isCompleted?: boolean;
    isDeclined?: boolean;
    isAwaiting?: boolean;
    declineReason?: string | null;
    allSubmitters: SignerPerson[];
    previousSignature?: string | null;
    previousInitials?: string | null;
    initialValues?: SignerValues;
    isPreview?: boolean;
    previewRoles?: Array<{ uuid: string; name: string }>;
}
type CanvasDrawEvent = React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>;

pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.js';

// ─── Condition & Formula Utilities ───────────────────────────────────────────

function evaluateConditions(field: SignerField, values: SignerValues) {
    const conds = field.conditions ?? [];
    if (conds.length === 0) return true;
    return conds.every((cond) => {
        const v = String(values[cond.field_uuid] ?? '');
        switch (cond.operator) {
            case 'eq':       return v === cond.value;
            case 'neq':      return v !== cond.value;
            case 'contains': return v.includes(cond.value ?? '');
            case 'filled':   return v.length > 0;
            case 'empty':    return v.length === 0;
            default:         return true;
        }
    });
}

// ─── Signature / Initials Modal ───────────────────────────────────────────────

function DrawTab({ onSave, onClose }: { onSave: (dataUrl: string) => void; onClose: () => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [drawing, setDrawing] = useState(false);
    const [lastPos, setLastPos] = useState<{ x: number; y: number } | null>(null);
    const [hasContent, setHasContent] = useState(false);

    function getPos(e: CanvasDrawEvent, canvas: HTMLCanvasElement) {
        const rect = canvas.getBoundingClientRect();
        const point = 'touches' in e ? (e.touches[0] ?? e.changedTouches[0]) : e;
        const clientX = point.clientX;
        const clientY = point.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height),
        };
    }

    function startDraw(e: CanvasDrawEvent) {
        e.preventDefault();
        setDrawing(true);
        if (canvasRef.current) setLastPos(getPos(e, canvasRef.current));
    }

    function draw(e: CanvasDrawEvent) {
        if (!drawing || !lastPos) return;
        e.preventDefault();
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        const pos = getPos(e, canvas);
        ctx.beginPath();
        ctx.moveTo(lastPos.x, lastPos.y);
        ctx.lineTo(pos.x, pos.y);
        ctx.strokeStyle = resolvedCanvasColor('signature-ink');
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.stroke();
        setLastPos(pos);
        setHasContent(true);
    }

    function clear() {
        const canvas = canvasRef.current;
        if (!canvas) return;
        canvas.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height);
        setHasContent(false);
    }

    return (
        <div>
            <div className="relative border-2 border-dashed rounded-md overflow-hidden ui-document-paper" style={{ touchAction: 'none' }}>
                <canvas
                    ref={canvasRef} width={480} height={160}
                    className="w-full cursor-crosshair block"
                    onMouseDown={startDraw} onMouseMove={draw}
                    onMouseUp={() => setDrawing(false)} onMouseLeave={() => setDrawing(false)}
                    onTouchStart={startDraw} onTouchMove={draw} onTouchEnd={() => setDrawing(false)}
                />
                {!hasContent && (
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <p className="text-muted-foreground text-sm">Draw your signature here</p>
                    </div>
                )}
                <div className="absolute bottom-4 left-6 right-6 border-t border-dashed border-border" />
            </div>
            <div className="flex items-center justify-between mt-4">
                <Button variant="ghost" size="sm" type="button" className="text-sm text-muted-foreground hover:text-foreground" onClick={clear}>
                    Clear
                </Button>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={onClose}>Cancel</Button>
                    <Button size="sm" disabled={!hasContent}
                        onClick={() => canvasRef.current && onSave(canvasRef.current.toDataURL('image/png'))}>
                        <Check className="h-4 w-4 mr-1" />Apply
                    </Button>
                </div>
            </div>
        </div>
    );
}

function TypeTab({ onSave, onClose }: { onSave: (dataUrl: string) => void; onClose: () => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [text, setText] = useState('');
    const [font, setFont] = useState('Dancing Script');

    const fonts = [
        { label: 'Script', value: 'Dancing Script' },
        { label: 'Cursive', value: 'Pacifico' },
        { label: 'Elegant', value: 'Great Vibes' },
        { label: 'Print', value: 'Architects Daughter' },
    ];

    useEffect(() => {
        if (!canvasRef.current || !text) return;
        const canvas = canvasRef.current;
        const ctx = canvas?.getContext('2d');
        if (!canvas || !ctx) return;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.font = `48px "${font}", cursive`;
        ctx.fillStyle = resolvedCanvasColor('signature-ink');
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, canvas.width / 2, canvas.height / 2);
    }, [text, font]);

    function apply() {
        if (!text.trim()) return;
        canvasRef.current && onSave(canvasRef.current.toDataURL('image/png'));
    }

    return (
        <div>
            <Input
                autoFocus
                type="text"
                placeholder="Type your name…"
                value={text}
                onChange={(e) => setText(e.target.value)}
                className="w-full rounded-md border px-3 py-2 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-ring"
            />
            <div className="flex gap-2 mb-3 flex-wrap">
                {fonts.map((f) => (
                    <Button variant="ghost" size="sm"
                        key={f.value}
                        onClick={() => setFont(f.value)}
                        className={`px-3 py-1 rounded-md border text-sm transition-colors ${font === f.value ? 'border-foreground bg-foreground text-background' : 'border-border hover:border-foreground'}`}
                        style={{ fontFamily: `"${f.value}", cursive` }}
                    >
                        {f.label}
                    </Button>
                ))}
            </div>
            <div className="relative border rounded-md overflow-hidden ui-document-paper h-20 flex items-center justify-center">
                <canvas ref={canvasRef} width={480} height={80} className="w-full h-full" />
                {!text && (
                    <span className="absolute text-muted-foreground text-sm pointer-events-none" style={{ fontFamily: `"${font}", cursive` }}>
                        Preview
                    </span>
                )}
            </div>
            <div className="flex justify-end gap-2 mt-4">
                <Button variant="outline" size="sm" onClick={onClose}>Cancel</Button>
                <Button size="sm" disabled={!text.trim()} onClick={apply}>
                    <Check className="h-4 w-4 mr-1" />Apply
                </Button>
            </div>
        </div>
    );
}

function UploadTab({ onSave, onClose }: { onSave: (dataUrl: string) => void; onClose: () => void }) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);

    function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = () => { if (typeof reader.result === 'string') setPreview(reader.result); };
        reader.readAsDataURL(file);
    }

    return (
        <div>
            <div
                className="border-2 border-dashed rounded-md h-32 flex flex-col items-center justify-center cursor-pointer hover:bg-muted/30 transition-colors"
                onClick={() => inputRef.current?.click()}
            >
                {preview ? (
                    <img src={preview} alt="Uploaded signature" className="max-h-full max-w-full object-contain p-2" />
                ) : (
                    <>
                        <p className="text-sm font-medium">Click to upload</p>
                        <p className="text-xs text-muted-foreground mt-1">PNG, JPG, or GIF</p>
                    </>
                )}
            </div>
            <Input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={handleFile} />
            <div className="flex justify-end gap-2 mt-4">
                <Button variant="outline" size="sm" onClick={onClose}>Cancel</Button>
                <Button size="sm" disabled={!preview} onClick={() => preview && onSave(preview)}>
                    <Check className="h-4 w-4 mr-1" />Apply
                </Button>
            </div>
        </div>
    );
}

// ─── Image / Stamp Upload Modal ───────────────────────────────────────────────

function ImageUploadModal({ title, submitterSlug, onSave, onClose }: { title: string; submitterSlug: string; onSave: (uuid: string, url: string) => void; onClose: () => void }) {
    const inputRef  = useRef<HTMLInputElement>(null);
    const [preview, setPreview]   = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError]       = useState<string | null>(null);

    async function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        setError(null);
        setUploading(true);

        const form = new FormData();
        form.append('file', file);

        try {
            const res = await fetch(`/s/${submitterSlug}/attachments`, {
                method: 'POST',
                body: form,
                headers: { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            });
            if (!res.ok) throw new Error(`Upload failed (${res.status})`);
            const data = await res.json();
            setPreview(data.url);
            onSave(data.uuid, data.url);
        } catch (err) {
            setError(err instanceof Error ? err.message : String(err));
        } finally {
            setUploading(false);
        }
    }

    return (
        <Dialog open onOpenChange={(open) => { if (!open) onClose(); }}>
            <DialogContent className="w-[calc(100%-2rem)] max-w-md p-0 gap-0">
                <DialogHeader className="px-5 py-4 border-b">
                    <DialogTitle className="text-base">{title}</DialogTitle>
                    <DialogDescription className="sr-only">Upload a picture to attach to the signing field.</DialogDescription>
                </DialogHeader>
                <div className="p-5">
                    <div
                        className="border-2 border-dashed rounded-md h-44 flex flex-col items-center justify-center cursor-pointer hover:bg-muted/30 transition-colors relative overflow-hidden"
                        onClick={() => !uploading && inputRef.current?.click()}
                    >
                        {preview ? (
                            <img src={preview} alt="Uploaded" className="max-h-full max-w-full object-contain p-2" />
                        ) : uploading ? (
                            <p className="text-sm text-muted-foreground">Uploading…</p>
                        ) : (
                            <>
                                <Upload className="h-8 w-8 text-muted-foreground mb-2" />
                                <p className="text-sm font-medium">Click to upload</p>
                                <p className="text-xs text-muted-foreground mt-1">PNG, JPG, GIF, WebP</p>
                            </>
                        )}
                    </div>
                    {error && <p className="text-xs text-destructive mt-2">{error}</p>}
                    <Input
                        ref={inputRef} type="file" accept="image/*"
                        className="hidden" onChange={handleFile}
                    />
                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" size="sm" onClick={onClose}>
                            {preview ? 'Done' : 'Cancel'}
                        </Button>
                        {preview && (
                            <Button variant="outline" size="sm" onClick={() => { setPreview(null); inputRef.current?.click(); }}>
                                <Upload className="h-3.5 w-3.5 mr-1" />Re-upload
                            </Button>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

function SignatureModal({ title, onSave, onClose }: { title: string; onSave: (dataUrl: string) => void; onClose: () => void }) {
    const [tab, setTab] = useState('draw');

    return (
        <Dialog open onOpenChange={(open) => { if (!open) onClose(); }}>
            <DialogContent className="w-[calc(100%-2rem)] max-w-md p-0 gap-0">
                <DialogHeader className="px-5 py-4 border-b">
                    <DialogTitle className="text-base">{title}</DialogTitle>
                    <DialogDescription className="sr-only">Choose a method to create your signature.</DialogDescription>
                </DialogHeader>

                <Tabs value={tab} onValueChange={setTab} className="w-full">
                    <TabsList className="w-full justify-start rounded-none border-b bg-transparent px-5">
                        <TabsTrigger value="draw">Draw</TabsTrigger>
                        <TabsTrigger value="type">Type</TabsTrigger>
                        <TabsTrigger value="upload">Upload</TabsTrigger>
                    </TabsList>
                    <TabsContent value="draw" className="p-5 mt-0"><DrawTab onSave={onSave} onClose={onClose} /></TabsContent>
                    <TabsContent value="type" className="p-5 mt-0"><TypeTab onSave={onSave} onClose={onClose} /></TabsContent>
                    <TabsContent value="upload" className="p-5 mt-0"><UploadTab onSave={onSave} onClose={onClose} /></TabsContent>
                </Tabs>
            </DialogContent>
        </Dialog>
    );
}

// ─── Single PDF Page Renderer ─────────────────────────────────────────────────

function PdfPageCanvas({ url, pageNumber, onDimensions }: { url: string; pageNumber: number; onDimensions?: (pageIndex: number, width: number, height: number) => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        let cancelled = false;
        let task: { promise: Promise<void>; cancel: () => void } | null = null;

        (async () => {
            try {
                const pdf = await pdfjsLib.getDocument({ url }).promise;
                const page = await pdf.getPage(pageNumber);
                if (cancelled) return;

                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = canvasRef.current;
                if (!canvas) return;

                canvas.width = viewport.width;
                canvas.height = viewport.height;
                const canvasContext = canvas.getContext('2d');
                if (!canvasContext) return;
                task = page.render({ canvas, canvasContext, viewport });
                await task.promise;

                if (!cancelled && onDimensions) onDimensions(pageNumber - 1, viewport.width, viewport.height);
            } catch { /* cancelled */ }
        })();

        return () => { cancelled = true; task?.cancel?.(); };
    }, [url, pageNumber]);

    return <canvas ref={canvasRef} className="w-full block" />;
}

// ─── Field Overlay on Page ────────────────────────────────────────────────────

function FieldOverlay({ field, area, value, active, onActivate, onChange, onCommit }: {
    field: SignerField; area: SignerArea; value?: SignerValue; active: boolean;
    onActivate: () => void; onChange: (value: SignerValue) => void; onCommit: () => void;
}) {
    const isFilled = value !== null && value !== undefined && value !== '' && value !== false;
    const color = signerColor(0);

    // Checkbox — toggle directly
    if (field.type === 'checkbox') {
        return (
            <div
                className="absolute cursor-pointer flex items-center justify-center rounded"
                style={{
                    left: `${area.x * 100}%`, top: `${area.y * 100}%`,
                    width: `${area.w * 100}%`, height: `${area.h * 100}%`,
                    border: `2px solid ${isFilled ? color : documentColors.fieldStroke}`,
                    backgroundColor: isFilled ? 'hsl(var(--signer-1) / 0.13)' : documentColors.fieldBlank,
                }}
                onClick={() => { onChange(!value); onCommit?.(); }}
            >
                {isFilled && <Check className="h-3 w-3" style={{ color }} />}
            </div>
        );
    }

    // Signature / Initials — show image when filled, placeholder when not
    if (field.type === 'signature' || field.type === 'initials') {
        return (
            <div
                className="absolute cursor-pointer flex items-center justify-center overflow-hidden"
                style={{
                    left: `${area.x * 100}%`, top: `${area.y * 100}%`,
                    width: `${area.w * 100}%`, height: `${area.h * 100}%`,
                    border: `2px ${isFilled ? 'solid' : 'dashed'} ${isFilled ? color : documentColors.fieldStroke}`,
                    backgroundColor: isFilled ? 'transparent' : documentColors.fieldBlank,
                }}
                onClick={onActivate}
            >
                {isFilled
                    ? <img src={typeof value === "string" ? value : ""} className="w-full h-full object-contain" alt="signature" />
                    : <span className="text-[10px] text-muted-foreground select-none">
                        {field.type === 'initials' ? 'Initials' : 'Sign here'}
                    </span>
                }
            </div>
        );
    }

    // Image / Stamp — click to upload
    if (field.type === 'image' || field.type === 'stamp') {
        return (
            <div
                className="absolute cursor-pointer flex items-center justify-center overflow-hidden"
                style={{
                    left: `${area.x * 100}%`, top: `${area.y * 100}%`,
                    width: `${area.w * 100}%`, height: `${area.h * 100}%`,
                    border: `2px ${isFilled ? 'solid' : 'dashed'} ${isFilled ? color : documentColors.fieldStroke}`,
                    backgroundColor: isFilled ? 'transparent' : documentColors.fieldBlank,
                }}
                onClick={onActivate}
            >
                {isFilled
                    ? <img src={typeof value === "string" ? value : ""} className="w-full h-full object-contain" alt={field.type} />
                    : <span className="text-[10px] text-muted-foreground select-none flex flex-col items-center gap-0.5">
                        <Image className="h-3 w-3" />
                        {field.type === 'stamp' ? 'Stamp' : 'Image'}
                      </span>
                }
            </div>
        );
    }

    // Text-based fields — inline editable textarea on the PDF
    const isActive = active;
    return (
        <div
            className="absolute overflow-hidden"
            style={{
                left: `${area.x * 100}%`, top: `${area.y * 100}%`,
                width: `${area.w * 100}%`, height: `${area.h * 100}%`,
                border: `2px ${isActive ? 'solid' : 'dashed'} ${isFilled || isActive ? color : documentColors.fieldStroke}`,
                backgroundColor: isActive ? documentColors.paper : isFilled ? documentColors.fieldFilled : documentColors.fieldBlank,
            }}
            onClick={onActivate}
        >
            {isActive ? (
                <Input
                    autoFocus
                    type={field.type === 'date' ? 'date' : field.type === 'number' ? 'number' : 'text'}
                    value={typeof value === 'string' || typeof value === 'number' ? value : ''}
                    onChange={(e) => onChange(e.target.value)}
                    onBlur={onCommit}
                    onKeyDown={(e) => { if (e.key === 'Enter') e.currentTarget.blur(); }}
                    className="w-full h-full border-0 outline-none bg-transparent px-1 text-xs"
                    style={{ fontSize: 'clamp(8px, 1.5vw, 13px)' }}
                    placeholder={field.name || field.type}
                />
            ) : (
                <span
                    className="block w-full h-full px-1 text-xs leading-tight overflow-hidden"
                    style={{ fontSize: 'clamp(8px, 1.5vw, 13px)', color: isFilled ? documentColors.ink : documentColors.fieldStroke }}
                >
                    {isFilled ? value : (field.name || field.type)}
                </span>
            )}
        </div>
    );
}

// ─── Document Page with Field Overlays ───────────────────────────────────────

function DocumentPageWithFields({
    doc, pageIndex, fields, values, activeField,
    onActivate, onChange, onCommit, onDimensions,
}: {
    doc: SignerDocument; pageIndex: number; fields: SignerField[]; values: SignerValues;
    activeField: string | null; onActivate: (field: SignerField) => void;
    onChange: (id: string, value: SignerValue) => void; onCommit: (id: string) => void;
    onDimensions?: (pageIndex: number, width: number, height: number) => void;
}) {
    const wrapRef = useRef<HTMLDivElement>(null);
    const isPdf = doc.content_type === 'application/pdf';

    const areasOnPage: Array<{field: SignerField; area: SignerArea}> = [];
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
                <PdfPageCanvas url={doc.url} pageNumber={pageIndex + 1} onDimensions={onDimensions} />
            ) : (
                <img src={doc.url} alt={doc.filename} className="w-full block" />
            )}

            {areasOnPage.map(({ field, area }) => (
                <FieldOverlay
                    key={`${field.uuid}:${pageIndex}`}
                    field={field}
                    area={area}
                    value={values[field.uuid]}
                    active={activeField === field.uuid}
                    onActivate={() => onActivate(field)}
                    onChange={(val) => onChange(field.uuid, val)}
                    onCommit={() => onCommit(field.uuid)}
                />
            ))}
        </div>
    );
}

// ─── Signer Status Badge ──────────────────────────────────────────────────────

const SIGNER_STATUS = {
    completed: { label: 'Signed',    icon: CheckCircle2, cls: 'text-success-soft-foreground bg-success-soft border-success-border' },
    declined:  { label: 'Declined',  icon: XCircle,      cls: 'text-destructive-soft-foreground bg-destructive-soft border-destructive-border' },
    opened:    { label: 'Opened',    icon: Eye,          cls: 'text-warning-soft-foreground bg-warning-soft border-warning-border' },
    sent:      { label: 'Invited',   icon: Send,         cls: 'text-info-soft-foreground bg-info-soft border-info-border' },
    awaiting:  { label: 'Awaiting',  icon: Clock,        cls: 'text-muted-foreground bg-muted border-border' },
};

function SignerStatusBadge({ status }: { status: string }) {
    const cfg = SIGNER_STATUS[status as keyof typeof SIGNER_STATUS] ?? SIGNER_STATUS.awaiting;
    const Icon = cfg.icon;
    return (
        <span className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full border ${cfg.cls}`}>
            <Icon className="h-3 w-3" />{cfg.label}
        </span>
    );
}

// ─── Signers Panel ────────────────────────────────────────────────────────────

function SignersPanel({ allSubmitters }: { allSubmitters?: SignerPerson[] }) {
    if (!allSubmitters || allSubmitters.length <= 1) return null;
    return (
        <div className="mt-6 bg-card rounded-md border p-4 max-w-xl mx-auto">
            <div className="flex items-center gap-2 mb-3">
                <Users className="h-4 w-4 text-muted-foreground" />
                <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Signers ({allSubmitters.length})
                </p>
            </div>
            <div className="space-y-2">
                {allSubmitters.map((s, idx) => (
                    <div
                        key={s.uuid}
                        className={`flex items-center gap-3 py-2 px-3 rounded-md ${s.is_current ? 'bg-primary/5 ring-1 ring-primary/20' : ''}`}
                    >
                        <div
                            className="h-7 w-7 rounded-full flex items-center justify-center text-xs font-bold text-signer-foreground shrink-0"
                            style={{ backgroundColor: signerColor(idx) }}
                        >
                            {(s.name || s.email || '?')[0].toUpperCase()}
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium truncate">
                                {s.name || s.email || `Party ${idx + 1}`}
                                {s.is_current && <span className="ml-1 text-xs text-primary">(you)</span>}
                            </p>
                            {s.email && s.name && (
                                <p className="text-xs text-muted-foreground truncate">{s.email}</p>
                            )}
                        </div>
                        <SignerStatusBadge status={s.status} />
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── Completed Screen ─────────────────────────────────────────────────────────

function CompletedScreen({ submitter, allSubmitters }: { submitter: SignerPerson; allSubmitters: SignerPerson[] }) {
    const pendingOthers = (allSubmitters ?? []).filter(
        (s) => !s.is_current && s.status !== 'completed' && s.status !== 'declined'
    );
    const allDone = pendingOthers.length === 0;

    return (
        <div className="min-h-screen bg-muted flex flex-col items-center justify-center px-4 py-10 gap-6">
            <Head title={allDone ? 'Form Completed' : 'Signed — Awaiting Others'} />
            <div className="max-w-md w-full text-center">
                <div className="bg-card rounded-md p-10 shadow-sm border">
                    <div className={`w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 ${allDone ? 'bg-success-soft' : 'bg-muted'}`}>
                        {allDone
                            ? <CheckCircle2 className="h-8 w-8 text-success" />
                            : <Clock className="h-8 w-8 text-muted-foreground" />
                        }
                    </div>
                    <h1 className="text-2xl font-bold mb-2">
                        {allDone ? 'Form Completed' : "You've Signed"}
                    </h1>
                    <p className="text-muted-foreground">
                        {allDone
                            ? 'All parties have signed. Your submission has been received.'
                            : `Waiting for ${pendingOthers.length} more ${pendingOthers.length === 1 ? 'signer' : 'signers'} to complete.`
                        }
                    </p>
                    {allDone && (
                        <p className="mt-6 text-sm text-muted-foreground">
                            Check your email for a signed copy if document-copy emails are enabled.
                            Contact the sender for a new download link if yours has expired.
                        </p>
                    )}
                </div>
            </div>
            <SignersPanel allSubmitters={allSubmitters} />
        </div>
    );
}

// ─── Declined Screen ──────────────────────────────────────────────────────────

function DeclinedScreen({ submitter, declineReason, allSubmitters }: { submitter: SignerPerson; declineReason?: string | null; allSubmitters: SignerPerson[] }) {
    return (
        <div className="min-h-screen bg-muted flex flex-col items-center justify-center px-4 py-10 gap-6">
            <Head title="Form Declined" />
            <div className="max-w-md w-full text-center">
                <div className="bg-card rounded-md p-10 shadow-sm border">
                    <div className="w-16 h-16 rounded-full bg-destructive-soft flex items-center justify-center mx-auto mb-4">
                        <XCircle className="h-8 w-8 text-destructive" />
                    </div>
                    <h1 className="text-2xl font-bold mb-2">Form Declined</h1>
                    <p className="text-muted-foreground">You have declined to sign this document.</p>
                    {declineReason && (
                        <div className="mt-4 p-3 bg-muted rounded-md text-left">
                            <p className="text-xs font-semibold text-muted-foreground uppercase mb-1">Reason</p>
                            <p className="text-sm">{declineReason}</p>
                        </div>
                    )}
                </div>
            </div>
            <SignersPanel allSubmitters={allSubmitters} />
        </div>
    );
}

// ─── Decline Modal ────────────────────────────────────────────────────────────

function DeclineModal({ submitter, onClose }: { submitter: SignerPerson; onClose: () => void }) {
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);

    function handleDecline() {
        if (!reason.trim()) return;
        setSubmitting(true);
        router.post(`/s/${submitter.slug}/decline`, { reason }, {
            onError: () => setSubmitting(false),
            onFinish: () => setSubmitting(false),
        });
    }

    return (
        <Dialog open onOpenChange={(open) => { if (!open) onClose(); }}>
            <DialogContent className="w-[calc(100%-2rem)] max-w-md p-0 gap-0">
                <DialogHeader className="px-5 py-4 border-b">
                    <DialogTitle className="text-base">Decline to Sign</DialogTitle>
                </DialogHeader>
                <div className="p-5">
                    <DialogDescription className="mb-3">
                        Please provide a reason for declining. This will be sent to the sender.
                    </DialogDescription>
                    <Textarea
                        autoFocus
                        value={reason}
                        onChange={(e) => setReason(e.target.value)}
                        rows={5}
                        placeholder="Provide a reason…"
                        className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
                    />
                    <div className="flex justify-end gap-2 mt-4">
                        <Button variant="outline" size="sm" onClick={onClose} disabled={submitting}>
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            disabled={!reason.trim() || submitting}
                            onClick={handleDecline}
                        >
                            <XCircle className="h-4 w-4 mr-1" />
                            {submitting ? 'Declining…' : 'Decline'}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

// ─── Awaiting Screen ──────────────────────────────────────────────────────────

function AwaitingScreen({ template, allSubmitters }: { template?: { name: string } | null; allSubmitters: SignerPerson[] }) {
    const pendingBefore = (allSubmitters ?? []).filter(
        (s) => !s.is_current && s.status !== 'completed' && s.status !== 'declined' &&
               (s.order ?? 99) < (allSubmitters.find((x) => x.is_current)?.order ?? 99)
    );

    return (
        <div className="min-h-screen bg-muted flex flex-col items-center justify-center px-4 py-10 gap-6">
            <Head title="Awaiting Other Signers" />
            <div className="max-w-md w-full text-center">
                <div className="bg-card rounded-md p-10 shadow-sm border">
                    <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                        <Clock className="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h1 className="text-2xl font-bold mb-2">Awaiting Other Signers</h1>
                    <p className="text-muted-foreground">
                        {pendingBefore.length === 1
                            ? 'Another party needs to sign before you. You\'ll be notified by email when it\'s your turn.'
                            : `${pendingBefore.length} parties need to sign before you. You'll be notified by email when it's your turn.`
                        }
                    </p>
                    <p className="mt-3 text-xs text-muted-foreground">
                        Document: <span className="font-medium">{template?.name || 'Document'}</span>
                    </p>
                </div>
            </div>
            <SignersPanel allSubmitters={allSubmitters} />
        </div>
    );
}

// ─── Main Signing Page ────────────────────────────────────────────────────────

export default function SubmitFormShow({
    submitter, fields, documents, schema, template,
    isCompleted, isDeclined, isAwaiting,
    declineReason, allSubmitters,
    previousSignature = null,
    previousInitials  = null,
    initialValues = {},
    isPreview = false, previewRoles = [],
}: SigningPageProps) {
    const [values, setValues] = useState<SignerValues>(() => {
        const initial = { ...initialValues };
        fields.forEach((f) => {
            if (f.required && f.type === 'signature' && previousSignature) initial[f.uuid] = previousSignature;
            if (f.required && f.type === 'initials'  && previousInitials)  initial[f.uuid] = previousInitials;
        });
        return initial;
    });
    const [activeField, setActiveField] = useState<string | null>(null);
    const [signatureModal, setSignatureModal] = useState<{ fieldUuid: string; title: string } | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string,string>>({});
    const [showDeclineModal, setShowDeclineModal] = useState(false);
    const [imageModal, setImageModal] = useState<{ fieldUuid: string; title: string } | null>(null); // { fieldUuid, title }

    if (isDeclined)  return <DeclinedScreen submitter={submitter} declineReason={declineReason} allSubmitters={allSubmitters} />;
    if (isAwaiting)  return <AwaitingScreen template={template} allSubmitters={allSubmitters} />;
    if (isCompleted) return <CompletedScreen submitter={submitter} allSubmitters={allSubmitters} />;

    // Formula fields: keep computed values in sync
    const computedValues = { ...values };
    fields.forEach((f) => {
        if (f.type === 'formula') {
            computedValues[f.uuid] = evaluateFormula(f.preferences?.formula ?? f.formula, fields, values);
        }
    });

    // Visible fields pass their conditions
    const visibleFields = fields.filter((f) => evaluateConditions(f, computedValues));

    // Fields that have at least one area placed on a document
    const placedFields = visibleFields.filter((f) => (f.areas ?? []).length > 0 && f.type !== 'heading');
    // Fields with no placement — still show as a sidebar form (includes headings)
    const unplacedFields = visibleFields.filter((f) => (f.areas ?? []).length === 0);

    const requiredUnfilled = visibleFields.filter((f) => {
        if (!f.required || f.type === 'heading' || f.type === 'formula') return false;
        const v = computedValues[f.uuid];
        return v === null || v === undefined || v === '' || v === false;
    });
    const canSubmit = requiredUnfilled.length === 0;

    function handleActivate(field: SignerField) {
        if (field.type === 'signature' || field.type === 'initials') {
            setSignatureModal({
                fieldUuid: field.uuid,
                title: field.type === 'initials' ? 'Draw Initials' : 'Draw Signature',
            });
        } else if (field.type === 'image' || field.type === 'stamp') {
            setImageModal({
                fieldUuid: field.uuid,
                title: field.type === 'stamp' ? 'Upload Stamp' : 'Upload Image',
            });
        } else {
            setActiveField(field.uuid);
        }
    }

    function handleChange(uuid: string, val: SignerValue) {
        setValues((prev) => ({ ...prev, [uuid]: val }));
        if (errors[uuid]) setErrors((prev) => { const n = { ...prev }; delete n[uuid]; return n; });
    }

    function handleCommit(_uuid: string) {
        setActiveField(null);
    }

    function handleSignatureSave(dataUrl: string) {
        if (signatureModal) {
            handleChange(signatureModal.fieldUuid, dataUrl);
        }
        setSignatureModal(null);
    }

    function handleImageSave(_uuid: string, url: string) {
        if (imageModal) {
            // Store the serving URL directly as the field value
            handleChange(imageModal.fieldUuid, url);
        }
        setImageModal(null);
    }

    function handleSubmit() {
        if (isPreview) return;
        // Validate required visible fields (exclude heading / formula)
        const newErrors: Record<string,string> = {};
        visibleFields.forEach((f) => {
            if (!f.required || f.type === 'heading' || f.type === 'formula') return;
            const v = computedValues[f.uuid];
            if (v === null || v === undefined || v === '' || v === false) {
                newErrors[f.uuid] = "This field is required.";
            }
        });
        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }
        setSubmitting(true);
        router.put(`/s/${submitter.slug}`, { values: computedValues }, {
            onError: () => setSubmitting(false),
            onFinish: () => setSubmitting(false),
        });
    }

    // Determine which documents actually have fields placed on them
    const documentsWithFields = documents.filter((doc) =>
        fields.some((f) => (f.areas ?? []).some((a) => a.attachment_uuid === doc.uuid))
    );
    const documentsToShow = documentsWithFields.length > 0 ? documentsWithFields : documents;

    return (
        <div className="min-h-screen bg-muted" onClick={() => setActiveField(null)}>
            <Head title={template?.name || 'Sign Document'} />

            {isPreview && (
                <div className="bg-warning text-warning-foreground text-sm font-semibold text-center py-2 px-4 flex items-center justify-center gap-3 sticky top-0 z-50">
                    <span>Preview Mode — this form is not live. Changes will not be saved.</span>
                    {previewRoles.length > 1 && previewRoles.map((role) => (
                        <a key={role.uuid}
                            href={`${window.location.pathname}?uuid=${role.uuid}`}
                            className="underline hover:no-underline">
                            {role.name}
                        </a>
                    ))}
                </div>
            )}

            {/* Header */}
            <header className="bg-card border-b sticky top-0 z-30 shadow-sm">
                <div className="max-w-4xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2 min-w-0">
                        <div className="p-1.5 rounded-md bg-primary/10 shrink-0">
                            <FileText className="h-4 w-4 text-primary" />
                        </div>
                        <span className="font-semibold text-sm truncate">{template?.name || 'Document'}</span>
                        {submitter?.email && (
                            <span className="text-xs text-muted-foreground hidden sm:inline truncate">
                                — {submitter.email}
                            </span>
                        )}
                    </div>

                    <div className="flex items-center gap-2 shrink-0">
                        {requiredUnfilled.length > 0 && (
                            <span className="text-xs text-muted-foreground hidden sm:inline">
                                {requiredUnfilled.length} required field{requiredUnfilled.length !== 1 ? 's' : ''} remaining
                            </span>
                        )}
                        <Button
                            size="sm"
                            variant="outline"
                            className="gap-1.5 text-destructive border-destructive/40 hover:bg-destructive/10 hover:text-destructive"
                            onClick={() => setShowDeclineModal(true)}
                        >
                            <XCircle className="h-4 w-4" />
                            <span className="hidden sm:inline">Decline</span>
                        </Button>
                        <Button
                            size="sm"
                            className="gap-1.5"
                            disabled={!canSubmit || submitting}
                            onClick={handleSubmit}
                        >
                            <Check className="h-4 w-4" />
                            {submitting ? 'Submitting…' : 'Complete & Sign'}
                        </Button>
                    </div>
                </div>
            </header>

            <div className="max-w-4xl mx-auto px-4 py-6">
                {/* Document pages with overlaid fields */}
                {documentsToShow.length > 0 ? (
                    <div className="space-y-2">
                        {documentsToShow.map((doc) =>
                            Array.from({ length: doc.page_count }, (_, i) => (
                                <div
                                    key={`${doc.uuid}-${i}`}
                                    className="bg-card shadow-md rounded overflow-hidden"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <DocumentPageWithFields
                                        doc={doc}
                                        pageIndex={i}
                                        fields={placedFields}
                                        values={computedValues}
                                        activeField={activeField}
                                        onActivate={handleActivate}
                                        onChange={handleChange}
                                        onCommit={handleCommit}
                                    />
                                </div>
                            ))
                        )}
                    </div>
                ) : (
                    // No document uploaded — fall back to simple form
                    <div className="bg-card rounded-md border p-6 max-w-xl mx-auto">
                        <p className="text-sm text-muted-foreground mb-4">
                            No document attached. Please fill the fields below.
                        </p>
                    </div>
                )}

                {/* Unplaced fields (defined but not positioned on any page) */}
                {unplacedFields.length > 0 && (
                    <div className="mt-6 bg-card rounded-md border p-5 max-w-xl mx-auto space-y-4">
                        <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                            Additional Information
                        </p>
                        {unplacedFields.map((field) => (
                            <UnplacedField
                                key={field.uuid}
                                field={field}
                                value={computedValues[field.uuid]}
                                error={errors[field.uuid]}
                                submitterSlug={submitter.slug}
                                onChange={(val) => handleChange(field.uuid, val)}
                            />
                        ))}
                    </div>
                )}

                {/* Bottom submit bar */}
                <div className="mt-8 bg-card rounded-md border p-4 max-w-xl mx-auto flex items-center justify-between gap-4">
                    <p className="text-xs text-muted-foreground">
                        By clicking Complete &amp; Sign, you agree this is your electronic signature.
                    </p>
                    <Button
                        className="shrink-0 gap-1.5"
                        disabled={!canSubmit || submitting}
                        onClick={handleSubmit}
                    >
                        <Check className="h-4 w-4" />
                        {submitting ? 'Submitting…' : 'Complete & Sign'}
                    </Button>
                </div>

                {/* Multi-party signers panel */}
                <SignersPanel allSubmitters={allSubmitters} />
            </div>

            {/* Signature modal */}
            {signatureModal && (
                <SignatureModal
                    title={signatureModal.title}
                    onSave={handleSignatureSave}
                    onClose={() => setSignatureModal(null)}
                />
            )}

            {/* Decline modal */}
            {showDeclineModal && (
                <DeclineModal
                    submitter={submitter}
                    onClose={() => setShowDeclineModal(false)}
                />
            )}

            {/* Image / Stamp upload modal */}
            {imageModal && (
                <ImageUploadModal
                    title={imageModal.title}
                    submitterSlug={submitter.slug}
                    onSave={handleImageSave}
                    onClose={() => setImageModal(null)}
                />
            )}
        </div>
    );
}

// ─── Image/Stamp upload for unplaced fields ───────────────────────────────────

function UnplacedImageField({ field, value, onChange, submitterSlug }: { field: SignerField; value?: SignerValue; onChange: (value: SignerValue) => void; submitterSlug: string }) {
    const inputRef   = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);

    async function handleFile(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) return;
        setUploadError(null);
        setUploading(true);
        const form = new FormData();
        form.append('file', file);
        try {
            const res = await fetch(`/s/${submitterSlug}/attachments`, {
                method: 'POST',
                body: form,
                headers: { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' },
            });
            if (!res.ok) throw new Error(`Upload failed (${res.status})`);
            const data = await res.json();
            onChange(data.url);
        } catch (err) {
            setUploadError(err instanceof Error ? err.message : String(err));
        } finally {
            setUploading(false);
        }
    }

    return (
        <div>
            <div
                className="border-2 border-dashed rounded-md h-36 flex flex-col items-center justify-center cursor-pointer hover:bg-muted/30 transition-colors"
                onClick={() => !uploading && inputRef.current?.click()}
            >
                {value ? (
                    <img src={typeof value === "string" ? value : ""} alt="Uploaded" className="max-h-full max-w-full object-contain p-2" />
                ) : uploading ? (
                    <p className="text-sm text-muted-foreground">Uploading…</p>
                ) : (
                    <>
                        <Upload className="h-6 w-6 text-muted-foreground mb-1" />
                        <p className="text-sm text-muted-foreground">
                            Click to upload {field.type === 'stamp' ? 'stamp' : 'image'}
                        </p>
                    </>
                )}
            </div>
            {value && (
                <Button variant="ghost" size="compact"
                    type="button"
                    className="mt-1 text-xs text-muted-foreground hover:text-foreground"
                    onClick={() => inputRef.current?.click()}
                >
                    Re-upload
                </Button>
            )}
            {uploadError && <p className="text-xs text-destructive mt-1">{uploadError}</p>}
            <Input ref={inputRef} type="file" accept="image/*" className="hidden" onChange={handleFile} />
        </div>
    );
}

// ─── Fallback field input for unplaced fields ─────────────────────────────────

function UnplacedField({ field, value, error, onChange, submitterSlug }: { field: SignerField; value?: SignerValue; error?: string; onChange: (value: SignerValue) => void; submitterSlug: string }) {
    const id = `f-${field.uuid}`;

    // Heading — display only, no input
    if (field.type === 'heading') {
        return (
            <div className="pt-2 pb-1 border-b border-border">
                <h3 className="text-base font-semibold">{field.content || field.name || 'Section'}</h3>
            </div>
        );
    }

    // Formula — read-only computed display
    if (field.type === 'formula') {
        return (
            <div>
                <Label className="block text-sm font-medium mb-1">{field.name || 'Calculated'}</Label>
                <div className="w-full rounded-md border border-input px-3 py-2 text-sm bg-muted/40 text-muted-foreground font-mono">
                    {value || '—'}
                </div>
            </div>
        );
    }

    const label = (
        <Label htmlFor={id} className="block text-sm font-medium mb-1">
            {field.name || field.type}
            {field.required && <span className="text-destructive ml-1">*</span>}
        </Label>
    );

    let input;
    if (field.type === 'checkbox') {
        input = (
            <Label className="flex items-center gap-2 cursor-pointer">
                <Checkbox
                    id={id}
                    checked={!!value}
                    onCheckedChange={(checked) => onChange(checked)}
                    className="rounded"
                />
                <span className="text-sm">{field.name || 'I agree'}</span>
            </Label>
        );
    } else if (field.type === 'select') {
        input = (
            <Select
                value={value ? String(value) : '__vouch_no_selection__'}
                onValueChange={(selected) => onChange(selected === '__vouch_no_selection__' ? '' : selected)}
            >
                <SelectTrigger id={id} className="w-full"><SelectValue placeholder="Select…" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="__vouch_no_selection__">Select…</SelectItem>
                    {(field.options || []).filter((o) => o.value && o.value !== '__vouch_no_selection__').map((o) => (
                        <SelectItem key={o.uuid || o.value} value={o.value}>{o.value}</SelectItem>
                    ))}
                </SelectContent>
            </Select>
        );
    } else if (field.type === 'textarea') {
        input = (
            <Textarea
                id={id}
                value={typeof value === 'string' || typeof value === 'number' ? value : ''}
                onChange={(e) => onChange(e.target.value)}
                rows={3}
                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring resize-none"
            />
        );
    } else if (field.type === 'image' || field.type === 'stamp') {
        input = (
            <UnplacedImageField
                field={field}
                value={value}
                onChange={onChange}
                submitterSlug={submitterSlug}
            />
        );
    } else if (field.type === 'file') {
        input = (
            <div>
                <Input
                    id={id}
                    type="file"
                    className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring file:mr-3 file:text-sm file:font-medium file:border-0 file:bg-transparent"
                    onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (!file) return;
                        const reader = new FileReader();
                        reader.onload = () => { if (typeof reader.result === 'string') onChange(reader.result); };
                        reader.readAsDataURL(file);
                    }}
                />
                {value && <p className="text-xs text-success mt-1">File attached</p>}
            </div>
        );
    } else {
        input = (
            <Input
                id={id}
                type={field.type === 'date' ? 'date' : field.type === 'number' ? 'number' : 'text'}
                value={typeof value === 'string' || typeof value === 'number' ? value : ''}
                onChange={(e) => onChange(e.target.value)}
                className="w-full rounded-md border border-input px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
        );
    }

    return (
        <div>
            {field.type !== 'checkbox' && label}
            {input}
            {error && <p className="text-xs text-destructive mt-1">This field is required</p>}
        </div>
    );
}
