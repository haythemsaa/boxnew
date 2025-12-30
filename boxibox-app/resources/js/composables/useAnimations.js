import { ref, onMounted, onUnmounted, nextTick } from 'vue'

/**
 * Custom easing functions
 */
export const easings = {
    linear: t => t,
    easeInQuad: t => t * t,
    easeOutQuad: t => t * (2 - t),
    easeInOutQuad: t => t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t,
    easeInCubic: t => t * t * t,
    easeOutCubic: t => (--t) * t * t + 1,
    easeInOutCubic: t => t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1,
    easeInQuart: t => t * t * t * t,
    easeOutQuart: t => 1 - (--t) * t * t * t,
    easeInOutQuart: t => t < 0.5 ? 8 * t * t * t * t : 1 - 8 * (--t) * t * t * t,
    easeInQuint: t => t * t * t * t * t,
    easeOutQuint: t => 1 + (--t) * t * t * t * t,
    easeInOutQuint: t => t < 0.5 ? 16 * t * t * t * t * t : 1 + 16 * (--t) * t * t * t * t,
    easeOutElastic: t => {
        const p = 0.3
        return Math.pow(2, -10 * t) * Math.sin((t - p / 4) * (2 * Math.PI) / p) + 1
    },
    easeOutBounce: t => {
        if (t < 1 / 2.75) return 7.5625 * t * t
        if (t < 2 / 2.75) return 7.5625 * (t -= 1.5 / 2.75) * t + 0.75
        if (t < 2.5 / 2.75) return 7.5625 * (t -= 2.25 / 2.75) * t + 0.9375
        return 7.5625 * (t -= 2.625 / 2.75) * t + 0.984375
    },
    easeInBack: t => {
        const s = 1.70158
        return t * t * ((s + 1) * t - s)
    },
    easeOutBack: t => {
        const s = 1.70158
        return --t * t * ((s + 1) * t + s) + 1
    },
    easeInOutBack: t => {
        const s = 1.70158 * 1.525
        if ((t *= 2) < 1) return 0.5 * (t * t * ((s + 1) * t - s))
        return 0.5 * ((t -= 2) * t * ((s + 1) * t + s) + 2)
    },
}

/**
 * Animate a value from start to end
 */
export function useAnimate() {
    const animationFrameId = ref(null)

    const animate = ({ from, to, duration = 500, easing = 'easeOutQuart', onUpdate, onComplete }) => {
        const easingFn = typeof easing === 'function' ? easing : easings[easing] || easings.linear
        const startTime = performance.now()

        const tick = (currentTime) => {
            const elapsed = currentTime - startTime
            const progress = Math.min(elapsed / duration, 1)
            const easedProgress = easingFn(progress)

            const currentValue = from + (to - from) * easedProgress
            onUpdate?.(currentValue, progress)

            if (progress < 1) {
                animationFrameId.value = requestAnimationFrame(tick)
            } else {
                onComplete?.()
            }
        }

        animationFrameId.value = requestAnimationFrame(tick)
    }

    const stop = () => {
        if (animationFrameId.value) {
            cancelAnimationFrame(animationFrameId.value)
            animationFrameId.value = null
        }
    }

    onUnmounted(stop)

    return { animate, stop }
}

/**
 * Counter animation hook
 */
export function useCountUp(targetValue, options = {}) {
    const {
        duration = 2000,
        easing = 'easeOutQuart',
        startOnMount = true,
        decimals = 0,
        prefix = '',
        suffix = '',
        separator = ' ',
    } = options

    const displayValue = ref(prefix + '0' + suffix)
    const isAnimating = ref(false)
    const { animate, stop } = useAnimate()

    const formatNumber = (num) => {
        const fixed = num.toFixed(decimals)
        const parts = fixed.split('.')
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, separator)
        return prefix + parts.join('.') + suffix
    }

    const start = (from = 0) => {
        isAnimating.value = true
        animate({
            from,
            to: targetValue.value ?? targetValue,
            duration,
            easing,
            onUpdate: (value) => {
                displayValue.value = formatNumber(value)
            },
            onComplete: () => {
                isAnimating.value = false
            }
        })
    }

    const reset = () => {
        stop()
        displayValue.value = prefix + '0' + suffix
        isAnimating.value = false
    }

    if (startOnMount) {
        onMounted(() => {
            nextTick(() => start())
        })
    }

    return { displayValue, isAnimating, start, reset, stop }
}

/**
 * Intersection Observer animation trigger
 */
export function useScrollAnimation(options = {}) {
    const {
        threshold = 0.1,
        rootMargin = '0px',
        once = true,
    } = options

    const elementRef = ref(null)
    const isVisible = ref(false)
    const hasAnimated = ref(false)
    let observer = null

    const observe = () => {
        if (!elementRef.value) return

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        isVisible.value = true
                        hasAnimated.value = true
                        if (once) {
                            observer?.unobserve(entry.target)
                        }
                    } else if (!once) {
                        isVisible.value = false
                    }
                })
            },
            { threshold, rootMargin }
        )

        observer.observe(elementRef.value)
    }

    onMounted(() => {
        nextTick(observe)
    })

    onUnmounted(() => {
        observer?.disconnect()
    })

    return { elementRef, isVisible, hasAnimated }
}

/**
 * Staggered animation for lists
 */
export function useStaggerAnimation(options = {}) {
    const {
        duration = 500,
        stagger = 100,
        easing = 'easeOutQuart',
    } = options

    const animateList = (elements, properties) => {
        elements.forEach((el, index) => {
            setTimeout(() => {
                animateElement(el, properties, duration, easing)
            }, index * stagger)
        })
    }

    const animateElement = (el, properties, dur, ease) => {
        const easingFn = easings[ease] || easings.linear
        const startTime = performance.now()
        const startValues = {}
        const targetValues = {}

        // Get start values
        Object.keys(properties).forEach(prop => {
            if (prop === 'opacity') {
                startValues[prop] = parseFloat(getComputedStyle(el).opacity)
            } else if (prop === 'transform') {
                startValues[prop] = 0
            }
            targetValues[prop] = properties[prop]
        })

        const tick = (currentTime) => {
            const elapsed = currentTime - startTime
            const progress = Math.min(elapsed / dur, 1)
            const easedProgress = easingFn(progress)

            Object.keys(properties).forEach(prop => {
                if (prop === 'opacity') {
                    const value = startValues[prop] + (targetValues[prop] - startValues[prop]) * easedProgress
                    el.style.opacity = value
                } else if (prop === 'translateY') {
                    const value = startValues.transform + (targetValues[prop] - startValues.transform) * easedProgress
                    el.style.transform = `translateY(${value}px)`
                } else if (prop === 'scale') {
                    const value = startValues.transform + (targetValues[prop] - startValues.transform) * easedProgress
                    el.style.transform = `scale(${value})`
                }
            })

            if (progress < 1) {
                requestAnimationFrame(tick)
            }
        }

        requestAnimationFrame(tick)
    }

    return { animateList }
}

/**
 * Typewriter effect
 */
export function useTypewriter(text, options = {}) {
    const {
        speed = 50,
        delay = 0,
        startOnMount = true,
        cursor = true,
        cursorChar = '|',
    } = options

    const displayText = ref('')
    const isTyping = ref(false)
    const showCursor = ref(cursor)
    let timeoutId = null
    let currentIndex = 0

    const type = () => {
        isTyping.value = true
        const textValue = typeof text === 'function' ? text() : (text.value ?? text)

        const typeChar = () => {
            if (currentIndex < textValue.length) {
                displayText.value += textValue[currentIndex]
                currentIndex++
                timeoutId = setTimeout(typeChar, speed)
            } else {
                isTyping.value = false
            }
        }

        timeoutId = setTimeout(typeChar, delay)
    }

    const reset = () => {
        if (timeoutId) clearTimeout(timeoutId)
        displayText.value = ''
        currentIndex = 0
        isTyping.value = false
    }

    const start = () => {
        reset()
        type()
    }

    if (startOnMount) {
        onMounted(start)
    }

    onUnmounted(() => {
        if (timeoutId) clearTimeout(timeoutId)
    })

    return { displayText, isTyping, showCursor, cursorChar, start, reset }
}

/**
 * Parallax scroll effect
 */
export function useParallax(options = {}) {
    const { speed = 0.5, direction = 'vertical' } = options

    const elementRef = ref(null)
    const offset = ref(0)

    const handleScroll = () => {
        if (!elementRef.value) return

        const rect = elementRef.value.getBoundingClientRect()
        const windowHeight = window.innerHeight
        const elementCenter = rect.top + rect.height / 2
        const windowCenter = windowHeight / 2
        const distance = elementCenter - windowCenter

        offset.value = distance * speed * -1
    }

    onMounted(() => {
        window.addEventListener('scroll', handleScroll, { passive: true })
        handleScroll()
    })

    onUnmounted(() => {
        window.removeEventListener('scroll', handleScroll)
    })

    const style = {
        transform: direction === 'vertical'
            ? `translateY(${offset.value}px)`
            : `translateX(${offset.value}px)`
    }

    return { elementRef, offset, style }
}

/**
 * Mouse follow effect
 */
export function useMouseFollow(options = {}) {
    const { smooth = 0.1, bounds = null } = options

    const position = ref({ x: 0, y: 0 })
    const targetPosition = ref({ x: 0, y: 0 })
    let animationId = null

    const handleMouseMove = (e) => {
        if (bounds) {
            const rect = bounds.value?.getBoundingClientRect()
            if (rect) {
                targetPosition.value = {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                }
            }
        } else {
            targetPosition.value = {
                x: e.clientX,
                y: e.clientY
            }
        }
    }

    const animate = () => {
        position.value.x += (targetPosition.value.x - position.value.x) * smooth
        position.value.y += (targetPosition.value.y - position.value.y) * smooth

        animationId = requestAnimationFrame(animate)
    }

    onMounted(() => {
        window.addEventListener('mousemove', handleMouseMove)
        animate()
    })

    onUnmounted(() => {
        window.removeEventListener('mousemove', handleMouseMove)
        if (animationId) cancelAnimationFrame(animationId)
    })

    return { position }
}

/**
 * Spring animation
 */
export function useSpring(initialValue = 0, options = {}) {
    const {
        stiffness = 100,
        damping = 10,
        mass = 1,
        precision = 0.01,
    } = options

    const value = ref(initialValue)
    const velocity = ref(0)
    const target = ref(initialValue)
    let animationId = null

    const animate = () => {
        const displacement = target.value - value.value
        const springForce = stiffness * displacement
        const dampingForce = damping * velocity.value
        const acceleration = (springForce - dampingForce) / mass

        velocity.value += acceleration * (1 / 60) // Assuming 60fps
        value.value += velocity.value * (1 / 60)

        if (Math.abs(displacement) > precision || Math.abs(velocity.value) > precision) {
            animationId = requestAnimationFrame(animate)
        } else {
            value.value = target.value
            velocity.value = 0
        }
    }

    const set = (newValue) => {
        target.value = newValue
        if (animationId) cancelAnimationFrame(animationId)
        animate()
    }

    const stop = () => {
        if (animationId) cancelAnimationFrame(animationId)
    }

    onUnmounted(stop)

    return { value, set, stop }
}

export default {
    useAnimate,
    useCountUp,
    useScrollAnimation,
    useStaggerAnimation,
    useTypewriter,
    useParallax,
    useMouseFollow,
    useSpring,
    easings,
}
