import { useState } from 'react';

export default function FileDropZone({ 
    onFileSelect, 
    acceptedTypes = '.ofx,.qfx,.xlsx,.xls,.csv',
    maxSize = 10, // MB
    disabled = false,
    selectedFile = null
}) {
    const [isDragging, setIsDragging] = useState(false);

    const handleDragOver = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!disabled) {
            setIsDragging(true);
        }
    };

    const handleDragLeave = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setIsDragging(false);

        if (disabled) return;

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    };

    const handleFileInput = (e) => {
        const files = e.target.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    };

    const handleFile = (file) => {
        // Check file size
        const fileSizeMB = file.size / 1024 / 1024;
        if (fileSizeMB > maxSize) {
            alert(`File size exceeds ${maxSize}MB limit`);
            return;
        }

        // Check file type
        const extension = '.' + file.name.split('.').pop().toLowerCase();
        const acceptedArray = acceptedTypes.split(',').map(t => t.trim());
        if (!acceptedArray.includes(extension)) {
            alert(`File type not accepted. Accepted types: ${acceptedTypes}`);
            return;
        }

        onFileSelect(file);
    };

    return (
        <div
            className={`relative rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                isDragging
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-300 bg-gray-50'
            } ${disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer hover:border-indigo-400'}`}
            onDragOver={handleDragOver}
            onDragLeave={handleDragLeave}
            onDrop={handleDrop}
        >
            <input
                type="file"
                accept={acceptedTypes}
                onChange={handleFileInput}
                className="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                disabled={disabled}
            />

            {selectedFile ? (
                <div className="space-y-2">
                    <svg
                        className="mx-auto h-12 w-12 text-green-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div className="text-sm">
                        <p className="font-semibold text-gray-900">{selectedFile.name}</p>
                        <p className="text-gray-500">
                            {(selectedFile.size / 1024).toFixed(2)} KB
                        </p>
                    </div>
                    <p className="text-xs text-gray-500">
                        Drop another file or click to replace
                    </p>
                </div>
            ) : (
                <div className="space-y-2">
                    <svg
                        className="mx-auto h-12 w-12 text-gray-400"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 48 48"
                    >
                        <path
                            d="M8 14a6 6 0 016-6h20a6 6 0 016 6v20a6 6 0 01-6 6H14a6 6 0 01-6-6V14z"
                            strokeWidth={2}
                            strokeLinecap="round"
                            strokeLinejoin="round"
                        />
                        <path
                            d="M24 18v12m-6-6h12"
                            strokeWidth={2}
                            strokeLinecap="round"
                        />
                    </svg>
                    <div className="text-sm">
                        <p className="font-semibold text-gray-900">
                            Drop your file here or click to browse
                        </p>
                        <p className="text-gray-500">
                            Supports: OFX, QFX, XLSX, XLS, CSV (max {maxSize}MB)
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
}
