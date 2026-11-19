
<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Navbar from '@/Components/Navbar.vue'

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const search = ref('')
const selectedCategory = ref('all')

const formatPrice = (value) => {
    const price = Number(value)

    if (!Number.isFinite(price)) {
        return null
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price)
}

const getPriceRange = (combinations, field) => {
    if (!Array.isArray(combinations) || combinations.length === 0) {
        return 'Belum tersedia'
    }

    const prices = combinations
        .map((combination) => combination?.[field])
        .filter((value) => value !== null && value !== undefined && value !== '')
        .map((value) => Number(value))
        .filter((price) => Number.isFinite(price) && price >= 0)

    if (prices.length === 0) {
        return 'Belum tersedia'
    }

    const minimum = Math.min(...prices)
    const maximum = Math.max(...prices)

    if (minimum === maximum) {
        return formatPrice(minimum)
    }

    return `${formatPrice(minimum)} – ${formatPrice(maximum)}`
}

const getImageUrl = (images) => {
    if (!Array.isArray(images) || images.length === 0 || !images[0]) {
        return null
    }

    const image = images[0]

    if (/^https?:\/\//i.test(image)) {
        return image
    }

    return `/storage/${String(image).replace(/^\/+/, '')}`
}

const hasHostLivePrice = (combinations) => {
    if (!Array.isArray(combinations)) {
        return false
    }

    return combinations.some((combination) => {
        const value = combination?.price_host_live

        return value !== null && value !== undefined && value !== '' &&
            Number.isFinite(Number(value)) && Number(value) >= 0
    })
}

const filteredProducts = computed(() => {
    const keyword = search.value.trim().toLowerCase()

    return props.products.filter((product) => {
        const matchesHostLive = hasHostLivePrice(product.combinations)

        const matchesSearch = !keyword ||
            String(product.name ?? '').toLowerCase().includes(keyword)

        const matchesCategory = selectedCategory.value === 'all' ||
            String(product.category_id) === selectedCategory.value

        return matchesHostLive && matchesSearch && matchesCategory
    })
})

const productCount = computed(() => filteredProducts.value.length)

const resetFilters = () => {
    search.value = ''
    selectedCategory.value = 'all'
}
</script>

<template>
    <Head title="Host Live Catalog — MOXXER" />

    <main class="catalog-page">
        <!-- NAVBAR -->
        <Navbar :can-login="true" :can-register="true" />

        <!-- SEARCH & FILTER -->
        <div class="catalog-toolbar">
            <div class="toolbar-inner">
                <!-- SEARCH -->
                <div class="search-box">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-4-4" />
                    </svg>

                    <input
                        v-model="search"
                        type="search"
                        placeholder="Search products"
                        aria-label="Search products"
                    />

                    <button
                        v-if="search"
                        type="button"
                        class="clear-search"
                        aria-label="Clear search"
                        @click="search = ''"
                    >
                        ×
                    </button>
                </div>

                <!-- CATEGORY FILTER -->
                <div class="filter-box">
                    <svg
                        class="filter-icon"
                        xmlns="http://www.w3.org/2000/svg"
                        width="17"
                        height="17"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 7h16" />
                        <path d="M7 12h10" />
                        <path d="M10 17h4" />
                    </svg>

                    <span class="filter-label">Filter</span>

                    <span class="filter-divider"></span>

                    <select
                        v-model="selectedCategory"
                        aria-label="Filter by category"
                    >
                        <option value="all">All Categories</option>

                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <svg
                        class="filter-chevron"
                        xmlns="http://www.w3.org/2000/svg"
                        width="13"
                        height="13"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- PRODUCTS -->
        <section id="catalog-products" class="products-section">
            <div class="section-heading">
                <div>
                    <p class="section-eyebrow">MOXXER HOST LIVE CATALOG</p>
                    <h2>Host Live Catalog</h2>
                </div>

                <span class="product-count">
                    {{ String(productCount).padStart(2, '0') }} PRODUCTS
                </span>
            </div>

            <div v-if="filteredProducts.length" class="product-grid">
                <article
                    v-for="product in filteredProducts"
                    :key="product.unique_id"
                    class="product-card"
                >
                    <div class="product-image">
                        <img
                            v-if="getImageUrl(product.images)"
                            :src="getImageUrl(product.images)"
                            :alt="product.name"
                            loading="lazy"
                        />

                        <div v-else class="image-placeholder">
                            <span>MOXXER.</span>
                            <small>IMAGE NOT AVAILABLE</small>
                        </div>
                    </div>

                    <div class="product-information">
                        <div class="product-title-row">
                            <h3>{{ product.name }}</h3>
                        </div>

                        <div class="product-prices">
                            <div class="price-item">
                                <span>HARGA HOST LIVE</span>

                                <strong>
                                    {{ getPriceRange(product.combinations, 'price_host_live') }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <!-- EMPTY / NO SEARCH RESULT -->
            <div v-else class="empty-catalog">
                <span class="empty-mark">MOXXER.</span>

                <template v-if="search || selectedCategory !== 'all'">
                    <h3>No products found.</h3>

                    <p>
                        No products match your search or selected category.
                        Try changing your filters.
                    </p>

                    <button
                        type="button"
                        class="reset-filters"
                        @click="resetFilters"
                    >
                        CLEAR FILTERS
                    </button>
                </template>

                <template v-else>
                    <h3>No products yet.</h3>

                    <p>
                        The Host Live collection is currently being prepared.
                        Please check back soon.
                    </p>
                </template>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="footer">
            <div class="footer-main">
                <div class="footer-brand">
                    <Link href="/" class="footer-logo">
                        MOXXER<span>.</span>
                    </Link>

                    <p>
                        Timeless footwear<br />
                        for modern confidence.
                    </p>
                </div>

                <div class="footer-column">
                    <span class="footer-heading">EXPLORE</span>
                    <Link href="/">Home</Link>
                    <a href="#catalog-products">Catalog</a>
                    <Link href="/#philosophy">Our Philosophy</Link>
                </div>

                <div class="footer-column">
                    <span class="footer-heading">ACCOUNT</span>
                    <Link :href="route('login')">Sign in</Link>

                    <Link
                        v-if="$page.props.canRegister"
                        :href="route('register')"
                    >
                        Create account
                    </Link>
                </div>

                <div class="footer-column">
                    <span class="footer-heading">THE MOXXER IDENTITY</span>
                    <p>Made to be remembered.</p>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ new Date().getFullYear() }} MOXXER. ALL RIGHTS RESERVED.</span>
                <span>TIMELESS BY DESIGN.</span>
                <a href="#">BACK TO TOP ↑</a>
            </div>
        </footer>
    </main>
</template>

<style scoped>
:global(*) {
    box-sizing: border-box;
}

:global(html) {
    scroll-behavior: smooth;
}

:global(body) {
    margin: 0;
    background: #f5f5f2;
    color: #171717;
    font-family: Inter, Helvetica, Arial, sans-serif;
}

.catalog-page {
    min-height: 100vh;
    background: #f5f5f2;
}

/* ========================================
   SEARCH & FILTER — APPLE INSPIRED
======================================== */

.catalog-toolbar {
    position: sticky;
    top: 86px;
    z-index: 90;
    width: 100%;
    padding: 17px 7%;
    background: rgb(245 245 242 / 88%);
    border-bottom: 1px solid rgb(0 0 0 / 5%);
    backdrop-filter: saturate(180%) blur(20px);
    -webkit-backdrop-filter: saturate(180%) blur(20px);
}

.toolbar-inner {
    width: 100%;
    max-width: none;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin: 0;
}

/* SEARCH */

.search-box {
    width: 100%;
    max-width: 460px;
    height: 46px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 15px;
    border: 1px solid rgb(0 0 0 / 7%);
    border-radius: 13px;
    background: rgb(255 255 255 / 88%);
    color: #86868b;
    box-shadow:
        0 1px 2px rgb(0 0 0 / 3%),
        0 3px 10px rgb(0 0 0 / 2%);
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.search-box:hover {
    border-color: rgb(0 0 0 / 13%);
    background: #fff;
}

.search-box:focus-within {
    border-color: #a1a1a6;
    background: #fff;
    box-shadow:
        0 0 0 3px rgb(0 0 0 / 4%),
        0 3px 10px rgb(0 0 0 / 3%);
}

.search-box > svg {
    flex-shrink: 0;
    color: #86868b;
}

.search-box input {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    padding: 0;
    border: none;
    outline: none;
    background: transparent;
    color: #1d1d1f;
    font-family: inherit;
    font-size: 12px;
    font-weight: 400;
    letter-spacing: .1px;
}

.search-box input::placeholder {
    color: #98989d;
}

.search-box input::-webkit-search-cancel-button {
    display: none;
}

.clear-search {
    width: 21px;
    height: 21px;
    flex-shrink: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 0;
    border: none;
    border-radius: 50%;
    background: #e5e5ea;
    color: #68686d;
    font-size: 17px;
    line-height: 1;
    cursor: pointer;
    transition: background .2s ease;
}

.clear-search:hover {
    background: #d2d2d7;
}

/* FILTER BUTTON */

.filter-box {
    position: relative;
    min-width: 245px;
    height: 46px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 15px;
    border: 1px solid rgb(0 0 0 / 7%);
    border-radius: 13px;
    background: rgb(255 255 255 / 88%);
    color: #1d1d1f;
    box-shadow:
        0 1px 2px rgb(0 0 0 / 3%),
        0 3px 10px rgb(0 0 0 / 2%);
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.filter-box:hover {
    border-color: rgb(0 0 0 / 13%);
    background: #fff;
}

.filter-box:focus-within {
    border-color: #a1a1a6;
    background: #fff;
    box-shadow: 0 0 0 3px rgb(0 0 0 / 4%);
}

.filter-icon {
    flex-shrink: 0;
    color: #66666b;
}

.filter-label {
    flex-shrink: 0;
    color: #424245;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: -.1px;
}

.filter-divider {
    width: 1px;
    height: 19px;
    flex-shrink: 0;
    background: #e5e5ea;
}

.filter-box select {
    flex: 1;
    width: 100%;
    min-width: 0;
    height: 100%;
    appearance: none;
    -webkit-appearance: none;
    border: none;
    outline: none;
    background: transparent;
    color: #6e6e73;
    font-family: inherit;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
}

.filter-box select:focus {
    color: #1d1d1f;
}

.filter-box select option {
    background: #fff;
    color: #1d1d1f;
}

.filter-chevron {
    flex-shrink: 0;
    color: #86868b;
    pointer-events: none;
}

/* ========================================
   PRODUCTS
======================================== */

.products-section {
    padding: 70px 7% 120px;
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 38px;
}

.section-eyebrow {
    margin: 0 0 12px;
    color: #777;
    font-size: 10px;
    font-weight: 600;
    letter-spacing: 2px;
}

.section-heading h2 {
    margin: 0;
    font-size: 35px;
    font-weight: 500;
    letter-spacing: -1.5px;
}

.product-count {
    color: #777;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1px;
}

/* GRID - DESKTOP 5 COLUMNS */

.product-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 32px 18px;
}

.product-card {
    min-width: 0;
}

.product-image {
    position: relative;
    width: 100%;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    background: #eaeae5;
}

.product-image img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .55s cubic-bezier(.2, .7, .2, 1);
}

.product-card:hover .product-image img {
    transform: scale(1.045);
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 8px;
    color: #a4a49d;
}

.image-placeholder span {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.image-placeholder small {
    font-size: 7px;
    letter-spacing: 1px;
}

.product-information {
    padding-top: 14px;
}

.product-title-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 7px;
    margin-bottom: 14px;
}

.product-title-row h3 {
    min-width: 0;
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: -.4px;
    overflow-wrap: anywhere;
}

.product-prices {
    border-top: 1px solid #deded8;
}

.price-item {
    min-height: 36px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 5px;
    border-bottom: 1px solid #deded8;
}

.price-item span {
    color: #777;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: .4px;
}

.price-item strong {
    min-width: 0;
    color: #222;
    font-size: 9px;
    font-weight: 600;
    text-align: right;
    overflow-wrap: anywhere;
}

/* ========================================
   EMPTY STATE
======================================== */

.empty-catalog {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
    border-top: 1px solid #deded8;
    border-bottom: 1px solid #deded8;
}

.empty-mark {
    margin-bottom: 20px;
    color: #aaa;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: -1px;
}

.empty-catalog h3 {
    margin: 0;
    font-size: 25px;
    font-weight: 500;
    letter-spacing: -1px;
}

.empty-catalog p {
    max-width: 320px;
    color: #777;
    font-size: 11px;
    line-height: 1.8;
}

.reset-filters {
    margin-top: 16px;
    padding: 12px 20px;
    border: 1px solid #171717;
    border-radius: 8px;
    background: #171717;
    color: #fff;
    font-family: inherit;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1px;
    cursor: pointer;
    transition: background .2s ease, color .2s ease;
}

.reset-filters:hover {
    background: transparent;
    color: #171717;
}

/* ========================================
   FOOTER
======================================== */

.footer {
    padding: 65px 7% 25px;
    background: #171717;
    color: #f5f5f2;
}

.footer-main {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
    gap: 35px;
    padding-bottom: 70px;
}

.footer-logo {
    color: #f5f5f2;
    text-decoration: none;
    font-size: 25px;
    font-weight: 800;
    letter-spacing: -1.7px;
}

.footer-logo span {
    color: #888;
}

.footer-brand p,
.footer-column p {
    margin: 17px 0 0;
    color: #999;
    font-size: 10px;
    line-height: 1.9;
}

.footer-column {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 13px;
}

.footer-heading {
    margin-bottom: 8px;
    color: #777;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: 1.2px;
}

.footer-column a {
    color: #d0d0ca;
    text-decoration: none;
    font-size: 10px;
    transition: color .2s ease;
}

.footer-column a:hover {
    color: white;
}

.footer-column p {
    margin: 0;
}

.footer-bottom {
    min-height: 48px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    border-top: 1px solid #383838;
    color: #888;
    font-size: 8px;
    letter-spacing: .8px;
}

.footer-bottom a {
    color: #aaa;
    text-decoration: none;
}

/* ========================================
   TABLET
======================================== */

@media (max-width: 900px) {
    .catalog-toolbar {
        padding-right: 5%;
        padding-left: 5%;
    }

    .products-section {
        padding-right: 5%;
        padding-left: 5%;
    }

    .product-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 24px 10px;
    }

    .product-information {
        padding-top: 10px;
    }

    .product-title-row h3 {
        font-size: 11px;
    }

    .product-label {
        font-size: 6px;
    }

    .price-item {
        min-height: 32px;
        gap: 3px;
    }

    .price-item span {
        font-size: 7px;
    }

    .price-item strong {
        font-size: 8px;
    }

    .footer {
        padding-right: 5%;
        padding-left: 5%;
    }
}

/* ========================================
   MOBILE
======================================== */

@media (max-width: 600px) {
    .catalog-toolbar {
        top: 72px;
        padding: 12px 16px;
    }

    .toolbar-inner {
        flex-direction: column;
        align-items: stretch;
        gap: 9px;
    }

    .search-box {
        max-width: none;
        height: 43px;
        border-radius: 12px;
    }

    .filter-box {
        width: 100%;
        min-width: 0;
        height: 43px;
        border-radius: 12px;
    }

    .products-section {
        padding: 45px 16px 85px;
    }

    .section-heading {
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 25px;
    }

    .section-heading h2 {
        font-size: 25px;
    }

    .product-count {
        font-size: 7px;
    }

    .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 28px 12px;
    }

    .product-image {
        aspect-ratio: 1 / 1;
    }

    .product-information {
        padding-top: 10px;
    }

    .product-title-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 5px;
        margin-bottom: 10px;
    }

    .product-title-row h3 {
        font-size: 11px;
        line-height: 1.4;
    }

    .product-label {
        font-size: 6px;
    }

    .price-item {
        min-height: 32px;
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        gap: 5px;
    }

    .price-item span {
        font-size: 7px;
    }

    .price-item strong {
        width: auto;
        font-size: 8px;
        text-align: right;
    }

    .image-placeholder {
        gap: 5px;
    }

    .image-placeholder span {
        font-size: 15px;
    }

    .image-placeholder small {
        font-size: 6px;
        letter-spacing: .5px;
    }

    .footer {
        padding: 50px 22px 20px;
    }

    .footer-main {
        grid-template-columns: 1fr 1fr;
        gap: 40px 25px;
        padding-bottom: 45px;
    }

    .footer-bottom {
        flex-wrap: wrap;
        justify-content: flex-start;
        padding-top: 18px;
        font-size: 7px;
    }
}
</style>
