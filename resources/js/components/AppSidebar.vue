<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Globe, Plus } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import CreateSiteModal from '@/components/CreateSiteModal.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Sites',
        href: '/sites',
        icon: Globe,
    },
];

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Repository',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: FolderGit2,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div class="px-3 py-2">
                <CreateSiteModal v-slot="{ open }">
                    <SidebarMenuButton
                        size="default"
                        class="w-full justify-start gap-2 bg-primary font-semibold text-primary-foreground shadow-xs hover:bg-primary/90"
                        @click="open"
                        tooltip="Add New Site"
                    >
                        <Plus class="h-4 w-4" />
                        <span>New Site</span>
                    </SidebarMenuButton>
                </CreateSiteModal>
            </div>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
