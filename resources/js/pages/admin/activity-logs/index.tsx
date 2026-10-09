import { Head, Link, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';

type UserSummary = {
    id: number;
    name: string;
    email: string;
};

type ActivityLog = {
    id: number;
    action: string;
    description: string | null;
    subject_type: string | null;
    subject_id: number | null;
    properties: Record<string, unknown> | null;
    ip_address: string | null;
    created_at: string | null;
    user: UserSummary | null;
};

type ActionOption = {
    value: string;
    label: string;
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
    logs: Paginated<ActivityLog>;
    actions: ActionOption[];
    filters: {
        search: string;
        action: string;
        date_from: string;
        date_to: string;
    };
};

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString();
}

function getActionLabel(
    action: string,
    actions: ActionOption[],
): string {
    return actions.find((option) => option.value === action)?.label
        ?? action;
}

export default function ActivityLogIndex({
    logs,
    actions,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search);
    const [action, setAction] = useState(filters.action);
    const [dateFrom, setDateFrom] = useState(filters.date_from);
    const [dateTo, setDateTo] = useState(filters.date_to);

    function applyFilters(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (dateFrom && dateTo && dateTo < dateFrom) {
            return;
        }

        const params: Record<string, string> = {};

        if (search.trim()) {
            params.search = search.trim();
        }

        if (action) {
            params.action = action;
        }

        if (dateFrom) {
            params.date_from = dateFrom;
        }

        if (dateTo) {
            params.date_to = dateTo;
        }

        router.get('/admin/activity-logs', params, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    function clearFilters() {
        setSearch('');
        setAction('');
        setDateFrom('');
        setDateTo('');

        router.get(
            '/admin/activity-logs',
            {},
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }

    return (
        <>
            <Head title="Activity History" />

            <main className="space-y-6 p-6">
                <header>
                    <Link
                        href="/admin/dashboard"
                        className="text-sm text-muted-foreground underline underline-offset-4"
                    >
                        ← Admin dashboard
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Activity History
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Review user authentication, document operations,
                        folder changes, and administrative actions.
                    </p>
                </header>

                <section className="rounded-xl border p-5">
                    <h2 className="font-semibold">
                        Search and filters
                    </h2>

                    <form
                        onSubmit={applyFilters}
                        className="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                    >
                        <div className="space-y-1 xl:col-span-2">
                            <label
                                htmlFor="activity-search"
                                className="text-sm font-medium"
                            >
                                Search
                            </label>

                            <input
                                id="activity-search"
                                type="search"
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                maxLength={100}
                                placeholder="Action, description, or user"
                                className="w-full rounded-lg border px-3 py-2"
                            />
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="activity-action"
                                className="text-sm font-medium"
                            >
                                Action
                            </label>

                            <select
                                id="activity-action"
                                value={action}
                                onChange={(event) =>
                                    setAction(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            >
                                <option value="">All actions</option>

                                {actions.map((option) => (
                                    <option
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="date-from"
                                className="text-sm font-medium"
                            >
                                From date
                            </label>

                            <input
                                id="date-from"
                                type="date"
                                value={dateFrom}
                                max={dateTo || undefined}
                                onChange={(event) =>
                                    setDateFrom(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            />
                        </div>

                        <div className="space-y-1">
                            <label
                                htmlFor="date-to"
                                className="text-sm font-medium"
                            >
                                To date
                            </label>

                            <input
                                id="date-to"
                                type="date"
                                value={dateTo}
                                min={dateFrom || undefined}
                                onChange={(event) =>
                                    setDateTo(event.target.value)
                                }
                                className="w-full rounded-lg border px-3 py-2"
                            />
                        </div>

                        <div className="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-4">
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

                        {dateFrom && dateTo && dateTo < dateFrom && (
                            <p className="text-sm text-red-600 md:col-span-2 xl:col-span-4">
                                The end date must be on or after the start date.
                            </p>
                        )}
                    </form>
                </section>

                <section className="space-y-4">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold">
                            Recorded events
                        </h2>

                        <p className="text-sm text-muted-foreground">
                            {logs.total} activities
                        </p>
                    </div>

                    {logs.data.length === 0 ? (
                        <div className="rounded-xl border border-dashed p-10 text-center">
                            <h3 className="font-medium">
                                No activity found
                            </h3>

                            <p className="mt-2 text-sm text-muted-foreground">
                                Try different filters or check back after
                                more actions have been recorded.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-left text-sm">
                                <thead className="border-b bg-muted/40">
                                    <tr>
                                        <th className="px-4 py-3">
                                            Date and time
                                        </th>
                                        <th className="px-4 py-3">
                                            User
                                        </th>
                                        <th className="px-4 py-3">
                                            Action
                                        </th>
                                        <th className="px-4 py-3">
                                            Subject
                                        </th>
                                        <th className="px-4 py-3">
                                            IP address
                                        </th>
                                        <th className="px-4 py-3">
                                            Details
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y">
                                    {logs.data.map((log) => (
                                        <tr key={log.id}>
                                            <td className="whitespace-nowrap px-4 py-4">
                                                {formatDate(log.created_at)}
                                            </td>

                                            <td className="px-4 py-4">
                                                {log.user ? (
                                                    <>
                                                        <p className="font-medium">
                                                            {log.user.name}
                                                        </p>

                                                        <p className="text-xs text-muted-foreground">
                                                            {log.user.email}
                                                        </p>
                                                    </>
                                                ) : (
                                                    <span className="text-muted-foreground">
                                                        Deleted or unavailable user
                                                    </span>
                                                )}
                                            </td>

                                            <td className="px-4 py-4">
                                                <span className="inline-flex rounded-full border px-2.5 py-1 text-xs">
                                                    {getActionLabel(
                                                        log.action,
                                                        actions,
                                                    )}
                                                </span>
                                            </td>

                                            <td className="px-4 py-4">
                                                {log.subject_type ? (
                                                    <span>
                                                        {log.subject_type}
                                                        {log.subject_id !== null
                                                            ? ` #${log.subject_id}`
                                                            : ''}
                                                    </span>
                                                ) : (
                                                    '—'
                                                )}
                                            </td>

                                            <td className="whitespace-nowrap px-4 py-4 text-xs text-muted-foreground">
                                                {log.ip_address || '—'}
                                            </td>

                                            <td className="max-w-sm px-4 py-4">
                                                <p className="break-words">
                                                    {log.description || '—'}
                                                </p>

                                                {log.properties && (
                                                    <details className="mt-2">
                                                        <summary className="cursor-pointer text-xs underline underline-offset-4">
                                                            View metadata
                                                        </summary>

                                                        <pre className="mt-2 max-w-sm overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-muted p-3 text-xs">
                                                            {JSON.stringify(
                                                                log.properties,
                                                                null,
                                                                2,
                                                            )}
                                                        </pre>
                                                    </details>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                    <footer className="flex flex-wrap items-center justify-between gap-4">
                        <p className="text-sm text-muted-foreground">
                            Showing {logs.from ?? 0}–{logs.to ?? 0}
                            {' '}of {logs.total}
                        </p>

                        <div className="flex gap-2">
                            {logs.prev_page_url && (
                                <Link
                                    href={logs.prev_page_url}
                                    className="rounded-lg border px-3 py-2 text-sm"
                                >
                                    Previous
                                </Link>
                            )}

                            {logs.next_page_url && (
                                <Link
                                    href={logs.next_page_url}
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
