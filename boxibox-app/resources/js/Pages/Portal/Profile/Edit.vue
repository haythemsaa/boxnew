<template>
    <AuthenticatedLayout title="My Profile">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Profile</h2>

                <!-- User Information -->
                <div class="space-y-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900">Account Information</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <!-- Address Information -->
                <div class="space-y-6 mb-8 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900">Address</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Street Address</label>
                        <input
                            v-model="form.address"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <input
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Postal Code</label>
                            <input
                                v-model="form.postal_code"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Country</label>
                        <input
                            v-model="form.country"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <!-- Password Change -->
                <div class="space-y-6 mb-8 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900">Change Password</h3>
                    <p class="text-sm text-gray-600">Leave blank if you don't want to change your password</p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Current Password</label>
                        <input
                            v-model="form.current_password"
                            type="password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.current_password" class="mt-1 text-sm text-red-600">{{ form.errors.current_password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50"
                    >
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object,
    customer: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone,
    mobile: props.customer.mobile,
    address: props.customer.address,
    city: props.customer.city,
    postal_code: props.customer.postal_code,
    country: props.customer.country,
    company_name: props.customer.company_name,
    vat_number: props.customer.vat_number,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('portal.profile.update'), {
        preserveScroll: true,
    });
};
</script>
