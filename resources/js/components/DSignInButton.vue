<script setup lang="ts">
import { computed } from 'vue';
import { useDAuth } from '../composables/useDAuth';

// "Sign in with Dengrweb Accounts". A plain link (full page load), because the sign-in leaves this site.
const props = withDefaults(defineProps<{ href?: string; label?: string; block?: boolean }>(), {
    href: undefined,
    label: undefined,
    block: false,
});

const { loginUrl, providerName } = useDAuth();
const target = computed(() => props.href ?? loginUrl.value ?? '/auth/dauth/redirect');
const text = computed(() => props.label ?? `Sign in with ${providerName.value}`);
</script>

<template>
    <a :href="target" :class="['d-signin', block && 'is-block']">
        <span class="d-signin-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" /><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1" /></svg>
        </span>
        <span>{{ text }}</span>
    </a>
</template>

<style scoped>
.d-signin {
    display: inline-flex;
    height: 2.75rem;
    align-items: center;
    justify-content: center;
    gap: 0.625rem;
    border-radius: 0.75rem;
    background: linear-gradient(90deg, #34d399, #22d3ee);
    padding: 0 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: #022c22;
    text-decoration: none;
    box-shadow: 0 10px 25px -10px rgb(16 185 129 / 0.6);
    transition: filter 0.15s;
}
.d-signin:hover {
    filter: brightness(1.06);
}
.d-signin:focus-visible {
    outline: 2px solid rgb(16 185 129 / 0.6);
    outline-offset: 2px;
}
.d-signin.is-block {
    display: flex;
    width: 100%;
}
.d-signin-mark {
    display: inline-flex;
    width: 1.5rem;
    height: 1.5rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: rgb(255 255 255 / 0.55);
}
.d-signin-mark svg {
    width: 14px;
    height: 14px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
}
</style>
