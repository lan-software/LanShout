<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { SendHorizonal } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const emit = defineEmits<{
    (e: 'submit', body: string): void;
}>();

const message = ref('');
const posting = ref(false);
const error = ref<string | null>(null);

async function submit() {
    const body = message.value.trim();
    if (!body || posting.value) return;

    posting.value = true;
    error.value = null;
    try {
        await emit('submit', body);
        message.value = '';
    } catch (e: any) {
        error.value = e?.message ?? t('chat.errorSending');
    } finally {
        posting.value = false;
    }
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submit();
    }
}
</script>

<template>
    <form
        class="flex items-center gap-1.5 border-t border-sidebar-border/70 p-2 dark:border-sidebar-border"
        @submit.prevent="submit"
    >
        <input
            v-model="message"
            type="text"
            :placeholder="$t('chat.inputPlaceholder')"
            :disabled="posting"
            class="flex-1 rounded-md border border-input bg-transparent px-2.5 py-1.5 text-sm placeholder:text-muted-foreground focus:ring-1 focus:ring-ring focus:outline-none disabled:opacity-50"
            @keydown="handleKeydown"
        />
        <Button
            type="submit"
            size="icon"
            variant="ghost"
            :disabled="posting || !message.trim()"
            class="h-8 w-8 shrink-0"
        >
            <SendHorizonal class="h-4 w-4" />
        </Button>
    </form>
    <p v-if="error" class="px-2 pb-1 text-xs text-red-600 dark:text-red-400">
        {{ error }}
    </p>
</template>
