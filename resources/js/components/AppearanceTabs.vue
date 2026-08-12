<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <div
        class="inline-flex gap-0.5 rounded-lg border border-sidebar-border/50 bg-muted p-1"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :title="label"
            :class="[
                'flex items-center justify-center rounded-md p-1.5 transition-colors',
                appearance === value
                    ? 'bg-background text-foreground shadow-xs'
                    : 'text-muted-foreground hover:bg-muted/80 hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="h-3.5 w-3.5" />
        </button>
    </div>
</template>
