<template>
    <AppLayout title="CRM - Leads Management">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header with Stats -->
                <div class="mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Leads Management</h1>
                            <p class="mt-2 text-gray-600 dark:text-gray-400">
                                Manage and convert your leads to customers
                            </p>
                        </div>
                        <button @click="showCreateModal = true"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            + New Lead
                        </button>
                    </div>

                    <!-- Analytics Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-sm text-gray-500">Total Leads</div>
                            <div class="text-2xl font-bold">{{ analytics.total_leads }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-sm text-gray-500">Converted</div>
                            <div class="text-2xl font-bold text-green-600">{{ analytics.converted }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-sm text-gray-500">Conversion Rate</div>
                            <div class="text-2xl font-bold text-blue-600">{{ analytics.conversion_rate.toFixed(1) }}%</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-sm text-gray-500">Hot Leads</div>
                            <div class="text-2xl font-bold text-orange-600">{{ analytics.hot_leads }}</div>
                        </div>
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                            <div class="text-sm text-gray-500">Unassigned</div>
                            <div class="text-2xl font-bold text-red-600">{{ analytics.unassigned }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Status</label>
                            <select v-model="filters.status" @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700">
                                <option value="">All Statuses</option>
                                <option value="new">New</option>
                                <option value="contacted">Contacted</option>
                                <option value="qualified">Qualified</option>
                                <option value="converted">Converted</option>
                                <option value="lost">Lost</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Source</label>
                            <select v-model="filters.source" @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700">
                                <option value="">All Sources</option>
                                <option value="website">Website</option>
                                <option value="phone">Phone</option>
                                <option value="referral">Referral</option>
                                <option value="walk-in">Walk-in</option>
                                <option value="google_ads">Google Ads</option>
                                <option value="facebook">Facebook</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Score</label>
                            <select v-model="filters.hot" @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-700">
                                <option value="">All Leads</option>
                                <option value="1">Hot Leads (≥70)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Search</label>
                            <input type="text" v-model="filters.search" @input="applyFilters"
                                   placeholder="Name or email..."
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700">
                        </div>
                    </div>
                </div>

                <!-- Leads Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lead</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="lead in leads.data" :key="lead.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ lead.first_name }} {{ lead.last_name }}
                                    </div>
                                    <div class="text-sm text-gray-500">{{ lead.company || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ lead.email }}</div>
                                    <div class="text-sm text-gray-500">{{ lead.phone || '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-lg font-bold" :class="getScoreColor(lead.score)">
                                            {{ lead.score }}
                                        </div>
                                        <div class="ml-2 text-xs">
                                            {{ lead.score >= 70 ? '🔥' : lead.score >= 40 ? '⭐' : '📊' }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full" :class="getStatusBadge(lead.status)">
                                        {{ lead.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ lead.source || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    {{ lead.assigned_to?.name || 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ new Date(lead.created_at).toLocaleDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button @click="editLead(lead)"
                                            class="text-blue-600 hover:text-blue-700 mr-3">
                                        Edit
                                    </button>
                                    <button v-if="lead.status !== 'converted'"
                                            @click="convertLead(lead)"
                                            class="text-green-600 hover:text-green-700">
                                        Convert
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                Showing {{ leads.from }} to {{ leads.to }} of {{ leads.total }} leads
                            </div>
                            <div class="flex gap-2">
                                <Link v-for="link in leads.links" :key="link.label"
                                      :href="link.url"
                                      :class="[
                                          'px-3 py-1 rounded',
                                          link.active ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                      ]"
                                      v-html="link.label">
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    leads: Object,
    analytics: Object,
});

const showCreateModal = ref(false);
const filters = reactive({
    status: '',
    source: '',
    hot: '',
    search: '',
});

const getScoreColor = (score) => {
    if (score >= 70) return 'text-red-600';
    if (score >= 40) return 'text-yellow-600';
    return 'text-gray-600';
};

const getStatusBadge = (status) => {
    const badges = {
        'new': 'bg-blue-100 text-blue-800',
        'contacted': 'bg-yellow-100 text-yellow-800',
        'qualified': 'bg-purple-100 text-purple-800',
        'converted': 'bg-green-100 text-green-800',
        'lost': 'bg-red-100 text-red-800',
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
};

const applyFilters = () => {
    router.get(route('tenant.crm.leads.index'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const editLead = (lead) => {
    router.visit(route('tenant.crm.leads.edit', lead.id));
};

const convertLead = (lead) => {
    if (confirm(`Convert ${lead.first_name} ${lead.last_name} to customer?`)) {
        router.post(route('tenant.crm.leads.convert', lead.id));
    }
};
</script>
