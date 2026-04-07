<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

interface RegexFilter {
    pattern: string;
}

interface FilterPreset {
    key: string;
    label: string;
    description: string;
    wordCount: number;
    alwaysActiveInNsfw: boolean;
}

interface ChatSettings {
    blocked_words: string[];
    regex_filters: RegexFilter[];
    filter_action: string;
    allow_urls: boolean;
    spam_repeat_threshold: number;
    spam_window_seconds: number;
    rate_limit_messages: number;
    rate_limit_window_seconds: number;
    slow_mode_enabled: boolean;
    slow_mode_cooldown_seconds: number;
    slow_mode_auto_enabled: boolean;
    slow_mode_auto_threshold: number;
    active_filter_presets: string[];
    nsfw_mode: boolean;
}

const props = defineProps<{
    settings: ChatSettings;
    canEdit: boolean;
    filterPresets: FilterPreset[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: admin.index().url },
    { title: t('chatSettings.title'), href: '/admin/chat-settings' },
];

// Form state
const blockedWordsText = ref(props.settings.blocked_words.join('\n'));
const regexFilters = ref<RegexFilter[]>([...props.settings.regex_filters]);
const filterAction = ref(props.settings.filter_action);
const allowUrls = ref(props.settings.allow_urls);
const spamRepeatThreshold = ref(props.settings.spam_repeat_threshold);
const spamWindowSeconds = ref(props.settings.spam_window_seconds);
const rateLimitMessages = ref(props.settings.rate_limit_messages);
const rateLimitWindowSeconds = ref(props.settings.rate_limit_window_seconds);
const slowModeEnabled = ref(props.settings.slow_mode_enabled);
const slowModeCooldownSeconds = ref(props.settings.slow_mode_cooldown_seconds);
const slowModeAutoEnabled = ref(props.settings.slow_mode_auto_enabled);
const slowModeAutoThreshold = ref(props.settings.slow_mode_auto_threshold);
const activeFilterPresets = ref<string[]>([
    ...(props.settings.active_filter_presets ?? []),
]);
const nsfwMode = ref(props.settings.nsfw_mode ?? false);

const saving = ref(false);
const saveError = ref<string | null>(null);

function togglePreset(key: string) {
    const index = activeFilterPresets.value.indexOf(key);
    if (index === -1) {
        activeFilterPresets.value.push(key);
    } else {
        activeFilterPresets.value.splice(index, 1);
    }
}

const blockedWords = computed(() =>
    blockedWordsText.value
        .split('\n')
        .map((w) => w.trim())
        .filter((w) => w.length > 0),
);

function addRegexFilter() {
    regexFilters.value.push({ pattern: '' });
}

function removeRegexFilter(index: number) {
    regexFilters.value.splice(index, 1);
}

function save() {
    saving.value = true;
    router.put(
        '/admin/chat-settings',
        {
            blocked_words: blockedWords.value,
            regex_filters: regexFilters.value.filter(
                (f) => f.pattern.trim() !== '',
            ),
            filter_action: filterAction.value,
            allow_urls: allowUrls.value,
            spam_repeat_threshold: spamRepeatThreshold.value,
            spam_window_seconds: spamWindowSeconds.value,
            rate_limit_messages: rateLimitMessages.value,
            rate_limit_window_seconds: rateLimitWindowSeconds.value,
            slow_mode_enabled: slowModeEnabled.value,
            slow_mode_cooldown_seconds: slowModeCooldownSeconds.value,
            slow_mode_auto_enabled: slowModeAutoEnabled.value,
            slow_mode_auto_threshold: slowModeAutoThreshold.value,
            active_filter_presets: activeFilterPresets.value,
            nsfw_mode: nsfwMode.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                saveError.value = null;
            },
            onError: (errors) => {
                saveError.value = Object.values(errors).flat().join(', ');
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <Head :title="$t('chatSettings.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        {{ $t('chatSettings.title') }}
                    </h1>
                    <p v-if="!canEdit" class="mt-1 text-muted-foreground">
                        <Badge variant="secondary">Read-only</Badge>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <p
                        v-if="saveError"
                        class="text-sm text-red-600 dark:text-red-400"
                    >
                        {{ saveError }}
                    </p>
                    <Button v-if="canEdit" :disabled="saving" @click="save">
                        {{ saving ? $t('common.loading') : $t('common.save') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <!-- Content Filtering -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            $t('chatSettings.contentFiltering.title')
                        }}</CardTitle>
                        <CardDescription>{{
                            $t('chatSettings.contentFiltering.blockedWordsHelp')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label>{{
                                $t('chatSettings.contentFiltering.blockedWords')
                            }}</Label>
                            <Textarea
                                v-model="blockedWordsText"
                                :disabled="!canEdit"
                                rows="5"
                                class="mt-1 font-mono text-sm"
                                :placeholder="
                                    $t(
                                        'chatSettings.contentFiltering.blockedWordsHelp',
                                    )
                                "
                            />
                        </div>

                        <div>
                            <div class="flex items-center justify-between">
                                <Label>{{
                                    $t(
                                        'chatSettings.contentFiltering.regexFilters',
                                    )
                                }}</Label>
                                <Button
                                    v-if="canEdit"
                                    variant="outline"
                                    size="sm"
                                    @click="addRegexFilter"
                                >
                                    <Plus class="mr-1 h-3 w-3" />
                                    {{
                                        $t(
                                            'chatSettings.contentFiltering.addFilter',
                                        )
                                    }}
                                </Button>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'chatSettings.contentFiltering.regexFiltersHelp',
                                    )
                                }}
                            </p>
                            <div
                                v-for="(filter, index) in regexFilters"
                                :key="index"
                                class="mt-2 flex items-center gap-2"
                            >
                                <Input
                                    v-model="filter.pattern"
                                    :disabled="!canEdit"
                                    class="flex-1 font-mono text-sm"
                                    :placeholder="
                                        $t(
                                            'chatSettings.contentFiltering.pattern',
                                        )
                                    "
                                />
                                <Button
                                    v-if="canEdit"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeRegexFilter(index)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </div>

                        <div>
                            <Label>{{
                                $t('chatSettings.contentFiltering.filterAction')
                            }}</Label>
                            <div class="mt-2 flex gap-4">
                                <label
                                    v-for="action in [
                                        'block',
                                        'censor',
                                        'flag',
                                    ]"
                                    :key="action"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        type="radio"
                                        :value="action"
                                        v-model="filterAction"
                                        :disabled="!canEdit"
                                        class="accent-primary"
                                    />
                                    <span class="text-sm">{{
                                        $t(
                                            `chatSettings.contentFiltering.${action}`,
                                        )
                                    }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input
                                id="allow_urls"
                                type="checkbox"
                                :checked="allowUrls"
                                :disabled="!canEdit"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                @change="
                                    allowUrls = (
                                        $event.target as HTMLInputElement
                                    ).checked
                                "
                            />
                            <Label for="allow_urls">{{
                                $t('chatSettings.contentFiltering.allowUrls')
                            }}</Label>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{
                                $t(
                                    'chatSettings.contentFiltering.allowUrlsHelp',
                                )
                            }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Filter Presets -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            $t('chatSettings.presets.title')
                        }}</CardTitle>
                        <CardDescription>{{
                            $t('chatSettings.presets.description')
                        }}</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="preset in filterPresets"
                            :key="preset.key"
                            class="flex items-start gap-3 rounded-md border p-3"
                        >
                            <input
                                type="checkbox"
                                :id="`preset-${preset.key}`"
                                :value="preset.key"
                                :checked="
                                    activeFilterPresets.includes(preset.key)
                                "
                                :disabled="!canEdit"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                @change="togglePreset(preset.key)"
                            />
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{
                                        preset.label
                                    }}</span>
                                    <Badge
                                        variant="outline"
                                        class="text-[10px]"
                                    >
                                        {{ preset.wordCount }}
                                        {{ $t('chatSettings.presets.words') }}
                                    </Badge>
                                    <Badge
                                        v-if="preset.alwaysActiveInNsfw"
                                        variant="secondary"
                                        class="text-[10px]"
                                    >
                                        {{
                                            $t(
                                                'chatSettings.presets.alwaysActive',
                                            )
                                        }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ preset.description }}
                                </p>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex items-center gap-2">
                                <input
                                    id="nsfw_mode"
                                    type="checkbox"
                                    :checked="nsfwMode"
                                    :disabled="!canEdit"
                                    class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                    @change="
                                        nsfwMode = (
                                            $event.target as HTMLInputElement
                                        ).checked
                                    "
                                />
                                <div>
                                    <Label>{{
                                        $t('chatSettings.presets.nsfwMode')
                                    }}</Label>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            $t(
                                                'chatSettings.presets.nsfwModeHelp',
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Spam Detection -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            $t('chatSettings.spam.title')
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label>{{
                                $t('chatSettings.spam.repeatThreshold')
                            }}</Label>
                            <Input
                                v-model.number="spamRepeatThreshold"
                                :disabled="!canEdit"
                                type="number"
                                min="1"
                                max="100"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t('chatSettings.spam.repeatThresholdHelp')
                                }}
                            </p>
                        </div>
                        <div>
                            <Label>{{
                                $t('chatSettings.spam.windowSeconds')
                            }}</Label>
                            <Input
                                v-model.number="spamWindowSeconds"
                                :disabled="!canEdit"
                                type="number"
                                min="10"
                                max="600"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ $t('chatSettings.spam.windowSecondsHelp') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Rate Limiting -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            $t('chatSettings.rateLimiting.title')
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label>{{
                                $t('chatSettings.rateLimiting.messages')
                            }}</Label>
                            <Input
                                v-model.number="rateLimitMessages"
                                :disabled="!canEdit"
                                type="number"
                                min="1"
                                max="100"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t('chatSettings.rateLimiting.messagesHelp')
                                }}
                            </p>
                        </div>
                        <div>
                            <Label>{{
                                $t('chatSettings.rateLimiting.windowSeconds')
                            }}</Label>
                            <Input
                                v-model.number="rateLimitWindowSeconds"
                                :disabled="!canEdit"
                                type="number"
                                min="10"
                                max="600"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'chatSettings.rateLimiting.windowSecondsHelp',
                                    )
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Slow Mode -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                            $t('chatSettings.slowMode.title')
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2">
                            <input
                                id="slow_mode_enabled"
                                type="checkbox"
                                :checked="slowModeEnabled"
                                :disabled="!canEdit"
                                class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                @change="
                                    slowModeEnabled = (
                                        $event.target as HTMLInputElement
                                    ).checked
                                "
                            />
                            <Label for="slow_mode_enabled">{{
                                $t('chatSettings.slowMode.enabled')
                            }}</Label>
                        </div>

                        <div>
                            <Label>{{
                                $t('chatSettings.slowMode.cooldownSeconds')
                            }}</Label>
                            <Input
                                v-model.number="slowModeCooldownSeconds"
                                :disabled="!canEdit"
                                type="number"
                                min="1"
                                max="300"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'chatSettings.slowMode.cooldownSecondsHelp',
                                    )
                                }}
                            </p>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex items-center gap-2">
                                <input
                                    id="slow_mode_auto_enabled"
                                    type="checkbox"
                                    :checked="slowModeAutoEnabled"
                                    :disabled="!canEdit"
                                    class="mt-0.5 size-4 shrink-0 rounded-[4px] border border-input accent-primary"
                                    @change="
                                        slowModeAutoEnabled = (
                                            $event.target as HTMLInputElement
                                        ).checked
                                    "
                                />
                                <Label>{{
                                    $t('chatSettings.slowMode.autoEnabled')
                                }}</Label>
                            </div>
                        </div>

                        <div>
                            <Label>{{
                                $t('chatSettings.slowMode.autoThreshold')
                            }}</Label>
                            <Input
                                v-model.number="slowModeAutoThreshold"
                                :disabled="!canEdit"
                                type="number"
                                min="5"
                                max="1000"
                                class="mt-1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    $t(
                                        'chatSettings.slowMode.autoThresholdHelp',
                                    )
                                }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
