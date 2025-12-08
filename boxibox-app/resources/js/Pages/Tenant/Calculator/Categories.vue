<template>
    <TenantLayout title="Catégories du calculateur" :breadcrumbs="[{ label: 'Calculateur', href: route('tenant.calculator.index') }, { label: 'Catégories' }]">
        <div class="space-y-6">
            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Gérer les catégories et articles</h3>
                        <p class="text-sm text-gray-500">Configurez les objets que vos clients peuvent ajouter dans le calculateur</p>
                    </div>
                    <button @click="showCreateCategoryModal = true" class="btn-primary">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouvelle catégorie
                    </button>
                </div>
            </div>

            <!-- Categories List -->
            <div class="space-y-4">
                <div v-for="category in categories" :key="category.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Category Header -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-2xl" :style="{ backgroundColor: category.color + '20' }">
                                {{ category.icon }}
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ category.name }}</h3>
                                <p class="text-sm text-gray-500">{{ category.items?.length || 0 }} articles</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="editCategory(category)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                                <PencilIcon class="w-5 h-5" />
                            </button>
                            <button @click="deleteCategory(category)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                            <button @click="toggleCategory(category.id)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                                <ChevronDownIcon :class="expandedCategories.includes(category.id) ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" />
                            </button>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div v-show="expandedCategories.includes(category.id)" class="divide-y divide-gray-100">
                        <div v-for="item in category.items" :key="item.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-lg">
                                    {{ item.icon }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ item.name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ item.width }}m × {{ item.depth }}m × {{ item.height }}m = {{ (item.width * item.depth * item.height).toFixed(2) }} m³
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="item.is_popular ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs font-medium">
                                    {{ item.is_popular ? 'Populaire' : 'Standard' }}
                                </span>
                                <button @click="editItem(item)" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button @click="deleteItem(item)" class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Add Item Button -->
                        <div class="px-6 py-4">
                            <button @click="openAddItemModal(category)" class="flex items-center gap-2 text-primary-600 hover:text-primary-800 text-sm font-medium">
                                <PlusIcon class="w-4 h-4" />
                                Ajouter un article
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="categories.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <CubeIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucune catégorie créée</p>
                    <button @click="showCreateCategoryModal = true" class="text-primary-600 hover:text-primary-800 text-sm mt-2">
                        Créer ma première catégorie
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Category Modal -->
        <div v-if="showCreateCategoryModal || showEditCategoryModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeCategoryModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ showEditCategoryModal ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}
                </h3>
                <form @submit.prevent="submitCategory" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input v-model="categoryForm.name" type="text" class="w-full rounded-xl border-gray-200" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Icône (emoji)</label>
                            <input v-model="categoryForm.icon" type="text" class="w-full rounded-xl border-gray-200" placeholder="🛋️" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                            <input v-model="categoryForm.color" type="color" class="w-full h-10 rounded-xl border-gray-200" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                        <input v-model="categoryForm.sort_order" type="number" min="0" class="w-full rounded-xl border-gray-200" />
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="closeCategoryModal" class="btn-secondary">Annuler</button>
                        <button type="submit" :disabled="categoryForm.processing" class="btn-primary">
                            {{ showEditCategoryModal ? 'Mettre à jour' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Create/Edit Item Modal -->
        <div v-if="showItemModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeItemModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ editingItem ? 'Modifier l\'article' : 'Nouvel article' }}
                </h3>
                <form @submit.prevent="submitItem" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input v-model="itemForm.name" type="text" class="w-full rounded-xl border-gray-200" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Icône (emoji)</label>
                            <input v-model="itemForm.icon" type="text" class="w-full rounded-xl border-gray-200" placeholder="🛋️" />
                        </div>
                        <div>
                            <label class="flex items-center gap-2 mt-6">
                                <input type="checkbox" v-model="itemForm.is_popular" class="rounded text-primary-600" />
                                <span class="text-sm text-gray-700">Article populaire</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Largeur (m)</label>
                            <input v-model="itemForm.width" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profondeur (m)</label>
                            <input v-model="itemForm.depth" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hauteur (m)</label>
                            <input v-model="itemForm.height" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200" required />
                        </div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-xl">
                        <p class="text-sm text-gray-600">
                            Volume calculé: <strong>{{ calculatedVolume }} m³</strong>
                        </p>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="closeItemModal" class="btn-secondary">Annuler</button>
                        <button type="submit" :disabled="itemForm.processing" class="btn-primary">
                            {{ editingItem ? 'Mettre à jour' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    ChevronDownIcon,
    CubeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    categories: Array,
})

const expandedCategories = ref(props.categories?.map(c => c.id) || [])
const showCreateCategoryModal = ref(false)
const showEditCategoryModal = ref(false)
const showItemModal = ref(false)
const editingItem = ref(null)
const selectedCategory = ref(null)

const categoryForm = useForm({
    id: null,
    name: '',
    icon: '📦',
    color: '#3B82F6',
    sort_order: 0,
})

const itemForm = useForm({
    id: null,
    category_id: null,
    name: '',
    icon: '📦',
    width: 0.5,
    depth: 0.5,
    height: 0.5,
    is_popular: false,
})

const calculatedVolume = computed(() => {
    return (itemForm.width * itemForm.depth * itemForm.height).toFixed(2)
})

const toggleCategory = (categoryId) => {
    const index = expandedCategories.value.indexOf(categoryId)
    if (index > -1) {
        expandedCategories.value.splice(index, 1)
    } else {
        expandedCategories.value.push(categoryId)
    }
}

const editCategory = (category) => {
    categoryForm.id = category.id
    categoryForm.name = category.name
    categoryForm.icon = category.icon
    categoryForm.color = category.color
    categoryForm.sort_order = category.sort_order
    showEditCategoryModal.value = true
}

const closeCategoryModal = () => {
    showCreateCategoryModal.value = false
    showEditCategoryModal.value = false
    categoryForm.reset()
}

const submitCategory = () => {
    if (showEditCategoryModal.value) {
        categoryForm.put(route('tenant.calculator.categories.update', categoryForm.id), {
            onSuccess: () => closeCategoryModal()
        })
    } else {
        categoryForm.post(route('tenant.calculator.categories.store'), {
            onSuccess: () => closeCategoryModal()
        })
    }
}

const deleteCategory = (category) => {
    if (confirm(`Supprimer la catégorie "${category.name}" et tous ses articles ?`)) {
        router.delete(route('tenant.calculator.categories.destroy', category.id))
    }
}

const openAddItemModal = (category) => {
    selectedCategory.value = category
    itemForm.category_id = category.id
    itemForm.reset()
    itemForm.category_id = category.id
    editingItem.value = null
    showItemModal.value = true
}

const editItem = (item) => {
    editingItem.value = item
    itemForm.id = item.id
    itemForm.category_id = item.category_id
    itemForm.name = item.name
    itemForm.icon = item.icon
    itemForm.width = item.width
    itemForm.depth = item.depth
    itemForm.height = item.height
    itemForm.is_popular = item.is_popular
    showItemModal.value = true
}

const closeItemModal = () => {
    showItemModal.value = false
    editingItem.value = null
    itemForm.reset()
}

const submitItem = () => {
    if (editingItem.value) {
        itemForm.put(route('tenant.calculator.items.update', itemForm.id), {
            onSuccess: () => closeItemModal()
        })
    } else {
        itemForm.post(route('tenant.calculator.items.store'), {
            onSuccess: () => closeItemModal()
        })
    }
}

const deleteItem = (item) => {
    if (confirm(`Supprimer l'article "${item.name}" ?`)) {
        router.delete(route('tenant.calculator.items.destroy', item.id))
    }
}
</script>
