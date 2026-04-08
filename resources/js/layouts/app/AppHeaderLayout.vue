<script setup lang="ts">
import AnnouncementBanner from '@/components/announcements/AnnouncementBanner.vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import MiniChat from '@/components/chat/MiniChat.vue';
import DemoBanner from '@/components/demo/DemoBanner.vue';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
    <AppShell class="flex-col">
        <DemoBanner />
        <AnnouncementBanner />
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
        <MiniChat v-if="isAuthenticated" />
    </AppShell>
</template>
