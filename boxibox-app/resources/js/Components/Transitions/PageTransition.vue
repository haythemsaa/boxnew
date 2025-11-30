<template>
    <transition
        :name="transitionName"
        mode="out-in"
        @before-enter="onBeforeEnter"
        @enter="onEnter"
        @after-enter="onAfterEnter"
        @before-leave="onBeforeLeave"
        @leave="onLeave"
        @after-leave="onAfterLeave"
    >
        <slot />
    </transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'fade-slide',
        validator: (v) => ['fade', 'fade-slide', 'slide-left', 'slide-right', 'scale', 'flip', 'zoom'].includes(v)
    },
    duration: {
        type: Number,
        default: 300
    }
})

const emit = defineEmits(['before-enter', 'enter', 'after-enter', 'before-leave', 'leave', 'after-leave'])

const transitionName = computed(() => `page-${props.type}`)

// Lifecycle hooks
const onBeforeEnter = (el) => {
    el.style.transitionDuration = `${props.duration}ms`
    emit('before-enter', el)
}

const onEnter = (el, done) => {
    emit('enter', el, done)
}

const onAfterEnter = (el) => {
    el.style.transitionDuration = ''
    emit('after-enter', el)
}

const onBeforeLeave = (el) => {
    el.style.transitionDuration = `${props.duration}ms`
    emit('before-leave', el)
}

const onLeave = (el, done) => {
    emit('leave', el, done)
}

const onAfterLeave = (el) => {
    emit('after-leave', el)
}
</script>

<style>
/* Fade */
.page-fade-enter-active,
.page-fade-leave-active {
    transition: opacity 0.3s ease;
}

.page-fade-enter-from,
.page-fade-leave-to {
    opacity: 0;
}

/* Fade Slide */
.page-fade-slide-enter-active,
.page-fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.page-fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* Slide Left */
.page-slide-left-enter-active,
.page-slide-left-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-slide-left-enter-from {
    opacity: 0;
    transform: translateX(30px);
}

.page-slide-left-leave-to {
    opacity: 0;
    transform: translateX(-30px);
}

/* Slide Right */
.page-slide-right-enter-active,
.page-slide-right-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-slide-right-enter-from {
    opacity: 0;
    transform: translateX(-30px);
}

.page-slide-right-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

/* Scale */
.page-scale-enter-active,
.page-scale-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-scale-enter-from {
    opacity: 0;
    transform: scale(0.95);
}

.page-scale-leave-to {
    opacity: 0;
    transform: scale(1.05);
}

/* Flip */
.page-flip-enter-active,
.page-flip-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backface-visibility: hidden;
}

.page-flip-enter-from {
    opacity: 0;
    transform: perspective(1000px) rotateY(-90deg);
}

.page-flip-leave-to {
    opacity: 0;
    transform: perspective(1000px) rotateY(90deg);
}

/* Zoom */
.page-zoom-enter-active,
.page-zoom-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-zoom-enter-from {
    opacity: 0;
    transform: scale(0.8);
}

.page-zoom-leave-to {
    opacity: 0;
    transform: scale(1.2);
}
</style>
