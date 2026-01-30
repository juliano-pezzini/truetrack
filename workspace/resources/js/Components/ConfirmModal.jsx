import Modal from './Modal';

export default function ConfirmModal({
    show = false,
    title = 'Confirm',
    message = 'Are you sure?',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    onConfirm = () => {},
    onCancel = () => {},
    isLoading = false,
    isDangerous = false,
    maxWidth = 'sm',
}) {
    const handleConfirm = () => {
        onConfirm();
    };

    const handleCancel = () => {
        onCancel();
    };

    const confirmButtonClass = isDangerous
        ? 'bg-red-600 hover:bg-red-700 text-white'
        : 'bg-blue-600 hover:bg-blue-700 text-white';

    return (
        <Modal show={show} maxWidth={maxWidth} closeable={!isLoading} onClose={handleCancel}>
            <div className="bg-white px-4 py-5 sm:px-6">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium leading-6 text-gray-900">
                        {title}
                    </h3>
                </div>
                <div className="mt-3">
                    <p className="text-sm text-gray-500">
                        {message}
                    </p>
                </div>
            </div>
            <div className="space-y-4 bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:gap-2 sm:px-6">
                <button
                    onClick={handleConfirm}
                    disabled={isLoading}
                    className={`w-full rounded-md border border-transparent px-4 py-2 text-sm font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 sm:w-auto ${confirmButtonClass} disabled:opacity-50 disabled:cursor-not-allowed`}
                >
                    {confirmText}
                </button>
                <button
                    onClick={handleCancel}
                    disabled={isLoading}
                    className="w-full rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed sm:w-auto"
                >
                    {cancelText}
                </button>
            </div>
        </Modal>
    );
}
