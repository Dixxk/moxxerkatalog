
<script setup>
import { computed, ref, onBeforeUnmount } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import OwnerSidebar from '@/Components/OwnerSidebar.vue'

const props = defineProps({
    owner: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
})

const search = ref('')
const showModal = ref(false)
const editingCategory = ref(null)
const showDeleteModal = ref(false)
const deletingCategory = ref(null)
const notification = ref('')
let notificationTimer = null

const form = useForm({
    name: '',
})

const filteredCategories = computed(() => {
    const keyword = search.value.trim().toLowerCase()

    if (!keyword) return props.categories

    return props.categories.filter(category =>
        String(category.name ?? '').toLowerCase().includes(keyword)
    )
})

const showNotification = (message) => {
    notification.value = message

    clearTimeout(notificationTimer)

    notificationTimer = setTimeout(() => {
        notification.value = ''
    }, 3000)
}

onBeforeUnmount(() => {
    clearTimeout(notificationTimer)
})

const openCreateModal = () => {
    editingCategory.value = null
    form.reset()
    form.clearErrors()
    showModal.value = true
}

const openEditModal = (category) => {
    editingCategory.value = category
    form.clearErrors()
    form.name = category.name
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingCategory.value = null
    form.reset()
    form.clearErrors()
}

const submitForm = () => {
    if (!form.name.trim()) {
        form.setError('name', 'Nama kategori belum diisi.')
        return
    }

    if (editingCategory.value) {
        form.patch(route('owner.categories.update', editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
                showNotification('Kategori berhasil diperbarui.')
            },
        })
    } else {
        form.post(route('owner.categories.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal()
                showNotification('Kategori berhasil ditambahkan.')
            },
        })
    }
}

const openDeleteModal = (category) => {
    deletingCategory.value = category
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    deletingCategory.value = null
}

const deleteCategory = () => {
    if (!deletingCategory.value) return

    router.delete(
        route('owner.categories.destroy', deletingCategory.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal()
                showNotification('Kategori berhasil dihapus.')
            },
        }
    )
}

const formatDate = (date) => {
    if (!date) return '-'

    return new Date(date).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>

<template>
    <Head title="Kategori Produk — MOXXER" />

    <main class="owner-layout">
        <OwnerSidebar :owner="props.owner" />

        <section class="main-content">
            <header class="page-header">
                <div>
                    <span class="eyebrow">MANAJEMEN PRODUK</span>
                    <h1>Kategori Produk</h1>
                    <p>Kelola kategori untuk mengorganisasi produk MOXXER.</p>
                </div>

                <button
                    class="add-button"
                    type="button"
                    @click="openCreateModal"
                >
                    <span>+</span>
                    Tambah Kategori
                </button>
            </header>

            <section class="content-section">
                <div class="section-heading">
                    <div>
                        <h2>Daftar Kategori</h2>
                        <p>{{ props.categories.length }} kategori terdaftar</p>
                    </div>

                    <label class="search-box">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        >
                            <circle cx="11" cy="11" r="7" />
                            <path d="m16 16 4 4" />
                        </svg>

                        <input
                            v-model="search"
                            type="search"
                            placeholder="Cari kategori..."
                        />
                    </label>
                </div>

                <div class="list-header">
                    <span>Nama Kategori</span>
                    <span>Tanggal Dibuat</span>
                    <span class="action-heading">Aksi</span>
                </div>

                <template v-if="filteredCategories.length">
                    <div
                        v-for="(category, index) in filteredCategories"
                        :key="category.id"
                        class="category-row"
                    >
                        <div class="category-name">
                            <span class="category-number">
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>

                            <span>{{ category.name }}</span>
                        </div>

                        <span class="category-date">
                            {{ formatDate(category.created_at) }}
                        </span>

                        <div class="row-actions">
                            <button
                                type="button"
                                class="action-button edit-button"
                                @click="openEditModal(category)"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m14 5 5 5" />
                                    <path d="M4 20h4l11-11a2.1 2.1 0 0 0-4-4L4 16v4Z" />
                                </svg>
                                Edit
                            </button>

                            <button
                                type="button"
                                class="action-button delete-button"
                                @click="openDeleteModal(category)"
                            >
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="m19 6-1 14H6L5 6" />
                                    <path d="M10 11v5M14 11v5" />
                                </svg>
                                Hapus
                            </button>
                        </div>
                    </div>
                </template>

                <div v-else class="empty-state">
                    <div class="empty-icon">
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="3" />
                            <path d="M8 9h8M8 13h5" />
                        </svg>
                    </div>

                    <h3>
                        {{ search ? 'Kategori tidak ditemukan' : 'Belum Ada Kategori' }}
                    </h3>

                    <p>
                        {{
                            search
                                ? 'Coba gunakan kata kunci pencarian yang lain.'
                                : 'Tambahkan kategori pertama untuk mulai mengorganisasi produk.'
                        }}
                    </p>

                    <button
                        v-if="!search"
                        type="button"
                        class="empty-add-button"
                        @click="openCreateModal"
                    >
                        + Tambah Kategori
                    </button>
                </div>
            </section>
        </section>

        <!-- Modal Tambah / Edit -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showModal"
                    class="modal-overlay"
                    @click.self="closeModal"
                >
                    <div class="modal-card">
                        <div class="modal-top">
                            <div>
                                <span class="modal-eyebrow">
                                    MANAJEMEN KATEGORI
                                </span>

                                <h2>
                                    {{
                                        editingCategory
                                            ? 'Edit Kategori'
                                            : 'Tambah Kategori'
                                    }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                class="close-button"
                                @click="closeModal"
                            >
                                ×
                            </button>
                        </div>

                        <form @submit.prevent="submitForm">
                            <div class="form-group">
                                <label for="category-name">
                                    Nama Kategori
                                </label>

                                <input
                                    id="category-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Contoh: Sepatu Formal"
                                    maxlength="255"
                                    :class="{
                                        'input-error': form.errors.name
                                    }"
                                    autofocus
                                />

                                <span
                                    v-if="form.errors.name"
                                    class="error-message"
                                >
                                    {{ form.errors.name }}
                                </span>
                            </div>

                            <div class="modal-actions">
                                <button
                                    type="button"
                                    class="cancel-button"
                                    @click="closeModal"
                                >
                                    Batal
                                </button>

                                <button
                                    type="submit"
                                    class="save-button"
                                    :disabled="form.processing"
                                >
                                    {{
                                        form.processing
                                            ? 'Menyimpan...'
                                            : editingCategory
                                                ? 'Simpan Perubahan'
                                                : 'Tambah Kategori'
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Modal Hapus -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="showDeleteModal"
                    class="modal-overlay"
                    @click.self="closeDeleteModal"
                >
                    <div class="modal-card delete-modal">
                        <div class="delete-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 8v4" />
                                <path d="M12 16h.01" />
                                <circle cx="12" cy="12" r="9" />
                            </svg>
                        </div>

                        <h2>Hapus Kategori?</h2>

                        <p>
                            Apakah kamu yakin ingin menghapus kategori
                            <strong>{{ deletingCategory?.name }}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>

                        <div class="modal-actions">
                            <button
                                type="button"
                                class="cancel-button"
                                @click="closeDeleteModal"
                            >
                                Batal
                            </button>

                            <button
                                type="button"
                                class="confirm-delete-button"
                                @click="deleteCategory"
                            >
                                Hapus Kategori
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Notifikasi -->
        <Teleport to="body">
            <Transition name="toast">
                <div
                    v-if="notification"
                    class="toast-notification"
                >
                    <span class="toast-check">✓</span>
                    {{ notification }}
                </div>
            </Transition>
        </Teleport>
    </main>
</template>

<style scoped>
* {
    box-sizing: border-box;
}

/* Main Layout */
.owner-layout {
    display: flex;
    align-items: flex-start;
    width: 100%;
    min-height: 100vh;
    background: #fafaf8;
    color: #171717;
    font-family: 'Inter', sans-serif;
}

.main-content {
    position: relative;
    flex: 1;
    min-width: 0;
    min-height: 100vh;
    margin-left: 0;
    padding: 48px 52px;
}

/* Page Header */
.page-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 38px;
}

.eyebrow,
.modal-eyebrow {
    display: block;
    margin-bottom: 12px;
    color: #92928d;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
}

.page-header h1 {
    margin: 0;
    font-size: 30px;
    font-weight: 650;
    letter-spacing: -1.2px;
}

.page-header p {
    margin: 9px 0 0;
    color: #858580;
    font-size: 13px;
}

/* Add Button */
.add-button,
.empty-add-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 43px;
    padding: 0 19px;
    border: 0;
    border-radius: 7px;
    background: #171717;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s;
}

.add-button:hover,
.empty-add-button:hover {
    background: #383838;
}

.add-button span {
    font-size: 19px;
    font-weight: 400;
    line-height: 1;
}

/* Content Section */
.content-section {
    padding: 27px;
    border: 1px solid #ebebe7;
    border-radius: 10px;
    background: #fff;
}

.section-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
}

.section-heading h2 {
    margin: 0;
    font-size: 15px;
    font-weight: 650;
}

.section-heading p {
    margin: 6px 0 0;
    color: #92928d;
    font-size: 11px;
}

/* Search */
.search-box {
    display: flex;
    align-items: center;
    gap: 9px;
    width: 245px;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #e9e9e5;
    border-radius: 6px;
    background: #fff;
}

.search-box svg {
    width: 16px;
    height: 16px;
    color: #92928d;
    flex-shrink: 0;
}

.search-box input {
    width: 100%;
    border: 0;
    outline: 0;
    background: transparent;
    color: #171717;
    font: inherit;
    font-size: 11px;
}

.search-box input::placeholder {
    color: #aaa9a3;
}

/* Category Table */
.list-header,
.category-row {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) 220px 160px;
    align-items: center;
    gap: 20px;
}

.list-header {
    min-height: 43px;
    padding: 0 17px;
    border-top: 1px solid #eeeeea;
    border-bottom: 1px solid #eeeeea;
    background: #fafaf9;
    color: #92928d;
    font-size: 10px;
    font-weight: 600;
}

.action-heading {
    text-align: right;
}

.category-row {
    min-height: 66px;
    padding: 0 17px;
    border-bottom: 1px solid #f0f0ed;
}

.category-row:last-child {
    border-bottom: 0;
}

.category-name {
    display: flex;
    align-items: center;
    gap: 13px;
    min-width: 0;
    font-size: 12px;
    font-weight: 600;
}

.category-number {
    color: #b1b1aa;
    font-size: 10px;
    font-weight: 500;
}

.category-date {
    color: #858580;
    font-size: 11px;
}

/* Row Actions */
.row-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 13px;
}

.action-button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 0;
    border: 0;
    background: transparent;
    font: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
}

.action-button svg {
    width: 14px;
    height: 14px;
}

.edit-button {
    color: #555550;
}

.delete-button {
    color: #b64d4d;
}

.action-button:hover {
    opacity: .65;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 65px 20px;
    text-align: center;
}

.empty-icon {
    display: grid;
    width: 48px;
    height: 48px;
    place-items: center;
    margin-bottom: 17px;
    border-radius: 12px;
    background: #f5f5f2;
    color: #777770;
}

.empty-icon svg {
    width: 23px;
    height: 23px;
}

.empty-state h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 650;
}

.empty-state p {
    margin: 8px 0 18px;
    color: #92928d;
    font-size: 11px;
}

.empty-add-button {
    min-height: 37px;
    font-size: 11px;
}

/* Modal */
.modal-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgb(0 0 0 / 35%);
    backdrop-filter: blur(4px);
}

.modal-card {
    width: 100%;
    max-width: 430px;
    padding: 27px;
    border: 1px solid #ededeb;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 20px 70px rgb(0 0 0 / 12%);
}

.modal-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
}

.modal-eyebrow {
    margin-bottom: 8px;
    font-size: 9px;
}

.modal-top h2,
.delete-modal h2 {
    margin: 0;
    font-size: 19px;
    font-weight: 650;
    letter-spacing: -.5px;
}

.close-button {
    width: 30px;
    height: 30px;
    border: 0;
    border-radius: 50%;
    background: #f5f5f2;
    color: #555;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
}

/* Form */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.form-group label {
    font-size: 11px;
    font-weight: 600;
}

.form-group input {
    width: 100%;
    height: 43px;
    padding: 0 13px;
    border: 1px solid #e6e6e2;
    border-radius: 6px;
    outline: none;
    color: #171717;
    font: inherit;
    font-size: 12px;
    transition: border .2s;
}

.form-group input:focus {
    border-color: #777;
}

.form-group input::placeholder {
    color: #aaa9a3;
}

.form-group input.input-error {
    border-color: #c65b5b;
}

.error-message {
    color: #c65b5b;
    font-size: 10px;
}

/* Modal Actions */
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 27px;
}

.cancel-button,
.save-button,
.confirm-delete-button {
    min-height: 39px;
    padding: 0 16px;
    border: 0;
    border-radius: 6px;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
}

.cancel-button {
    background: #f2f2ef;
    color: #555;
}

.save-button {
    background: #171717;
    color: #fff;
}

.save-button:disabled {
    opacity: .55;
    cursor: not-allowed;
}

/* Delete Modal */
.delete-modal {
    max-width: 390px;
    text-align: center;
}

.delete-icon {
    display: grid;
    width: 45px;
    height: 45px;
    place-items: center;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: #fff1f0;
    color: #bd5555;
}

.delete-icon svg {
    width: 22px;
    height: 22px;
}

.delete-modal p {
    margin: 12px 0 0;
    color: #777;
    font-size: 12px;
    line-height: 1.7;
}

.delete-modal p strong {
    color: #171717;
}

.delete-modal .modal-actions {
    justify-content: center;
}

.confirm-delete-button {
    background: #bd4545;
    color: #fff;
}

/* Toast */
.toast-notification {
    position: fixed;
    z-index: 1100;
    right: 25px;
    bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: calc(100vw - 40px);
    padding: 13px 18px;
    border: 1px solid #e9e9e5;
    border-radius: 8px;
    background: #fff;
    color: #252525;
    box-shadow: 0 8px 30px rgb(0 0 0 / 9%);
    font-size: 12px;
}

.toast-check {
    display: grid;
    width: 20px;
    height: 20px;
    place-items: center;
    border-radius: 50%;
    background: #e9f5eb;
    color: #38834b;
    font-size: 12px;
    font-weight: 700;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active,
.toast-enter-active,
.toast-leave-active {
    transition: opacity .2s, transform .2s;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-card {
    transform: translateY(10px);
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(8px);
}

/* Responsive */
@media (max-width: 1000px) {
    .main-content {
        padding: 38px 28px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 30px 20px;
    }

    .page-header {
        align-items: flex-start;
        flex-direction: column;
    }

    .page-header h1 {
        font-size: 25px;
    }

    .content-section {
        padding: 19px 15px;
    }

    .section-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .search-box {
        width: 100%;
    }

    .list-header,
    .category-row {
        grid-template-columns: minmax(130px, 1fr) 125px;
        gap: 10px;
    }

    .list-header span:nth-child(2),
    .category-date {
        display: none;
    }

    .action-heading {
        display: block !important;
    }

    .category-row {
        min-height: 65px;
    }

    .row-actions {
        gap: 10px;
    }
}

@media (max-width: 400px) {
    .main-content {
        padding: 25px 14px;
    }

    .category-name {
        gap: 8px;
        font-size: 11px;
    }

    .row-actions {
        gap: 7px;
    }

    .action-button {
        font-size: 9px;
    }
}
</style>
