import { Head, Link } from '@inertiajs/react';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'user';
    is_active: boolean;
    email_verified_at: string | null;
    created_at: string | null;
};

type PageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: ManagedUser[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Props = {
    users: PaginatedUsers;
    filters: {
        search: string;
        role: string;
        status: string;
    };
    flash: {
        success?: string | null;
        warning?: string | null;
    };
};

function StatusBadge({ active }: { active: boolean }) {
    return (
        <span
            className={`inline-flex rounded-full px-2.5 py-1 text-xs font-medium ${active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-600'
                }`}
        >
            {active ? 'Active' : 'Inactive'}
        </span>
    );
}

export default function UserIndex({
    users,
    filters,
    flash,
}: Props) {
    return (
        <>
            <Head title="User Management" />

            <main className="space-y-6 p-6">
                <header className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">
                            User Management
                        </h1>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Create accounts and manage access to the portal.
                        </p>
                    </div>

                    <Link
                        href="/admin/users/create"
                        className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                    >
                        Create user
                    </Link>
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

                <form
                    action="/admin/users"
                    method="GET"
                    className="flex flex-wrap items-end gap-3"
                >
                    <div className="min-w-56 flex-1 space-y-1">
                        <label htmlFor="search" className="text-sm font-medium">
                            Search
                        </label>

                        <input
                            id="search"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Name or email"
                            className="w-full rounded-lg border px-3 py-2"
                        />
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="role" className="text-sm font-medium">
                            Role
                        </label>

                        <select
                            id="role"
                            name="role"
                            defaultValue={filters.role}
                            className="rounded-lg border px-3 py-2"
                        >
                            <option value="">All roles</option>
                            <option value="admin">Administrator</option>
                            <option value="user">User</option>
                        </select>
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="status" className="text-sm font-medium">
                            Status
                        </label>

                        <select
                            id="status"
                            name="status"
                            defaultValue={filters.status}
                            className="rounded-lg border px-3 py-2"
                        >
                            <option value="">All statuses</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button
                        type="submit"
                        className="rounded-lg border px-4 py-2 text-sm font-medium"
                    >
                        Apply filters
                    </button>

                    <Link
                        href="/admin/users"
                        className="rounded-lg px-3 py-2 text-sm text-muted-foreground"
                    >
                        Clear
                    </Link>
                </form>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-left text-sm">
                        <thead className="border-b bg-muted/40">
                            <tr>
                                <th className="px-4 py-3">User</th>
                                <th className="px-4 py-3">Role</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Email verified</th>
                                <th className="px-4 py-3">Created</th>
                                <th className="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody className="divide-y">
                            {users.data.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {user.name}
                                        </div>

                                        <div className="text-muted-foreground">
                                            {user.email}
                                        </div>
                                    </td>

                                    <td className="px-4 py-3">
                                        {user.role === 'admin'
                                            ? 'Administrator'
                                            : 'User'}
                                    </td>

                                    <td className="px-4 py-3">
                                        <StatusBadge active={user.is_active} />
                                    </td>

                                    <td className="px-4 py-3">
                                        {user.email_verified_at
                                            ? 'Verified'
                                            : 'Pending'}
                                    </td>

                                    <td className="px-4 py-3">
                                        {user.created_at
                                            ? new Date(user.created_at).toLocaleDateString()
                                            : '—'}
                                    </td>

                                    <td className="px-4 py-3 text-right">
                                        <Link
                                            href={`/admin/users/${user.id}/edit`}
                                            className="font-medium underline underline-offset-4"
                                        >
                                            Edit
                                        </Link>
                                    </td>
                                </tr>
                            ))}

                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-12 text-center text-muted-foreground"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <footer className="flex flex-wrap items-center justify-between gap-4">
                    <p className="text-sm text-muted-foreground">
                        {users.total} users · Page {users.current_page} of {users.last_page}
                    </p>

                    <div className="flex gap-2">
                        {users.prev_page_url && (
                            <Link
                                href={users.prev_page_url}
                                className="rounded-lg border px-3 py-2 text-sm"
                            >
                                Previous
                            </Link>
                        )}

                        {users.next_page_url && (
                            <Link
                                href={users.next_page_url}
                                className="rounded-lg border px-3 py-2 text-sm"
                            >
                                Next
                            </Link>
                        )}
                    </div>
                </footer>
            </main>
        </>
    );
}
