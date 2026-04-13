import '@testing-library/jest-dom';

// Mock InertiaJS route helper
global.route = (name, params) => {
    const routes = {
        'accounts.index': '/accounts',
        'accounts.create': '/accounts/create',
        'accounts.store': '/accounts',
        'accounts.show': (id) => `/accounts/${id}`,
        'accounts.edit': (id) => `/accounts/${id}/edit`,
        'accounts.update': (id) => `/accounts/${id}`,
        'accounts.destroy': (id) => `/accounts/${id}`,
        'transactions.index': '/transactions',
        'transactions.create': '/transactions/create',
        'transactions.store': '/transactions',
        'transactions.show': (id) => `/transactions/${id}`,
        'transactions.edit': (id) => `/transactions/${id}/edit`,
        'transactions.update': (id) => `/transactions/${id}`,
        'transactions.destroy': (id) => `/transactions/${id}`,
        'transactions.bulk-destroy': '/transactions/bulk-delete',
    };

    if (typeof routes[name] === 'function') {
        return routes[name](params);
    }

    return routes[name] || '';
};
