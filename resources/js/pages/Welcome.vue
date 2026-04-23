<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { MessageSquare } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

defineProps<{
    canRegister: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <Head :title="$t('common.appName') + ' — Event Chat'" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-6"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary"
                >
                    <MessageSquare class="h-5 w-5 text-primary-foreground" />
                </div>
                <span class="text-lg font-semibold tracking-tight">{{ $t('common.appName') }}</span>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    href="/login"
                    class="rounded-full border border-border px-5 py-2 text-sm font-medium text-muted-foreground transition hover:border-primary hover:text-foreground"
                >
                    {{ $t('landing.signIn') }}
                </Link>
                <Link
                    v-if="canRegister"
                    href="/register"
                    class="rounded-full bg-primary px-5 py-2 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                >
                    {{ $t('landing.register') }}
                </Link>
            </div>
        </header>

        <main
            class="flex flex-1 flex-col items-center justify-center px-6 text-center"
        >
            <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10"
            >
                <MessageSquare class="h-8 w-8 text-primary" />
            </div>
            <h1
                class="mt-6 max-w-2xl text-4xl leading-tight font-bold sm:text-5xl"
            >
                {{ $t('landing.heroTitle') }}
            </h1>
            <p
                class="mt-4 max-w-lg text-lg leading-relaxed text-muted-foreground"
            >
                {{ $t('landing.heroDescription') }}
            </p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <Link
                    href="/login"
                    class="rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-primary-foreground transition hover:bg-primary/90"
                >
                    {{ $t('landing.joinConversation') }}
                </Link>
                <a
                    href="/auth/redirect"
                    class="rounded-xl border border-border px-7 py-3 text-sm font-medium text-muted-foreground transition hover:border-primary hover:text-foreground"
                >
                    {{ $t('landing.loginViaLanCore') }}
                </a>
            </div>

            <div class="mx-auto mt-20 grid max-w-3xl gap-6 sm:grid-cols-3">
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">{{ $t('landing.features.liveChat.title') }}</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ $t('landing.features.liveChat.description') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"
                            />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">{{ $t('landing.features.moderation.title') }}</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ $t('landing.features.moderation.description') }}
                    </p>
                </div>
                <div
                    class="rounded-xl border border-border bg-card p-6 text-left"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-primary"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"
                            />
                            <polyline points="10 17 15 12 10 7" />
                            <line x1="15" x2="3" y1="12" y2="12" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold">{{ $t('landing.features.lanCoreSso.title') }}</h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ $t('landing.features.lanCoreSso.description') }}
                    </p>
                </div>
            </div>
        </main>

        <footer class="py-8 text-center text-xs text-muted-foreground">
            {{ $t('landing.poweredBy') }}
        </footer>
    </div>
</template>
