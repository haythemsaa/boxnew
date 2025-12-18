<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    categories: Object,
    priorities: Object,
})

const form = useForm({
    customer_id: null,
    subject: '',
    description: '',
    category: 'general',
    priority: 'medium',
})

const customerSearch = ref('')
const customers = ref([])
const selectedCustomer = ref(null)
const showDropdown = ref(false)
const searching = ref(false)

const searchCustomers = async () => {
    if (customerSearch.value.length < 2) {
        customers.value = []
        return
    }

    searching.value = true
    try {
        const response = await fetch(route('tenant.support.customers') + '?search=' + encodeURIComponent(customerSearch.value))
        customers.value = await response.json()
        showDropdown.value = true
    } finally {
        searching.value = false
    }
}

const selectCustomer = (customer) => {
    selectedCustomer.value = customer
    form.customer_id = customer.id
    customerSearch.value = `${customer.first_name} ${customer.last_name}`
    showDropdown.value = false
}

const clearCustomer = () => {
    selectedCustomer.value = null
    form.customer_id = null
    customerSearch.value = ''
}

const submit = () => {
    form.post(route('tenant.support.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Nouveau Ticket Support" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <Link
                        :href="route('tenant.support.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Nouveau Ticket</h1>
                        <p class="text-gray-600">Creer un ticket de support pour un client</p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
                    <!-- Customer Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Client *
                        </label>
                        <div class="relative">
                            <div v-if="selectedCustomer" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg border">
                                <div class="flex-1">
                                    <div class="font-medium">{{ selectedCustomer.first_name }} {{ selectedCustomer.last_name }}</div>
                                    <div class="text-sm text-gray-500">{{ selectedCustomer.email }}</div>
                                </div>
                                <button
                                    type="button"
                                    @click="clearCustomer"
                                    class="p-1 text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div v-else>
                                <input
                                    type="text"
                                    v-model="customerSearch"
                                    @input="searchCustomers"
                                    @focus="showDropdown = customers.length > 0"
                                    placeholder="Rechercher un client par nom ou email..."
                                    class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                                />
                                <div
                                    v-if="showDropdown && customers.length > 0"
                                    class="absolute z-10 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-auto"
                                >
                                    <button
                                        v-for="customer in customers"
                                        :key="customer.id"
                                        type="button"
                                        @click="selectCustomer(customer)"
                                        class="w-full px-4 py-2 text-left hover:bg-gray-50 border-b last:border-b-0"
                                    >
                                        <div class="font-medium">{{ customer.first_name }} {{ customer.last_name }}</div>
                                        <div class="text-sm text-gray-500">{{ customer.email }}</div>
                                    </button>
                                </div>
                                <p v-if="searching" class="text-sm text-gray-500 mt-1">Recherche...</p>
                            </div>
                        </div>
                        <p v-if="form.errors.customer_id" class="text-sm text-red-600 mt-1">
                            {{ form.errors.customer_id }}
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
                            placeholder="Resume du probleme..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.subject" class="text-sm text-red-600 mt-1">
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Category & Priority -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Categorie
                            </label>
                            <select
                                v-model="form.category"
                                class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option v-for="(label, key) in categories" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
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
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Description *
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="5"
                            placeholder="Decrivez le probleme en detail..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.description" class="text-sm text-red-600 mt-1">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="route('tenant.support.index')"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition"
                        >
                            Creer le Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
