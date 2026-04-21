<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const { t } = useI18n();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('settings.profile.pageTitle'),
        href: edit().url,
    },
]);

const page = usePage();
const user = page.props.auth.user;
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="$t('settings.profile.pageTitle')" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    :title="$t('settings.profile.information')"
                    :description="$t('settings.profile.description')"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">{{ $t('settings.profile.name') }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            :placeholder="$t('settings.profile.namePlaceholder')"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ $t('settings.profile.email') }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            :placeholder="$t('settings.profile.emailPlaceholder')"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="chat_color">{{
                            $t('settings.profile.chatColor')
                        }}</Label>
                        <div class="flex items-center gap-3">
                            <input
                                id="chat_color"
                                type="color"
                                class="mt-1 h-10 w-20 cursor-pointer rounded border border-input"
                                name="chat_color"
                                value="#fafafafa"
                            />
                            <span class="text-sm text-muted-foreground">
                                {{ $t('settings.profile.chatColorDescription') }}
                            </span>
                        </div>
                        <InputError class="mt-2" :message="errors.chat_color" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="locale">{{
                            $t('settings.profile.language')
                        }}</Label>
                        <select
                            id="locale"
                            name="locale"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            :value="user.locale || 'en'"
                        >
                            <option value="en">🇬🇧 English</option>
                            <option value="de">🇩🇪 Deutsch</option>
                            <option value="fr">🇫🇷 Français</option>
                            <option value="es">🇪🇸 Español</option>
                        </select>
                        <InputError class="mt-2" :message="errors.locale" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ $t('settings.profile.emailUnverified') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                {{ $t('settings.profile.resendLink') }}
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{ $t('settings.profile.verificationSent') }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >{{ $t('settings.profile.updateButton') }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ $t('settings.profile.saved') }}
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
