import { documentColors, resolvedCanvasColor } from '@/lib/design-tokens';
import type * as React from 'react';
import { useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Check } from 'lucide-react';

function SignatureCanvas({ onSave, onCancel }: { onSave: (dataUrl: string) => void; onCancel: () => void }) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [drawing, setDrawing] = useState(false);
    const [lastPos, setLastPos] = useState<{ x: number; y: number } | null>(null);
    const [hasContent, setHasContent] = useState(false);

    function getPos(e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>, canvas: HTMLCanvasElement) {
        const rect = canvas.getBoundingClientRect();
        const point = 'touches' in e ? (e.touches[0] ?? e.changedTouches[0]) : e;
        const clientX = point.clientX;
        const clientY = point.clientY;
        return {
            x: (clientX - rect.left) * (canvas.width / rect.width),
            y: (clientY - rect.top) * (canvas.height / rect.height),
        };
    }

    function startDraw(e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) {
        e.preventDefault();
        setDrawing(true);
        if (canvasRef.current) setLastPos(getPos(e, canvasRef.current));
    }

    function draw(e: React.MouseEvent<HTMLCanvasElement> | React.TouchEvent<HTMLCanvasElement>) {
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
        <div className="space-y-3">
            <div className="relative border-2 border-dashed rounded-md overflow-hidden ui-document-paper" style={{ touchAction: 'none' }}>
                <canvas
                    ref={canvasRef}
                    width={480} height={140}
                    className="w-full cursor-crosshair block"
                    onMouseDown={startDraw}
                    onMouseMove={draw}
                    onMouseUp={() => setDrawing(false)}
                    onMouseLeave={() => setDrawing(false)}
                    onTouchStart={startDraw}
                    onTouchMove={draw}
                    onTouchEnd={() => setDrawing(false)}
                />
                {!hasContent && (
                    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <p className="text-muted-foreground text-sm">Draw here</p>
                    </div>
                )}
                <div className="absolute bottom-4 left-6 right-6 border-t border-dashed border-border" />
            </div>
            <div className="flex items-center justify-between">
                <Button variant="ghost" size="sm" type="button" className="text-sm text-muted-foreground hover:text-foreground" onClick={clear}>
                    Clear
                </Button>
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={onCancel}>Cancel</Button>
                    <Button size="sm" disabled={!hasContent} onClick={() => canvasRef.current && onSave(canvasRef.current.toDataURL('image/png'))}>
                        <Check className="h-4 w-4 mr-1" />Save
                    </Button>
                </div>
            </div>
        </div>
    );
}

function SignatureSection({ label, value, field, otherEditing, onStartEdit, onCancelEdit }: {
    label: string; value?: string | null; field: 'signature' | 'initials'; otherEditing: boolean;
    onStartEdit?: () => void; onCancelEdit?: () => void;
}) {
    const [editing, setEditing] = useState(false);

    function save(dataUrl: string) {
        router.post('/user/signatures', { [field]: dataUrl }, {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    }

    function remove() {
        router.delete(`/user/signatures/${field}`, { preserveScroll: true });
    }

    function startEdit() {
        setEditing(true);
        onStartEdit?.();
    }

    function cancelEdit() {
        setEditing(false);
        onCancelEdit?.();
    }

    return (
        <section className="rounded-md border bg-card p-6 space-y-4">
            <div className="flex items-center justify-between">
                <h2 className="text-lg font-semibold">{label}</h2>
                {value && !editing && (
                    <Button variant="ghost" size="compact"
                        type="button"
                        onClick={remove}
                        className="text-xs text-muted-foreground hover:text-destructive transition-colors"
                    >
                        Remove
                    </Button>
                )}
            </div>

            {value && !editing && (
                <div className="border rounded-md p-4 ui-document-paper flex items-center justify-center h-24">
                    <img src={value} alt={`Your ${label.toLowerCase()}`} className="max-h-full max-w-full object-contain" />
                </div>
            )}

            {editing ? (
                <SignatureCanvas onSave={save} onCancel={cancelEdit} />
            ) : (
                <Button className="w-full" onClick={startEdit} disabled={otherEditing}>
                    {value ? `Update ${label}` : `Add ${label}`}
                </Button>
            )}
        </section>
    );
}

export default function UserSignatureForm({ userSignature, userInitials }: { userSignature?: string | null; userInitials?: string | null }) {
    const [activeEdit, setActiveEdit] = useState<string | null>(null);

    return (
        <>
            <SignatureSection
                label="Signature"
                value={userSignature}
                field="signature"
                otherEditing={activeEdit === 'initials'}
                onStartEdit={() => setActiveEdit('signature')}
                onCancelEdit={() => setActiveEdit(null)}
            />
            <SignatureSection
                label="Initials"
                value={userInitials}
                field="initials"
                otherEditing={activeEdit === 'signature'}
                onStartEdit={() => setActiveEdit('initials')}
                onCancelEdit={() => setActiveEdit(null)}
            />
        </>
    );
}
