import { useRef } from 'react';

export default function ImportTabs({ activeTab, onTabChange }) {
    const tabs = [
        { id: 'import', label: 'Import' },
        { id: 'history', label: 'History' },
    ];
    const tabRefs = useRef([]);

    const handleKeyDown = (event, index) => {
        const { key } = event;
        let newIndex = index;

        if (key === 'ArrowRight') {
            newIndex = (index + 1) % tabs.length;
        } else if (key === 'ArrowLeft') {
            newIndex = (index - 1 + tabs.length) % tabs.length;
        } else if (key === 'Home') {
            newIndex = 0;
        } else if (key === 'End') {
            newIndex = tabs.length - 1;
        } else {
            return;
        }

        event.preventDefault();
        const newTabId = tabs[newIndex].id;
        onTabChange(newTabId);
        const newTab = tabRefs.current[newIndex];
        if (newTab && typeof newTab.focus === 'function') {
            newTab.focus();
        }
    };

    return (
        <div className="rounded-lg bg-white p-2 shadow-sm">
            <div className="flex gap-2" role="tablist" aria-label="Import sections">
                {tabs.map((tab, index) => {
                    const isActive = activeTab === tab.id;

                    return (
                        <button
                            key={tab.id}
                            type="button"
                            role="tab"
                            aria-selected={isActive}
                            aria-controls={`panel-${tab.id}`}
                            id={`tab-${tab.id}`}
                            tabIndex={isActive ? 0 : -1}
                            onClick={() => onTabChange(tab.id)}
                            onKeyDown={(event) => handleKeyDown(event, index)}
                            ref={(element) => {
                                tabRefs.current[index] = element;
                            }}
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
