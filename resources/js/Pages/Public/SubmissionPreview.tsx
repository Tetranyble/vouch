import { Head } from '@inertiajs/react';
import { CheckCircle, Clock, XCircle, Download, FileText } from 'lucide-react';

const STATUS_ICON = {
    completed: <CheckCircle className="w-4 h-4 text-success" />,
    declined:  <XCircle className="w-4 h-4 text-error" />,
    pending:   <Clock className="w-4 h-4 text-warning" />,
    awaiting:  <Clock className="w-4 h-4 text-base-content/40" />,
    sent:      <Clock className="w-4 h-4 text-info" />,
    opened:    <Clock className="w-4 h-4 text-info" />,
};

export default function SubmissionPreview({ submission, submitters, documents }) {
    const allDone = submitters.every(s => s.completed_at);

    return (
        <>
            <Head title={submission.name ?? 'Submission Preview'} />

            <div className="min-h-screen bg-base-200 flex flex-col items-center py-12 px-4">
                {/* Header */}
                <div className="w-full max-w-2xl mb-6 text-center">
                    <div className="flex justify-center mb-3">
                        {allDone
                            ? <CheckCircle className="w-12 h-12 text-success" />
                            : <Clock className="w-12 h-12 text-warning" />}
                    </div>
                    <h1 className="text-2xl font-bold">{submission.name}</h1>
                    <p className="text-base-content/60 text-sm mt-1">
                        {allDone ? 'All parties have signed this document.' : 'This document is awaiting signatures.'}
                    </p>
                </div>

                {/* Signers */}
                <div className="w-full max-w-2xl card bg-base-100 border border-base-300 shadow-sm mb-4">
                    <div className="card-body">
                        <h2 className="card-title text-base font-semibold">Signers</h2>
                        <ul className="divide-y divide-base-200">
                            {submitters.map((s) => (
                                <li key={s.id} className="flex items-center gap-3 py-3">
                                    {STATUS_ICON[s.status] ?? STATUS_ICON.pending}
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium truncate">{s.name || s.email || 'Signer'}</p>
                                        {s.email && <p className="text-xs text-base-content/50 truncate">{s.email}</p>}
                                    </div>
                                    <span className={`badge badge-sm ${
                                        s.status === 'completed' ? 'badge-success' :
                                        s.status === 'declined'  ? 'badge-error'   :
                                        'badge-ghost'
                                    }`}>
                                        {s.status}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>

                {/* Documents */}
                {documents.length > 0 && (
                    <div className="w-full max-w-2xl card bg-base-100 border border-base-300 shadow-sm">
                        <div className="card-body">
                            <h2 className="card-title text-base font-semibold">Completed Documents</h2>
                            <ul className="divide-y divide-base-200">
                                {documents.map((doc) => (
                                    <li key={doc.id} className="flex items-center gap-3 py-3">
                                        <FileText className="w-5 h-5 text-primary shrink-0" />
                                        <span className="flex-1 text-sm truncate">{doc.filename}</span>
                                        <a
                                            href={doc.download_url}
                                            className="btn btn-sm btn-ghost gap-1"
                                            download
                                        >
                                            <Download className="w-4 h-4" />
                                            Download
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                )}

                {documents.length === 0 && (
                    <div className="w-full max-w-2xl text-center text-base-content/50 text-sm mt-4">
                        Documents will be available once all parties have signed.
                    </div>
                )}
            </div>
        </>
    );
}
