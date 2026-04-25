<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { MessageSquare, Shield, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('common.admin'),
        href: admin.index().url,
    },
]);

const page = usePage();
const auth = computed(() => page.props.auth);

const hasAnyRole = (roles: string[]): boolean => {
    const userRoles = auth.value?.user?.roles || [];
    return roles.some((role) => userRoles.includes(role));
};

const canManageUsers = computed(() => hasAnyRole(['admin', 'super_admin']));
const canViewChatSettings = computed(() =>
    hasAnyRole(['admin', 'super_admin', 'moderator']),
);
</script>

<template>
    <Head :title="$t('common.admin')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold">{{ $t('admin.title') }}</h1>
                <p class="mt-2 text-muted-foreground">
                    {{ $t('admin.description') }}
                </p>
            </div>

            <!-- Management Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <!-- User Management Card -->
                <Link
                    v-if="canManageUsers"
                    href="/admin/users"
                    class="group relative overflow-hidden rounded-lg border border-sidebar-border/70 bg-card p-6 transition-all hover:border-primary hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Users class="h-6 w-6" />
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold">
                        {{ $t('admin.userManagement.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('admin.userManagement.description') }}
                    </p>
                </Link>

                <!-- Chat Settings Card -->
                <Link
                    v-if="canViewChatSettings"
                    href="/admin/chat-settings"
                    class="group relative overflow-hidden rounded-lg border border-sidebar-border/70 bg-card p-6 transition-all hover:border-primary hover:shadow-md dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <MessageSquare class="h-6 w-6" />
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold">
                        {{ $t('chatSettings.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('admin.chatSettingsCard.description') }}
                    </p>
                </Link>

                <!-- Roles & Permissions Card (Placeholder) -->
                <div
                    class="relative overflow-hidden rounded-lg border border-sidebar-border/70 bg-card p-6 opacity-50 dark:border-sidebar-border"
                >
                    <div class="flex items-start justify-between">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-muted text-muted-foreground"
                        >
                            <Shield class="h-6 w-6" />
                        </div>
                    </div>
                    <h3 class="mt-4 text-lg font-semibold">
                        {{ $t('admin.rolesPermissions.title') }}
                    </h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ $t('common.comingSoon') }}
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
