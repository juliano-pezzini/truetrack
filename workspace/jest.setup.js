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
    };

    if (typeof routes[name] === 'function') {
        return routes[name](params);
    }

    return routes[name] || '';
};
