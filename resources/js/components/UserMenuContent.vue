<!-- resources/js/components/NavUserDropdown.vue -->
<script setup lang="ts">
// keep your file-path comments as you prefer

import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { LogOut, Settings, Users } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Props {
    user: User;
}

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();

type DropdownLink = {
    title: string;
    icon: Component;
    routeName?: string;     // prefer route names so you can keep using route()
    href?: string;          // fallback to raw href if you need it
    method?: 'get' | 'post' | 'put' | 'patch' | 'delete';
    prefetch?: boolean;
    onClick?: (e: Event) => void;
};

// Primary group (e.g., Settings, Profile, etc.)
const userMenuGroupItems: DropdownLink[] = [
    { title: 'Settings', icon: Settings, routeName: 'profile.edit', prefetch: true },
    { title: 'Users', icon: Users, routeName: 'settings.users.index' }, // example matching your comment
];

// Actions section (e.g., Log out)
const userMenuActionItems: DropdownLink[] = [
    { title: 'Log out', icon: LogOut, routeName: 'logout', method: 'post', onClick: handleLogout },
];
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup>
        <DropdownMenuItem
            v-for="item in userMenuGroupItems"
            :key="item.title"
            :as-child="true"
        >
            <Link
                class="block w-full"
                :href="item.routeName ? route(item.routeName) : item.href"
                :method="item.method"
                :prefetch="item.prefetch"
                @click="item.onClick"
                as="button"
            >
                <component :is="item.icon" class="mr-2 h-4 w-4" />
                {{ item.title }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <DropdownMenuItem
        v-for="item in userMenuActionItems"
        :key="item.title"
        :as-child="true"
    >
        <Link
            class="block w-full"
            :href="item.routeName ? route(item.routeName) : item.href"
            :method="item.method"
            :prefetch="item.prefetch"
            @click="item.onClick"
            as="button"
        >
            <component :is="item.icon" class="mr-2 h-4 w-4" />
            {{ item.title }}
        </Link>
    </DropdownMenuItem>
</template>
