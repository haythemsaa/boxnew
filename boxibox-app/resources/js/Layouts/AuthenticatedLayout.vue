<template>
    <TenantLayout :title="title" :breadcrumbs="breadcrumbs">
        <slot />
    </TenantLayout>
</template>

<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue'

defineProps({
    title: String,
    breadcrumbs: Array,
})
</script>
