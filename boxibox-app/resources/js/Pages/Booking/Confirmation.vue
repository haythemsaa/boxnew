<template>
    <PublicLayout title="Réservation Confirmée !">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Success Message -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Réservation Confirmée !</h1>
                <p class="text-lg text-gray-600">Votre box de stockage a été réservé avec succès</p>
            </div>

            <!-- Contract Details -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Détails de la Réservation</h2>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Numéro de Contrat</p>
                        <p class="text-lg font-semibold text-gray-900">{{ contract.contract_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Statut</p>
                        <span class="inline-flex px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            {{ formatStatus(contract.status) }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-b py-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Box de Stockage</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Box</p>
                            <p class="font-medium text-gray-900">{{ contract.box?.number || contract.box?.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Site</p>
                            <p class="font-medium text-gray-900">{{ contract.box?.site?.name }}</p>
                            <p class="text-sm text-gray-500">{{ contract.box?.site?.city }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Dimensions</p>
                            <p class="font-medium text-gray-900">
                                {{ contract.box?.length }} x {{ contract.box?.width }} x {{ contract.box?.height }} m
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Prix Mensuel</p>
                            <p class="text-xl font-bold text-blue-600">{{ formatPrice(contract.monthly_price) }}€</p>
                        </div>
                    </div>
                </div>

                <div class="border-b pb-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Période de Location</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Date de Début</p>
                            <p class="font-medium text-gray-900">{{ formatDate(contract.start_date) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Fréquence de Facturation</p>
                            <p class="font-medium text-gray-900">{{ formatBillingFrequency(contract.billing_frequency) }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-4">Informations Client</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Nom</p>
                            <p class="font-medium text-gray-900">{{ customerName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-medium text-gray-900">{{ contract.customer?.email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Téléphone</p>
                            <p class="font-medium text-gray-900">{{ contract.customer?.phone }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Mode de Paiement</p>
                            <p class="font-medium text-gray-900">{{ formatPaymentMethod(contract.payment_method) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Prochaines Étapes</h3>
                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                1
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Vérifiez Votre Email</p>
                            <p class="text-sm text-gray-600">
                                Nous avons envoyé un email de confirmation à {{ contract.customer?.email }} avec les détails de votre contrat
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                2
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Effectuez le Paiement</p>
                            <p class="text-sm text-gray-600">
                                Veuillez transférer {{ formatPrice(parseFloat(contract.monthly_price || 0) * 2) }}€ (dépôt + premier mois) selon les coordonnées bancaires dans votre email
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                3
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Emménagez !</p>
                            <p class="text-sm text-gray-600">
                                Une fois le paiement confirmé, vous recevrez votre code d'accès et pourrez emménager le {{ formatDate(contract.start_date) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Besoin d'Aide ?</h3>
                <p class="text-gray-600 mb-4">Si vous avez des questions concernant votre réservation, contactez-nous :</p>
                <div class="space-y-2">
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ contract.box?.site?.email || 'info@boxibox.com' }}
                    </div>
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ contract.box?.site?.phone || '+33 1 23 45 67 89' }}
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                    :href="route('booking.index')"
                    class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 text-center"
                >
                    Voir Plus de Box
                </Link>
                <button
                    @click="printPage"
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700"
                >
                    Imprimer la Confirmation
                </button>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    contract: Object,
});

const customerName = computed(() => {
    if (props.contract.customer?.type === 'company') {
        return props.contract.customer.company_name;
    }
    return `${props.contract.customer?.first_name || ''} ${props.contract.customer?.last_name || ''}`.trim() || 'Client';
});

const formatPrice = (price) => {
    if (price === null || price === undefined) return '0.00';
    return parseFloat(price).toFixed(2);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatStatus = (status) => {
    const map = {
        active: 'Actif',
        pending: 'En attente',
        cancelled: 'Annulé',
        expired: 'Expiré',
    };
    return map[status] || status;
};

const formatBillingFrequency = (frequency) => {
    const map = {
        monthly: 'Mensuelle',
        quarterly: 'Trimestrielle',
        yearly: 'Annuelle',
    };
    return map[frequency] || frequency;
};

const formatPaymentMethod = (method) => {
    const map = {
        bank_transfer: 'Virement bancaire',
        credit_card: 'Carte bancaire',
        direct_debit: 'Prélèvement automatique',
    };
    return map[method] || method;
};

const printPage = () => {
    window.print();
};
</script>
