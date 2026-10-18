<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { isCurrentService, useDAuth } from '../composables/useDAuth';
import { icons } from '../icons';
import type { DService } from '../types';

// Grid button that opens a slide-over listing every D app. Self-contained: no UI kit or Tailwind needed.
// Dark mode follows a `dark` class on <html>, like Tailwind's class strategy.
const props = withDefaults(
    defineProps<{
        /** Overrides the list shared by the Laravel package. */
        services?: DService[];
        title?: string;
        description?: string;
    }>(),
    {
        services: undefined,
        title: 'Services',
        description: 'One account for everything. Pick where you want to go.',
    },
);

const shared = useDAuth();
const list = computed(() => props.services ?? shared.services.value);

const open = ref(false);
const panel = ref<HTMLElement | null>(null);
const trigger = ref<HTMLButtonElement | null>(null);

const tileStyle = (service: DService) => ({ '--d-tile': service.color || '#64748b' });
const initials = (name: string) =>
    name
        .split(/\s+/)
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();

const onKey = (event: KeyboardEvent) => {
    if (event.key === 'Escape') open.value = false;
};

let previousOverflow = '';
watch(open, async (isOpen) => {
    if (typeof document === 'undefined') return;
    if (isOpen) {
        previousOverflow = document.documentElement.style.overflow;
        document.documentElement.style.overflow = 'hidden';
        document.addEventListener('keydown', onKey);
        await nextTick();
        panel.value?.focus();
    } else {
        document.documentElement.style.overflow = previousOverflow;
        document.removeEventListener('keydown', onKey);
        trigger.value?.focus();
    }
});

onBeforeUnmount(() => {
    if (open.value && typeof document !== 'undefined') {
        document.documentElement.style.overflow = previousOverflow;
        document.removeEventListener('keydown', onKey);
    }
});
</script>

<template>
    <button
        ref="trigger"
        type="button"
        class="d-switcher-trigger"
        :data-state="open ? 'open' : 'closed'"
        :aria-expanded="open"
        aria-haspopup="dialog"
        aria-label="Open services"
        @click="open = !open"
    >
        <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" aria-hidden="true" v-html="icons.grid" />
    </button>

    <Teleport to="body">
        <Transition name="d-fade">
            <div v-if="open" class="d-switcher-overlay" @click="open = false" />
        </Transition>
        <Transition name="d-slide">
            <aside v-if="open" ref="panel" class="d-switcher-panel" role="dialog" aria-modal="true" :aria-label="title" tabindex="-1">
                <header class="d-switcher-header">
                    <div>
                        <h2>{{ title }}</h2>
                        <p>{{ description }}</p>
                    </div>
                    <button type="button" class="d-switcher-close" aria-label="Close" @click="open = false">
                        <svg viewBox="0 0 24 24" aria-hidden="true" v-html="icons.close" />
                    </button>
                </header>

                <div class="d-switcher-body">
                    <p v-if="!list.length" class="d-switcher-empty">No services yet.</p>
                    <div v-else class="d-switcher-grid">
                        <a
                            v-for="service in list"
                            :key="service.key || service.url"
                            :href="service.url"
                            :class="['d-switcher-tile', isCurrentService(service.url) && 'is-current']"
                            :aria-current="isCurrentService(service.url) ? 'page' : undefined"
                            :style="tileStyle(service)"
                        >
                            <span class="d-switcher-icon">
                                <img v-if="service.logo" :src="service.logo" alt="" />
                                <svg v-else-if="service.icon && icons[service.icon]" viewBox="0 0 24 24" aria-hidden="true" v-html="icons[service.icon]" />
                                <span v-else class="d-switcher-initials">{{ initials(service.name) }}</span>
                            </span>
                            <span class="d-switcher-name">{{ service.name }}</span>
                            <span v-if="service.description" class="d-switcher-desc">{{ service.description }}</span>
                        </a>
                    </div>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<style scoped>
.d-switcher-trigger {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    border: 1px solid #e2e8f0;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition:
        background-color 0.15s,
        color 0.15s,
        border-color 0.15s;
}
.d-switcher-trigger svg {
    width: 15px;
    height: 15px;
}
.d-switcher-trigger:hover,
.d-switcher-trigger[data-state='open'] {
    border-color: #cbd5e1;
    background: #f1f5f9;
    color: #334155;
}
.d-switcher-trigger:focus-visible {
    outline: 2px solid rgb(16 185 129 / 0.45);
    outline-offset: 1px;
}
:global(.dark) .d-switcher-trigger {
    border-color: #334155;
    color: #94a3b8;
}
:global(.dark) .d-switcher-trigger:hover,
:global(.dark) .d-switcher-trigger[data-state='open'] {
    border-color: #475569;
    background: #1e293b;
    color: #e2e8f0;
}

.d-switcher-overlay {
    position: fixed;
    inset: 0;
    z-index: 60;
    background: rgb(15 23 42 / 0.45);
    backdrop-filter: blur(2px);
}

.d-switcher-panel {
    position: fixed;
    inset: 0 0 0 auto;
    z-index: 61;
    display: flex;
    width: min(100%, 24rem);
    flex-direction: column;
    background: #ffffff;
    color: #0f172a;
    box-shadow: -20px 0 50px rgb(15 23 42 / 0.18);
    font-family: inherit;
    outline: none;
}
:global(.dark) .d-switcher-panel {
    background: #0b1220;
    color: #f1f5f9;
    box-shadow: -20px 0 50px rgb(0 0 0 / 0.5);
}

.d-switcher-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    border-bottom: 1px solid #e2e8f0;
    padding: 1.25rem 1.5rem;
}
:global(.dark) .d-switcher-header {
    border-color: #1e293b;
}
.d-switcher-header h2 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    line-height: 1.5rem;
}
.d-switcher-header p {
    margin: 0.125rem 0 0;
    font-size: 0.75rem;
    color: #64748b;
}
:global(.dark) .d-switcher-header p {
    color: #94a3b8;
}

.d-switcher-close {
    display: inline-flex;
    width: 2rem;
    height: 2rem;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 0.5rem;
    background: transparent;
    color: #64748b;
    cursor: pointer;
}
.d-switcher-close:hover {
    background: #f1f5f9;
    color: #0f172a;
}
:global(.dark) .d-switcher-close:hover {
    background: #1e293b;
    color: #f1f5f9;
}
.d-switcher-close svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
}

.d-switcher-body {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}
.d-switcher-empty {
    padding: 2rem 0;
    text-align: center;
    font-size: 0.875rem;
    color: #64748b;
}

.d-switcher-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}
@media (min-width: 400px) {
    .d-switcher-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

.d-switcher-tile {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid transparent;
    border-radius: 0.75rem;
    padding: 1rem 0.5rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    transition:
        background-color 0.15s,
        border-color 0.15s;
}
.d-switcher-tile:hover {
    border-color: #e2e8f0;
    background: #f8fafc;
}
.d-switcher-tile:focus-visible {
    outline: 2px solid rgb(16 185 129 / 0.45);
    outline-offset: 1px;
}
.d-switcher-tile.is-current {
    border-color: #a7f3d0;
    background: rgb(236 253 245 / 0.7);
}
:global(.dark) .d-switcher-tile:hover {
    border-color: #334155;
    background: rgb(30 41 59 / 0.6);
}
:global(.dark) .d-switcher-tile.is-current {
    border-color: rgb(6 78 59 / 0.7);
    background: rgb(2 44 34 / 0.35);
}

.d-switcher-icon {
    display: inline-flex;
    width: 2.75rem;
    height: 2.75rem;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 0.75rem;
    background: color-mix(in srgb, var(--d-tile) 12%, transparent);
    color: var(--d-tile);
    transition: transform 0.15s;
}
.d-switcher-tile:hover .d-switcher-icon {
    transform: scale(1.05);
}
:global(.dark) .d-switcher-icon {
    background: color-mix(in srgb, var(--d-tile) 22%, transparent);
    color: color-mix(in srgb, var(--d-tile) 55%, white);
}
.d-switcher-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.75;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.d-switcher-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.d-switcher-initials {
    font-size: 0.875rem;
    font-weight: 700;
}

.d-switcher-name {
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
}
:global(.dark) .d-switcher-name {
    color: #e2e8f0;
}
.d-switcher-desc {
    display: -webkit-box;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    font-size: 10px;
    line-height: 1.35;
    color: #64748b;
}
:global(.dark) .d-switcher-desc {
    color: #94a3b8;
}

.d-fade-enter-active,
.d-fade-leave-active {
    transition: opacity 0.25s ease;
}
.d-fade-enter-from,
.d-fade-leave-to {
    opacity: 0;
}
.d-slide-enter-active,
.d-slide-leave-active {
    transition: transform 0.3s cubic-bezier(0.32, 0.72, 0, 1);
}
.d-slide-enter-from,
.d-slide-leave-to {
    transform: translateX(100%);
}
@media (prefers-reduced-motion: reduce) {
    .d-fade-enter-active,
    .d-fade-leave-active,
    .d-slide-enter-active,
    .d-slide-leave-active {
        transition: none;
    }
}
</style>
