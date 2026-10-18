import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DAuthProps, DService } from '../types';

/** Reads `page.props.dauth`, shared by the Laravel package on every Inertia response. */
export function useDAuth() {
    const page = usePage();
    const dauth = computed(() => (page.props as { dauth?: DAuthProps }).dauth ?? null);

    return {
        dauth,
        services: computed<DService[]>(() => dauth.value?.services ?? []),
        loginUrl: computed(() => dauth.value?.loginUrl ?? null),
        logoutUrl: computed(() => dauth.value?.logoutUrl ?? null),
        accountUrl: computed(() => dauth.value?.accountUrl ?? null),
        providerName: computed(() => dauth.value?.name ?? 'Dengrweb Accounts'),
    };
}

/** True when `url` points at the site the user is on now. */
export function isCurrentService(url: string): boolean {
    if (typeof window === 'undefined') return false;
    try {
        return new URL(url, window.location.href).origin === window.location.origin;
    } catch {
        return false;
    }
}
