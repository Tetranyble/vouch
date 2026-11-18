import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent,
    AlertDialogFooter, AlertDialogHeader, AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
    DropdownMenuSub, DropdownMenuSubTrigger, DropdownMenuSubContent,
} from '@/Components/ui/dropdown-menu';
import {
    Plus, Search, FileText, User, Calendar, Folder, MoreVertical,
    Pencil, Copy, Archive, Share2, FolderOpen, FolderPlus, X,
} from 'lucide-react';
import { useState, useCallback } from 'react';

function NewFolderDialog({ onClose }) {
    const [name, setName] = useState('');
    function submit() {
        if (!name.trim()) return;
        router.post('/template-folders', { name }, {
            preserveScroll: true,
            onSuccess: () => { setName(''); onClose(); },
        });
    }
    return (
        <AlertDialog open onOpenChange={(o) => !o && onClose()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>New Folder</AlertDialogTitle>
                </AlertDialogHeader>
                <Input
                    autoFocus
                    placeholder="Folder name"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && submit()}
                />
                <AlertDialogFooter>
                    <AlertDialogCancel onClick={onClose}>Cancel</AlertDialogCancel>
                    <AlertDialogAction onClick={submit} disabled={!name.trim()}>Create</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

function TemplateCard({ template, folders }) {
    function handleArchive() {
        router.delete(`/templates/${template.id}`, { preserveScroll: true });
    }
    function handleClone() {
        router.post(`/templates/${template.id}/clone`, {}, { preserveScroll: true });
    }
    function handleMove(folderId) {
        router.put(`/templates/${template.id}/move`, { folder_id: folderId }, { preserveScroll: true });
    }

    return (
        <div className="group relative flex rounded-md border bg-card overflow-hidden hover:shadow-md transition-shadow">
            {template.thumbnail_url && (
                <div className="w-20 sm:w-24 shrink-0 bg-muted border-r overflow-hidden">
                    <img src={template.thumbnail_url} alt="" className="w-full h-full object-cover object-top" />
                </div>
            )}
            <div className="flex flex-col justify-between p-4 sm:p-5 flex-1 min-w-0">
                <Link href={`/templates/${template.id}`} className="absolute inset-0" />

                <div className="flex items-start justify-between">
                    <h3 className="font-semibold text-sm sm:text-base leading-tight line-clamp-2 pr-8">
                        {template.name}
                    </h3>
                    {/* Always visible on mobile, hover-only on desktop */}
                    <div className="relative z-10 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-7 w-7 -mr-1 -mt-1">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/builder`} className="flex items-center gap-2">
                                        <Pencil className="h-4 w-4" /> Edit
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={`/templates/${template.id}/share`} className="flex items-center gap-2">
                                        <Share2 className="h-4 w-4" /> Share
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={handleClone} className="flex items-center gap-2">
                                    <Copy className="h-4 w-4" /> Clone
                                </DropdownMenuItem>
                                {folders?.length > 0 && (
                                    <DropdownMenuSub>
                                        <DropdownMenuSubTrigger className="flex items-center gap-2">
                                            <FolderOpen className="h-4 w-4" /> Move to folder
                                        </DropdownMenuSubTrigger>
                                        <DropdownMenuSubContent>
                                            {folders.map((f) => (
                                                <DropdownMenuItem key={f.id} onClick={() => handleMove(f.id)}>
                                                    {f.name}
                                                </DropdownMenuItem>
                                            ))}
                                        </DropdownMenuSubContent>
                                    </DropdownMenuSub>
                                )}
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    className="text-destructive focus:text-destructive"
                                    onClick={handleArchive}
                                >
                                    <Archive className="h-4 w-4 mr-2" /> Archive
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>

                <div className="space-y-1 mt-2">
                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <User className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate">{template.author?.name || template.author?.email}</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Calendar className="h-3.5 w-3.5 shrink-0" />
                        <span className="shrink-0">{new Date(template.created_at).toLocaleDateString()}</span>
                        {template.folder_name && template.folder_name !== 'Default' && (
                            <>
                                <Folder className="h-3.5 w-3.5 ml-1 shrink-0" />
                                <span className="truncate">{template.folder_name}</span>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

function FolderFilter({ folders, activeFolder, onSelect }) {
    if (!folders?.length) return null;
    const nonDefault = folders.filter((f) => f.name !== 'Default');
    if (!nonDefault.length) return null;

    return (
        <div className="overflow-x-auto scrollbar-none -mx-4 px-4 md:mx-0 md:px-0">
            <div className="flex gap-2 mb-5 w-max md:w-auto md:flex-wrap">
                <Button variant="ghost" size="sm"
                    onClick={() => onSelect(null)}
                    className={`px-3 h-8 rounded-md border text-sm transition-colors whitespace-nowrap ${!activeFolder ? 'border-foreground text-foreground' : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'}`}
                >
                    All
                </Button>
                {nonDefault.map((f) => (
                    <Button variant="ghost" size="sm"
                        key={f.id}
                        onClick={() => onSelect(f.id === activeFolder ? null : f.id)}
                        className={`px-3 h-8 rounded-md border text-sm flex items-center gap-1.5 transition-colors whitespace-nowrap ${activeFolder === f.id ? 'border-foreground text-foreground' : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'}`}
                    >
                        <Folder className="h-3.5 w-3.5 shrink-0" />
                        {f.name}
                    </Button>
                ))}
            </div>
        </div>
    );
}

export default function TemplatesIndex({ templates, folders, filters }) {
    const [search, setSearch] = useState(filters.q || '');
    const [activeFolder, setActiveFolder] = useState<number | string | null>(null);
    const [showNewFolder, setShowNewFolder] = useState(false);
    const [mobileSearchOpen, setMobileSearchOpen] = useState(false);

    const handleSearch = useCallback((value) => {
        setSearch(value);
        router.get('/templates', { q: value || undefined }, {
            preserveState: true,
            replace: true,
        });
    }, []);

    function handleFolderSelect(folderId) {
        setActiveFolder(folderId);
        router.get('/templates', { folder_id: folderId || undefined, q: search || undefined }, {
            preserveState: true,
            replace: true,
        });
    }

    const items = templates?.data ?? [];
    const pagination = templates;

    return (
        <AuthenticatedLayout>
            <Head title="Templates" />
            {showNewFolder && <NewFolderDialog onClose={() => setShowNewFolder(false)} />}

            <div className="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-4 md:mb-6 gap-3">
                    <div className="flex items-center gap-2 md:gap-3 min-w-0">
                        <h1 className="ui-page-title truncate">Templates</h1>
                        {pagination?.total > 0 && (
                            <Badge variant="secondary" className="text-sm shrink-0">
                                {pagination.total}
                            </Badge>
                        )}
                    </div>
                    <div className="flex items-center gap-2 shrink-0">
                        {/* Desktop search */}
                        {(items.length > 0 || filters.q) && (
                            <div className="relative hidden sm:block">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    placeholder="Search templates…"
                                    className="pl-9 w-52"
                                    value={search}
                                    onChange={(e) => handleSearch(e.target.value)}
                                />
                            </div>
                        )}
                        {/* Mobile search toggle */}
                        {(items.length > 0 || filters.q) && (
                            <Button
                                variant="ghost"
                                size="icon"
                                className="sm:hidden h-9 w-9"
                                onClick={() => setMobileSearchOpen((o) => !o)}
                            >
                                {mobileSearchOpen ? <X className="h-4 w-4" /> : <Search className="h-4 w-4" />}
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            size="icon"
                            className="h-9 w-9 shrink-0"
                            title="New folder"
                            onClick={() => setShowNewFolder(true)}
                        >
                            <FolderPlus className="h-4 w-4" />
                        </Button>
                        <Link href="/templates/create">
                            <Button variant="outline" className="gap-2 border h-9">
                                <Plus className="h-4 w-4" />
                                <span className="hidden md:block">Create</span>
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Mobile search bar (expands below header) */}
                {mobileSearchOpen && (
                    <div className="sm:hidden mb-4">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                                autoFocus
                                placeholder="Search templates…"
                                className="pl-9"
                                value={search}
                                onChange={(e) => handleSearch(e.target.value)}
                            />
                        </div>
                    </div>
                )}

                <FolderFilter folders={folders} activeFolder={activeFolder} onSelect={handleFolderSelect} />

                {/* Templates grid */}
                {items.length > 0 ? (
                    <>
                        <div className="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                            {items.map((template) => (
                                <TemplateCard key={template.id} template={template} folders={folders} />
                            ))}
                        </div>

                        {pagination?.last_page > 1 && (
                            <div className="flex items-center justify-between mt-6 md:mt-8">
                                <p className="text-sm text-muted-foreground">
                                    {pagination.from}–{pagination.to} of {pagination.total}
                                </p>
                                <div className="flex gap-2">
                                    {pagination.current_page > 1 && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={pagination.prev_page_url}>Previous</Link>
                                        </Button>
                                    )}
                                    {pagination.current_page < pagination.last_page && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={pagination.next_page_url}>Next</Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        <div className="mt-4 text-center">
                            <Link href="/templates-archived" className="text-sm text-muted-foreground hover:text-foreground underline-offset-4 hover:underline">
                                View archived templates
                            </Link>
                        </div>
                    </>
                ) : (
                    <div className="border-2 border-dashed border-muted-foreground/25 rounded-md p-8 sm:p-16 text-center">
                        <FileText className="h-10 w-10 sm:h-12 sm:w-12 mx-auto text-muted-foreground mb-4" />
                        <h3 className="text-lg sm:text-xl font-semibold">No templates yet</h3>
                        <p className="text-muted-foreground mt-1 mb-6 text-sm sm:text-base">
                            Create your first document template to get started
                        </p>
                        <Link href="/templates/create">
                            <Button className="gap-2">
                                <Plus className="h-4 w-4" />Create Template
                            </Button>
                        </Link>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
