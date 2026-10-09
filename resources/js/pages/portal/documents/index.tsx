import { Head, Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

type FolderOption = {
    id: number;
    name: string;
};

type ExtensionOption = {
    value: string;
    label: string;
};

type DocumentVersion = {
    id: number;
    version_number: number;
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string | null;
};

type DocumentItem = {
    id: number;
    title: string;
    description: string | null;
    extension: string;
    created_at: string | null;
    updated_at: string | null;
    folder: {
        id: number;
        name: string;
    } | null;
    uploader: {
        name: string;
    } | null;
    current_version: DocumentVersion | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    documents: Paginated<DocumentItem>;
    folders: FolderOption[];
    extensions: ExtensionOption[];
    filters: {
        search: string;
        folder_id: string;
        extension: string;
        sort: string;
        direction: string;
    };
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString();
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function DocumentSearch({
    documents,
    folders,
    extensions,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [folderId, setFolderId] = useState(filters.folder_id);
    const [extension, setExtension] = useState(filters.extension);
    const [sort, setSort] = useState(filters.sort);
    const [direction, setDirection] = useState(filters.direction);

    function applyFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        const params: Record<string, string> = {
            sort,
            direction,
        };

        if (search.trim()) {
            params.search = search.trim();
        }

        if (folderId) {
            params.folder_id = folderId;
        }

        if (extension) {
            params.extension = extension;
        }

        router.get('/portal/documents', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function clearFilters() {
        setSearch('');
        setFolderId('');
        setExtension('');
        setSort('updated_at');
        setDirection('desc');

        router.get(
            '/portal/documents',
            {
                sort: 'updated_at',
                direction: 'desc',
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Documents" />

            <main className="space-y-6 p-6">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <Link
                            href="/portal"
                            className="text-sm text-muted-foreground underline underline-offset-4"
                        >
                            ← All folders
                        </Link>

                        <h1 className="mt-4 text-2xl font-semibold">
                            Documents
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Search, filter, and find documents you can access.
                        </p>
                    </div>
                </header>

                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">
                        Search and filters
                    </h2>

                    <form
                        onSubmit={applyFilters}
                        className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5"
                    >
                        <div className="space-y-1 xl:col-span-2">
                            <label
                                htmlFor="search"
                                className="text-sm font-medium"
                            >
                                Document name
                            </label>

                            <input
                                id="search"
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search title or filename"
                                maxLength={100}
                                className="w-full rounded-lg border px-3 py-2"
                            />
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="folder"
                                className="text-sm font-medium"
                            >
                                Folder
                            </label>

                            <select
                                id="folder"
                                value={folderId}
                                onChange={(event) =>
                                    setFolderId(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            >
                                <option value="">All accessible folders</option>

                                {folders.map((folder) => (
                                    <option
                                        key={folder.id}
                                        value={folder.id}
                                    >
                                        {folder.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="extension"
                                className="text-sm font-medium"
                            >
                                File type
                            </label>

                            <select
                                id="extension"
                                value={extension}
                                onChange={(event) =>
                                    setExtension(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            >
                                <option value="">All file types</option>

                                {extensions.map((item) => (
                                    <option
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="sort"
                                className="text-sm font-medium"
                            >
                                Sort by
                            </label>

                            <select
                                id="sort"
                                value={sort}
                                onChange={(event) =>
                                    setSort(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            >
                                <option value="updated_at">
                                    Last updated
                                </option>
                                <option value="created_at">
                                    Date created
                                </option>
                                <option value="title">
                                    Document name
                                </option>
                            </select>
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="direction"
                                className="text-sm font-medium"
                            >
                                Sort direction
                            </label>

                            <select
                                id="direction"
                                value={direction}
                                onChange={(event) =>
                                    setDirection(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            >
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>

                        <div className="flex flex-wrap items-end gap-2 sm:col-span-2 xl:col-span-5">
                            <button
                                type="submit"
                                className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                            >
                                Apply filters
                            </button>

                            <button
                                type="button"
                                onClick={clearFilters}
                                className="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Clear filters
                            </button>
                        </div>
                    </form>
                </section>

                <section className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold">
                            Search results
                        </h2>

                        <p className="text-sm text-muted-foreground">
                            {documents.total} documents found
                        </p>
                    </div>

                    {documents.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-10 text-center">
                            <h3 className="font-medium">
                                No documents found
                            </h3>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Try changing your search terms or filters.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-3">
                                            Document
                                        </th>
                                        <th className="px-4 py-3">
                                            Folder
                                        </th>
                                        <th className="px-4 py-3">
                                            Type
                                        </th>
                                        <th className="px-4 py-3">
                                            Updated
                                        </th>
                                        <th className="px-4 py-3">
                                            Uploader
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {documents.data.map((document) => (
                                        <tr key={document.id}>
                                            <td className="max-w-xs px-4 py-4">
                                                <p className="break-words font-medium">
                                                    {document.title}
                                                </p>

                                                <p className="mt-1 break-all text-xs text-muted-foreground">
                                                    {document.current_version
                                                        ?.original_name ?? 'No file version'}
                                                </p>

                                                {document.current_version && (
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        Version{' '}
                                                        {document.current_version.version_number}
                                                        {' · '}
                                                        {formatSize(
                                                            document.current_version.size,
                                                        )}
                                                    </p>
                                                )}
                                            </td>

                                            <td className="px-4 py-4">
                                                {document.folder ? (
                                                    <Link
                                                        href={`/portal/folders/${document.folder.id}`}
                                                        className="underline underline-offset-4"
                                                    >
                                                        {document.folder.name}
                                                    </Link>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>

                                            <td className="px-4 py-4">
                                                <span className="rounded-md border px-2 py-1 text-xs uppercase">
                                                    {document.extension}
                                                </span>
                                            </td>

                                            <td className="whitespace-nowrap px-4 py-4">
                                                {formatDate(document.updated_at)}
                                            </td>

                                            <td className="px-4 py-4">
                                                {document.uploader?.name ?? 'Unknown'}
                                            </td>

                                            <td className="whitespace-nowrap px-4 py-4 text-right">
                                                <div className="flex justify-end gap-3">
                                                    <Link
                                                        href={`/portal/folders/${document.folder?.id}`}
                                                        className="underline underline-offset-4"
                                                    >
                                                        Open
                                                    </Link>

                                                    {document.current_version && (
                                                        <a
                                                            href={`/portal/documents/${document.id}/download`}
                                                            className="underline underline-offset-4"
                                                        >
                                                            Download
                                                        </a>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <footer className="flex flex-wrap items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {documents.from ?? 0}–{documents.to ?? 0}
                            {' '}of {documents.total}
                        </p>

                        <div className="flex gap-2">
                            {documents.prev_page_url && (
                                <Link
                                    href={documents.prev_page_url}
                                    className="rounded-lg border px-3 py-2 text-sm"
                                >
                                    Previous
                                </Link>
                            )}

                            {documents.next_page_url && (
                                <Link
                                    href={documents.next_page_url}
                                    className="rounded-lg border px-3 py-2 text-sm"
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    </footer>
                </section>
            </main>
        </>
    );
}
