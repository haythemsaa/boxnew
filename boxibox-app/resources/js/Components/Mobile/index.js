// Mobile Components Index
// Export all mobile-optimized components

export { default as SwipeableCard } from './SwipeableCard.vue';
export { default as QuickStatCard } from './QuickStatCard.vue';
export { default as ActionButton } from './ActionButton.vue';
export { default as BottomSheet } from './BottomSheet.vue';
export { default as FloatingActionButton } from './FloatingActionButton.vue';

// Helper function to detect mobile
export function isMobile() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Helper function to detect if standalone (PWA)
export function isStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
           window.navigator.standalone ||
           document.referrer.includes('android-app://');
}

// Helper function for haptic feedback
export function hapticFeedback(type = 'light') {
    if ('vibrate' in navigator) {
        const patterns = {
            light: [10],
            medium: [20],
            heavy: [30],
            success: [10, 50, 10],
            error: [30, 50, 30],
        };
        navigator.vibrate(patterns[type] || patterns.light);
    }
}

// Safe area insets helper
export function getSafeAreaInsets() {
    const computedStyle = getComputedStyle(document.documentElement);
    return {
        top: parseInt(computedStyle.getPropertyValue('--sat') || '0'),
        right: parseInt(computedStyle.getPropertyValue('--sar') || '0'),
        bottom: parseInt(computedStyle.getPropertyValue('--sab') || '0'),
        left: parseInt(computedStyle.getPropertyValue('--sal') || '0'),
    };
}
