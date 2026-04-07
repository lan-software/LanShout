<script setup lang="ts">
import { useChatPresence } from '@/composables/useChatPresence';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import ActiveUserList from '../components/chat/ActiveUserList.vue';
import ChatInput from '../components/chat/ChatInput.vue';
import ChatWall from '../components/chat/ChatWall.vue';

const { t } = useI18n();

interface User {
    id: number;
    name: string;
    chat_color?: string | null;
}
interface Message {
    id: number;
    body: string;
    created_at: string;
    user: User;
}
interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

const props = defineProps<{
    slowModeActive?: boolean;
    slowModeCooldown?: number;
    userMuted?: boolean;
    muteDetails?: {
        reason?: string | null;
        expires_at?: string | null;
    } | null;
    lancoreBaseUrl?: string;
}>();

const messages = ref<Message[]>([]);
const loading = ref<boolean>(false);
const error = ref<string | null>(null);
const currentPage = ref<number>(0);
const hasMore = ref<boolean>(true);
const scrollToBottomFlag = ref<boolean>(false);

const { activeUsers, slowModeActive: liveSlowMode } = useChatPresence();

const isSlowModeActive = computed(
    () => props.slowModeActive || liveSlowMode.value,
);
const isMuted = computed(() => props.userMuted ?? false);

async function loadMoreMessages() {
    if (loading.value || !hasMore.value) return;

    loading.value = true;
    error.value = null;

    try {
        const nextPage = currentPage.value + 1;
        const res = await fetch(`/messages?page=${nextPage}&per_page=20`, {
            headers: { Accept: 'application/json' },
        });
        const json = await res.json();

        const items: Message[] = json?.data ?? [];
        const meta: PaginationMeta | undefined = json?.meta;

        if (Array.isArray(items) && items.length > 0) {
            const reversed = [...items].reverse();
            messages.value = [...reversed, ...messages.value];
            currentPage.value = nextPage;

            if (meta) {
                hasMore.value = meta.current_page < meta.last_page;
            } else {
                hasMore.value = items.length === 20;
            }
        } else {
            hasMore.value = false;
        }
    } catch (e: unknown) {
        error.value = (e as Error)?.message ?? t('chat.errorLoading');
    } finally {
        loading.value = false;
    }
}

async function submitMessage(body: string) {
    const token = (
        document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement
    )?.content;
    const res = await fetch('/messages', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token ?? '',
        },
        body: JSON.stringify({ body }),
    });
    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        throw new Error(data?.message || t('chat.errorSending'));
    }
    const data = await res.json();
    const msg: Message = data?.data ?? data;
    messages.value = [...messages.value, msg];
    scrollToBottomFlag.value = true;
}

onMounted(() => {
    loadMoreMessages();
});
</script>

<template>
    <Head :title="$t('chat.title')" />
    <AppLayout>
        <div class="mx-auto flex w-full max-w-5xl gap-3 p-4">
            <!-- Main Chat Area -->
            <div class="flex min-w-0 flex-1 flex-col gap-3">
                <h1 class="text-xl font-semibold">{{ $t('chat.title') }}</h1>

                <!-- Slow mode indicator -->
                <div
                    v-if="isSlowModeActive && !isMuted"
                    class="rounded-md border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200"
                >
                    {{ $t('chatSettings.slowMode.title') }}
                </div>

                <ChatWall
                    :messages="messages"
                    :loading="loading"
                    :error="error"
                    :has-more="hasMore"
                    :scroll-to-bottom="scrollToBottomFlag"
                    @load-more="loadMoreMessages"
                    @scrolled="scrollToBottomFlag = false"
                />

                <!-- Muted banner -->
                <div
                    v-if="isMuted"
                    class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-200"
                >
                    <p class="font-medium">{{ $t('muted.title') }}</p>
                    <p v-if="muteDetails?.reason" class="mt-1">
                        {{ $t('muted.reason', { reason: muteDetails.reason }) }}
                    </p>
                    <p v-if="muteDetails?.expires_at" class="mt-1">
                        {{
                            $t('muted.until', {
                                until: new Date(
                                    muteDetails.expires_at,
                                ).toLocaleString(),
                            })
                        }}
                    </p>
                    <p v-else class="mt-1">{{ $t('muted.permanent') }}</p>
                    <p class="mt-1 text-muted-foreground">
                        {{ $t('muted.contact') }}
                    </p>
                </div>

                <ChatInput
                    v-if="!isMuted"
                    :slow-mode="isSlowModeActive"
                    :slow-mode-cooldown="slowModeCooldown ?? 10"
                    @submit="submitMessage"
                />
            </div>

            <!-- Active Users Sidebar -->
            <div class="hidden w-64 shrink-0 lg:block">
                <ActiveUserList
                    :active-users="activeUsers"
                    :lancore-base-url="lancoreBaseUrl"
                />
            </div>
        </div>
    </AppLayout>
</template>
