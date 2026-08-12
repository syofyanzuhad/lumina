<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        :class="className"
        v-bind="$attrs"
    >
        <defs>
            <linearGradient
                id="lumina-gradient"
                x1="2"
                y1="2"
                x2="22"
                y2="22"
                gradientUnits="userSpaceOnUse"
            >
                <stop offset="0%" stop-color="#6366F1" />
                <stop offset="50%" stop-color="#8B5CF6" />
                <stop offset="100%" stop-color="#EC4899" />
            </linearGradient>
            <linearGradient
                id="lumina-gradient-inner"
                x1="6"
                y1="6"
                x2="18"
                y2="18"
                gradientUnits="userSpaceOnUse"
            >
                <stop offset="0%" stop-color="#A5B4FC" />
                <stop offset="100%" stop-color="#F472B6" />
            </linearGradient>
        </defs>
        <path
            d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z"
            fill="url(#lumina-gradient)"
        />
        <path
            d="M12 6L13.2 10.8L18 12L13.2 13.2L12 18L10.8 13.2L6 12L10.8 10.8L12 6Z"
            fill="url(#lumina-gradient-inner)"
            opacity="0.85"
        />
    </svg>
</template>
