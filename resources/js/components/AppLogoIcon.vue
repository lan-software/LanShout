<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import logoIcon from '../../images/logo-icon.png';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    class?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img :src="logoIcon" :class="$props.class" alt="LanShout" />
</template>
