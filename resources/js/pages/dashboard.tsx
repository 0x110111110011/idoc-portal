import { Head, Link } from '@inertiajs/react';

type FolderItem = {
    id: number;
    name: string;
    description: string | null;
    documents_count: number;
    updated_at: string | null;
};

type RecentDocument = {
    id: number;
    title: string;
    extension: string;
    updated_at: string | null;
    folder: {
        id: number;
        name: string;
    } | null;
    uploader: {
        name: string;
    } | null;
    current_version: {
        version_number: number;
        original_name: string;
        size: number;
    } | null;
};

type Activity = {
    id: number;
    action: string;
    description: string | null;
    created_at: string | null;
};

type Props = {
    stats: {
        accessibleFolders: number;
        accessibleDocuments: number;
    };
    folders: FolderItem[];
    recentDocuments: RecentDocument[];
    recentActivities: Activity[];
};

function formatDate(value: string | null): string {
    return value ? new Date(value).toLocaleString() : '—';
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

export default function Dashboard({
    stats,
    folders,
    recentDocuments,
    recentActivities,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <main className="space-y-8 p-6">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            My Dashboard
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Your folders, accessible documents, and recent activity.
                        </p>
                    </div>

                    <Link
                        href="/portal/documents"
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        Find documents
                    </Link>
                </header>

                <section className="grid gap-4 sm:grid-cols-2">
                    <article className="rounded-xl border p-5">
                        <p className="text-sm text-muted-foreground">
                            Accessible folders
                        </p>

                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {stats.accessibleFolders.toLocaleString()}
                        </p>

                        <Link
                            href="/portal"
                            className="mt-3 inline-block text-sm underline underline-offset-4"
                        >
                            Browse folders
                        </Link>
                    </article>

                    <article className="rounded-xl border p-5">
                        <p className="text-sm text-muted-foreground">
                            Accessible documents
                        </p>

                        <p className="mt-2 text-3xl font-semibold tabular-nums">
                            {stats.accessibleDocuments.toLocaleString()}
                        </p>

                        <Link
                            href="/portal/documents"
                            className="mt-3 inline-block text-sm underline underline-offset-4"
                        >
                            Search documents
                        </Link>
                    </article>
                </section>

                <section className="space-y-4">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <h2 className="text-lg font-semibold">
                                Accessible folders
                            </h2>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Your recently updated folders.
                            </p>
                        </div>

                        <Link
                            href="/portal"
                            className="text-sm underline underline-offset-4"
                        >
                            View all
                        </Link>
                    </div>

                    {folders.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-8 text-center">
                            <h3 className="font-medium">
                                No folders available
                            </h3>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Create a folder if permitted, or ask an owner
                                to share one with you.
                            </p>
                        </div>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {folders.map((folder) => (
                                <article
                                    key={folder.id}
                                    className="rounded-xl border p-5"
                                >
                                    <h3 className="font-semibold">
                                        {folder.name}
                                    </h3>

                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {folder.description || 'No description'}
                                    </p>

                                    <p className="mt-4 text-sm text-muted-foreground">
                                        {folder.documents_count} documents
                                    </p>

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Updated {formatDate(folder.updated_at)}
                                    </p>

                                    <Link
                                        href={`/portal/folders/${folder.id}`}
                                        className="mt-4 inline-block rounded-lg border px-3 py-2 text-sm font-medium"
                                    >
                                        Open folder
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}
                </section>

                <section className="rounded-xl border">
                    <div className="flex items-center justify-between gap-3 border-b p-5">
                        <div>
                            <h2 className="font-semibold">
                                Recently updated documents
                            </h2>

                            <p className="mt-1 text-sm text-muted-foreground">
                                The latest changes within folders you can access.
                            </p>
                        </div>

                        <Link
                            href="/portal/documents"
                            className="text-sm underline underline-offset-4"
                        >
                            Browse all
                        </Link>
                    </div>

                    <div className="divide-y">
                        {recentDocuments.map((document) => (
                            <article
                                key={document.id}
                                className="flex flex-wrap items-start justify-between gap-3 p-4"
                            >
                                <div className="min-w-0">
                                    <p className="break-words font-medium">
                                        {document.title}
                                    </p>

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {document.folder?.name ?? 'Unknown folder'}
                                        {' · '}
                                        {document.extension.toUpperCase()}
                                    </p>

                                    {document.current_version && (
                                        <p className="mt-1 break-all text-xs text-muted-foreground">
                                            v{document.current_version.version_number}
                                            {' · '}
                                            {formatSize(document.current_version.size)}
                                        </p>
                                    )}

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        Updated {formatDate(document.updated_at)}
                                    </p>
                                </div>

                                {document.folder && (
                                    <Link
                                        href={`/portal/folders/${document.folder.id}`}
                                        className="text-sm underline underline-offset-4"
                                    >
                                        Open
                                    </Link>
                                )}
                            </article>
                        ))}

                        {recentDocuments.length === 0 && (
                            <p className="p-6 text-sm text-muted-foreground">
                                No documents are available yet.
                            </p>
                        )}
                    </div>
                </section>

                <section className="rounded-xl border">
                    <div className="border-b p-5">
                        <h2 className="font-semibold">
                            My recent activity
                        </h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Actions performed by your account.
                        </p>
                    </div>

                    <div className="divide-y">
                        {recentActivities.map((activity) => (
                            <article
                                key={activity.id}
                                className="flex flex-wrap items-start justify-between gap-3 p-4"
                            >
                                <div>
                                    <p className="font-medium">
                                        {activity.description || activity.action}
                                    </p>

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {activity.action}
                                    </p>
                                </div>

                                <time className="text-xs text-muted-foreground">
                                    {formatDate(activity.created_at)}
                                </time>
                            </article>
                        ))}

                        {recentActivities.length === 0 && (
                            <p className="p-6 text-sm text-muted-foreground">
                                No activity has been recorded for your account.
                            </p>
                        )}
                    </div>
                </section>
            </main>
        </>
    );
}
