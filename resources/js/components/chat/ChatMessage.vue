<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { getUserColor, needsPillBackground } from '@/composables/useChatColor';
import { computed } from 'vue';

type User = {
    id: number;
    name: string;
    chat_color?: string | null;
};

type Message = {
    id: number;
    body: string;
    created_at: string;
    user: User;
};

const props = defineProps<{
    message: Message;
}>();

const { appearance } = useAppearance();

const usernameColor = computed(() => {
    return getUserColor(
        props.message.user?.name ?? 'User',
        props.message.user?.chat_color,
    );
});

const isDarkMode = computed(() => {
    if (appearance.value === 'dark') return true;
    if (appearance.value === 'light') return false;
    // System preference
    return (
        typeof window !== 'undefined' &&
        window.matchMedia?.('(prefers-color-scheme: dark)').matches
    );
});

const pillBackground = computed(() => {
    return needsPillBackground(usernameColor.value, isDarkMode.value);
});

const nameStyle = computed(() => {
    const style: Record<string, string> = { color: usernameColor.value };
    if (pillBackground.value) {
        style.backgroundColor = pillBackground.value;
        style.padding = '1px 6px';
        style.borderRadius = '9999px';
    }
    return style;
});

const formattedTime = computed(() => {
    return new Date(props.message.created_at).toLocaleTimeString();
});
</script>

<template>
    <div class="flex flex-col rounded bg-black/2 p-2 dark:bg-white/5">
        <div class="text-xs text-muted-foreground">
            <span class="font-medium" :style="nameStyle">
                {{ message.user?.name ?? 'User' }}
            </span>
            <span class="ml-2">{{ formattedTime }}</span>
        </div>
        <div class="text-sm break-words whitespace-pre-wrap">
            {{ message.body }}
        </div>
    </div>
</template>
