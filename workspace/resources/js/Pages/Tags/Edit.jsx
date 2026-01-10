import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Edit({ auth, tag }) {
    const { data, setData, put, processing, errors } = useForm({
        name: tag.name || '',
        color: tag.color || '#3B82F6',
    });

    const colorPresets = [
        { name: 'Red', value: '#EF4444' },
        { name: 'Orange', value: '#F97316' },
        { name: 'Amber', value: '#F59E0B' },
        { name: 'Green', value: '#10B981' },
        { name: 'Teal', value: '#14B8A6' },
        { name: 'Blue', value: '#3B82F6' },
        { name: 'Violet', value: '#8B5CF6' },
        { name: 'Pink', value: '#EC4899' },
    ];

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('tags.update', tag.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Tag
                </h2>
            }
        >
            <Head title="Edit Tag" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit}>
                                {/* Tag Name */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="name" value="Tag Name" />
                                    <TextInput
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        className="mt-1 block w-full"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Color */}
                                <div className="mb-4">
                                    <InputLabel htmlFor="color" value="Color" />
                                    
                                    {/* Color Presets */}
                                    <div className="mt-2 flex flex-wrap gap-2 mb-3">
                                        {colorPresets.map((preset) => (
                                            <button
                                                key={preset.value}
                                                type="button"
                                                onClick={() => setData('color', preset.value)}
                                                className={`w-10 h-10 rounded-md border-2 transition-all ${
                                                    data.color === preset.value
                                                        ? 'border-gray-900 scale-110'
                                                        : 'border-gray-300 hover:border-gray-400'
                                                }`}
                                                style={{ backgroundColor: preset.value }}
                                                title={preset.name}
                                            />
                                        ))}
                                    </div>

                                    {/* Custom Color Input */}
                                    <div className="flex items-center gap-3">
                                        <input
                                            id="color"
                                            type="color"
                                            value={data.color}
                                            onChange={(e) => setData('color', e.target.value)}
                                            className="h-10 w-20 rounded border-gray-300 cursor-pointer"
                                        />
                                        <TextInput
                                            type="text"
                                            value={data.color}
                                            onChange={(e) => setData('color', e.target.value)}
                                            className="flex-1"
                                            placeholder="#3B82F6"
                                            pattern="^#[0-9A-Fa-f]{6}$"
                                        />
                                    </div>
                                    <InputError message={errors.color} className="mt-2" />
                                    <p className="mt-1 text-sm text-gray-600">
                                        Choose a preset color or enter a custom hex color code.
                                    </p>
                                </div>

                                {/* Preview */}
                                <div className="mb-6 p-4 bg-gray-50 rounded-lg">
                                    <p className="text-sm font-medium text-gray-700 mb-2">Preview:</p>
                                    <div className="flex items-center gap-2">
                                        <div
                                            className="w-6 h-6 rounded-full"
                                            style={{ backgroundColor: data.color }}
                                        ></div>
                                        <span className="text-sm font-medium text-gray-900">
                                            {data.name || 'Tag Name'}
                                        </span>
                                    </div>
                                </div>

                                {/* Actions */}
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={route('tags.index')}
                                        className="text-sm text-gray-600 hover:text-gray-900"
                                    >
                                        Cancel
                                    </Link>
                                    <PrimaryButton disabled={processing}>
                                        Update Tag
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
