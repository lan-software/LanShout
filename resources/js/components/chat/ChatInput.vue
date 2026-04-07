<script setup lang="ts">
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import * as z from 'zod';

import { Button } from '@/components/ui/button';
import {
    FormControl,
    FormField,
    FormItem,
    FormMessage,
} from '@/components/ui/form';
import { Textarea } from '@/components/ui/textarea';

const { t } = useI18n();

const props = withDefaults(
    defineProps<{
        slowMode?: boolean;
        slowModeCooldown?: number;
    }>(),
    {
        slowMode: false,
        slowModeCooldown: 10,
    },
);

const emit = defineEmits<{
    (e: 'submit', body: string): void;
}>();

const posting = ref(false);
const error = ref<string | null>(null);
const cooldownRemaining = ref(0);
let cooldownTimer: ReturnType<typeof setInterval> | null = null;

const formSchema = toTypedSchema(
    z.object({
        message: z.string().min(1, {
            message: t('validation.required'),
        }),
    }),
);

const { handleSubmit, resetForm } = useForm({
    validationSchema: formSchema,
});

function startCooldown() {
    if (!props.slowMode) return;

    cooldownRemaining.value = props.slowModeCooldown;
    cooldownTimer = setInterval(() => {
        cooldownRemaining.value--;
        if (cooldownRemaining.value <= 0) {
            if (cooldownTimer) {
                clearInterval(cooldownTimer);
                cooldownTimer = null;
            }
        }
    }, 1000);
}

onUnmounted(() => {
    if (cooldownTimer) {
        clearInterval(cooldownTimer);
    }
});

const onSubmit = handleSubmit(async (values) => {
    if (!values.message.trim()) return;
    if (cooldownRemaining.value > 0) return;

    posting.value = true;
    error.value = null;
    try {
        await emit('submit', values.message);
        resetForm();
        startCooldown();
    } catch (e: unknown) {
        error.value = (e as Error)?.message ?? t('chat.errorSending');
    } finally {
        posting.value = false;
    }
});

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        onSubmit();
    }
};
</script>

<template>
    <form class="w-full space-y-2" @submit="onSubmit">
        <!-- Slow mode countdown -->
        <div
            v-if="slowMode && cooldownRemaining > 0"
            class="rounded-md border border-yellow-200 bg-yellow-50 px-3 py-1.5 text-xs text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200"
        >
            {{ $t('chat.slowModeActive', { seconds: cooldownRemaining }) }}
        </div>

        <div class="flex items-start gap-2">
            <FormField v-slot="{ componentField }" name="message">
                <FormItem class="flex-1">
                    <FormControl>
                        <Textarea
                            :placeholder="$t('chat.inputPlaceholder')"
                            rows="2"
                            class="resize-none"
                            v-bind="componentField"
                            :disabled="cooldownRemaining > 0"
                            @keydown="handleKeydown"
                        />
                    </FormControl>
                    <FormMessage />
                </FormItem>
            </FormField>
            <Button
                type="submit"
                :disabled="posting || cooldownRemaining > 0"
                class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
            >
                {{ posting ? $t('common.loading') : $t('chat.send') }}
            </Button>
        </div>
        <p v-if="error" class="text-sm text-red-600 dark:text-red-400">
            {{ error }}
        </p>
    </form>
</template>
