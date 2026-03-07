/**
 * Manual route definitions for Laravel Fortify routes.
 * These routes are auto-registered by Fortify and not discoverable by Wayfinder,
 * so they are defined here instead of being generated into resources/js/routes/.
 */

export const register = (): string => '/register';

register.url = (): string => '/register';

register.form = () => ({
    action: '/register',
    method: 'post' as const,
});
