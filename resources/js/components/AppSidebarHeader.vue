<script setup lang="ts">
import { Plus } from '@lucide/vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CreateSiteModal from '@/components/CreateSiteModal.vue';
import SiteSwitcher from '@/components/SiteSwitcher.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2 flex-1">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <div class="flex items-center gap-2">
            <SiteSwitcher />
            <CreateSiteModal v-slot="{ open }">
                <Button
                    variant="outline"
                    size="sm"
                    class="h-9 px-2.5 sm:px-3 text-xs font-semibold gap-1.5"
                    title="Add New Site"
                    @click="open"
                >
                    <Plus class="h-3.5 w-3.5" />
                    <span class="hidden sm:inline">New Site</span>
                </Button>
            </CreateSiteModal>
        </div>
    </header>
</template>
