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
        'accounts.bulk-destroy': '/accounts/bulk-delete',
        'categories.index': '/categories',
        'categories.create': '/categories/create',
        'categories.store': '/categories',
        'categories.show': (id) => `/categories/${id}`,
        'categories.edit': (id) => `/categories/${id}/edit`,
        'categories.update': (id) => `/categories/${id}`,
        'categories.destroy': (id) => `/categories/${id}`,
        'categories.bulk-destroy': '/categories/bulk-delete',
        'tags.index': '/tags',
        'tags.create': '/tags/create',
        'tags.store': '/tags',
        'tags.show': (id) => `/tags/${id}`,
        'tags.edit': (id) => `/tags/${id}/edit`,
        'tags.update': (id) => `/tags/${id}`,
        'tags.destroy': (id) => `/tags/${id}`,
        'tags.bulk-destroy': '/tags/bulk-delete',
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
