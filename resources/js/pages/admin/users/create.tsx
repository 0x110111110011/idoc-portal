import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type RoleOption = {
    value: 'admin' | 'user';
    label: string;
};

type Props = {
    roles: RoleOption[];
};

export default function CreateUser({ roles }: Props) {
    const form = useForm({
        name: '',
        email: '',
        role: 'user' as 'admin' | 'user',
        is_active: true,
        password: '',
        password_confirmation: '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.post('/admin/users');
    }

    return (
        <>
            <Head title="Create User" />

            <main className="mx-auto max-w-2xl space-y-6 p-6">
                <header>
                    <Link
                        href="/admin/users"
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Back to users
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Create user
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Create a portal account and assign its permissions.
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
                            maxLength={255}
                            autoComplete="name"
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
                            autoComplete="email"
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.email && (
                            <p className="text-sm text-red-600">{form.errors.email}</p>
                        )}
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
                        <label htmlFor="password" className="text-sm font-medium">
                            Initial password
                        </label>

                        <input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            required
                            autoComplete="new-password"
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        <p className="text-xs text-muted-foreground">
                            Use at least 12 characters with uppercase, lowercase,
                            numbers, and symbols.
                        </p>

                        {form.errors.password && (
                            <p className="text-sm text-red-600">{form.errors.password}</p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label
                            htmlFor="password_confirmation"
                            className="text-sm font-medium"
                        >
                            Confirm password
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(e) =>
                                form.setData('password_confirmation', e.target.value)
                            }
                            required
                            autoComplete="new-password"
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.password_confirmation && (
                            <p className="text-sm text-red-600">
                                {form.errors.password_confirmation}
                            </p>
                        )}
                    </div>

                    <div className="flex items-center gap-3">
                        <input
                            id="is_active"
                            type="checkbox"
                            checked={form.data.is_active}
                            onChange={(e) =>
                                form.setData('is_active', e.target.checked)
                            }
                            className="size-4"
                        />

                        <label htmlFor="is_active" className="text-sm">
                            Account is active
                        </label>
                    </div>

                    {form.errors.is_active && (
                        <p className="text-sm text-red-600">
                            {form.errors.is_active}
                        </p>
                    )}

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
                            {form.processing ? 'Creating…' : 'Create user'}
                        </button>
                    </div>
                </form>
            </main>
        </>
    );
}
