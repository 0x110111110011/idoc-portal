import { Head, Link } from '@inertiajs/react';

type Activity = {
    id: number;
    action: string;
    description: string | null;
    created_at: string | null;
    subject_type: string | null;
    subject_id: number | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
};

type RecentDocument = {
    id: number;
    title: string;
    extension: string;
    created_at: string | null;
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

type Props = {
    stats: {
        totalUsers: number;
        activeUsers: number;
        inactiveUsers: number;
        totalFolders: number;
        totalDocuments: number;
        totalActivities: number;
    };
    recentActivities: Activity[];
    recentDocuments: RecentDocument[];
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

function StatCard({
    label,
    value,
    description,
}: {
    label: string;
    value: number;
    description: string;
}) {
    return (
        <article className="rounded-xl border p-5">
            <p className="text-sm text-muted-foreground">{label}</p>

            <p className="mt-2 text-3xl font-semibold tabular-nums">
                {value.toLocaleString()}
            </p>

            <p className="mt-2 text-xs text-muted-foreground">
                {description}
            </p>
        </article>
    );
}

export default function AdminDashboard({
    stats,
    recentActivities,
    recentDocuments,
}: Props) {
    return (
        <>
            <Head title="Admin Dashboard" />

            <main className="space-y-8 p-6">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Admin Dashboard
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Overview of the internal document portal.
                        </p>
                    </div>

                    <Link
                        href="/portal"
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        Open document portal
                    </Link>
                </header>

                <section>
                    <h2 className="mb-4 text-lg font-semibold">
                        System overview
                    </h2>

                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <StatCard
                            label="Total users"
                            value={stats.totalUsers}
                            description={`${stats.activeUsers} active · ${stats.inactiveUsers} inactive`}
                        />

                        <StatCard
                            label="Total folders"
                            value={stats.totalFolders}
                            description="Folders currently in the portal"
                        />

                        <StatCard
                            label="Documents"
                            value={stats.totalDocuments}
                            description="Non-deleted document records"
                        />

                        <StatCard
                            label="Activity records"
                            value={stats.totalActivities}
                            description="Recorded system actions"
                        />
                    </div>
                </section>

                <section className="grid gap-6 xl:grid-cols-2">
                    <div className="rounded-xl border">
                        <div className="flex items-center justify-between gap-3 border-b p-5">
                            <div>
                                <h2 className="font-semibold">
                                    Recent uploads
                                </h2>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    Latest documents added to the system.
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
                                        <Link
                                            href={
                                                document.folder
                                                    ? `/portal/folders/${document.folder.id}`
                                                    : '/portal/documents'
                                            }
                                            className="break-words font-medium underline-offset-4 hover:underline"
                                        >
                                            {document.title}
                                        </Link>

                                        <p className="mt-1 text-xs text-muted-foreground">
                                            {document.folder?.name ?? 'Unknown folder'}
                                            {' · '}
                                            {document.uploader?.name ?? 'Unknown uploader'}
                                        </p>

                                        {document.current_version && (
                                            <p className="mt-1 break-all text-xs text-muted-foreground">
                                                v{document.current_version.version_number}
                                                {' · '}
                                                {formatSize(document.current_version.size)}
                                            </p>
                                        )}
                                    </div>

                                    <span className="rounded-md border px-2 py-1 text-xs uppercase">
                                        {document.extension}
                                    </span>
                                </article>
                            ))}

                            {recentDocuments.length === 0 && (
                                <p className="p-6 text-sm text-muted-foreground">
                                    No documents have been uploaded yet.
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="rounded-xl border">
                        <div className="flex items-center justify-between gap-3 border-b p-5">
                            <div>
                                <h2 className="font-semibold">
                                    Recent activity
                                </h2>

                                <p className="mt-1 text-sm text-muted-foreground">
                                    Latest recorded administrative and user actions.
                                </p>
                            </div>

                            <Link
                                href="/admin/activity-logs"
                                className="text-sm underline underline-offset-4"
                            >
                                View all
                            </Link>
                        </div>

                        <div className="divide-y">
                            {recentActivities.map((activity) => (
                                <article
                                    key={activity.id}
                                    className="p-4"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <span className="rounded-full border px-2.5 py-1 text-xs">
                                            {activity.action}
                                        </span>

                                        <time className="text-xs text-muted-foreground">
                                            {formatDate(activity.created_at)}
                                        </time>
                                    </div>

                                    <p className="mt-2 text-sm">
                                        {activity.description || activity.action}
                                    </p>

                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {activity.user?.name ?? 'Deleted or unavailable user'}

                                        {activity.subject_type && (
                                            <>
                                                {' · '}
                                                {activity.subject_type}

                                                {activity.subject_id !== null
                                                    ? ` #${activity.subject_id}`
                                                    : ''}
                                            </>
                                        )}
                                    </p>
                                </article>
                            ))}

                            {recentActivities.length === 0 && (
                                <p className="p-6 text-sm text-muted-foreground">
                                    No activity has been recorded yet.
                                </p>
                            )}
                        </div>
                    </div>
                </section>

                <section>
                    <h2 className="mb-4 text-lg font-semibold">
                        Administration
                    </h2>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <Link
                            href="/admin/users"
                            className="rounded-xl border p-5 transition hover:bg-muted/40"
                        >
                            <h3 className="font-semibold">
                                User management
                            </h3>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Create accounts, change roles, and manage account status.
                            </p>
                        </Link>

                        <Link
                            href="/admin/activity-logs"
                            className="rounded-xl border p-5 transition hover:bg-muted/40"
                        >
                            <h3 className="font-semibold">
                                Activity history
                            </h3>

                            <p className="mt-1 text-sm text-muted-foreground">
                                Search and review audit records.
                            </p>
                        </Link>
                    </div>
                </section>
            </main>
        </>
    );
}
