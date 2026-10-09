import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Folder = {
    id: number;
    name: string;
    description: string | null;
};

type Props = {
    folder: Folder;
};

export default function EditFolder({ folder }: Props) {
    const form = useForm({
        name: folder.name,
        description: folder.description ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.put(`/portal/folders/${folder.id}`);
    }

    return (
        <>
            <Head title={`Edit ${folder.name}`} />

            <main className="mx-auto max-w-2xl space-y-6 p-6">
                <header>
                    <Link
                        href="/portal"
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Back to folders
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Edit folder
                    </h1>
                </header>

                <form
                    onSubmit={submit}
                    className="space-y-5 rounded-xl border p-6"
                >
                    <div className="space-y-1">
                        <label htmlFor="name" className="text-sm font-medium">
                            Folder name
                        </label>

                        <input
                            id="name"
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            required
                            maxLength={255}
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.name && (
                            <p className="text-sm text-red-600">
                                {form.errors.name}
                            </p>
                        )}
                    </div>

                    <div className="space-y-1">
                        <label
                            htmlFor="description"
                            className="text-sm font-medium"
                        >
                            Description
                        </label>

                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                            rows={4}
                            maxLength={5000}
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.description && (
                            <p className="text-sm text-red-600">
                                {form.errors.description}
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end gap-3">
                        <Link
                            href="/portal"
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
