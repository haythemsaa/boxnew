<template>
    <TransitionGroup
        :name="transitionName"
        tag="div"
        :class="containerClass"
        @before-enter="onBeforeEnter"
        @enter="onEnter"
        @leave="onLeave"
    >
        <slot />
    </TransitionGroup>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'fade-slide',
        validator: (v) => ['fade', 'fade-slide', 'scale', 'flip'].includes(v)
    },
    stagger: {
        type: Number,
        default: 50
    },
    duration: {
        type: Number,
        default: 300
    },
    containerClass: {
        type: String,
        default: ''
    }
})

const transitionName = computed(() => `list-${props.type}`)

const onBeforeEnter = (el) => {
    el.style.transitionDelay = `${el.dataset.index * props.stagger}ms`
    el.style.transitionDuration = `${props.duration}ms`
}

const onEnter = (el, done) => {
    setTimeout(done, props.duration + (el.dataset.index * props.stagger))
}

const onLeave = (el, done) => {
    el.style.transitionDelay = '0ms'
    el.style.transitionDuration = `${props.duration}ms`
    setTimeout(done, props.duration)
}
</script>

<style>
/* List Fade */
.list-fade-enter-active,
.list-fade-leave-active {
    transition: opacity 0.3s ease;
}

.list-fade-enter-from,
.list-fade-leave-to {
    opacity: 0;
}

.list-fade-move {
    transition: transform 0.3s ease;
}

/* List Fade Slide */
.list-fade-slide-enter-active,
.list-fade-slide-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-fade-slide-enter-from {
    opacity: 0;
    transform: translateY(20px);
}

.list-fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

.list-fade-slide-move {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* List Scale */
.list-scale-enter-active,
.list-scale-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-scale-enter-from {
    opacity: 0;
    transform: scale(0.8);
}

.list-scale-leave-to {
    opacity: 0;
    transform: scale(0.8);
}

.list-scale-move {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* List Flip */
.list-flip-enter-active,
.list-flip-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    backface-visibility: hidden;
}

.list-flip-enter-from {
    opacity: 0;
    transform: rotateX(-90deg);
}

.list-flip-leave-to {
    opacity: 0;
    transform: rotateX(90deg);
}

.list-flip-move {
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
