<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-8 object-contain" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{
            t('common.appName')
        }}</span>
    </div>
</template>
