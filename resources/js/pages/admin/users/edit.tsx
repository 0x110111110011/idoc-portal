import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type RoleOption = {
    value: 'admin' | 'user';
    label: string;
};

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'user';
    is_active: boolean;
    email_verified_at: string | null;
};

type Props = {
    user: ManagedUser;
    roles: RoleOption[];
};

export default function EditUser({ user, roles }: Props) {
    const form = useForm({
        name: user.name,
        email: user.email,
        role: user.role,
        is_active: user.is_active,
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.put(`/admin/users/${user.id}`, {
            onSuccess: () => {
                form.reset('password', 'password_confirmation');
            },
        });
    }

    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <main className="mx-auto max-w-2xl space-y-6 p-6">
                <header>
                    <Link
                        href="/admin/users"
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Back to users
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Edit user
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Account ID: {user.id}
                    </p>
                </header>

                <form onSubmit={submit} className="space-y-5 rounded-xl border p-6">
                    <div className="space-y-1">
                        <label htmlFor="name" className="text-sm font-medium">
                            Full name
                        </label>

                        <input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            required
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.name && (
                            <p className="text-sm text-red-600">{form.errors.name}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="email" className="text-sm font-medium">
                            Email address
                        </label>

                        <input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                            required
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.email && (
                            <p className="text-sm text-red-600">{form.errors.email}</p>
                        )}

                        <p className="text-xs text-muted-foreground">
                            Changing the address requires email verification again.
                        </p>
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="role" className="text-sm font-medium">
                            Role
                        </label>

                        <select
                            id="role"
                            value={form.data.role}
                            onChange={(e) =>
                                form.setData('role', e.target.value as 'admin' | 'user')
                            }
                            className="w-full rounded-lg border px-3 py-2"
                        >
                            {roles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </select>

                        {form.errors.role && (
                            <p className="text-sm text-red-600">{form.errors.role}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="is_active" className="text-sm font-medium">
                            Account status
                        </label>

                        <select
                            id="is_active"
                            value={form.data.is_active ? '1' : '0'}
                            onChange={(e) =>
                                form.setData('is_active', e.target.value === '1')
                            }
                            className="w-full rounded-lg border px-3 py-2"
                        >
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>

                        {form.errors.is_active && (
                            <p className="text-sm text-red-600">
                                {form.errors.is_active}
                            </p>
                        )}
                    </div>

                    <div className="border-t pt-5">
                        <h2 className="font-medium">Reset password</h2>

                        <p className="mt-1 text-sm text-muted-foreground">
                            Leave both fields blank to keep the current password.
                        </p>
                    </div>

                    <div className="space-y-1">
                        <label htmlFor="password" className="text-sm font-medium">
                            New password
                        </label>

                        <input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            autoComplete="new-password"
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.password && (
                            <p className="text-sm text-red-600">{form.errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label
                            htmlFor="password_confirmation"
                            className="text-sm font-medium"
                        >
                            Confirm new password
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) =>
                                form.setData('password_confirmation', e.target.value)
                            }
                            autoComplete="new-password"
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.password_confirmation && (
                            <p className="text-sm text-red-600">
                                {form.errors.password_confirmation}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href="/admin/users"
                            className="rounded-lg border px-4 py-2 text-sm"
                        >
                            Cancel
                        </Link>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </button>
                    </div>
                </form>
            </main>
        </>
    );
}
