
<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import OwnerSidebar from '@/Components/OwnerSidebar.vue'

const props = defineProps({
    owner: {
        type: Object,
        required: true,
    },
    products: {
        type: Object,
        required: true,
    },
})

const productList = computed(() => props.products.data ?? [])

const search = ref('')
const selectedCategory = ref('all')

const showDeleteModal = ref(false)
const selectedProduct = ref(null)
const isDeleting = ref(false)

/*
|--------------------------------------------------------------------------
| CATEGORY OPTIONS
|--------------------------------------------------------------------------
*/

const categoryOptions = computed(() => {
    const categories = productList.value
        .filter(product => product.category)
        .map(product => ({
            id: String(product.category.id),
            name: product.category.name,
        }))

    const uniqueCategories = new Map()

    categories.forEach(category => {
        uniqueCategories.set(category.id, category)
    })

    return [...uniqueCategories.values()].sort((a, b) =>
        a.name.localeCompare(b.name, 'id')
    )
})

/*
|--------------------------------------------------------------------------
| SEARCH & CATEGORY FILTER
|--------------------------------------------------------------------------
*/

const filteredProducts = computed(() => {
    const keyword = search.value.trim().toLowerCase()

    return productList.value.filter(product => {
        const matchesSearch = !keyword ||
            String(product.name ?? '').toLowerCase().includes(keyword) ||
            String(product.unique_id ?? '').toLowerCase().includes(keyword)

        const matchesCategory = selectedCategory.value === 'all' ||
            String(product.category_id ?? '') === selectedCategory.value

        return matchesSearch && matchesCategory
    })
})

const totalProducts = computed(() => {
    return props.products.total ?? productList.value.length
})

const resetFilters = () => {
    search.value = ''
    selectedCategory.value = 'all'
}

/*
|--------------------------------------------------------------------------
| PRODUCT IMAGE
|--------------------------------------------------------------------------
*/

const getImage = (product) => {
    const images = product.images

    if (!Array.isArray(images) || !images.length) {
        return null
    }

    const image = images[0]

    if (!image) return null

    if (/^https?:\/\//i.test(image)) {
        return image
    }

    return `/storage/${String(image).replace(/^\/+/, '')}`
}

/*
|--------------------------------------------------------------------------
| PRODUCT PRICE
|--------------------------------------------------------------------------
*/

const formatPrice = (price) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price)
}

const getPriceRange = (product, field) => {
    const combinations = Array.isArray(product.combinations)
        ? product.combinations
        : []

    const prices = combinations
        .map(combination => Number(combination[field]))
        .filter(price => Number.isFinite(price) && price >= 0)

    if (!prices.length) {
        return '-'
    }

    const minimum = Math.min(...prices)
    const maximum = Math.max(...prices)

    if (minimum === maximum) {
        return formatPrice(minimum)
    }

    return `${formatPrice(minimum)} – ${formatPrice(maximum)}`
}

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/

const openDeleteModal = (product) => {
    selectedProduct.value = product
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (isDeleting.value) return

    showDeleteModal.value = false
    selectedProduct.value = null
}

const deleteProduct = () => {
    if (!selectedProduct.value || isDeleting.value) return

    isDeleting.value = true

    router.delete(
        route('owner.products.destroy', selectedProduct.value.id),
        {
            preserveScroll: true,

            onSuccess: () => {
                showDeleteModal.value = false
                selectedProduct.value = null
            },

            onFinish: () => {
                isDeleting.value = false
            },
        }
    )
}
</script>

<template>
    <div class="owner-layout">
        <OwnerSidebar :owner="owner" />

        <main class="main-content">
            <!-- PAGE HEADER -->
            <header class="page-header">
                <div>
                    <span class="eyebrow">OWNER WORKSPACE</span>
                    <h1>Produk</h1>
                    <p>Kelola seluruh produk MOXXER dalam satu tempat.</p>
                </div>

                <div class="header-actions">
                    <Link
                        :href="route('owner.categories.index')"
                        class="category-button"
                    >
                        <span class="category-icon">▦</span>
                        Kategori
                    </Link>

                    <Link
                        :href="route('owner.products.create')"
                        class="add-button"
                    >
                        <span>+</span>
                        Tambah Produk
                    </Link>
                </div>
            </header>

            <!-- STICKY SEARCH & CATEGORY FILTER -->
            <div class="product-toolbar">
                <div class="toolbar-search">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>

                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nama produk atau Unique ID..."
                        aria-label="Cari produk"
                    />

                    <button
                        v-if="search"
                        type="button"
                        class="clear-search"
                        aria-label="Hapus pencarian"
                        @click="search = ''"
                    >
                        ×
                    </button>
                </div>

                <div class="toolbar-filter">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 7h16M7 12h10m-7 5h4" />
                    </svg>

                    <select
                        v-model="selectedCategory"
                        aria-label="Filter kategori"
                    >
                        <option value="all">Semua Kategori</option>

                        <option
                            v-for="category in categoryOptions"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <svg
                        class="select-arrow"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </div>

                <button
                    v-if="search || selectedCategory !== 'all'"
                    type="button"
                    class="reset-filter-button"
                    @click="resetFilters"
                >
                    Reset
                </button>
            </div>

            <!-- CONTENT SECTION -->
            <section class="content-section">
                <div class="section-header">
                    <div>
                        <h2>Daftar Produk</h2>
                        <p>
                            Menampilkan {{ filteredProducts.length }}
                            dari {{ totalProducts }} produk
                        </p>
                    </div>
                </div>

                <!-- PRODUCT GRID -->
                <div
                    v-if="filteredProducts.length"
                    class="product-grid"
                >
                    <article
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="product-card"
                    >
                        <div class="product-image">
                            <img
                                v-if="getImage(product)"
                                :src="getImage(product)"
                                :alt="product.name"
                                loading="lazy"
                            />

                            <div v-else class="image-placeholder">
                                <span>◇</span>
                                <small>Gambar tidak tersedia</small>
                            </div>
                        </div>

                        <div class="product-info">
                            <h3 class="product-title">
                                {{ product.name }}
                            </h3>

                            <div class="product-prices">
                                <div class="price-item">
                                    <span class="price-label">B2B</span>

                                    <span class="price-value">
                                        {{ getPriceRange(product, 'price_b2b') }}
                                    </span>
                                </div>

                                <div class="price-item">
                                    <span class="price-label">Dropship</span>

                                    <span class="price-value">
                                        {{ getPriceRange(product, 'price_dropship') }}
                                    </span>
                                </div>

                                <div class="price-item">
                                    <span class="price-label">Host Live</span>

                                    <span class="price-value">
                                        {{ getPriceRange(product, 'price_host_live') }}
                                    </span>
                                </div>
                            </div>

                            <div class="product-actions">
                                <Link
                                    :href="route('owner.products.edit', product.unique_id)"
                                    class="edit-button"
                                >
                                    Edit
                                </Link>

                                <button
                                    type="button"
                                    class="delete-button"
                                    @click="openDeleteModal(product)"
                                >
                                    Hapus
                                </button>

                                <Link
                                    :href="route('owner.products.create', {
                                        clone: product.unique_id,
                                    })"
                                    class="clone-button"
                                >
                                    Salin
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>

                <!-- EMPTY STATE -->
                <div v-else class="empty-state">
                    <div class="empty-icon">◇</div>

                    <template v-if="search || selectedCategory !== 'all'">
                        <h3>Produk Tidak Ditemukan</h3>

                        <p>
                            Tidak ada produk yang sesuai dengan pencarian
                            atau kategori yang dipilih.
                        </p>

                        <button
                            type="button"
                            class="empty-add-button"
                            @click="resetFilters"
                        >
                            Reset Pencarian
                        </button>
                    </template>

                    <template v-else>
                        <h3>Belum Ada Produk</h3>

                        <p>
                            Produk yang kamu tambahkan nantinya akan muncul di sini.
                        </p>

                        <Link
                            :href="route('owner.products.create')"
                            class="empty-add-button"
                        >
                            + Tambah Produk
                        </Link>
                    </template>
                </div>
            </section>
        </main>

        <!-- CUSTOM DELETE CONFIRMATION MODAL -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showDeleteModal"
                    class="modal-overlay"
                    @click.self="closeDeleteModal"
                    @keydown.esc="closeDeleteModal"
                >
                    <div
                        class="delete-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="delete-modal-title"
                    >
                        <div class="modal-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 3L22 20H2L12 3Z" />
                                <path d="M12 9V13" />
                                <path d="M12 16.5V16.6" />
                            </svg>
                        </div>

                        <h2 id="delete-modal-title">
                            Hapus Produk?
                        </h2>

                        <p class="modal-description">
                            Kamu yakin ingin menghapus produk
                            <strong>{{ selectedProduct?.name }}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        <div class="modal-actions">
                            <button
                                type="button"
                                class="modal-cancel-button"
                                :disabled="isDeleting"
                                @click="closeDeleteModal"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                class="modal-confirm-button"
                                :disabled="isDeleting"
                                @click="deleteProduct"
                            >
                                <span
                                    v-if="isDeleting"
                                    class="loading-spinner"
                                ></span>

                                {{ isDeleting ? 'Menghapus...' : 'Hapus Produk' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
* {
    box-sizing: border-box;
}

.owner-layout {
    min-height: 100vh;
    display: flex;
    background: #fafaf8;
    color: #171717;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.main-content {
    flex: 1;
    min-width: 0;
    padding: 48px 55px;
}

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    margin-bottom: 25px;
}

.eyebrow {
    color: #999992;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.5px;
}

.page-header h1 {
    margin: 10px 0 7px;
    font-size: 30px;
    font-weight: 650;
    letter-spacing: -1.2px;
}

.page-header p,
.section-header p {
    margin: 0;
    color: #92928b;
    font-size: 12px;
}

/* Header Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-button,
.add-button {
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 17px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s, border-color .2s;
    white-space: nowrap;
}

.category-button {
    border: 1px solid #e5e5df;
    background: white;
    color: #292929;
}

.category-button:hover {
    border-color: #c9c9c2;
    background: #f5f5f2;
}

.category-icon {
    font-size: 15px;
}

.add-button {
    border: none;
    background: #171717;
    color: white;
}

.add-button:hover {
    background: #333;
}

.add-button span {
    font-size: 18px;
    font-weight: 400;
}

/* Sticky Search & Filter */
.product-toolbar {
    position: sticky;
    top: 12px;
    z-index: 50;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding: 12px;
    border: 1px solid #eaeae5;
    border-radius: 10px;
    background: rgb(250 250 248 / 96%);
    box-shadow: 0 4px 15px rgb(0 0 0 / 3%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.toolbar-search,
.toolbar-filter {
    height: 40px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 12px;
    border: 1px solid #e5e5df;
    border-radius: 7px;
    background: white;
    color: #92928b;
}

.toolbar-search {
    flex: 1;
    min-width: 180px;
}

.toolbar-search > svg,
.toolbar-filter > svg {
    width: 17px;
    height: 17px;
    flex-shrink: 0;
}

.toolbar-search input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    padding: 0;
    border: none;
    outline: none;
    background: transparent;
    color: #292929;
    font-family: inherit;
    font-size: 11px;
}

.toolbar-search input::placeholder {
    color: #aaa9a1;
}

.toolbar-search input::-webkit-search-cancel-button {
    display: none;
}

.clear-search {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: none;
    background: transparent;
    color: #92928b;
    font-size: 20px;
    cursor: pointer;
}

.toolbar-filter {
    position: relative;
    width: 220px;
    flex-shrink: 0;
}

.toolbar-filter select {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    appearance: none;
    border: none;
    outline: none;
    background: transparent;
    color: #292929;
    font-family: inherit;
    font-size: 11px;
    cursor: pointer;
}

.toolbar-filter .select-arrow {
    width: 14px;
    height: 14px;
    pointer-events: none;
}

.reset-filter-button {
    height: 40px;
    flex-shrink: 0;
    padding: 0 13px;
    border: 1px solid #e5e5df;
    border-radius: 7px;
    background: white;
    color: #777;
    font-family: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, color .2s;
}

.reset-filter-button:hover {
    background: #171717;
    color: white;
}

/* Content */
.content-section {
    padding: 25px;
    border: 1px solid #eaeae5;
    border-radius: 12px;
    background: white;
}

.section-header {
    margin-bottom: 25px;
}

.section-header h2 {
    margin: 0 0 7px;
    font-size: 15px;
    font-weight: 650;
}

/* Product Grid */
.product-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
}

.product-card {
    min-width: 0;
    overflow: hidden;
    border: 1px solid #eaeae5;
    border-radius: 9px;
    background: #fff;
    transition: border-color .2s, transform .2s, box-shadow .2s;
}

.product-card:hover {
    border-color: #d5d5ce;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgb(0 0 0 / 5%);
}

/* Product Image */
.product-image {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #f5f5f2;
}

.product-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s;
}

.product-card:hover .product-image img {
    transform: scale(1.04);
}

.image-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    color: #aaa9a1;
}

.image-placeholder span {
    font-size: 35px;
}

.image-placeholder small {
    margin-top: 8px;
    font-size: 9px;
}

/* Product Information */
.product-info {
    padding: 12px 11px 11px;
}

.product-title {
    display: -webkit-box;
    overflow: hidden;
    min-height: 34px;
    color: #242424;
    font-size: 11px;
    font-weight: 650;
    line-height: 1.45;
    overflow-wrap: anywhere;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

/* Prices */
.product-prices {
    display: flex;
    flex-direction: column;
    gap: 7px;
    padding-top: 9px;
    border-top: 1px solid #eeeeea;
}

.price-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 6px;
    min-width: 0;
}

.price-label {
    flex-shrink: 0;
    color: #999992;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: .1px;
}

.price-value {
    min-width: 0;
    color: #292929;
    font-size: 10px;
    font-weight: 650;
    line-height: 1.4;
    text-align: right;
    overflow-wrap: anywhere;
}

/* Product Actions */
.product-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 7px;
    margin-top: 11px;
    padding-top: 9px;
    border-top: 1px solid #eeeeea;
}

.edit-button,
.delete-button,
.clone-button {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    height: 30px;
    border: 1px solid #e5e5df;
    border-radius: 5px;
    background: white;
    font-family: inherit;
    font-size: 9px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s, border-color .2s;
}

.edit-button {
    color: #292929;
}

.edit-button:hover {
    border-color: #c9c9c2;
    background: #f5f5f2;
}

.delete-button {
    color: #b42318;
}

.delete-button:hover {
    border-color: #e5b4af;
    background: #fff5f4;
}

.clone-button {
    color: #292929;
}

.clone-button:hover {
    border-color: #c9c9c2;
    background: #f5f5f2;
}

/* Empty State */
.empty-state {
    padding: 65px 20px;
    text-align: center;
}

.empty-icon {
    color: #b4b4ac;
    font-size: 35px;
}

.empty-state h3 {
    margin: 13px 0 7px;
    font-size: 14px;
    font-weight: 650;
}

.empty-state p {
    margin: 0;
    color: #999992;
    font-size: 11px;
}

.empty-add-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 39px;
    margin-top: 20px;
    padding: 0 17px;
    border: none;
    border-radius: 7px;
    background: #171717;
    color: white;
    font-family: inherit;
    font-size: 11px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: background .2s;
}

.empty-add-button:hover {
    background: #333;
}

/* Apple-style Delete Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgb(0 0 0 / 28%);
    backdrop-filter: blur(9px);
    -webkit-backdrop-filter: blur(9px);
}

.delete-modal {
    width: 100%;
    max-width: 360px;
    padding: 30px 28px 24px;
    border: 1px solid rgb(255 255 255 / 70%);
    border-radius: 20px;
    background: rgb(255 255 255 / 96%);
    box-shadow:
        0 24px 80px rgb(0 0 0 / 16%),
        0 2px 8px rgb(0 0 0 / 5%);
    text-align: center;
}

.modal-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 54px;
    height: 54px;
    margin: 0 auto 19px;
    border-radius: 17px;
    background: #fff1ef;
    color: #df493e;
}

.modal-icon svg {
    width: 27px;
    height: 27px;
}

.delete-modal h2 {
    margin: 0 0 10px;
    color: #1c1c1e;
    font-size: 19px;
    font-weight: 650;
    letter-spacing: -.5px;
}

.modal-description {
    margin: 0 auto;
    max-width: 280px;
    color: #77777c;
    font-size: 12px;
    line-height: 1.7;
}

.modal-description strong {
    color: #292929;
    font-weight: 650;
}

.modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 26px;
}

.modal-cancel-button,
.modal-confirm-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 43px;
    border: none;
    border-radius: 10px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s, transform .15s, opacity .2s;
}

.modal-cancel-button {
    background: #f1f1f3;
    color: #29292d;
}

.modal-cancel-button:hover {
    background: #e8e8eb;
}

.modal-confirm-button {
    background: #e5483f;
    color: white;
}

.modal-confirm-button:hover {
    background: #d63830;
}

.modal-cancel-button:active,
.modal-confirm-button:active {
    transform: scale(.97);
}

.modal-cancel-button:disabled,
.modal-confirm-button:disabled {
    cursor: not-allowed;
    opacity: .6;
}

.loading-spinner {
    width: 13px;
    height: 13px;
    border: 2px solid rgb(255 255 255 / 35%);
    border-top-color: white;
    border-radius: 50%;
    animation: spin .7s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Modal Animation */
.modal-enter-active,
.modal-leave-active {
    transition: opacity .22s ease;
}

.modal-enter-active .delete-modal,
.modal-leave-active .delete-modal {
    transition: transform .22s ease, opacity .22s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .delete-modal,
.modal-leave-to .delete-modal {
    opacity: 0;
    transform: translateY(10px) scale(.96);
}

/* Responsive */
@media (max-width: 1250px) {
    .product-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }
}

@media (max-width: 1050px) {
    .main-content {
        padding: 38px 28px;
    }

    .product-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }
}

@media (max-width: 760px) {
    .main-content {
        padding: 30px 20px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
        margin-bottom: 20px;
    }

    .header-actions {
        width: 100%;
    }

    .category-button,
    .add-button {
        flex: 1;
    }

    /* Sticky toolbar on mobile */
    .product-toolbar {
        top: 8px;
        flex-wrap: wrap;
        gap: 8px;
        padding: 9px;
    }

    .toolbar-search {
        flex-basis: 100%;
        min-width: 100%;
        height: 39px;
    }

    .toolbar-filter {
        flex: 1;
        width: auto;
        min-width: 0;
        height: 39px;
    }

    .reset-filter-button {
        height: 39px;
    }

    .content-section {
        padding: 18px 14px;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 13px;
    }

    .product-info {
        padding: 11px 9px;
    }

    .product-title {
        font-size: 10px;
    }

    .price-label,
    .price-value {
        font-size: 8px;
    }

    .product-prices {
        gap: 6px;
    }

    .product-actions {
        gap: 6px;
    }

    .edit-button,
    .delete-button,
    .clone-button {
        height: 29px;
        font-size: 9px;
    }
}

@media (max-width: 380px) {
    .header-actions {
        gap: 7px;
    }

    .category-button,
    .add-button {
        padding: 0 10px;
        font-size: 10px;
    }

    .product-grid {
        gap: 9px;
    }

    .product-info {
        padding: 9px 7px;
    }

    .price-label,
    .price-value {
        font-size: 7.5px;
    }

    .toolbar-search input {
        font-size: 10px;
    }

    .toolbar-filter select {
        font-size: 10px;
    }
}

/* Modal Responsive */
@media (max-width: 420px) {
    .delete-modal {
        max-width: 330px;
        padding: 26px 21px 21px;
        border-radius: 17px;
    }

    .modal-actions {
        gap: 8px;
    }

    .modal-cancel-button,
    .modal-confirm-button {
        height: 41px;
        font-size: 11px;
    }
}
</style>
