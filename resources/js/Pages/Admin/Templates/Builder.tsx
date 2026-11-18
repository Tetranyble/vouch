import { documentColors, signerColor } from '@/lib/design-tokens';
import { Textarea } from '@/Components/ui/textarea';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { ScrollArea } from '@/Components/ui/scroll-area';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
    Tooltip, TooltipContent, TooltipProvider, TooltipTrigger,
} from '@/Components/ui/tooltip';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import {
    ChevronLeft, ChevronUp, ChevronDown, Save, Plus, Trash2,
    Type, Hash, Calendar, Image, PenLine, CheckSquare, CircleDot,
    Paperclip, Phone, CreditCard, FileSignature, AlignJustify,
    Upload, X, Eye, Move, Heading2, Calculator, GitBranch,
    List, Stamp, Minus, ShieldCheck, Fingerprint, LayoutGrid,
    RotateCcw, RotateCw, Copy, Columns, Menu, MoreVertical,
} from 'lucide-react';
import { useState, useCallback, useRef, useEffect, useMemo } from 'react';
import type * as React from 'react';
import * as pdfjsLib from 'pdfjs-dist';


// Serialized template-builder contracts. Keep synchronized with the builder controller payload.
interface BuilderArea {
    attachment_uuid: string;
    page: number;
    x: number;
    y: number;
    w: number;
    h: number;
}
interface BuilderField {
    uuid: string;
    name: string;
    type: string;
    submitter_uuid: string;
    required?: boolean;
    readonly?: boolean;
    prefillable?: boolean;
    default_value?: string | number | boolean;
    areas: BuilderArea[];
    options?: Array<{ uuid: string; value: string }>;
    conditions?: Array<{ field_uuid: string; action: string; value?: string }>;
    preferences?: { format?: string; formula?: string; currency?: string; price?: number };
    validation?: { type?: string; min?: number; max?: number; pattern?: string; message?: string };
}
interface BuilderSubmitter {
    uuid: string;
    name: string;
    auto_sign?: boolean;
}
interface BuilderDocument {
    uuid: string;
    filename: string;
    url: string;
    content_type: string;
    page_count: number;
}
interface BuilderTemplate {
    id: number;
    name: string;
    fields?: BuilderField[];
    submitters?: BuilderSubmitter[];
    documents?: BuilderDocument[];
    preferences?: Record<string, string | number | boolean | null>;
}
type PointerPositionEvent = MouseEvent | TouchEvent | React.MouseEvent<HTMLElement> | React.TouchEvent<HTMLElement>;

pdfjsLib.GlobalWorkerOptions.workerSrc = '/pdf.worker.js';

function uuid() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = (Math.random() * 16) | 0;
        return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────

function getClientPos(e: PointerPositionEvent) {
    if ('touches' in e && e.touches.length > 0) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
    if ('changedTouches' in e && e.changedTouches.length > 0) return { x: e.changedTouches[0].clientX, y: e.changedTouches[0].clientY };
    return 'clientX' in e ? { x: e.clientX, y: e.clientY } : { x: 0, y: 0 };
}

// ─── Constants ────────────────────────────────────────────────────────────────

const FIELD_TYPES = [
    { type: 'text',         label: 'Text',          icon: Type,          drawable: true },
    { type: 'signature',    label: 'Signature',      icon: FileSignature, drawable: true },
    { type: 'initials',     label: 'Initials',       icon: PenLine,       drawable: true },
    { type: 'date',         label: 'Date',           icon: Calendar,      drawable: true },
    { type: 'number',       label: 'Number',         icon: Hash,          drawable: true },
    { type: 'image',        label: 'Image',          icon: Image,         drawable: true },
    { type: 'file',         label: 'File',           icon: Paperclip,     drawable: false },
    { type: 'select',       label: 'Select',         icon: ChevronDown,   drawable: true },
    { type: 'checkbox',     label: 'Checkbox',       icon: CheckSquare,   drawable: true },
    { type: 'multiple',     label: 'Multiple',       icon: List,          drawable: true },
    { type: 'radio',        label: 'Radio',          icon: CircleDot,     drawable: true },
    { type: 'cells',        label: 'Cells',          icon: Columns,       drawable: true },
    { type: 'stamp',        label: 'Stamp',          icon: Stamp,         drawable: true },
    { type: 'textarea',     label: 'Textarea',       icon: AlignJustify,  drawable: true },
    { type: 'phone',        label: 'Phone',          icon: Phone,         drawable: false },
    { type: 'payment',      label: 'Payment',        icon: CreditCard,    drawable: false },
    { type: 'heading',      label: 'Heading',        icon: Heading2,      drawable: true },
    { type: 'formula',      label: 'Formula',        icon: Calculator,    drawable: true },
    { type: 'verification', label: 'Verification',   icon: ShieldCheck,   drawable: false },
    { type: 'kba',          label: 'KBA',            icon: Fingerprint,   drawable: false },
    { type: 'strikethrough',label: 'Strikethrough',  icon: Minus,         drawable: true },
];

// Signer annotations are centrally defined in design-tokens.ts.

const PARTY_NAMES = [
    'First Party', 'Second Party', 'Third Party', 'Fourth Party', 'Fifth Party',
    'Sixth Party', 'Seventh Party', 'Eighth Party', 'Ninth Party', 'Tenth Party',
    'Eleventh Party', 'Twelfth Party', 'Thirteenth Party', 'Fourteenth Party', 'Fifteenth Party',
    'Sixteenth Party', 'Seventeenth Party', 'Eighteenth Party', 'Nineteenth Party', 'Twentieth Party',
];

// Default area sizes as fraction of page width
const DEFAULT_SIZES = {
    checkbox:     { w: 0.033, h: 0.033 },
    image:        { w: 0.2,   h: 0.1   },
    signature:    { w: 0.2,   h: 0.06  },
    initials:     { w: 0.1,   h: 0.04  },
    stamp:        { w: 0.2,   h: 0.08  },
    verification: { w: 0.2,   h: 0.08  },
    kba:          { w: 0.2,   h: 0.08  },
    strikethrough:{ w: 0.2,   h: 0.015 },
    heading:      { w: 0.3,   h: 0.05  },
};

function fieldColor(submitters: BuilderSubmitter[], submitterUuid: string) {
    const idx = submitters.findIndex((s) => s.uuid === submitterUuid);
    return signerColor(idx < 0 ? 0 : idx);
}

function defaultSize(type: string) {
    return DEFAULT_SIZES[type as keyof typeof DEFAULT_SIZES] ?? { w: 0.2, h: 0.055 };
}

// ─── PDF Page Canvas ──────────────────────────────────────────────────────────

function PdfPage({ url, pageNumber, onCapture }: { url: string; pageNumber: number; onCapture?: (dataUrl: string) => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);

    useEffect(() => {
        let cancelled = false;
        let renderTask: { promise: Promise<void>; cancel: () => void } | null = null;

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
                if (!canvasContext) throw new Error('Canvas 2D context unavailable');
                renderTask = page.render({ canvas, canvasContext, viewport });
                await renderTask.promise;

                if (!cancelled && onCapture) {
                    onCapture(canvas.toDataURL('image/jpeg', 0.6));
                }
            } catch {
                // cancelled or render error
            }
        })();

        return () => {
            cancelled = true;
            renderTask?.cancel?.();
        };
    }, [url, pageNumber]);

    return <canvas ref={canvasRef} className="w-full block" />;
}

// ─── Field Area Overlay ───────────────────────────────────────────────────────

const RESIZE_HANDLES = [
    { id: 'nw', cursor: 'nw-resize', top: -3,  left: -3,  style: { top: -3,    left: -3,   width: 8, height: 8 } },
    { id: 'n',  cursor: 'n-resize',  top: -3,  left: '50%', style: { top: -3,  left: 'calc(50% - 4px)', width: 8, height: 8 } },
    { id: 'ne', cursor: 'ne-resize', top: -3,  right: -3, style: { top: -3,    right: -3,  width: 8, height: 8 } },
    { id: 'e',  cursor: 'e-resize',  style: { top: 'calc(50% - 4px)', right: -3, width: 8, height: 8 } },
    { id: 'se', cursor: 'se-resize', style: { bottom: -3, right: -3,  width: 8, height: 8 } },
    { id: 's',  cursor: 's-resize',  style: { bottom: -3, left: 'calc(50% - 4px)', width: 8, height: 8 } },
    { id: 'sw', cursor: 'sw-resize', style: { bottom: -3, left: -3,   width: 8, height: 8 } },
    { id: 'w',  cursor: 'w-resize',  style: { top: 'calc(50% - 4px)', left: -3, width: 8, height: 8 } },
];

function FieldAreaOverlay({
    field, area, pageW, pageH, selected, color,
    onSelect, onDelete, onMove, onResize,
}: {
    field: BuilderField;
    area: BuilderArea;
    pageW: number;
    pageH: number;
    selected: boolean;
    color: string;
    onSelect: () => void;
    onDelete: () => void;
    onMove: (patch: Partial<BuilderArea>) => void;
    onResize: (patch: Partial<BuilderArea>) => void;
}) {
    const areaRef = useRef<HTMLDivElement>(null);

    const px = area.x * pageW;
    const py = area.y * pageH;
    const pw = Math.max(16, area.w * pageW);
    const ph = Math.max(10, area.h * pageH);

    function startDrag(e: React.MouseEvent<HTMLDivElement> | React.TouchEvent<HTMLDivElement>) {
        if ('button' in e && e.button !== 0) return;
        e.stopPropagation();
        e.preventDefault();
        const { x: startX, y: startY } = getClientPos(e);
        const origX = area.x;
        const origY = area.y;

        function handleDragMove(ev: MouseEvent | TouchEvent) {
            ev.preventDefault();
            const { x, y } = getClientPos(ev);
            onMove({
                x: Math.max(0, Math.min(1 - area.w, origX + (x - startX) / pageW)),
                y: Math.max(0, Math.min(1 - area.h, origY + (y - startY) / pageH)),
            });
        }
        function onUp() {
            window.removeEventListener('mousemove', handleDragMove);
            window.removeEventListener('mouseup', onUp);
            window.removeEventListener('touchmove', handleDragMove);
            window.removeEventListener('touchend', onUp);
        }
        window.addEventListener('mousemove', handleDragMove);
        window.addEventListener('mouseup', onUp);
        window.addEventListener('touchmove', handleDragMove, { passive: false });
        window.addEventListener('touchend', onUp);
    }

    function startResize(handleId: string, e: React.MouseEvent<HTMLDivElement> | React.TouchEvent<HTMLDivElement>) {
        e.stopPropagation();
        e.preventDefault();
        const { x: startX, y: startY } = getClientPos(e);
        const orig = { x: area.x, y: area.y, w: area.w, h: area.h };

        function onMove(ev: MouseEvent | TouchEvent) {
            ev.preventDefault();
            const { x, y } = getClientPos(ev);
            const dx = (x - startX) / pageW;
            const dy = (y - startY) / pageH;
            const patch = { ...orig };

            if (handleId.includes('e')) {
                patch.w = Math.max(16 / pageW, orig.w + dx);
            }
            if (handleId.includes('s')) {
                patch.h = Math.max(10 / pageH, orig.h + dy);
            }
            if (handleId.includes('w')) {
                const newW = Math.max(16 / pageW, orig.w - dx);
                patch.x = orig.x + orig.w - newW;
                patch.w = newW;
            }
            if (handleId.includes('n')) {
                const newH = Math.max(10 / pageH, orig.h - dy);
                patch.y = orig.y + orig.h - newH;
                patch.h = newH;
            }
            patch.x = Math.max(0, patch.x);
            patch.y = Math.max(0, patch.y);
            patch.w = Math.min(1 - patch.x, patch.w);
            patch.h = Math.min(1 - patch.y, patch.h);
            onResize(patch);
        }
        function onUp() {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onUp);
        }
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onUp);
    }

    const FieldIcon = FIELD_TYPES.find((f) => f.type === field.type)?.icon ?? Type;

    return (
        <div
            ref={areaRef}
            className={`absolute group select-none ${selected ? 'z-20' : 'z-10 hover:z-15'}`}
            style={{
                left: px, top: py, width: pw, height: ph,
                outline: `2px solid ${color}`,
                outlineOffset: selected ? 1 : 0,
                backgroundColor: `color-mix(in srgb, ${color} 13%, transparent)`,
                cursor: 'move',
            }}
            onClick={(e) => { e.stopPropagation(); onSelect(); }}
            onMouseDown={startDrag}
            onTouchStart={startDrag}
        >
            {/* Label strip above */}
            <div
                className="absolute left-0 flex items-center gap-1 whitespace-nowrap pointer-events-none"
                style={{ top: -20, fontSize: 10, lineHeight: '16px', zIndex: 30 }}
            >
                <div
                    className="flex items-center gap-0.5 px-1 rounded text-signer-foreground font-medium max-w-[120px] overflow-hidden"
                    style={{ backgroundColor: color }}
                >
                    <FieldIcon className="h-2.5 w-2.5 shrink-0" />
                    <span className="truncate">{field.name || field.type}</span>
                </div>
            </div>

            {/* Delete button — shown on hover/select */}
            <Button variant="ghost" size="sm"
                className="absolute -top-5 right-0 text-signer-foreground rounded px-0.5 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-auto"
                style={{ backgroundColor: color, zIndex: 35, fontSize: 10, lineHeight: '18px' }}
                onMouseDown={(e) => e.stopPropagation()}
                onClick={(e) => { e.stopPropagation(); onDelete(); }}
            >
                <X className="h-3 w-3 inline" />
            </Button>

            {/* 8 resize handles — shown on select */}
            {selected && RESIZE_HANDLES.map((h) => (
                <div
                    key={h.id}
                    className="absolute rounded-sm"
                    style={{
                        ...h.style,
                        position: 'absolute',
                        backgroundColor: documentColors.paper,
                        border: `2px solid ${color}`,
                        cursor: h.cursor,
                        zIndex: 40,
                    }}
                    onMouseDown={(e) => { e.stopPropagation(); startResize(h.id, e); }}
                    onTouchStart={(e) => { e.stopPropagation(); startResize(h.id, e); }}
                />
            ))}

            {/* Required indicator dot */}
            {field.required && (
                <div
                    className="absolute top-0.5 right-0.5 w-1.5 h-1.5 rounded-full"
                    style={{ backgroundColor: color }}
                />
            )}
        </div>
    );
}

// ─── Document Upload Zone ─────────────────────────────────────────────────────

function UploadZone({ templateId, onUploaded }: { templateId: number; onUploaded: (doc: BuilderDocument) => void }) {
    const [dragging, setDragging] = useState(false);
    const [uploading, setUploading] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    async function handleFiles(files: Iterable<File>) {
        for (const file of files) {
            setUploading(true);
            const form = new FormData();
            form.append('file', file);
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            form.append('_token', csrfToken);
            try {
                const res = await fetch(`/templates/${templateId}/documents`, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    onUploaded(await res.json());
                } else {
                    let msg = `Upload failed (${res.status}).`;
                    try {
                        const body = await res.json();
                        const first = body.message || Object.values(body.errors || {})?.[0]?.[0];
                        if (first) msg = first;
                    } catch {}
                    alert(msg);
                }
            } catch (err) {
                alert(`Upload error: ${err instanceof Error ? err.message : String(err)}`);
            } finally {
                setUploading(false);
            }
        }
    }

    return (
        <div
            className={`rounded-xl border-2 border-dashed flex flex-col items-center justify-center py-16 gap-4 transition-colors ${dragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/30 hover:border-primary/50'}`}
            onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
            onDragLeave={() => setDragging(false)}
            onDrop={(e) => { e.preventDefault(); setDragging(false); handleFiles([...e.dataTransfer.files]); }}
        >
            <div className="p-4 rounded-full bg-muted">
                <Upload className="h-8 w-8 text-muted-foreground" />
            </div>
            <div className="text-center">
                <p className="font-medium">Upload a document</p>
                <p className="text-sm text-muted-foreground mt-1">PDF, JPG, PNG, GIF, WebP — up to 20 MB</p>
            </div>
            <Button variant="outline" disabled={uploading} onClick={() => inputRef.current?.click()}>
                {uploading ? 'Uploading…' : 'Choose file'}
            </Button>
            <Input
                ref={inputRef} type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                multiple className="hidden" onChange={(e) => handleFiles(Array.from(e.target.files ?? []))}
            />
        </div>
    );
}

// ─── Page Canvas ──────────────────────────────────────────────────────────────

function PageCanvas({
    doc, pageIndex, fields, submitters, drawingField,
    selectedAreaKey, onAreaSelect, onAreaDelete, onAreaMove, onAreaResize,
    onDrawComplete, onCapture,
}: {
    doc: BuilderDocument;
    pageIndex: number;
    fields: BuilderField[];
    submitters: BuilderSubmitter[];
    drawingField: BuilderField | null;
    selectedAreaKey: string | null;
    onAreaSelect: (key: string | null) => void;
    onAreaDelete: (fieldUuid: string, docUuid: string, page: number) => void;
    onAreaMove: (fieldUuid: string, docUuid: string, page: number, patch: Partial<BuilderArea>) => void;
    onAreaResize: (fieldUuid: string, docUuid: string, page: number, patch: Partial<BuilderArea>) => void;
    onDrawComplete: (area: BuilderArea) => void;
    onCapture?: (dataUrl: string) => void;
}) {
    const wrapRef = useRef<HTMLDivElement>(null);
    const [displayDims, setDisplayDims] = useState({ w: 0, h: 0 });
    // drawRect tracks the in-progress drag-to-draw rectangle
    const [drawRect, setDrawRect] = useState<{ x: number; y: number; w: number; h: number } | null>(null);
    const isPdf = doc.content_type === 'application/pdf';

    useEffect(() => {
        if (!wrapRef.current) return;
        const obs = new ResizeObserver(() => {
            if (wrapRef.current) {
                setDisplayDims({ w: wrapRef.current.offsetWidth, h: wrapRef.current.offsetHeight });
            }
        });
        obs.observe(wrapRef.current);
        return () => obs.disconnect();
    }, []);

    function handlePointerDown(e: React.MouseEvent<HTMLDivElement> | React.TouchEvent<HTMLDivElement>) {
        if (!drawingField || !wrapRef.current || !displayDims.w) return;
        if ('button' in e && e.button !== 0) return;
        e.preventDefault();
        e.stopPropagation();

        const rect = wrapRef.current.getBoundingClientRect();
        const { x: cx0, y: cy0 } = getClientPos(e);
        const startX = cx0 - rect.left;
        const startY = cy0 - rect.top;

        setDrawRect({ x: startX, y: startY, w: 0, h: 0 });

        function onMove(ev: MouseEvent | TouchEvent) {
            ev.preventDefault();
            const { x, y } = getClientPos(ev);
            const cx = x - rect.left;
            const cy = y - rect.top;
            setDrawRect({
                x: Math.min(startX, cx),
                y: Math.min(startY, cy),
                w: Math.abs(cx - startX),
                h: Math.abs(cy - startY),
            });
        }

        function onUp(ev: MouseEvent | TouchEvent) {
            window.removeEventListener('mousemove', onMove);
            window.removeEventListener('mouseup', onUp);
            window.removeEventListener('touchmove', onMove);
            window.removeEventListener('touchend', onUp);

            const { x, y } = getClientPos(ev);
            const cx = x - rect.left;
            const cy = y - rect.top;
            const rawW = Math.abs(cx - startX);
            const rawH = Math.abs(cy - startY);

            setDrawRect(null);

            const { w: dw, h: dh } = displayDims;
            const def = defaultSize(drawingField.type);

            const finalW = rawW > 10 ? rawW / dw : def.w;
            const finalH = rawH > 10 ? rawH / dh : def.h;
            const finalX = (rawW > 10 ? Math.min(startX, cx) : startX) / dw;
            const finalY = (rawH > 10 ? Math.min(startY, cy) : startY) / dh;

            onDrawComplete({
                attachment_uuid: doc.uuid,
                page: pageIndex,
                x: Math.max(0, Math.min(1 - finalW, finalX)),
                y: Math.max(0, Math.min(1 - finalH, finalY)),
                w: Math.min(1, finalW),
                h: Math.min(1, finalH),
            });
        }

        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);
        window.addEventListener('touchmove', onMove, { passive: false });
        window.addEventListener('touchend', onUp);
    }

    const areasOnPage = useMemo(() => {
        const result: Array<{ field: BuilderField; area: BuilderArea }> = [];
        fields.forEach((field) => {
            (field.areas ?? []).forEach((area) => {
                if (area.attachment_uuid === doc.uuid && area.page === pageIndex) {
                    result.push({ field, area });
                }
            });
        });
        return result;
    }, [fields, doc.uuid, pageIndex]);

    return (
        <div
            ref={wrapRef}
            className={`relative select-none ${drawingField ? 'cursor-crosshair' : ''}`}
            style={{ lineHeight: 0 }}
            onMouseDown={handlePointerDown}
            onTouchStart={handlePointerDown}
            onClick={(e) => {
                if (!drawingField) onAreaSelect(null);
            }}
        >
            {isPdf ? (
                <PdfPage url={doc.url} pageNumber={pageIndex + 1} onCapture={pageIndex === 0 ? onCapture : undefined} />
            ) : (
                <img src={doc.url} alt={doc.filename} className="w-full block" />
            )}

            {/* Existing field areas */}
            {areasOnPage.map(({ field, area }) => {
                const key = `${field.uuid}:${area.attachment_uuid}:${area.page}`;
                return (
                    <FieldAreaOverlay
                        key={key}
                        field={field}
                        area={area}
                        pageW={displayDims.w}
                        pageH={displayDims.h}
                        color={fieldColor(submitters, field.submitter_uuid)}
                        selected={selectedAreaKey === key}
                        onSelect={() => onAreaSelect(key)}
                        onDelete={() => onAreaDelete(field.uuid, doc.uuid, pageIndex)}
                        onMove={(patch) => onAreaMove(field.uuid, doc.uuid, pageIndex, patch)}
                        onResize={(patch) => onAreaResize(field.uuid, doc.uuid, pageIndex, patch)}
                    />
                );
            })}

            {/* Draw-in-progress ghost rectangle */}
            {drawRect && drawRect.w > 2 && drawRect.h > 2 && (
                <div
                    className="absolute pointer-events-none border-2 border-dashed border-primary bg-primary/10"
                    style={{ left: drawRect.x, top: drawRect.y, width: drawRect.w, height: drawRect.h, zIndex: 50 }}
                />
            )}
        </div>
    );
}

// ─── Field Sidebar ─────────────────────────────────────────────────────────────

function FieldSidebar({
    submitters, fields, drawingField, selectedFieldUuid,
    onAddField, onRemoveField, onUpdateField, onStartDraw, onStopDraw, onSelectField,
    onAddSubmitter, onRemoveSubmitter, onUpdateSubmitter, onMoveSubmitter, canAddSubmitter,
}: {
    submitters: BuilderSubmitter[];
    fields: BuilderField[];
    drawingField: BuilderField | null;
    selectedFieldUuid: string | null;
    onAddField: (submitterUuid: string, fieldType: string) => void;
    onRemoveField: (id: string) => void;
    onUpdateField: (id: string, field: BuilderField) => void;
    onStartDraw: (field: BuilderField) => void;
    onStopDraw: () => void;
    onSelectField: (id: string) => void;
    onAddSubmitter: () => void;
    onRemoveSubmitter: (id: string) => void;
    onUpdateSubmitter: (id: string, patch: Partial<BuilderSubmitter>) => void;
    onMoveSubmitter: (id: string, direction: number) => void;
    canAddSubmitter: boolean;
}) {
    return (
        <div className="flex flex-col h-full">
            <div className="px-4 py-3 border-b shrink-0">
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Fields</p>
            </div>

            <ScrollArea className="flex-1 min-h-0">
                <div className="px-3 py-3 space-y-5">
                    {submitters.map((submitter, sIdx) => {
                        const submitterFields = fields.filter((f) => f.submitter_uuid === submitter.uuid);
                        const color = signerColor(sIdx);

                        return (
                            <div key={submitter.uuid}>
                                {/* Submitter header */}
                                <div className="flex items-center gap-1 mb-2 group/sub">
                                    <div
                                        className="h-5 w-5 rounded-full shrink-0 flex items-center justify-center text-[10px] font-bold text-signer-foreground"
                                        style={{ backgroundColor: color }}
                                    >
                                        {submitter.name?.[0]?.toUpperCase() || '?'}
                                    </div>
                                    <Input
                                        value={submitter.name || ''}
                                        onChange={(e) => onUpdateSubmitter(submitter.uuid, { name: e.target.value })}
                                        className="h-6 text-xs font-medium border-0 shadow-none px-1 focus-visible:ring-1 bg-transparent flex-1 min-w-0"
                                        placeholder="Party name"
                                    />
                                    {submitters.length > 1 && (
                                        <div className="flex flex-col opacity-0 group-hover/sub:opacity-100 transition-opacity">
                                            <Button variant="ghost" size="icon-sm"
                                                className="p-0.5 text-muted-foreground hover:text-foreground disabled:opacity-30"
                                                disabled={sIdx === 0}
                                                onClick={() => onMoveSubmitter(submitter.uuid, -1)}
                                            ><ChevronUp className="h-3 w-3" /></Button>
                                            <Button variant="ghost" size="icon-sm"
                                                className="p-0.5 text-muted-foreground hover:text-foreground disabled:opacity-30"
                                                disabled={sIdx === submitters.length - 1}
                                                onClick={() => onMoveSubmitter(submitter.uuid, 1)}
                                            ><ChevronDown className="h-3 w-3" /></Button>
                                        </div>
                                    )}
                                    {/* Add field dropdown */}
                                    <DropdownMenu>
                                        <DropdownMenuTrigger asChild>
                                            <Button size="icon" variant="ghost" className="h-6 w-6 shrink-0">
                                                <Plus className="h-3.5 w-3.5" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" className="w-44">
                                            {FIELD_TYPES.map(({ type, label, icon: Icon }) => (
                                                <DropdownMenuItem
                                                    key={type}
                                                    className="gap-2 text-sm"
                                                    onClick={() => onAddField(submitter.uuid, type)}
                                                >
                                                    <Icon className="h-3.5 w-3.5" />{label}
                                                </DropdownMenuItem>
                                            ))}
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                    {submitters.length > 1 && (
                                        <TooltipProvider delayDuration={300}>
                                            <Tooltip>
                                                <TooltipTrigger asChild>
                                                    <Button variant="ghost" size="icon-sm"
                                                        className="shrink-0 p-1 text-muted-foreground hover:text-destructive opacity-0 group-hover/sub:opacity-100 transition-opacity"
                                                        onClick={() => onRemoveSubmitter(submitter.uuid)}
                                                    ><X className="h-3 w-3" /></Button>
                                                </TooltipTrigger>
                                                <TooltipContent side="right">Remove party</TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    )}
                                </div>

                                {/* Field type buttons grid */}
                                <div className="grid grid-cols-3 gap-1 mb-2">
                                    {FIELD_TYPES.filter((ft) => !['heading', 'strikethrough', 'verification', 'kba'].includes(ft.type)).map(({ type, label, icon: Icon }) => {
                                        const isDrawing = drawingField?.type === type && drawingField?.submitter_uuid === submitter.uuid;
                                        return (
                                            <Button variant="ghost" size="icon-sm"
                                                key={type}
                                                className={`flex flex-col items-center gap-0.5 p-1.5 rounded text-[10px] transition-colors ${isDrawing ? 'bg-primary text-primary-foreground' : 'hover:bg-accent text-muted-foreground hover:text-foreground'}`}
                                                onClick={() => isDrawing ? onStopDraw() : onAddField(submitter.uuid, type)}
                                            >
                                                <Icon className="h-3.5 w-3.5" />
                                                <span className="leading-tight text-center">{label}</span>
                                            </Button>
                                        );
                                    })}
                                </div>

                                {/* Auto-sign toggle */}
                                <Label className="flex items-center gap-2 mb-2 cursor-pointer select-none group/autosign">
                                    <Checkbox
                                        checked={submitter.auto_sign ?? false}
                                        onCheckedChange={(v) => onUpdateSubmitter(submitter.uuid, { auto_sign: v })}
                                    />
                                    <span className="text-xs text-muted-foreground group-hover/autosign:text-foreground transition-colors">
                                        Auto-sign if signature saved
                                    </span>
                                </Label>

                                {/* Fields list */}
                                <div className="space-y-0.5 ml-1">
                                    {submitterFields.length === 0 && (
                                        <p className="text-xs text-muted-foreground py-1 pl-1 italic">No fields yet. Click a type above to draw.</p>
                                    )}
                                    {submitterFields.map((field) => {
                                        const FieldIcon = FIELD_TYPES.find((f) => f.type === field.type)?.icon ?? Type;
                                        const hasArea = (field.areas ?? []).length > 0;
                                        const isDrawing = drawingField?.uuid === field.uuid;

                                        return (
                                            <div
                                                key={field.uuid}
                                                onClick={() => onSelectField?.(field.uuid)}
                                                className={`group/field flex items-center gap-2 px-2 py-1.5 rounded-md transition-colors cursor-pointer ${isDrawing ? 'bg-primary/10 ring-1 ring-primary' : selectedFieldUuid === field.uuid ? 'bg-accent' : 'hover:bg-accent'}`}
                                            >
                                                <div
                                                    className="h-3.5 w-3.5 shrink-0"
                                                    style={{ color: fieldColor(submitters, field.submitter_uuid) }}
                                                >
                                                    <FieldIcon className="h-3.5 w-3.5 text-muted-foreground" />
                                                </div>
                                                <Input
                                                    value={field.name || ''}
                                                    onChange={(e) => onUpdateField(field.uuid, { ...field, name: e.target.value })}
                                                    className="h-6 text-xs border-0 shadow-none px-0 focus-visible:ring-0 bg-transparent"
                                                    placeholder={field.type}
                                                    onClick={(e) => e.stopPropagation()}
                                                />
                                                <TooltipProvider delayDuration={300}>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button variant="ghost" size="icon-sm"
                                                                className={`shrink-0 p-1 rounded transition-colors ${isDrawing ? 'text-primary' : hasArea ? 'text-success' : 'text-muted-foreground hover:text-primary'}`}
                                                                onClick={(e) => { e.stopPropagation(); isDrawing ? onStopDraw() : onStartDraw(field); }}
                                                            ><Move className="h-3 w-3" /></Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent side="right">
                                                            {isDrawing ? 'Cancel' : hasArea ? 'Draw another area' : 'Draw on document'}
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                                <Button variant="ghost" size="icon-sm"
                                                    className="shrink-0 p-1 text-muted-foreground hover:text-destructive opacity-0 group-hover/field:opacity-100 transition-opacity"
                                                    onClick={(e) => { e.stopPropagation(); onRemoveField(field.uuid); }}
                                                ><X className="h-3 w-3" /></Button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </ScrollArea>

            {canAddSubmitter && (
                <div className="px-3 py-3 border-t shrink-0">
                    <Button variant="outline" size="sm" className="w-full gap-1.5 text-xs" onClick={onAddSubmitter}>
                        <Plus className="h-3.5 w-3.5" />Add party
                    </Button>
                </div>
            )}
        </div>
    );
}

// ─── Field Settings Panel ──────────────────────────────────────────────────────

function FieldSettingsPanel({ field, fields, documents, submitters, onUpdate, onClose, onDrawArea, onDeleteArea, onCopyToAllPages }: {
    field: BuilderField | null;
    fields: BuilderField[];
    documents: BuilderDocument[];
    submitters: BuilderSubmitter[];
    onUpdate: (id: string, field: BuilderField) => void;
    onClose: () => void;
    onDrawArea: () => void;
    onDeleteArea: (area: BuilderArea) => void;
    onCopyToAllPages: () => void;
}) {
    if (!field) {
        return (
            <div className="hidden md:flex w-72 shrink-0 border-l bg-muted/20 p-4 flex-col items-center justify-center text-center text-xs text-muted-foreground gap-2">
                <GitBranch className="h-8 w-8 opacity-30" />
                <span>Click a field or area to configure it</span>
            </div>
        );
    }

    function set(patch: Partial<BuilderField>) { onUpdate(field.uuid, { ...field, ...patch }); }
    function setPref(patch: NonNullable<BuilderField["preferences"]>) { set({ preferences: { ...(field.preferences ?? {}), ...patch } }); }
    function setVal(patch: NonNullable<BuilderField["validation"]>) { set({ validation: { ...(field.validation ?? {}), ...patch } }); }

    const hasOptions = ['select', 'radio', 'multiple'].includes(field.type);
    const hasValidation = ['text', 'cells'].includes(field.type);
    const hasNumberFormat = field.type === 'number';
    const hasDateFormat = field.type === 'date';
    const hasSigFormat = field.type === 'signature';
    const isCheckbox = field.type === 'checkbox';
    const isHeading = field.type === 'heading';
    const isPayment = field.type === 'payment';
    const isFormula = field.type === 'formula';
    const canCopyToPages = ['date', 'signature', 'initials', 'text', 'cells', 'stamp'].includes(field.type);
    const canBeRequired = !['heading', 'strikethrough', 'stamp', 'phone', 'verification', 'kba'].includes(field.type);
    const canBeReadonly = ['text', 'number', 'date', 'select'].includes(field.type);
    const canBePrefillable = ['text', 'number', 'date', 'select', 'checkbox', 'radio'].includes(field.type);

    function addOption() {
        set({ options: [...(field.options ?? []), { uuid: uuid(), value: '' }] });
    }
    function updateOption(uuid: string, value: string) {
        set({ options: (field.options ?? []).map((o) => o.uuid === uuid ? { ...o, value } : o) });
    }
    function removeOption(uuid: string) {
        set({ options: (field.options ?? []).filter((o) => o.uuid !== uuid) });
    }

    // Build list of areas with human-readable page labels
    const areaList = (field.areas ?? []).map((area) => {
        const docIdx = documents.findIndex((d) => d.uuid === area.attachment_uuid);
        return { area, label: `Doc ${docIdx + 1} · Page ${area.page + 1}` };
    });

    return (
        <div className="w-full md:w-72 shrink-0 md:border-l bg-muted/20 flex flex-col h-full">
            <div className="flex items-center justify-between px-4 py-3 border-b shrink-0">
                <p className="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                    {field.type.charAt(0).toUpperCase() + field.type.slice(1)} Field
                </p>
                <Button variant="ghost" size="sm" onClick={onClose} className="text-muted-foreground hover:text-foreground">
                    <X className="h-4 w-4" />
                </Button>
            </div>

            <ScrollArea className="flex-1 min-h-0">
                <div className="px-4 py-4 space-y-4">

                    {/* Field name */}
                    <div className="space-y-1.5">
                        <Label className="text-xs">Field name</Label>
                        <Input
                            value={field.name || ''}
                            onChange={(e) => set({ name: e.target.value })}
                            placeholder={field.type}
                            className="h-8 text-sm"
                        />
                    </div>

                    {/* Required / Read-only */}
                    {(canBeRequired || canBeReadonly || canBePrefillable) && (
                        <div className="flex flex-wrap gap-4">
                            {canBeRequired && (
                                <Label className="flex items-center gap-2 text-xs cursor-pointer">
                                    <Checkbox
                                        checked={field.required ?? true}
                                        onCheckedChange={(v) => set({ required: !!v })}
                                    />
                                    Required
                                </Label>
                            )}
                            {canBeReadonly && (
                                <Label className="flex items-center gap-2 text-xs cursor-pointer">
                                    <Checkbox
                                        checked={field.readonly ?? false}
                                        onCheckedChange={(v) => set({ readonly: !!v })}
                                    />
                                    Read-only
                                </Label>
                            )}
                            {canBePrefillable && (
                                <Label className="flex items-center gap-2 text-xs cursor-pointer">
                                    <Checkbox
                                        checked={field.prefillable ?? false}
                                        onCheckedChange={(v) => set({ prefillable: !!v })}
                                    />
                                    Prefillable
                                </Label>
                            )}
                        </div>
                    )}

                    {/* Checkbox default checked */}
                    {isCheckbox && (
                        <Label className="flex items-center gap-2 text-xs cursor-pointer">
                            <Checkbox
                                checked={field.default_value === true || field.default_value === 'true'}
                                onCheckedChange={(v) => set({ default_value: v ? true : false })}
                            />
                            Checked by default
                        </Label>
                    )}

                    {/* Signature format */}
                    {hasSigFormat && (
                        <div className="space-y-1.5">
                            <Label className="text-xs">Format</Label>
                            <Select
                                value={field.preferences?.format ?? 'any'}
                                onValueChange={(v) => setPref({ format: v })}
                            >
                                <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="any">Any (drawn, typed, or upload)</SelectItem>
                                    <SelectItem value="drawn">Drawn only</SelectItem>
                                    <SelectItem value="typed">Typed only</SelectItem>
                                    <SelectItem value="drawn_or_typed">Drawn or typed</SelectItem>
                                    <SelectItem value="drawn_or_upload">Drawn or upload</SelectItem>
                                    <SelectItem value="upload">Upload only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {/* Date format + signing date toggle */}
                    {hasDateFormat && (
                        <>
                            <div className="space-y-1.5">
                                <Label className="text-xs">Date format</Label>
                                <Select
                                    value={field.preferences?.format ?? 'MM/DD/YYYY'}
                                    onValueChange={(v) => setPref({ format: v })}
                                >
                                    <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {['MM/DD/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD', 'DD-MM-YYYY', 'DD.MM.YYYY'].map((f) => (
                                            <SelectItem key={f} value={f}>{f}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Label className="flex items-center gap-2 text-xs cursor-pointer">
                                <Checkbox
                                    checked={field.default_value === '{{date}}'}
                                    onCheckedChange={(v) => set({ default_value: v ? '{{date}}' : '', readonly: !!v })}
                                />
                                Set to signing date (auto-fill)
                            </Label>
                        </>
                    )}

                    {/* Number format */}
                    {hasNumberFormat && (
                        <>
                            <div className="space-y-1.5">
                                <Label className="text-xs">Format</Label>
                                <Select
                                    value={field.preferences?.format ?? 'none'}
                                    onValueChange={(v) => setPref({ format: v })}
                                >
                                    <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">None (plain number)</SelectItem>
                                        <SelectItem value="usd">USD ($1,234.56)</SelectItem>
                                        <SelectItem value="eur">EUR (€1.234,56)</SelectItem>
                                        <SelectItem value="gbp">GBP (£1,234.56)</SelectItem>
                                        <SelectItem value="comma">1,234,567.89</SelectItem>
                                        <SelectItem value="dot">1.234.567,89</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div className="space-y-1.5">
                                    <Label className="text-xs">Min</Label>
                                    <Input
                                        type="number" className="h-8 text-sm"
                                        value={field.validation?.min ?? ''}
                                        onChange={(e) => setVal({ min: e.target.value === '' ? undefined : Number(e.target.value) })}
                                        placeholder="No min"
                                    />
                                </div>
                                <div className="space-y-1.5">
                                    <Label className="text-xs">Max</Label>
                                    <Input
                                        type="number" className="h-8 text-sm"
                                        value={field.validation?.max ?? ''}
                                        onChange={(e) => setVal({ max: e.target.value === '' ? undefined : Number(e.target.value) })}
                                        placeholder="No max"
                                    />
                                </div>
                            </div>
                        </>
                    )}

                    {/* Formula (for number type) */}
                    {isFormula && (
                        <div className="space-y-1.5">
                            <Label className="text-xs">Formula</Label>
                            <Textarea
                                className="w-full rounded-md border bg-background px-3 py-2 text-xs font-mono resize-none min-h-[60px]"
                                value={field.preferences?.formula ?? ''}
                                onChange={(e) => setPref({ formula: e.target.value })}
                                placeholder="e.g. {{Price}} * {{Qty}}"
                            />
                            <p className="text-[10px] text-muted-foreground">
                                Use <code className="bg-muted px-1 rounded">{'{{field_name}}'}</code> to reference other number fields.
                            </p>
                            <div className="flex flex-wrap gap-1">
                                {fields.filter((f) => f.type === 'number' && f.uuid !== field.uuid && f.name).map((f) => (
                                    <Button variant="ghost" size="compact"
                                        key={f.uuid}
                                        className="text-[10px] px-1.5 py-0.5 rounded bg-muted hover:bg-accent"
                                        onClick={() => setPref({ formula: (field.preferences?.formula ?? '') + `{{${f.name}}}` })}
                                    >
                                        {f.name}
                                    </Button>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Text / Cells validation */}
                    {hasValidation && (
                        <div className="space-y-1.5">
                            <Label className="text-xs">Validation</Label>
                            <Select
                                value={field.validation?.type ?? 'none'}
                                onValueChange={(v) => setVal({ type: v, pattern: undefined, message: undefined })}
                            >
                                <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    <SelectItem value="email">Email</SelectItem>
                                    <SelectItem value="url">URL</SelectItem>
                                    <SelectItem value="ssn">SSN</SelectItem>
                                    <SelectItem value="ein">EIN</SelectItem>
                                    <SelectItem value="zip">ZIP code</SelectItem>
                                    <SelectItem value="numbers_only">Numbers only</SelectItem>
                                    <SelectItem value="letters_only">Letters only</SelectItem>
                                    <SelectItem value="length">Length range</SelectItem>
                                    <SelectItem value="custom">Custom regex</SelectItem>
                                </SelectContent>
                            </Select>
                            {field.validation?.type === 'length' && (
                                <div className="grid grid-cols-2 gap-2">
                                    <Input type="number" placeholder="Min" className="h-8 text-sm"
                                        value={field.validation?.min ?? ''}
                                        onChange={(e) => setVal({ min: Number(e.target.value) })} />
                                    <Input type="number" placeholder="Max" className="h-8 text-sm"
                                        value={field.validation?.max ?? ''}
                                        onChange={(e) => setVal({ max: Number(e.target.value) })} />
                                </div>
                            )}
                            {field.validation?.type === 'custom' && (
                                <>
                                    <Input placeholder="Regex pattern" className="h-8 text-sm font-mono"
                                        value={field.validation?.pattern ?? ''}
                                        onChange={(e) => setVal({ pattern: e.target.value })} />
                                    <Input placeholder="Error message" className="h-8 text-sm"
                                        value={field.validation?.message ?? ''}
                                        onChange={(e) => setVal({ message: e.target.value })} />
                                </>
                            )}
                        </div>
                    )}

                    {/* Default value */}
                    {['text', 'number', 'textarea'].includes(field.type) && (
                        <div className="space-y-1.5">
                            <Label className="text-xs">Default value</Label>
                            <Input
                                value={String(field.default_value ?? '')}
                                onChange={(e) => set({ default_value: e.target.value })}
                                className="h-8 text-sm"
                                placeholder="Leave blank for none"
                            />
                        </div>
                    )}

                    {/* Heading default text */}
                    {isHeading && (
                        <div className="space-y-1.5">
                            <Label className="text-xs">Heading text</Label>
                            <Input
                                value={String(field.default_value ?? '')}
                                onChange={(e) => set({ default_value: e.target.value })}
                                className="h-8 text-sm"
                                placeholder="Heading text"
                            />
                        </div>
                    )}

                    {/* Payment settings */}
                    {isPayment && (
                        <div className="space-y-3">
                            <div className="space-y-1.5">
                                <Label className="text-xs">Currency</Label>
                                <Select
                                    value={field.preferences?.currency ?? 'USD'}
                                    onValueChange={(v) => setPref({ currency: v })}
                                >
                                    <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="USD">USD ($)</SelectItem>
                                        <SelectItem value="EUR">EUR (€)</SelectItem>
                                        <SelectItem value="GBP">GBP (£)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-xs">Price</Label>
                                <Input
                                    type="number" step="0.01" className="h-8 text-sm"
                                    value={field.preferences?.price ?? ''}
                                    onChange={(e) => setPref({ price: parseFloat(e.target.value) || 0 })}
                                    placeholder="0.00"
                                />
                            </div>
                        </div>
                    )}

                    {/* Select / Radio / Multiple options */}
                    {hasOptions && (
                        <div className="space-y-2">
                            <div className="flex items-center justify-between">
                                <Label className="text-xs">Options</Label>
                                <Button variant="ghost" size="compact" onClick={addOption} className="text-xs text-primary hover:underline">+ Add option</Button>
                            </div>
                            {(field.options ?? []).map((opt) => (
                                <div key={opt.uuid} className="flex items-center gap-1.5">
                                    <Input
                                        value={opt.value}
                                        onChange={(e) => updateOption(opt.uuid, e.target.value)}
                                        className="h-7 text-xs flex-1"
                                        placeholder="Option value"
                                    />
                                    <Button variant="ghost" size="sm" onClick={() => removeOption(opt.uuid)} className="text-muted-foreground hover:text-destructive">
                                        <X className="h-3.5 w-3.5" />
                                    </Button>
                                </div>
                            ))}
                            {(field.options ?? []).length === 0 && (
                                <p className="text-xs text-muted-foreground italic">No options yet</p>
                            )}
                        </div>
                    )}

                    {/* Submitter assignment */}
                    <div className="space-y-1.5">
                        <Label className="text-xs">Assigned to</Label>
                        <Select
                            value={field.submitter_uuid ?? submitters[0]?.uuid}
                            onValueChange={(v) => set({ submitter_uuid: v })}
                        >
                            <SelectTrigger className="h-8 text-sm"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {submitters.map((s, i) => (
                                    <SelectItem key={s.uuid} value={s.uuid}>
                                        <span className="flex items-center gap-2">
                                            <span
                                                className="h-3 w-3 rounded-full inline-block shrink-0"
                                                style={{ backgroundColor: signerColor(i) }}
                                            />
                                            {s.name || `Party ${i + 1}`}
                                        </span>
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Field areas */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label className="text-xs">Areas on document ({areaList.length})</Label>
                            <Button variant="ghost" size="compact"
                                onClick={onDrawArea}
                                className="text-xs text-primary hover:underline"
                            >+ Draw area</Button>
                        </div>
                        {areaList.length === 0 && (
                            <p className="text-xs text-muted-foreground italic">No area drawn yet</p>
                        )}
                        {areaList.map(({ area, label }, idx) => (
                            <div key={idx} className="flex items-center justify-between text-xs bg-muted/50 rounded px-2 py-1">
                                <span>{label}</span>
                                <Button variant="ghost" size="sm"
                                    onClick={() => onDeleteArea(area)}
                                    className="text-muted-foreground hover:text-destructive"
                                ><X className="h-3 w-3" /></Button>
                            </div>
                        ))}
                        {canCopyToPages && areaList.length > 0 && documents.some((d) => d.page_count > 1) && (
                            <Button variant="outline" size="sm" className="w-full text-xs gap-1.5" onClick={onCopyToAllPages}>
                                <Copy className="h-3.5 w-3.5" />Copy to all pages
                            </Button>
                        )}
                    </div>

                    {/* Conditions */}
                    <div className="space-y-2">
                        <Label className="text-xs">Conditions</Label>
                        {(field.conditions ?? []).length === 0 ? (
                            <p className="text-xs text-muted-foreground italic">No conditions set</p>
                        ) : (
                            <div className="space-y-1.5">
                                {(field.conditions ?? []).map((cond, idx) => {
                                    const sourceField = fields.find((f) => f.uuid === cond.field_uuid);
                                    return (
                                        <div key={idx} className="flex items-start gap-1.5 text-xs bg-muted/50 rounded px-2 py-1.5">
                                            <div className="flex-1 min-w-0">
                                                <div className="font-medium truncate">{sourceField?.name || 'Unknown field'}</div>
                                                <div className="text-muted-foreground">{cond.action?.replace(/_/g, ' ')}{cond.value ? `: ${cond.value}` : ''}</div>
                                            </div>
                                            <Button variant="ghost" size="sm"
                                                onClick={() => set({ conditions: (field.conditions ?? []).filter((_, i) => i !== idx) })}
                                                className="text-muted-foreground hover:text-destructive shrink-0"
                                            ><X className="h-3 w-3" /></Button>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                        <ConditionEditor field={field} fields={fields} onAdd={(cond) => set({ conditions: [...(field.conditions ?? []), cond] })} />
                    </div>

                </div>
            </ScrollArea>
        </div>
    );
}

// ─── Condition Editor ─────────────────────────────────────────────────────────

function ConditionEditor({ field, fields, onAdd }: {
    field: BuilderField;
    fields: BuilderField[];
    onAdd: (condition: NonNullable<BuilderField['conditions']>[number]) => void;
}) {
    const [sourceUuid, setSourceUuid] = useState('');
    const [action, setAction] = useState('filled');
    const [value, setValue] = useState('');

    const sourceField = fields.find((f) => f.uuid === sourceUuid);
    const eligibleFields = fields.filter((f) => f.uuid !== field.uuid && !['heading', 'strikethrough'].includes(f.type) && f.name);

    const actions = sourceField
        ? (sourceField.type === 'checkbox'
            ? ['filled', 'not_filled']
            : ['filled', 'not_filled', 'equals', 'not_equals'])
        : [];

    const needsValue = ['equals', 'not_equals'].includes(action) && sourceField;
    const hasOptions = needsValue && ['select', 'radio', 'multiple'].includes(sourceField?.type);

    function submit() {
        if (!sourceUuid || !action) return;
        onAdd({ field_uuid: sourceUuid, action, value: needsValue ? value : undefined });
        setSourceUuid(''); setAction('filled'); setValue('');
    }

    if (eligibleFields.length === 0) return null;

    return (
        <div className="space-y-2 border rounded p-2 bg-muted/30">
            <p className="text-[10px] font-medium text-muted-foreground uppercase tracking-wide">Add condition</p>
            <Select value={sourceUuid} onValueChange={(v) => { setSourceUuid(v); setAction('filled'); setValue(''); }}>
                <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="Select field…" /></SelectTrigger>
                <SelectContent>
                    {eligibleFields.map((f) => (
                        <SelectItem key={f.uuid} value={f.uuid}>{f.name}</SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {sourceUuid && (
                <Select value={action} onValueChange={setAction}>
                    <SelectTrigger className="h-7 text-xs"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        {actions.map((a) => <SelectItem key={a} value={a}>{a.replace(/_/g, ' ')}</SelectItem>)}
                    </SelectContent>
                </Select>
            )}
            {needsValue && (
                hasOptions ? (
                    <Select value={value} onValueChange={setValue}>
                        <SelectTrigger className="h-7 text-xs"><SelectValue placeholder="Select value…" /></SelectTrigger>
                        <SelectContent>
                            {(sourceField.options ?? []).map((o) => (
                                <SelectItem key={o.uuid} value={o.uuid}>{o.value}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <Input value={value} onChange={(e) => setValue(e.target.value)} placeholder="Value" className="h-7 text-xs" />
                )
            )}
            {sourceUuid && (
                <Button size="sm" className="w-full h-7 text-xs" onClick={submit}>Add</Button>
            )}
        </div>
    );
}

// ─── Main Builder ─────────────────────────────────────────────────────────────

export default function TemplateBuilder({ template }: { template: BuilderTemplate }) {
    const [name, setName]           = useState(template.name || '');
    const [submitters, setSubmitters] = useState<BuilderSubmitter[]>(
        template.submitters?.length
            ? template.submitters
            : [{ name: 'First Party', uuid: uuid() }]
    );
    const [fields, setFields]       = useState<BuilderField[]>(template.fields || []);
    const [documents, setDocuments] = useState<BuilderDocument[]>(template.documents || []);
    const [saving, setSaving]       = useState(false);
    const [drawingField, setDrawingField] = useState<BuilderField | null>(null); // field being drawn
    const [selectedAreaKey, setSelectedAreaKey] = useState<string | null>(null);
    const [selectedFieldUuid, setSelectedFieldUuid] = useState<string | null>(null);
    const [uploading, setUploading] = useState(false);
    const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
    const [mobileSettingsOpen, setMobileSettingsOpen] = useState(false);

    // Undo/redo stacks
    const undoStack = useRef<string[]>([]);
    const redoStack = useRef<string[]>([]);

    function snapshot() {
        undoStack.current.push(JSON.stringify({ fields, submitters }));
        redoStack.current = [];
    }

    function undo() {
        if (!undoStack.current.length) return;
        redoStack.current.push(JSON.stringify({ fields, submitters }));
        const prev = JSON.parse(undoStack.current.pop()!);
        setFields(prev.fields);
        setSubmitters(prev.submitters);
    }
    function redo() {
        if (!redoStack.current.length) return;
        undoStack.current.push(JSON.stringify({ fields, submitters }));
        const next = JSON.parse(redoStack.current.pop()!);
        setFields(next.fields);
        setSubmitters(next.submitters);
    }

    const capturedThumbnails = useRef<Set<string>>(new Set());
    const selectedField = fields.find((f) => f.uuid === selectedFieldUuid) ?? null;

    useEffect(() => {
        if (!selectedField) setMobileSettingsOpen(false);
    }, [selectedField]);

    // ─ Keyboard shortcuts ─────────────────────────────────────────────────────

    useEffect(() => {
        function handleKey(e: KeyboardEvent) {
            const tag = document.activeElement?.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA') return;

            if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) { e.preventDefault(); undo(); return; }
            if ((e.metaKey || e.ctrlKey) && (e.key === 'Z' || (e.key === 'z' && e.shiftKey))) { e.preventDefault(); redo(); return; }
            if (e.key === 'Escape') { setDrawingField(null); setSelectedAreaKey(null); return; }

            if (!selectedAreaKey) return;
            const [fieldUuid, docUuid, pageStr] = selectedAreaKey.split(':');
            const pageIndex = parseInt(pageStr ?? "0", 10);
            const STEP = e.shiftKey ? 5 : 1;

            function moveSelected(dx: number, dy: number) {
                setFields((prev) => prev.map((f) => {
                    if (f.uuid !== fieldUuid) return f;
                    return {
                        ...f,
                        areas: (f.areas ?? []).map((a) => {
                            if (a.attachment_uuid !== docUuid || a.page !== pageIndex) return a;
                            const doc = documents.find((d) => d.uuid === docUuid);
                            const pageEl = document.querySelector<HTMLElement>(`[data-page="${docUuid}-${pageIndex}"]`);
                            const pw = pageEl?.offsetWidth ?? 800;
                            const ph = pageEl?.offsetHeight ?? 1100;
                            return {
                                ...a,
                                x: Math.max(0, Math.min(1 - a.w, a.x + dx / pw)),
                                y: Math.max(0, Math.min(1 - a.h, a.y + dy / ph)),
                            };
                        }),
                    };
                }));
            }

            if (e.key === 'Delete' || e.key === 'Backspace') {
                e.preventDefault();
                handleAreaDelete(fieldUuid, docUuid, parseInt(pageStr, 10));
                setSelectedAreaKey(null);
                return;
            }
            if (e.key === 'ArrowLeft')  { e.preventDefault(); moveSelected(-STEP, 0); }
            if (e.key === 'ArrowRight') { e.preventDefault(); moveSelected(STEP, 0); }
            if (e.key === 'ArrowUp')    { e.preventDefault(); moveSelected(0, -STEP); }
            if (e.key === 'ArrowDown')  { e.preventDefault(); moveSelected(0, STEP); }
        }
        window.addEventListener('keydown', handleKey);
        return () => window.removeEventListener('keydown', handleKey);
    }, [selectedAreaKey, documents]);

    // ─ Field management ───────────────────────────────────────────────────────

    const addField = useCallback((submitterUuid: string, type: string) => {
        snapshot();
        const ft = FIELD_TYPES.find((f) => f.type === type);
        const newField = {
            uuid: uuid(),
            name: '',
            type,
            submitter_uuid: submitterUuid,
            required: !['heading', 'strikethrough', 'stamp'].includes(type),
            areas: [],
            options: ['select', 'radio', 'multiple'].includes(type)
                ? [{ uuid: uuid(), value: 'Option 1' }, { uuid: uuid(), value: 'Option 2' }]
                : undefined,
        };
        setFields((prev) => [...prev, newField]);
        if (ft?.drawable) {
            setDrawingField(newField);
        }
        setSelectedFieldUuid(newField.uuid);
    }, [fields, submitters]);

    const removeField = useCallback((uuid: string) => {
        snapshot();
        setFields((prev) => prev.filter((f) => f.uuid !== uuid));
        setDrawingField((prev) => prev?.uuid === uuid ? null : prev);
        if (selectedFieldUuid === uuid) setSelectedFieldUuid(null);
    }, [fields, submitters, selectedFieldUuid]);

    const updateField = useCallback((uuid: string, updated: BuilderField) => {
        setFields((prev) => prev.map((f) => (f.uuid === uuid ? updated : f)));
    }, []);

    // ─ Area management ────────────────────────────────────────────────────────

    function handleDrawComplete(areaData: BuilderArea) {
        if (!drawingField) return;
        const fieldUuid = drawingField.uuid;
        setFields((prev) => prev.map((f) => {
            if (f.uuid !== fieldUuid) return f;
            return { ...f, areas: [...(f.areas ?? []), areaData] };
        }));
        setSelectedAreaKey(`${fieldUuid}:${areaData.attachment_uuid}:${areaData.page}`);
        setDrawingField(null);
    }

    function handleAreaDelete(fieldUuid: string, attachmentUuid: string, page: number) {
        setFields((prev) => prev.map((f) => {
            if (f.uuid !== fieldUuid) return f;
            return {
                ...f,
                areas: (f.areas ?? []).filter(
                    (a) => !(a.attachment_uuid === attachmentUuid && a.page === page)
                ),
            };
        }));
    }

    function handleAreaMove(fieldUuid: string, attachmentUuid: string, page: number, patch: Partial<BuilderArea>) {
        setFields((prev) => prev.map((f) => {
            if (f.uuid !== fieldUuid) return f;
            return {
                ...f,
                areas: (f.areas ?? []).map((a) =>
                    a.attachment_uuid === attachmentUuid && a.page === page ? { ...a, ...patch } : a
                ),
            };
        }));
    }

    function handleAreaResize(fieldUuid: string, attachmentUuid: string, page: number, patch: Partial<BuilderArea>) {
        setFields((prev) => prev.map((f) => {
            if (f.uuid !== fieldUuid) return f;
            return {
                ...f,
                areas: (f.areas ?? []).map((a) =>
                    a.attachment_uuid === attachmentUuid && a.page === page ? { ...a, ...patch } : a
                ),
            };
        }));
    }

    function handleDeleteAreaFromSettings(area: BuilderArea) {
        if (!selectedField) return;
        handleAreaDelete(selectedField.uuid, area.attachment_uuid, area.page);
    }

    function handleCopyToAllPages() {
        if (!selectedField || !selectedField.areas?.length) return;
        const firstArea = selectedField.areas[0];
        const doc = documents.find((d) => d.uuid === firstArea.attachment_uuid);
        if (!doc) return;
        const newAreas = [...selectedField.areas];
        for (let p = 0; p < (doc.page_count ?? 1); p++) {
            if (p === firstArea.page) continue;
            if (newAreas.some((a) => a.attachment_uuid === firstArea.attachment_uuid && a.page === p)) continue;
            newAreas.push({ ...firstArea, page: p });
        }
        updateField(selectedField.uuid, { ...selectedField, areas: newAreas });
    }

    // ─ Document upload ────────────────────────────────────────────────────────

    async function handleFileUpload(files: Iterable<File>) {
        for (const file of files) {
            setUploading(true);
            const form = new FormData();
            form.append('file', file);
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            form.append('_token', csrfToken);
            try {
                const res = await fetch(`/templates/${template.id}/documents`, {
                    method: 'POST',
                    body: form,
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (res.ok) {
                    const data = await res.json();
                    setDocuments((prev) => [...prev, {
                        uuid: data.uuid, filename: data.filename,
                        content_type: data.content_type, page_count: data.page_count, url: data.url,
                    }]);
                } else {
                    let msg = `Upload failed (${res.status}).`;
                    try {
                        const body = await res.json();
                        const first = body.message || Object.values(body.errors || {})?.[0]?.[0];
                        if (first) msg = first;
                    } catch {}
                    alert(msg);
                }
            } catch (err) {
                alert(`Upload error: ${err instanceof Error ? err.message : String(err)}`);
            } finally {
                setUploading(false);
            }
        }
    }

    async function handleDocumentDelete(docUuid: string) {
        if (!confirm('Remove this document? All associated field areas will also be removed.')) return;
        const res = await fetch(`/templates/${template.id}/documents/${docUuid}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                'X-HTTP-Method-Override': 'DELETE',
            },
        });
        if (res.ok) {
            setDocuments((prev) => prev.filter((d) => d.uuid !== docUuid));
            setFields((prev) => prev.map((f) => ({
                ...f,
                areas: (f.areas ?? []).filter((a) => a.attachment_uuid !== docUuid),
            })));
        }
    }

    async function handleCaptureThumbnail(docUuid: string, dataUrl: string) {
        if (capturedThumbnails.current.has(docUuid)) return;
        capturedThumbnails.current.add(docUuid);
        try {
            await fetch(`/templates/${template.id}/documents/${docUuid}/thumbnail`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ data: dataUrl }),
            });
        } catch { /* best-effort */ }
    }

    // ─ Submitter management ───────────────────────────────────────────────────

    const addSubmitter = useCallback(() => {
        setSubmitters((prev) => {
            if (prev.length >= PARTY_NAMES.length) return prev;
            return [...prev, { name: PARTY_NAMES[prev.length], uuid: uuid() }];
        });
    }, []);

    const removeSubmitter = useCallback((uuid: string) => {
        if (!window.confirm('Remove this party? All their fields will also be deleted.')) return;
        snapshot();
        setSubmitters((prev) => prev.filter((s) => s.uuid !== uuid));
        setFields((prev) => prev.filter((f) => f.submitter_uuid !== uuid));
        setDrawingField((prev) => (prev?.submitter_uuid === uuid ? null : prev));
    }, [fields, submitters]);

    const updateSubmitter = useCallback((uuid: string, updates: Partial<BuilderSubmitter>) => {
        setSubmitters((prev) => prev.map((s) => (s.uuid === uuid ? { ...s, ...updates } : s)));
    }, []);

    const moveSubmitter = useCallback((uuid: string, direction: number) => {
        setSubmitters((prev) => {
            const idx = prev.findIndex((s) => s.uuid === uuid);
            const newIdx = idx + direction;
            if (newIdx < 0 || newIdx >= prev.length) return prev;
            const next = [...prev];
            [next[idx], next[newIdx]] = [next[newIdx], next[idx]];
            return next;
        });
    }, []);

    // ─ Save ───────────────────────────────────────────────────────────────────

    function handleSave() {
        setSaving(true);
        const schema = documents.map((doc) => ({
            attachment_uuid: doc.uuid,
            name: doc.filename?.replace(/\.[^.]+$/, '') ?? 'Document',
        }));
        router.put(`/templates/${template.id}/builder`, {
            name,
            // Inertia serializes these nested builder contracts as regular JSON objects.
            fields: fields as any,
            submitters: submitters as any,
            schema,
            preferences: template.preferences || {},
        }, {
            onFinish: () => setSaving(false),
            preserveState: true,
            preserveScroll: true,
        });
    }

    // ─ Window drag-drop ───────────────────────────────────────────────────────

    useEffect(() => {
        function prevent(e: DragEvent) { e.preventDefault(); }
        function handleDrop(e: DragEvent) {
            e.preventDefault();
            const files = Array.from(e.dataTransfer?.files ?? []).filter(
                (f) => /^(application\/pdf|image\/(jpeg|png|gif|webp))$/.test(f.type)
            );
            if (files.length) handleFileUpload(files);
        }
        window.addEventListener('dragover', prevent);
        window.addEventListener('drop', handleDrop);
        return () => { window.removeEventListener('dragover', prevent); window.removeEventListener('drop', handleDrop); };
    }, []);

    // ─ Render ─────────────────────────────────────────────────────────────────

    const fileInputRef = useRef<HTMLInputElement>(null);

    return (
        <div className="h-screen flex flex-col bg-background overflow-hidden">
            <Head title={`Builder: ${template.name}`} />

            {/* Top bar */}
            <header className="shrink-0 border-b bg-background/95 backdrop-blur z-30">
                <div className="flex h-13 items-center px-3 sm:px-4 gap-2 sm:gap-3">
                    {/* Mobile: hamburger to toggle sidebar */}
                    <Button
                        variant="ghost" size="icon"
                        className="h-8 w-8 md:hidden shrink-0"
                        onClick={() => setMobileSidebarOpen(v => !v)}
                    >
                        <Menu className="h-4 w-4" />
                    </Button>

                    <Link
                        href={`/templates/${template.id}`}
                        className="hidden sm:flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground shrink-0"
                    >
                        <ChevronLeft className="h-4 w-4" />Back
                    </Link>

                    <Input
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className="h-8 text-sm font-medium border-0 shadow-none focus-visible:ring-0 px-2 flex-1 sm:flex-none sm:max-w-xs min-w-0"
                        placeholder="Template name"
                    />

                    <div className="flex items-center gap-1 sm:gap-2 ml-auto shrink-0">
                        {/* Desktop: Undo / Redo */}
                        <div className="hidden sm:flex items-center gap-1">
                            <TooltipProvider delayDuration={300}>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={undo} disabled={undoStack.current.length === 0}>
                                            <RotateCcw className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Undo (⌘Z)</TooltipContent>
                                </Tooltip>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button variant="ghost" size="icon" className="h-8 w-8" onClick={redo} disabled={redoStack.current.length === 0}>
                                            <RotateCw className="h-4 w-4" />
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>Redo (⌘⇧Z)</TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </div>

                        {/* Desktop: Add document */}
                        <Button
                            variant="outline" size="sm" className="hidden sm:flex gap-1.5"
                            disabled={uploading}
                            onClick={() => fileInputRef.current?.click()}
                        >
                            <Upload className="h-3.5 w-3.5" />
                            {uploading ? 'Uploading…' : 'Add document'}
                        </Button>
                        <Input
                            ref={fileInputRef} type="file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                            multiple className="hidden" onChange={(e) => handleFileUpload(Array.from(e.target.files ?? []))}
                        />

                        {/* Desktop: Preview */}
                        <Button variant="outline" size="sm" asChild className="hidden sm:flex">
                            <Link href={`/templates/${template.id}/preview`} target="_blank" className="gap-1.5">
                                <Eye className="h-3.5 w-3.5" />Preview
                            </Link>
                        </Button>

                        {/* Mobile: overflow menu */}
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8 sm:hidden">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={undo} disabled={undoStack.current.length === 0}>
                                    <RotateCcw className="h-3.5 w-3.5 mr-2" />Undo
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={redo} disabled={redoStack.current.length === 0}>
                                    <RotateCw className="h-3.5 w-3.5 mr-2" />Redo
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onClick={() => fileInputRef.current?.click()} disabled={uploading}>
                                    <Upload className="h-3.5 w-3.5 mr-2" />{uploading ? 'Uploading…' : 'Add document'}
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/preview`} target="_blank" className="flex items-center">
                                        <Eye className="h-3.5 w-3.5 mr-2" />Preview
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <Button size="sm" className="gap-1.5" onClick={handleSave} disabled={saving}>
                            <Save className="h-3.5 w-3.5" />
                            {saving ? 'Saving…' : 'Save'}
                        </Button>
                    </div>
                </div>
            </header>

            {/* Builder body */}
            <div className="flex flex-1 overflow-hidden relative">
                {/* Mobile sidebar backdrop */}
                {mobileSidebarOpen && (
                    <div
                        className="fixed inset-0 bg-overlay/40 z-20 md:hidden"
                        onClick={() => setMobileSidebarOpen(false)}
                    />
                )}

                {/* Left sidebar */}
                <aside className={`
                    fixed inset-y-0 left-0 z-30
                    md:relative md:inset-auto md:z-auto
                    w-64 shrink-0 border-r
                    bg-background md:bg-muted/30
                    overflow-hidden flex flex-col
                    transition-transform duration-200 ease-in-out
                    ${mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}
                `}>
                    <FieldSidebar
                        submitters={submitters}
                        fields={fields}
                        drawingField={drawingField}
                        selectedFieldUuid={selectedFieldUuid}
                        onAddField={addField}
                        onRemoveField={removeField}
                        onUpdateField={updateField}
                        onStartDraw={(f) => setDrawingField(f)}
                        onStopDraw={() => setDrawingField(null)}
                        onSelectField={(uuid) => {
                            setSelectedFieldUuid((prev) => prev === uuid ? null : uuid);
                        }}
                        onAddSubmitter={addSubmitter}
                        onRemoveSubmitter={removeSubmitter}
                        onUpdateSubmitter={updateSubmitter}
                        onMoveSubmitter={moveSubmitter}
                        canAddSubmitter={submitters.length < PARTY_NAMES.length}
                    />
                </aside>

                {/* Canvas area */}
                <main className="flex-1 overflow-auto bg-muted/20 p-3 sm:p-6">
                    {drawingField && (
                        <div className="sticky top-0 z-20 mb-4 flex items-center gap-2 bg-primary text-primary-foreground px-3 sm:px-4 py-2 rounded-lg shadow-lg text-sm font-medium">
                            <Move className="h-4 w-4 shrink-0" />
                            <span className="truncate min-w-0">Drag to place <strong>"{drawingField.name || drawingField.type}"</strong></span>
                            <Button variant="secondary" size="sm" className="ml-auto h-6 shrink-0" onClick={() => setDrawingField(null)}>
                                Cancel
                            </Button>
                        </div>
                    )}

                    {documents.length === 0 ? (
                        <UploadZone
                            templateId={template.id}
                            onUploaded={(data) => setDocuments((prev) => [...prev, data])}
                        />
                    ) : (
                        <div className="space-y-8 max-w-3xl mx-auto">
                            {documents.map((doc) => (
                                <div key={doc.uuid} className="rounded-xl overflow-hidden border ui-document-paper shadow-sm">
                                    <div className="flex items-center gap-2 px-4 py-2 bg-muted border-b">
                                        <Paperclip className="h-4 w-4 text-muted-foreground shrink-0" />
                                        <span className="text-sm font-medium truncate flex-1">{doc.filename}</span>
                                        <Badge variant="secondary" className="text-xs">
                                            {doc.page_count} {doc.page_count === 1 ? 'page' : 'pages'}
                                        </Badge>
                                        <Button
                                            variant="ghost" size="icon" className="h-7 w-7 text-muted-foreground hover:text-destructive"
                                            onClick={() => handleDocumentDelete(doc.uuid)}
                                        ><Trash2 className="h-3.5 w-3.5" /></Button>
                                    </div>

                                    {Array.from({ length: doc.page_count }, (_, i) => (
                                        <div
                                            key={i}
                                            data-page={`${doc.uuid}-${i}`}
                                            className={i < doc.page_count - 1 ? 'border-b' : ''}
                                        >
                                            <PageCanvas
                                                doc={doc}
                                                pageIndex={i}
                                                fields={fields}
                                                submitters={submitters}
                                                drawingField={drawingField}
                                                selectedAreaKey={selectedAreaKey}
                                                onAreaSelect={(key) => {
                                                    setSelectedAreaKey(key);
                                                    if (key) {
                                                        const [fieldUuid] = key.split(':');
                                                        setSelectedFieldUuid(fieldUuid);
                                                    }
                                                }}
                                                onAreaDelete={handleAreaDelete}
                                                onAreaMove={handleAreaMove}
                                                onAreaResize={handleAreaResize}
                                                onDrawComplete={handleDrawComplete}
                                                onCapture={(dataUrl) => handleCaptureThumbnail(doc.uuid, dataUrl)}
                                            />
                                        </div>
                                    ))}
                                </div>
                            ))}
                        </div>
                    )}
                    {/* Mobile: FAB to open field settings */}
                    {selectedField && (
                        <div className="fixed bottom-4 right-4 z-20 md:hidden">
                            <Button size="sm" className="shadow-lg gap-1.5 rounded-full px-4" onClick={() => setMobileSettingsOpen(true)}>
                                <GitBranch className="h-3.5 w-3.5" />Field settings
                            </Button>
                        </div>
                    )}
                </main>

                {/* Mobile settings backdrop */}
                {mobileSettingsOpen && (
                    <div
                        className="fixed inset-0 bg-overlay/40 z-30 md:hidden"
                        onClick={() => setMobileSettingsOpen(false)}
                    />
                )}

                {/* Right settings panel */}
                <div className={`
                    ${mobileSettingsOpen ? 'fixed bottom-0 inset-x-0 z-40 max-h-[80vh] rounded-t-xl shadow-2xl overflow-hidden flex flex-col' : 'hidden'}
                    md:static md:flex md:max-h-none md:rounded-none md:shadow-none md:overflow-visible md:z-auto
                `}>
                    {mobileSettingsOpen && (
                        <div className="flex items-center justify-between px-4 py-3 bg-background border-b shrink-0 md:hidden">
                            <span className="text-sm font-medium">Field settings</span>
                            <Button variant="ghost" size="sm" onClick={() => setMobileSettingsOpen(false)} className="text-muted-foreground hover:text-foreground">
                                <X className="h-4 w-4" />
                            </Button>
                        </div>
                    )}
                    <FieldSettingsPanel
                        field={selectedField}
                        fields={fields}
                        documents={documents}
                        submitters={submitters}
                        onUpdate={updateField}
                        onClose={() => { setSelectedFieldUuid(null); setMobileSettingsOpen(false); }}
                        onDrawArea={() => selectedField && setDrawingField(selectedField)}
                        onDeleteArea={handleDeleteAreaFromSettings}
                        onCopyToAllPages={handleCopyToAllPages}
                    />
                </div>
            </div>
        </div>
    );
}
