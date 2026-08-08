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
    <div class="inline-flex gap-0.5 rounded-lg bg-muted p-1 border border-sidebar-border/50">
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :title="label"
            :class="[
                'flex items-center justify-center rounded-md p-1.5 transition-colors',
                appearance === value
                    ? 'bg-background text-foreground shadow-xs'
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted/80',
            ]"
        >
            <component :is="Icon" class="h-3.5 w-3.5" />
        </button>
    </div>
</template>
