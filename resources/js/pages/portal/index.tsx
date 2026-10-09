
import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface FolderItem {
    id: number;
    name: string;
    description: string | null;
    documents_count: number;
    created_at: string | null;
    creator: {
        name: string;
    } | null;
    can_edit: boolean;
    can_delete: boolean;
}

interface PaginatedFolders {
    data: FolderItem[];
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface PortalIndexProps {
    folders: PaginatedFolders;
    canCreateFolders?: boolean;
}

export default function PortalIndex({
    folders,
    canCreateFolders = true,
}: PortalIndexProps) {
    const form = useForm({
        name: '',
        description: '',
    });

    function submitFolder(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post('/portal/folders', {
            onSuccess: () => {
                form.reset();
            },
        });
    }

    function deleteFolder(folder: FolderItem) {
        const confirmed = window.confirm(
            `Permanently delete "${folder.name}" and all its documents and historical versions? This cannot be undone.`,
        );

        if (confirmed) {
            router.delete(`/portal/folders/${folder.id}`);
        }
    }

    return (
        <>
            <Head title="My Folders" />

            <main className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Page header and document navigation */}
                <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            My Folders
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Manage your folders and documents.
                        </p>
                    </div>

                    <Link
                        href="/portal/documents"
                        className="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition-colors hover:bg-muted"
                    >
                        Browse all documents
                    </Link>
                </header>

                {/* Create folder form */}
                {canCreateFolders && (
                    <section className="rounded-xl border p-6">
                        <h2 className="mb-4 text-lg font-semibold">
                            Create a folder
                        </h2>

                        <form
                            onSubmit={submitFolder}
                            className="space-y-4"
                        >
                            <div className="space-y-1">
                                <label
                                    htmlFor="folder-name"
                                    className="text-sm font-medium"
                                >
                                    Folder name
                                </label>

                                <input
                                    id="folder-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData(
                                            'name',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border px-3 py-2"
                                    placeholder="Project documents"
                                    required
                                />

                                {form.errors.name && (
                                    <p className="text-sm text-red-600">
                                        {form.errors.name}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <label
                                    htmlFor="folder-description"
                                    className="text-sm font-medium"
                                >
                                    Description
                                </label>

                                <input
                                    id="folder-description"
                                    type="text"
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border px-3 py-2"
                                    placeholder="Optional description"
                                />

                                {form.errors.description && (
                                    <p className="text-sm text-red-600">
                                        {form.errors.description}
                                    </p>
                                )}
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                                >
                                    {form.processing
                                        ? 'Creating...'
                                        : 'Create folder'}
                                </button>
                            </div>
                        </form>
                    </section>
                )}

                {/* Folder listing */}
                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">
                            Folders
                        </h2>

                        <span className="text-sm text-muted-foreground">
                            {folders.total} folders
                        </span>
                    </div>

                    {folders.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-10 text-center">
                            <h3 className="font-medium">
                                No folders yet
                            </h3>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Create a folder or ask its owner to share one
                                with you.
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {folders.data.map((folder) => (
                                <article
                                    key={folder.id}
                                    className="rounded-xl border p-5 transition-colors hover:bg-muted/30"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 className="font-semibold">
                                                {folder.name}
                                            </h3>

                                            <p className="mt-1 text-sm text-muted-foreground">
                                                {folder.description ||
                                                    'No description'}
                                            </p>
                                        </div>

                                        <span
                                            aria-hidden="true"
                                            className="text-xl"
                                        >
                                            📁
                                        </span>
                                    </div>

                                    <div className="mt-5 flex items-center justify-between gap-3 text-sm text-muted-foreground">
                                        <span>
                                            {folder.documents_count} documents
                                        </span>

                                        {folder.creator && (
                                            <span>
                                                {folder.creator.name}
                                            </span>
                                        )}
                                    </div>

                                    {/* Folder actions */}
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        <Link
                                            href={`/portal/folders/${folder.id}`}
                                            className="rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted"
                                        >
                                            Open folder
                                        </Link>

                                        {folder.can_edit && (
                                            <Link
                                                href={`/portal/folders/${folder.id}/edit`}
                                                className="rounded-lg border px-3 py-2 text-sm font-medium transition-colors hover:bg-muted"
                                            >
                                                Edit
                                            </Link>
                                        )}

                                        {folder.can_delete && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    deleteFolder(folder)
                                                }
                                                className="rounded-lg border border-red-300 px-3 py-2 text-sm text-red-600 transition-colors hover:bg-red-50"
                                            >
                                                Delete
                                            </button>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    )}

                    {/* Pagination */}
                    <div className="flex justify-end gap-2">
                        {folders.prev_page_url && (
                            <Link
                                href={folders.prev_page_url}
                                className="rounded-lg border px-3 py-2 text-sm transition-colors hover:bg-muted"
                            >
                                Previous
                            </Link>
                        )}

                        {folders.next_page_url && (
                            <Link
                                href={folders.next_page_url}
                                className="rounded-lg border px-3 py-2 text-sm transition-colors hover:bg-muted"
                            >
                                Next
                            </Link>
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}
