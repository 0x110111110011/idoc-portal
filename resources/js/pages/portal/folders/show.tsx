import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Folder = {
    id: number;
    name: string;
    description: string | null;
    creator: { name: string } | null;
};

type Version = {
    id: number;
    version_number: number;
    original_name: string;
    mime_type: string;
    size: number;
    created_at: string | null;
    uploader: { name: string } | null;
};

type DocumentItem = {
    id: number;
    title: string;
    description: string | null;
    extension: string;
    created_at: string | null;
    updated_at: string | null;
    uploader: { name: string } | null;
    current_version: Version | null;
    versions: Version[];
};

type SharedUser = {
    id: number;
    name: string;
    email: string;
    access: 'view' | 'edit';
};

type AvailableUser = {
    id: number;
    name: string;
    email: string;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    folder: Folder;
    documents: Paginated<DocumentItem>;
    sharedUsers: SharedUser[];
    availableUsers: AvailableUser[];
    permissions: {
        canEdit: boolean;
        canManageAccess: boolean;
    };
    flash: {
        success?: string | null;
        warning?: string | null;
    };
};

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${(bytes / 1024).toFixed(1)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(value: string | null): string {
    return value
        ? new Date(value).toLocaleString()
        : '—';
}

function ShareUserRow({
    folderId,
    user,
}: {
    folderId: number;
    user: SharedUser;
}) {
    function removeShare() {
        if (!window.confirm(`Remove ${user.email}'s folder access?`)) {
            return;
        }

        router.delete(
            `/portal/folders/${folderId}/shares/${user.id}`,
            { preserveScroll: true },
        );
    }

    return (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b py-3 last:border-b-0">
            <div>
                <p className="font-medium">{user.name}</p>
                <p className="text-sm text-muted-foreground">
                    {user.email}
                </p>
            </div>

            <div className="flex items-center gap-3">
                <span className="rounded-full border px-3 py-1 text-xs">
                    {user.access === 'edit' ? 'Can edit' : 'Can view'}
                </span>

                <button
                    type="button"
                    onClick={removeShare}
                    className="text-sm text-red-600 underline underline-offset-4"
                >
                    Remove
                </button>
            </div>
        </div>
    );
}

function DocumentRow({
    folderId,
    document,
    canEdit,
}: {
    folderId: number;
    document: DocumentItem;
    canEdit: boolean;
}) {
    const form = useForm<{ file: File | null }>({
        file: null,
    });

    function submitVersion(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.post(`/portal/documents/${document.id}/versions`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    const current = document.current_version;

    const canPreview =
        current?.mime_type === 'application/pdf' ||
        current?.mime_type === 'image/jpeg' ||
        current?.mime_type === 'image/png';

    return (
        <article className="space-y-4 rounded-xl border p-5">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="min-w-0">
                    <h3 className="break-words font-semibold">
                        {document.title}
                    </h3>

                    <p className="mt-1 text-sm text-muted-foreground">
                        {document.description || 'No description'}
                    </p>

                    <p className="mt-2 text-xs text-muted-foreground">
                        Uploaded by {document.uploader?.name || 'Unknown'} ·
                        {' '}Updated {formatDate(document.updated_at)}
                    </p>
                </div>

                <span className="rounded-md border px-2 py-1 text-xs uppercase">
                    {document.extension}
                </span>
            </div>

            {current ? (
                <div className="rounded-lg bg-muted/40 p-4">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="min-w-0">
                            <p className="break-all text-sm font-medium">
                                {current.original_name}
                            </p>

                            <p className="mt-1 text-xs text-muted-foreground">
                                Version {current.version_number} ·
                                {' '}{formatSize(current.size)} ·
                                {' '}{current.mime_type}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {canEdit && (
                                <Link
                                    href={`/portal/documents/${document.id}/edit`}
                                    className="text-sm font-medium underline underline-offset-4"
                                >
                                    Edit details
                                </Link>
                            )}

                            {canPreview && (
                                <a
                                    href={`/portal/documents/${document.id}/preview`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="text-sm font-medium underline underline-offset-4"
                                >
                                    Preview
                                </a>
                            )}

                            <a
                                href={`/portal/documents/${document.id}/download`}
                                className="text-sm font-medium underline underline-offset-4"
                            >
                                Download
                            </a>

                            {canEdit && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        const confirmed = window.confirm(
                                            `Delete "${document.title}"? The document will be soft-deleted.`,
                                        );

                                        if (confirmed) {
                                            router.delete(
                                                `/portal/documents/${document.id}`,
                                                { preserveScroll: true },
                                            );
                                        }
                                    }}
                                    className="text-sm text-red-600 underline underline-offset-4"
                                >
                                    Delete
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            ) : (
                <p className="text-sm text-red-600">
                    No current file version is available.
                </p>
            )}

            <details className="rounded-lg border p-3">
                <summary className="cursor-pointer text-sm font-medium">
                    Version history ({document.versions.length})
                </summary>

                <div className="mt-3 divide-y">
                    {document.versions.map((version) => (
                        <div
                            key={version.id}
                            className="flex flex-wrap items-center justify-between gap-3 py-3"
                        >
                            <div className="min-w-0">
                                <p className="break-all text-sm">
                                    v{version.version_number} ·
                                    {' '}{version.original_name}
                                </p>

                                <p className="mt-1 text-xs text-muted-foreground">
                                    {formatSize(version.size)} ·
                                    {' '}{formatDate(version.created_at)} ·
                                    {' '}{version.uploader?.name || 'Unknown'}
                                </p>
                            </div>

                            <a
                                href={`/portal/documents/${document.id}/versions/${version.id}/download`}
                                className="text-sm underline underline-offset-4"
                            >
                                Download
                            </a>
                        </div>
                    ))}

                    {document.versions.length === 0 && (
                        <p className="py-3 text-sm text-muted-foreground">
                            No version history available.
                        </p>
                    )}
                </div>
            </details>

            {canEdit && (
                <form
                    onSubmit={submitVersion}
                    className="flex flex-wrap items-end gap-3 border-t pt-4"
                >
                    <div className="min-w-56 flex-1 space-y-1">
                        <label className="text-sm font-medium">
                            Upload a new version
                        </label>

                        <input
                            type="file"
                            required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.png,.jpg,.jpeg"
                            onChange={(event) =>
                                form.setData(
                                    'file',
                                    event.currentTarget.files?.[0] ?? null,
                                )
                            }
                            className="block w-full text-sm"
                        />

                        <p className="text-xs text-muted-foreground">
                            Maximum 50 MB.
                        </p>

                        {form.errors.file && (
                            <p className="text-sm text-red-600">
                                {form.errors.file}
                            </p>
                        )}

                        {form.progress && (
                            <progress
                                value={form.progress.percentage}
                                max={100}
                                className="mt-2 w-full"
                            />
                        )}
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing || !form.data.file}
                        className="rounded-lg border px-4 py-2 text-sm font-medium disabled:opacity-50"
                    >
                        {form.processing ? 'Uploading…' : 'Upload version'}
                    </button>
                </form>
            )}
        </article>
    );
}

export default function FolderShow({
    folder,
    documents,
    sharedUsers,
    availableUsers,
    permissions,
    flash,
}: Props) {
    const uploadForm = useForm<{
        title: string;
        description: string;
        file: File | null;
    }>({
        title: '',
        description: '',
        file: null,
    });

    const shareForm = useForm<{
        user_id: string;
        access: 'view' | 'edit';
    }>({
        user_id: '',
        access: 'view',
    });

    function uploadDocument(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        uploadForm.post(`/portal/folders/${folder.id}/documents`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => uploadForm.reset(),
        });
    }

    function shareFolder(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        shareForm.post(`/portal/folders/${folder.id}/shares`, {
            preserveScroll: true,
            onSuccess: () => shareForm.reset(),
        });
    }

    return (
        <>
            <Head title={folder.name} />

            <main className="space-y-6 p-6">
                <header>
                    <Link
                        href="/portal"
                        className="text-sm text-muted-foreground underline underline-offset-4"
                    >
                        ← All folders
                    </Link>

                    <div className="mt-4">
                        <h1 className="text-2xl font-semibold">
                            {folder.name}
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            {folder.description || 'No folder description'}
                        </p>
                    </div>
                </header>

                {flash.success && (
                    <div className="rounded-lg border border-green-300 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                {flash.warning && (
                    <div className="rounded-lg border border-yellow-300 p-3 text-sm text-yellow-800">
                        {flash.warning}
                    </div>
                )}

                {permissions.canEdit && (
                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Upload a document</h2>

                        <form
                            onSubmit={uploadDocument}
                            className="mt-4 grid gap-4 md:grid-cols-2"
                        >
                            <div className="space-y-1">
                                <label
                                    htmlFor="document-title"
                                    className="text-sm font-medium"
                                >
                                    Document title
                                </label>

                                <input
                                    id="document-title"
                                    required
                                    maxLength={255}
                                    value={uploadForm.data.title}
                                    onChange={(event) =>
                                        uploadForm.setData(
                                            'title',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border px-3 py-2"
                                />

                                {uploadForm.errors.title && (
                                    <p className="text-sm text-red-600">
                                        {uploadForm.errors.title}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <label
                                    htmlFor="document-description"
                                    className="text-sm font-medium"
                                >
                                    Description
                                </label>

                                <input
                                    id="document-description"
                                    value={uploadForm.data.description}
                                    onChange={(event) =>
                                        uploadForm.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border px-3 py-2"
                                />

                                {uploadForm.errors.description && (
                                    <p className="text-sm text-red-600">
                                        {uploadForm.errors.description}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1 md:col-span-2">
                                <label
                                    htmlFor="document-file"
                                    className="text-sm font-medium"
                                >
                                    File
                                </label>

                                <input
                                    id="document-file"
                                    type="file"
                                    required
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.png,.jpg,.jpeg"
                                    onChange={(event) =>
                                        uploadForm.setData(
                                            'file',
                                            event.currentTarget.files?.[0] ?? null,
                                        )
                                    }
                                    className="block w-full text-sm"
                                />

                                <p className="text-xs text-muted-foreground">
                                    Allowed documents and images, maximum 50 MB.
                                </p>

                                {uploadForm.errors.file && (
                                    <p className="text-sm text-red-600">
                                        {uploadForm.errors.file}
                                    </p>
                                )}

                                {uploadForm.progress && (
                                    <progress
                                        value={uploadForm.progress.percentage}
                                        max={100}
                                        className="mt-2 w-full"
                                    />
                                )}
                            </div>

                            <div>
                                <button
                                    type="submit"
                                    disabled={
                                        uploadForm.processing ||
                                        !uploadForm.data.file
                                    }
                                    className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                                >
                                    {uploadForm.processing
                                        ? 'Uploading…'
                                        : 'Upload document'}
                                </button>
                            </div>
                        </form>
                    </section>
                )}

                {permissions.canManageAccess && (
                    <section className="rounded-xl border p-5">
                        <h2 className="font-semibold">Share this folder</h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            View access allows reading and downloading.
                            Edit access also allows uploads and new versions.
                        </p>

                        <form
                            onSubmit={shareFolder}
                            className="mt-4 flex flex-wrap items-end gap-3"
                        >
                            <div className="min-w-56 flex-1 space-y-1">
                                <label
                                    htmlFor="share-user"
                                    className="text-sm font-medium"
                                >
                                    User
                                </label>

                                <select
                                    id="share-user"
                                    required
                                    value={shareForm.data.user_id}
                                    onChange={(event) =>
                                        shareForm.setData(
                                            'user_id',
                                            event.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border px-3 py-2"
                                >
                                    <option value="">Select a user</option>

                                    {availableUsers.map((user) => (
                                        <option
                                            key={user.id}
                                            value={user.id}
                                        >
                                            {user.name} — {user.email}
                                        </option>
                                    ))}
                                </select>

                                {shareForm.errors.user_id && (
                                    <p className="text-sm text-red-600">
                                        {shareForm.errors.user_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1">
                                <label
                                    htmlFor="share-access"
                                    className="text-sm font-medium"
                                >
                                    Permission
                                </label>

                                <select
                                    id="share-access"
                                    value={shareForm.data.access}
                                    onChange={(event) =>
                                        shareForm.setData(
                                            'access',
                                            event.target.value as 'view' | 'edit',
                                        )
                                    }
                                    className="rounded-lg border px-3 py-2"
                                >
                                    <option value="view">View</option>
                                    <option value="edit">Edit</option>
                                </select>
                            </div>

                            <button
                                type="submit"
                                disabled={
                                    shareForm.processing ||
                                    !shareForm.data.user_id
                                }
                                className="rounded-lg border px-4 py-2 text-sm font-medium disabled:opacity-50"
                            >
                                {shareForm.processing
                                    ? 'Saving…'
                                    : 'Save access'}
                            </button>
                        </form>

                        {availableUsers.length === 0 && (
                            <p className="mt-3 text-sm text-muted-foreground">
                                No active regular users are available to share with.
                            </p>
                        )}

                        <div className="mt-5">
                            <h3 className="text-sm font-medium">
                                Current access
                            </h3>

                            {sharedUsers.length === 0 ? (
                                <p className="mt-2 text-sm text-muted-foreground">
                                    This folder has not been shared with other users.
                                </p>
                            ) : (
                                <div className="mt-2">
                                    {sharedUsers.map((user) => (
                                        <ShareUserRow
                                            key={user.id}
                                            folderId={folder.id}
                                            user={user}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </section>
                )}

                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Documents</h2>

                        <span className="text-sm text-muted-foreground">
                            {documents.total} documents
                        </span>
                    </div>

                    {documents.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-10 text-center">
                            <h3 className="font-medium">No documents yet</h3>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Upload your first document to this folder.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {documents.data.map((document) => (
                                <DocumentRow
                                    key={document.id}
                                    folderId={folder.id}
                                    document={document}
                                    canEdit={permissions.canEdit}
                                />
                            ))}
                        </div>
                    )}

                    <div className="flex justify-end gap-2">
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
                </section>
            </main>
        </>
    );
}
