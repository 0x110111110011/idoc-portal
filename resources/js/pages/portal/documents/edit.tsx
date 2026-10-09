import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

type Props = {
    document: {
        id: number;
        title: string;
        description: string | null;
        extension: string;
        folder: {
            id: number;
            name: string;
        };
        current_version: {
            original_name: string;
            version_number: number;
            size: number;
        } | null;
    };
};

export default function EditDocument({ document }: Props) {
    const form = useForm({
        title: document.title,
        description: document.description ?? '',
    });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        form.put(`/portal/documents/${document.id}`);
    }

    return (
        <>
            <Head title={`Edit ${document.title}`} />

            <main className="mx-auto max-w-2xl space-y-6 p-6">
                <header>
                    <Link
                        href={`/portal/folders/${document.folder.id}`}
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Back to {document.folder.name}
                    </Link>

                    <h1 className="mt-4 text-2xl font-semibold">
                        Edit document
                    </h1>

                    <p className="mt-1 text-sm text-muted-foreground">
                        Updating metadata will not replace the uploaded file.
                    </p>
                </header>

                <section className="rounded-xl border p-4">
                    <p className="text-sm font-medium">
                        Current file
                    </p>

                    <p className="mt-2 break-all text-sm">
                        {document.current_version?.original_name ?? 'No file'}
                    </p>

                    <p className="mt-1 text-xs text-muted-foreground">
                        {document.extension.toUpperCase()}
                        {document.current_version
                            ? ` · Version ${document.current_version.version_number}`
                            : ''}
                    </p>
                </section>

                <form
                    onSubmit={submit}
                    className="space-y-5 rounded-xl border p-6"
                >
                    <div className="space-y-1">
                        <label htmlFor="title" className="text-sm font-medium">
                            Document title
                        </label>

                        <input
                            id="title"
                            value={form.data.title}
                            onChange={(event) =>
                                form.setData('title', event.target.value)
                            }
                            required
                            maxLength={255}
                            className="w-full rounded-lg border px-3 py-2"
                        />

                        {form.errors.title && (
                            <p className="text-sm text-red-600">
                                {form.errors.title}
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
                            href={`/portal/folders/${document.folder.id}`}
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
