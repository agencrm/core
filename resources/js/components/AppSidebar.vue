<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Presentation, MessageSquare, Users, Tag, Handshake, Workflow, TextCursorInput } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import AppearanceDropdown from '@/components/AppearanceDropdown.vue'

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'CRM',
        href: '/contacts',
        icon: Handshake,
    },
    {
        title: 'Flows',
        href: '/flows',
        icon: Workflow,
    },
    {
        title: 'Forms',
        href: '/forms',
        icon: TextCursorInput,
    },
    {
        title: 'Files',
        href: '/files',
        icon: Folder,
    },
    {
        title: 'Meetings',
        href: '/meetings',
        icon: Presentation,
    },
    {
        title: 'Messages',
        href: '/messages',
        icon: MessageSquare,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <AppearanceDropdown />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
