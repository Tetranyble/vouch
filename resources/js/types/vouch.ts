/** Shared frontend data contracts. Keep in sync with Laravel Inertia resources. */
export interface AuthUser {
    id: number;
    email: string;
    first_name: string | null;
    last_name: string | null;
    role: string;
    account_id: number;
    email_verified_at?: string | null;
}

export interface VouchPageProps extends Record<string, unknown> {
    auth: { user: AuthUser | null };
    flash: { success?: string | null; error?: string | null };
    errors: Record<string, string>;
}

export interface ApiTokenInfo {
    id: number;
    masked: string;
    created_at: string;
    updated_at: string;
}

export interface DocumentInfo {
    uuid: string;
    filename: string;
    storage_path?: string;
    content_type?: string;
    filesize?: number;
    page_count?: number;
    preview_path?: string;
}

export interface TemplateSummary {
    id: number;
    slug: string;
    name: string;
    documents?: DocumentInfo[];
    archived_at?: string | null;
}

export interface SubmissionSummary {
    id: number;
    status: string;
    created_at: string;
    template_id: number;
}
