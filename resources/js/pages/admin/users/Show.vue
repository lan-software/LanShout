<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    CheckCircle,
    Mail,
    XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

interface Permission {
    id: number;
    name: string;
    display_name: string;
}

interface Role {
    id: number;
    name: string;
    display_name: string;
    description: string;
    permissions: Permission[];
}

interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles: Role[];
}

interface Props {
    user: User;
}

defineProps<Props>();

const { t } = useI18n();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('common.admin'),
        href: admin.index().url,
    },
    {
        title: t('admin.users.title'),
        href: '/admin/users',
    },
    {
        title: t('admin.userDetails.title'),
        href: '#',
    },
]);

const getRoleBadgeVariant = (roleName: string) => {
    switch (roleName) {
        case 'super_admin':
            return 'destructive';
        case 'admin':
            return 'default';
        case 'moderator':
            return 'secondary';
        default:
            return 'outline';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`${$t('admin.userDetails.title')}: ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Button variant="ghost" size="icon" as-child>
                        <Link href="/admin/users">
                            <ArrowLeft class="h-5 w-5" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold">{{ user.name }}</h1>
                        <p
                            class="mt-1 flex items-center gap-2 text-muted-foreground"
                        >
                            <Mail class="h-4 w-4" />
                            {{ user.email }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- User Info Card -->
            <div
                class="rounded-lg border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
            >
                <h2 class="mb-4 text-lg font-semibold">{{ $t('admin.userDetails.information') }}</h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ $t('admin.userDetails.emailVerification') }}</span>
                        <div class="flex items-center gap-2">
                            <Badge
                                :variant="
                                    user.email_verified_at
                                        ? 'default'
                                        : 'secondary'
                                "
                                class="flex items-center gap-1"
                            >
                                <component
                                    :is="
                                        user.email_verified_at
                                            ? CheckCircle
                                            : XCircle
                                    "
                                    class="h-3 w-3"
                                />
                                {{
                                    user.email_verified_at
                                        ? $t('admin.users.verified')
                                        : $t('admin.users.unverified')
                                }}
                            </Badge>
                        </div>
                    </div>

                    <Separator />

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ $t('admin.userDetails.memberSince') }}</span>
                        <div class="flex items-center gap-2 text-sm">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            {{ formatDate(user.created_at) }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-sm text-muted-foreground">{{ $t('admin.userDetails.lastUpdated') }}</span>
                        <div class="flex items-center gap-2 text-sm">
                            <Calendar class="h-4 w-4 text-muted-foreground" />
                            {{ formatDate(user.updated_at) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Roles Card -->
            <div
                class="rounded-lg border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border"
            >
                <h2 class="mb-4 text-lg font-semibold">{{ $t('admin.userDetails.roles') }}</h2>

                <div v-if="user.roles.length > 0" class="space-y-4">
                    <div
                        v-for="role in user.roles"
                        :key="role.id"
                        class="space-y-2"
                    >
                        <div class="flex items-center gap-2">
                            <Badge :variant="getRoleBadgeVariant(role.name)">
                                {{ role.display_name }}
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ role.description }}
                        </p>

                        <!-- Permissions for this role -->
                        <div
                            v-if="role.permissions.length > 0"
                            class="mt-2 ml-4"
                        >
                            <p
                                class="mb-2 text-xs font-medium text-muted-foreground"
                            >
                                {{ $t('admin.userDetails.permissions') }}
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="permission in role.permissions"
                                    :key="permission.id"
                                    variant="outline"
                                    class="text-xs"
                                >
                                    {{ permission.display_name }}
                                </Badge>
                            </div>
                        </div>

                        <Separator
                            v-if="
                                user.roles.indexOf(role) < user.roles.length - 1
                            "
                            class="mt-4"
                        />
                    </div>
                </div>
                <div v-else class="py-4 text-center text-muted-foreground">
                    {{ $t('admin.userDetails.noRoles') }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
