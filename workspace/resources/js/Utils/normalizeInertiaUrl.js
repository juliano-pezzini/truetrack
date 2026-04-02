export default function normalizeInertiaUrl(url) {
    if (!url) {
        return null;
    }

    if (url.startsWith('/')) {
        return url;
    }

    try {
        const parsedUrl = new URL(url, window.location.origin);
        return `${parsedUrl.pathname}${parsedUrl.search}${parsedUrl.hash}`;
    } catch {
        return url;
    }
}
