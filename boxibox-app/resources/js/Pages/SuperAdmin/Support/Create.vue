<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'

const props = defineProps({
    tenants: Array,
    priorities: Object,
})

const form = useForm({
    tenant_id: '',
    subject: '',
    message: '',
    priority: 'medium',
})

const submit = () => {
    form.post(route('superadmin.support.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Nouveau Ticket Support" />

    <SuperAdminLayout>
        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <Link
                        :href="route('superadmin.support.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Nouveau Ticket</h1>
                        <p class="text-gray-600">Contacter un tenant</p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
                    <!-- Tenant Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tenant *
                        </label>
                        <select
                            v-model="form.tenant_id"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">Selectionnez un tenant...</option>
                            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                {{ tenant.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.tenant_id" class="text-sm text-red-600 mt-1">
                            {{ form.errors.tenant_id }}
                        </p>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Sujet *
                        </label>
                        <input
                            type="text"
                            v-model="form.subject"
                            placeholder="Sujet de votre message..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.subject" class="text-sm text-red-600 mt-1">
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Priorite
                        </label>
                        <select
                            v-model="form.priority"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option v-for="(label, key) in priorities" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Message *
                        </label>
                        <textarea
                            v-model="form.message"
                            rows="6"
                            placeholder="Votre message..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.message" class="text-sm text-red-600 mt-1">
                            {{ form.errors.message }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="route('superadmin.support.index')"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition"
                        >
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>
