export default function ImportTabs({ activeTab, onTabChange }) {
    const tabs = [
        { id: 'import', label: 'Import' },
        { id: 'history', label: 'History' },
    ];

    return (
        <div className="rounded-lg bg-white p-2 shadow-sm">
            <div className="flex gap-2" role="tablist" aria-label="Import sections">
                {tabs.map((tab) => {
                    const isActive = activeTab === tab.id;

                    return (
                        <button
                            key={tab.id}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            aria-controls={`panel-${tab.id}`}
                            id={`tab-${tab.id}`}
                            onClick={() => onTabChange(tab.id)}
                            className={`rounded-md px-4 py-2 text-sm font-medium transition-colors ${
                                isActive
                                    ? 'bg-indigo-600 text-white'
                                    : 'text-gray-700 hover:bg-gray-100'
                            }`}
                        >
                            {tab.label}
                        </button>
                    );
                })}
            </div>
        </div>
    );
}
