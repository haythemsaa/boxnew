<template>
    <GuestLayout>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 text-center">Welcome Back</h2>
            <p class="mt-2 text-sm text-gray-600 text-center">
                Sign in to your account
            </p>
        </div>

        <form @submit.prevent="submit">
            <!-- Email -->
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    Email
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    :class="{ 'border-red-500': form.errors.email }"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                    {{ form.errors.email }}
                </p>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <Link
                        :href="route('password.request')"
                        class="text-sm text-primary-600 hover:text-primary-700"
                    >
                        Forgot password?
                    </Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    :class="{ 'border-red-500': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password }}
                </p>
            </div>

            <!-- Remember Me -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <!-- Error Message -->
            <div v-if="form.errors.failed" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-600">{{ form.errors.failed }}</p>
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-2.5 px-4 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="!form.processing">Sign In</span>
                <span v-else class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>
        </form>

        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                Don't have an account?
                <Link
                    :href="route('register')"
                    class="font-medium text-primary-600 hover:text-primary-700"
                >
                    Sign up
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>
