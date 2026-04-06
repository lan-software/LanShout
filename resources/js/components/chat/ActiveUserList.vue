<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { getUserColor, needsPillBackground } from '@/composables/useChatColor';
import { useAppearance } from '@/composables/useAppearance';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    MoreVertical,
    VolumeX,
    Volume2,
    ExternalLink,
    Users,
} from 'lucide-vue-next';
import type { ActiveUser } from '@/composables/useChatPresence';

const props = defineProps<{
    activeUsers: ActiveUser[];
    lancoreBaseUrl?: string;
}>();

const { t } = useI18n();
const page = usePage();

const { appearance } = useAppearance();

const isDarkMode = computed(() => {
    if (appearance.value === 'dark') return true;
    if (appearance.value === 'light') return false;
    return typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: dark)').matches;
});

function getNameStyle(user: ActiveUser): Record<string, string> {
    const color = getUserColor(user.name, user.chat_color);
    const style: Record<string, string> = { color };
    const pill = needsPillBackground(color, isDarkMode.value);
    if (pill) {
        style.backgroundColor = pill;
        style.padding = '1px 6px';
        style.borderRadius = '9999px';
    }
    return style;
}

const isModerator = computed(
    () => (page.props as Record<string, unknown>).isModerator ?? false,
);

// Mute dialog state
const muteDialogOpen = ref(false);
const muteTarget = ref<ActiveUser | null>(null);
const muteReason = ref('');
const muteDuration = ref<number | null>(30);
const mutePermanent = ref(false);
const muting = ref(false);

function openMuteDialog(user: ActiveUser, permanent: boolean) {
    muteTarget.value = user;
    muteReason.value = '';
    muteDuration.value = permanent ? null : 30;
    mutePermanent.value = permanent;
    muteDialogOpen.value = true;
}

async function submitMute() {
    if (!muteTarget.value) return;
    muting.value = true;

    try {
        const token = (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
        )?.content;
        const res = await fetch(`/admin/users/${muteTarget.value.id}/mute`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token ?? '',
            },
            body: JSON.stringify({
                reason: muteReason.value || null,
                duration: mutePermanent.value ? null : muteDuration.value,
            }),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data?.message || 'Failed to mute user');
        }

        muteDialogOpen.value = false;
    } catch {
        // Error handled silently
    } finally {
        muting.value = false;
    }
}

function getRoleBadgeVariant(
    roleName: string,
): 'destructive' | 'default' | 'secondary' | 'outline' {
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
}

function getHighestRole(roles: string[]): string | null {
    const priority = ['super_admin', 'admin', 'moderator'];
    for (const role of priority) {
        if (roles.includes(role)) return role;
    }
    return null;
}

function getRoleDisplay(role: string): string {
    switch (role) {
        case 'super_admin':
            return 'Super Admin';
        case 'admin':
            return 'Admin';
        case 'moderator':
            return 'Moderator';
        default:
            return role;
    }
}

function getLanCoreUrl(userId: number | null | undefined): string | null {
    if (!userId || !props.lancoreBaseUrl) return null;
    return `${props.lancoreBaseUrl}/users/${userId}`;
}
</script>

<template>
    <div class="flex h-full flex-col rounded-md border border-sidebar-border/70 dark:border-sidebar-border">
        <div class="flex items-center gap-2 border-b border-sidebar-border/70 p-3 dark:border-sidebar-border">
            <Users class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm font-medium">
                {{ $t('activeUsers.title') }}
                <span class="text-muted-foreground">({{ activeUsers.length }})</span>
            </span>
        </div>

        <div class="flex-1 overflow-y-auto p-2">
            <div v-if="activeUsers.length === 0" class="flex items-center justify-center p-4 text-sm text-muted-foreground">
                {{ $t('activeUsers.noUsers') }}
            </div>

            <div
                v-for="user in activeUsers"
                :key="user.id"
                class="group flex items-center justify-between rounded px-2 py-1.5 hover:bg-black/5 dark:hover:bg-white/5"
            >
                <div class="flex min-w-0 flex-1 items-center gap-2">
                    <span
                        class="truncate text-sm font-medium"
                        :style="getNameStyle(user)"
                    >
                        {{ user.name }}
                    </span>
                    <Badge
                        v-if="getHighestRole(user.roles)"
                        :variant="getRoleBadgeVariant(getHighestRole(user.roles)!)"
                        class="shrink-0 text-[10px] px-1 py-0"
                    >
                        {{ getRoleDisplay(getHighestRole(user.roles)!) }}
                    </Badge>
                </div>

                <DropdownMenu v-if="isModerator">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-6 w-6 p-0 opacity-0 group-hover:opacity-100"
                        >
                            <MoreVertical class="h-3 w-3" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="openMuteDialog(user, false)">
                            <VolumeX class="mr-2 h-4 w-4" />
                            {{ $t('activeUsers.muteTimed') }}
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="openMuteDialog(user, true)">
                            <Volume2 class="mr-2 h-4 w-4" />
                            {{ $t('activeUsers.mutePermanent') }}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator v-if="getLanCoreUrl(user.lancore_user_id)" />
                        <DropdownMenuItem
                            v-if="getLanCoreUrl(user.lancore_user_id)"
                            as="a"
                            :href="getLanCoreUrl(user.lancore_user_id)!"
                            target="_blank"
                        >
                            <ExternalLink class="mr-2 h-4 w-4" />
                            {{ $t('activeUsers.showInLanCore') }}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>

    <!-- Mute Dialog -->
    <Dialog v-model:open="muteDialogOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>
                    {{ mutePermanent ? $t('activeUsers.mutePermanent') : $t('activeUsers.muteTimed') }}
                </DialogTitle>
                <DialogDescription>
                    {{ muteTarget?.name }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-4">
                <div v-if="!mutePermanent">
                    <Label>{{ $t('activeUsers.muteDuration') }}</Label>
                    <Input
                        v-model.number="muteDuration"
                        type="number"
                        min="1"
                        max="525600"
                        class="mt-1"
                    />
                </div>
                <div>
                    <Label>{{ $t('activeUsers.muteReason') }}</Label>
                    <Textarea
                        v-model="muteReason"
                        rows="3"
                        class="mt-1"
                    />
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="muteDialogOpen = false">
                    {{ $t('common.cancel') }}
                </Button>
                <Button
                    variant="destructive"
                    :disabled="muting || (!mutePermanent && !muteDuration)"
                    @click="submitMute"
                >
                    {{ muting ? $t('common.loading') : (mutePermanent ? $t('activeUsers.mutePermanent') : $t('activeUsers.muteTimed')) }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
