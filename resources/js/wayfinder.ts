export type RouteMethods = 'get' | 'post' | 'put' | 'patch' | 'delete' | 'head';

export type RouteDefinition<TMethod extends RouteMethods | RouteMethods[] = RouteMethods> = {
    url: string;
    method: TMethod extends RouteMethods[] ? TMethod[number] : TMethod;
};

export type RouteFormDefinition<TMethod extends RouteMethods | RouteMethods[] = RouteMethods> = {
    action: string;
    method: TMethod extends RouteMethods[] ? TMethod[number] : TMethod;
};

export type RouteQueryOptions = {
    query?: Record<string, unknown>;
    mergeQuery?: Record<string, unknown>;
};

const encodeQuery = (query: Record<string, unknown> = {}): string => {
    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            return;
        }

        if (Array.isArray(value)) {
            value.forEach((item) => {
                params.append(key, String(item));
            });
            return;
        }

        params.append(key, String(value));
    });

    const queryString = params.toString();
    return queryString ? `?${queryString}` : '';
};

export const queryParams = (options?: RouteQueryOptions): string => {
    if (!options) return '';

    if (options.mergeQuery) {
        return encodeQuery(options.mergeQuery);
    }

    if (options.query) {
        return encodeQuery(options.query);
    }

    return '';
};

export const applyUrlDefaults = (url: string): string => url;
