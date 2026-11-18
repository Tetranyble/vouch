import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { useState, useRef } from 'react';
import { Upload, FileCheck, FileX, ShieldCheck, ShieldAlert, X, Loader2 } from 'lucide-react';

export default function VerifySignature() {
    const [files, setFiles] = useState<File[]>([]);
    const [results, setResults] = useState<Array<Record<string, any>>>([]);
    const [loading, setLoading] = useState(false);
    const [dragOver, setDragOver] = useState(false);
    const inputRef = useRef<HTMLInputElement>(null);

    function addFiles(incoming: ArrayLike<File> | null) {
        if (!incoming) return;
        const pdfs = Array.from(incoming).filter(f => f.type === 'application/pdf' || f.name.endsWith('.pdf'));
        setFiles(prev => [...prev, ...pdfs]);
        setResults([]);
    }

    function removeFile(index) {
        setFiles(prev => prev.filter((_, i) => i !== index));
        setResults([]);
    }

    function onDrop(e) {
        e.preventDefault();
        setDragOver(false);
        addFiles(e.dataTransfer.files);
    }

    async function handleVerify() {
        if (!files.length) return;
        setLoading(true);
        setResults([]);

        const formData = new FormData();
        files.forEach(f => formData.append('files[]', f));
        formData.append('_token', document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '');

        try {
            const res = await fetch('/verify-pdf-signature', {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: formData,
            });
            const data = await res.json();
            setResults(Array.isArray(data) ? data : [data]);
        } catch {
            setResults([{ error: 'Request failed. Please try again.' }]);
        } finally {
            setLoading(false);
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title="Verify PDF Signatures" />

            <div className="max-w-3xl mx-auto px-4 py-8 space-y-6">
                <div>
                    <h1 className="ui-page-title">Verify PDF Signatures</h1>
                    <p className="text-sm text-base-content/60 mt-1">
                        Upload signed PDF documents to verify their digital signatures and document integrity.
                    </p>
                </div>

                {/* Drop zone */}
                <div
                    onClick={() => inputRef.current?.click()}
                    onDragOver={e => { e.preventDefault(); setDragOver(true); }}
                    onDragLeave={() => setDragOver(false)}
                    onDrop={onDrop}
                    className={`border-2 border-dashed rounded-md p-10 text-center cursor-pointer transition-colors ${
                        dragOver
                            ? 'border-primary bg-primary/5'
                            : 'border-base-300 hover:border-primary/50'
                    }`}
                >
                    <Upload className="w-8 h-8 mx-auto mb-3 text-base-content/40" />
                    <p className="font-medium">Drop PDF files here or click to browse</p>
                    <p className="text-sm text-base-content/50 mt-1">Only PDF files are accepted</p>
                    <Input
                        ref={inputRef}
                        type="file"
                        accept="application/pdf,.pdf"
                        multiple
                        className="hidden"
                        onChange={e => addFiles(e.target.files)}
                    />
                </div>

                {/* File list */}
                {files.length > 0 && (
                    <ul className="space-y-2">
                        {files.map((f, i) => (
                            <li key={i} className="flex items-center gap-3 bg-base-200 rounded-md px-4 py-2">
                                <FileCheck className="w-4 h-4 text-primary shrink-0" />
                                <span className="flex-1 text-sm truncate">{f.name}</span>
                                <span className="text-xs text-base-content/50">{(f.size / 1024).toFixed(1)} KB</span>
                                <Button variant="ghost" size="sm"
                                    type="button"
                                    onClick={() => removeFile(i)}
                                    className="btn btn-ghost btn-xs btn-circle"
                                >
                                    <X className="w-3 h-3" />
                                </Button>
                            </li>
                        ))}
                    </ul>
                )}

                <Button variant="ghost" size="sm"
                    onClick={handleVerify}
                    disabled={!files.length || loading}
                    className="btn btn-primary w-full"
                >
                    {loading ? (
                        <>
                            <Loader2 className="w-4 h-4 animate-spin" />
                            Verifying…
                        </>
                    ) : (
                        <>
                            <ShieldCheck className="w-4 h-4" />
                            Verify Signatures
                        </>
                    )}
                </Button>

                {/* Results */}
                {results.length > 0 && (
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Results</h2>
                        {results.map((result, i) => (
                            <ResultCard key={i} result={result} />
                        ))}
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function ResultCard({ result }) {
    if (result.error) {
        return (
            <div className="alert alert-error">
                <FileX className="w-5 h-5 shrink-0" />
                <span>{result.error}</span>
            </div>
        );
    }

    const verified = result.checksum_status === 'verified';

    return (
        <div className="card bg-base-100 border border-base-300 shadow-sm">
            <div className="card-body gap-4">
                {result.name && (
                    <h3 className="card-title text-base font-medium truncate">{result.name}</h3>
                )}

                {/* Checksum row */}
                <div className="flex items-center gap-3">
                    {verified ? (
                        <ShieldCheck className="w-5 h-5 text-success shrink-0" />
                    ) : (
                        <ShieldAlert className="w-5 h-5 text-warning shrink-0" />
                    )}
                    <div>
                        <p className="text-sm font-medium">
                            Document integrity:{' '}
                            <span className={verified ? 'text-success' : 'text-warning'}>
                                {verified ? 'Verified' : 'Not found in records'}
                            </span>
                        </p>
                        <p className="text-xs text-base-content/50">
                            SHA-256 checksum {verified ? 'matches a completed document' : 'does not match any known document'}
                        </p>
                    </div>
                </div>

                {/* Signatures */}
                {result.signatures?.length > 0 ? (
                    <div className="space-y-3">
                        <p className="text-sm font-medium text-base-content/70">
                            Digital signatures ({result.signatures.length})
                        </p>
                        {result.signatures.map((sig, j) => (
                            <SignatureRow key={j} sig={sig} />
                        ))}
                    </div>
                ) : (
                    <p className="text-sm text-base-content/50">No embedded digital signatures found.</p>
                )}
            </div>
        </div>
    );
}

function SignatureRow({ sig }) {
    return (
        <div className="bg-base-200 rounded-md px-4 py-3 text-sm space-y-1">
            {sig.name && (
                <p>
                    <span className="font-medium">Signer:</span> {sig.name}
                </p>
            )}
            {sig.signed_at && (
                <p>
                    <span className="font-medium">Signed at:</span>{' '}
                    {new Date(sig.signed_at).toLocaleString()}
                </p>
            )}
            {sig.reason && (
                <p>
                    <span className="font-medium">Reason:</span> {sig.reason}
                </p>
            )}
            {sig.location && (
                <p>
                    <span className="font-medium">Location:</span> {sig.location}
                </p>
            )}
            <p>
                <span className="font-medium">Cryptographic verification:</span>{' '}
                <span className={sig.verified ? 'text-success' : 'text-warning'}>
                    {sig.verified ? 'Valid' : 'Could not verify'}
                </span>
            </p>
        </div>
    );
}
