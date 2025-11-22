<template>
    <PortalLayout title="Request Services">
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h1 class="text-2xl font-bold mb-6">Request Additional Services</h1>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Box Change -->
                        <div class="border rounded-lg p-6 hover:border-blue-500 transition cursor-pointer"
                             @click="selectService('box_change')">
                            <div class="text-4xl mb-3">📦</div>
                            <h3 class="font-semibold text-lg mb-2">Change Box</h3>
                            <p class="text-sm text-gray-600">
                                Upgrade or downgrade to a different box size
                            </p>
                        </div>

                        <!-- Add Insurance -->
                        <div class="border rounded-lg p-6 hover:border-blue-500 transition cursor-pointer"
                             @click="selectService('insurance')">
                            <div class="text-4xl mb-3">🛡️</div>
                            <h3 class="font-semibold text-lg mb-2">Add Insurance</h3>
                            <p class="text-sm text-gray-600">
                                Protect your belongings with additional insurance
                            </p>
                        </div>

                        <!-- Rent Products -->
                        <div class="border rounded-lg p-6 hover:border-blue-500 transition cursor-pointer"
                             @click="selectService('products')">
                            <div class="text-4xl mb-3">🔒</div>
                            <h3 class="font-semibold text-lg mb-2">Rent Products</h3>
                            <p class="text-sm text-gray-600">
                                Locks, boxes, packing materials
                            </p>
                        </div>

                        <!-- Request Invoice -->
                        <div class="border rounded-lg p-6 hover:border-blue-500 transition cursor-pointer"
                             @click="selectService('invoice')">
                            <div class="text-4xl mb-3">📄</div>
                            <h3 class="font-semibold text-lg mb-2">Request Invoice</h3>
                            <p class="text-sm text-gray-600">
                                Get a copy of your invoice
                            </p>
                        </div>

                        <!-- Termination Notice -->
                        <div class="border rounded-lg p-6 hover:border-red-500 transition cursor-pointer"
                             @click="selectService('termination')">
                            <div class="text-4xl mb-3">⚠️</div>
                            <h3 class="font-semibold text-lg mb-2">Give Notice</h3>
                            <p class="text-sm text-gray-600">
                                Terminate your contract (requires notice period)
                            </p>
                        </div>

                        <!-- Other Request -->
                        <div class="border rounded-lg p-6 hover:border-blue-500 transition cursor-pointer"
                             @click="selectService('other')">
                            <div class="text-4xl mb-3">💬</div>
                            <h3 class="font-semibold text-lg mb-2">Other Request</h3>
                            <p class="text-sm text-gray-600">
                                Contact us for any other service
                            </p>
                        </div>
                    </div>

                    <!-- Service Request Form (shown when service selected) -->
                    <div v-if="selectedService" class="mt-8 border-t pt-8">
                        <h2 class="text-xl font-bold mb-4">
                            Request: {{ getServiceTitle(selectedService) }}
                        </h2>

                        <form @submit.prevent="submitRequest">
                            <div class="space-y-4">
                                <div v-if="selectedService === 'box_change'">
                                    <label class="block text-sm font-medium mb-1">Select New Box</label>
                                    <select v-model="form.box_id" class="w-full rounded-md border-gray-300">
                                        <option value="">Choose a box...</option>
                                        <option v-for="box in availableBoxes" :key="box.id" :value="box.id">
                                            {{ box.number }} - {{ box.size }}m² (€{{ box.price_per_month }}/month)
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1">Additional Details</label>
                                    <textarea v-model="form.message" rows="4"
                                              class="w-full rounded-md border-gray-300"
                                              placeholder="Please provide any additional information..."></textarea>
                                </div>

                                <div class="flex gap-3">
                                    <button type="submit"
                                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                        Submit Request
                                    </button>
                                    <button type="button" @click="selectedService = null"
                                            class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';

const props = defineProps({
    availableBoxes: Array,
});

const selectedService = ref(null);
const form = reactive({
    service_type: '',
    box_id: '',
    message: '',
});

const selectService = (service) => {
    selectedService.value = service;
    form.service_type = service;
};

const getServiceTitle = (service) => {
    const titles = {
        'box_change': 'Box Change',
        'insurance': 'Add Insurance',
        'products': 'Rent Products',
        'invoice': 'Request Invoice',
        'termination': 'Give Termination Notice',
        'other': 'Other Request',
    };
    return titles[service] || service;
};

const submitRequest = () => {
    router.post(route('portal.services.store'), form, {
        onSuccess: () => {
            alert('Your request has been submitted successfully!');
            selectedService.value = null;
            form.box_id = '';
            form.message = '';
        }
    });
};
</script>
