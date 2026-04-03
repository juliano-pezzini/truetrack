export default function normalizeInertiaUrl(url) {
    if (!url) {
        return null;
    }

    const currentOrigin = window.location.origin;

    if (url.startsWith('/')) {
        return url;
    }

    try {
        const parsedUrl = new URL(url, currentOrigin);

        if (parsedUrl.origin !== currentOrigin) {
            return null;
        }

        return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
    } catch {
        return null;
    }
}
