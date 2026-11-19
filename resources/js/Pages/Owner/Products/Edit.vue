<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
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
    product: {
        type: Object,
        required: true,
    },
})

const createDragKey = () => {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
        return crypto.randomUUID()
    }

    return `${Date.now()}-${Math.random().toString(36).slice(2)}`
}

/*
|--------------------------------------------------------------------------
| INITIAL PRODUCT DATA
|--------------------------------------------------------------------------
*/

const getExistingImageUrl = (path, index) => {
    const imageUrls = props.product.image_urls

    if (Array.isArray(imageUrls) && imageUrls[index]) {
        return imageUrls[index]
    }

    if (imageUrls && typeof imageUrls === 'object' && imageUrls[path]) {
        return imageUrls[path]
    }

    if (/^https?:\/\//i.test(path)) {
        return path
    }

    if (path.startsWith('/')) {
        return path
    }

    return `/storage/${path}`
}

const initialImages = Array.isArray(props.product.images)
    ? props.product.images
    : []

const imageItems = ref(
    initialImages.map((path, index) => ({
        id: createDragKey(),
        type: 'existing',
        path,
        name: path.split('/').pop() || `Foto ${index + 1}`,
        url: getExistingImageUrl(path, index),
    }))
)

const initialVariants = Array.isArray(props.product.variants)
    ? props.product.variants
    : []

const initialCombinations = Array.isArray(props.product.combinations)
    ? props.product.combinations
    : []

const form = useForm({
    name: props.product.name ?? '',
    category_id: props.product.category_id ?? '',
    description: props.product.description ?? '',
    images: [],
    existing_images: [...initialImages],
    image_order: [],
    variants: initialVariants.map((variant) => ({
        _dragKey: createDragKey(),
        _optionDragKeys: (variant.options || []).map(() => createDragKey()),
        name: variant.name ?? '',
        options: Array.isArray(variant.options)
            ? [...variant.options]
            : [],
    })),
    combinations: initialCombinations.map((combination) => ({
        sku: combination.sku ?? '',
        values: { ...(combination.values || {}) },
        price_b2b: combination.price_b2b ?? '',
        price_dropship: combination.price_dropship ?? '',
        price_host_live: combination.price_host_live ?? '',
    })),
})

const fileInput = ref(null)

const draggedImageKey = ref(null)
const dragOverImageKey = ref(null)

const draggedVariantKey = ref(null)
const draggedOption = ref(null)

const filterOpen = ref(false)
const selectedVariantFilters = ref({})

const bulkPrices = ref({
    sku: '',
    price_b2b: '',
    price_dropship: '',
    price_host_live: '',
})

/*
|--------------------------------------------------------------------------
| IMAGE PAYLOAD SYNCHRONIZATION
|--------------------------------------------------------------------------
*/

const syncImagePayload = () => {
    const existingImages = []
    const newImages = []

    imageItems.value.forEach((image) => {
        if (image.type === 'existing') {
            existingImages.push(image.path)
        } else {
            newImages.push(image.file)
        }
    })

    form.existing_images = existingImages
    form.images = newImages

    let newImageIndex = 0

    form.image_order = imageItems.value.map((image) => {
        if (image.type === 'existing') {
            return {
                type: 'existing',
                path: image.path,
            }
        }

        const order = {
            type: 'new',
            index: newImageIndex,
        }

        newImageIndex++

        return order
    })
}

/*
|--------------------------------------------------------------------------
| APPLE STYLE NOTIFICATION
|--------------------------------------------------------------------------
*/

const notification = ref({
    show: false,
    title: '',
    message: '',
})

let notificationTimer = null

const showNotification = (message, title = 'Data belum lengkap') => {
    if (notificationTimer) {
        clearTimeout(notificationTimer)
    }

    notification.value = {
        show: true,
        title,
        message,
    }

    notificationTimer = setTimeout(() => {
        notification.value.show = false
        notificationTimer = null
    }, 4000)
}

const closeNotification = () => {
    notification.value.show = false

    if (notificationTimer) {
        clearTimeout(notificationTimer)
        notificationTimer = null
    }
}

const focusInvalidField = async (targetId) => {
    if (!targetId) return

    await nextTick()

    const element = document.getElementById(targetId)

    if (!element) return

    element.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    })

    setTimeout(() => {
        element.focus?.({ preventScroll: true })
    }, 350)
}

/*
|--------------------------------------------------------------------------
| IMAGE SLOTS
|--------------------------------------------------------------------------
*/

const imageSlots = computed(() => {
    const occupiedSlots = imageItems.value.map((image, index) => ({
        key: image.id,
        index,
        image,
    }))

    const emptySlots = Array.from(
        { length: 10 - occupiedSlots.length },
        (_, index) => {
            const slotIndex = occupiedSlots.length + index

            return {
                key: `empty-${slotIndex}`,
                index: slotIndex,
                image: null,
            }
        }
    )

    return [...occupiedSlots, ...emptySlots]
})

/*
|--------------------------------------------------------------------------
| VARIANT FILTER
|--------------------------------------------------------------------------
*/

const variantFilterCount = computed(() => {
    return Object.values(selectedVariantFilters.value)
        .reduce((total, values) => total + values.length, 0)
})

const variantFilterOptions = computed(() => {
    return form.variants
        .map((variant) => ({
            name: variant.name.trim(),
            options: [...new Set(
                variant.options
                    .map((option) => option.trim())
                    .filter(Boolean)
            )],
        }))
        .filter((variant) => variant.name && variant.options.length)
})

const filteredCombinations = computed(() => {
    const activeFilters = Object.entries(selectedVariantFilters.value)
        .filter(([, values]) => values.length > 0)

    return form.combinations
        .map((combination, index) => ({
            combination,
            index,
        }))
        .filter(({ combination }) => {
            return activeFilters.every(([variantName, selectedValues]) => {
                return selectedValues.includes(
                    combination.values?.[variantName]
                )
            })
        })
})

const toggleVariantFilter = (variantName, option) => {
    const currentValues = [
        ...(selectedVariantFilters.value[variantName] || []),
    ]

    if (currentValues.includes(option)) {
        selectedVariantFilters.value = {
            ...selectedVariantFilters.value,
            [variantName]: currentValues.filter((value) => value !== option),
        }
    } else {
        selectedVariantFilters.value = {
            ...selectedVariantFilters.value,
            [variantName]: [...currentValues, option],
        }
    }
}

const isVariantFilterSelected = (variantName, option) => {
    return (selectedVariantFilters.value[variantName] || [])
        .includes(option)
}

const clearVariantFilters = () => {
    selectedVariantFilters.value = {}
}

const syncVariantFilters = (validVariants) => {
    const updatedFilters = {}

    validVariants.forEach((variant) => {
        const previousValues =
            selectedVariantFilters.value[variant.name] || []

        const availableValues = previousValues.filter((value) => {
            return variant.options.includes(value)
        })

        if (availableValues.length) {
            updatedFilters[variant.name] = availableValues
        }
    })

    selectedVariantFilters.value = updatedFilters
}

/*
|--------------------------------------------------------------------------
| AUTOMATIC COMBINATIONS
|--------------------------------------------------------------------------
*/

const getCombinationKey = (values) => {
    return JSON.stringify(
        Object.entries(values).sort(([a], [b]) => a.localeCompare(b))
    )
}

const getCombinationIndexKey = (combination, variants) => {
    const indexes = variants.map((variant) => {
        return variant.options.indexOf(
            combination.values?.[variant.name]
        )
    })

    if (indexes.some((index) => index < 0)) {
        return null
    }

    return JSON.stringify(indexes)
}

const lastVariantSnapshot = ref(
    form.variants.map((variant) => ({
        name: variant.name.trim(),
        options: [...new Set(
            variant.options
                .map((option) => option.trim())
                .filter(Boolean)
        )],
    }))
)

const syncCombinations = () => {
    const previousCombinations = form.combinations
    const previousSnapshot = lastVariantSnapshot.value

    const validVariants = form.variants.map((variant) => ({
        name: variant.name.trim(),
        options: [...new Set(
            variant.options
                .map((option) => option.trim())
                .filter(Boolean)
        )],
    }))

    const hasIncompleteVariant = validVariants.some((variant) => {
        return !variant.name || !variant.options.length
    })

    const hasDuplicateNames = new Set(
        validVariants.map((variant) => variant.name.toLowerCase())
    ).size !== validVariants.length

    if (!form.variants.length) {
        form.combinations = []
        selectedVariantFilters.value = {}
        lastVariantSnapshot.value = []
        return
    }

    if (hasIncompleteVariant || hasDuplicateNames) {
        form.combinations = []
        selectedVariantFilters.value = {}
        lastVariantSnapshot.value = validVariants
        return
    }

    syncVariantFilters(validVariants)

    const previousByValue = new Map()

    previousCombinations.forEach((combination) => {
        previousByValue.set(
            getCombinationKey(combination.values || {}),
            combination
        )
    })

    const canUseIndexFallback =
        previousSnapshot.length === validVariants.length &&
        previousSnapshot.every((variant, index) => {
            return variant.options.length === validVariants[index].options.length
        })

    const previousByIndex = new Map()

    if (canUseIndexFallback) {
        previousCombinations.forEach((combination) => {
            const key = getCombinationIndexKey(
                combination,
                previousSnapshot
            )

            if (key !== null) {
                previousByIndex.set(key, combination)
            }
        })
    }

    const generatedValues = []

    const generate = (variantIndex, currentValues) => {
        if (variantIndex >= validVariants.length) {
            generatedValues.push({ ...currentValues })
            return
        }

        const variant = validVariants[variantIndex]

        variant.options.forEach((option) => {
            generate(variantIndex + 1, {
                ...currentValues,
                [variant.name]: option,
            })
        })
    }

    generate(0, {})

    const newCombinations = generatedValues.map((values) => {
        const valueKey = getCombinationKey(values)

        let previous = previousByValue.get(valueKey)

        if (!previous && canUseIndexFallback) {
            const temporaryCombination = { values }

            const indexKey = getCombinationIndexKey(
                temporaryCombination,
                validVariants
            )

            previous = previousByIndex.get(indexKey)
        }

        return {
            sku: previous?.sku || '',
            values,
            price_b2b: previous?.price_b2b ?? '',
            price_dropship: previous?.price_dropship ?? '',
            price_host_live: previous?.price_host_live ?? '',
        }
    })

    form.combinations = newCombinations
    lastVariantSnapshot.value = validVariants
}

/*
|--------------------------------------------------------------------------
| VARIANT MANAGEMENT
|--------------------------------------------------------------------------
*/

const addVariant = () => {
    form.variants.push({
        _dragKey: createDragKey(),
        _optionDragKeys: [createDragKey()],
        name: '',
        options: [''],
    })

    syncCombinations()
}

const removeVariant = (index) => {
    form.variants.splice(index, 1)
    syncCombinations()
}

const addOption = (variantIndex) => {
    form.variants[variantIndex].options.push('')
    form.variants[variantIndex]._optionDragKeys.push(createDragKey())

    syncCombinations()
}

const removeOption = (variantIndex, optionIndex) => {
    const variant = form.variants[variantIndex]

    variant.options.splice(optionIndex, 1)
    variant._optionDragKeys.splice(optionIndex, 1)

    if (!variant.options.length) {
        variant.options.push('')
        variant._optionDragKeys.push(createDragKey())
    }

    syncCombinations()
}

/*
|--------------------------------------------------------------------------
| VARIANT DRAG & DROP
|--------------------------------------------------------------------------
*/

const startVariantDrag = (variantKey, event) => {
    draggedVariantKey.value = variantKey

    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', variantKey)
}

const moveVariantTo = (targetKey, event) => {
    const sourceKey = draggedVariantKey.value

    if (!sourceKey || sourceKey === targetKey) return

    const sourceIndex = form.variants.findIndex(
        (variant) => variant._dragKey === sourceKey
    )

    const targetIndex = form.variants.findIndex(
        (variant) => variant._dragKey === targetKey
    )

    if (sourceIndex < 0 || targetIndex < 0) return

    const targetElement = event.currentTarget
    const rect = targetElement.getBoundingClientRect()
    const afterTarget = event.clientY > rect.top + rect.height / 2

    const [movedVariant] = form.variants.splice(sourceIndex, 1)

    const currentTargetIndex = form.variants.findIndex(
        (variant) => variant._dragKey === targetKey
    )

    if (currentTargetIndex < 0) return

    const insertIndex = currentTargetIndex + (afterTarget ? 1 : 0)

    if (insertIndex === sourceIndex) {
        form.variants.splice(sourceIndex, 0, movedVariant)
        return
    }

    form.variants.splice(insertIndex, 0, movedVariant)

    syncCombinations()
}

const resetVariantDrag = () => {
    draggedVariantKey.value = null
}

/*
|--------------------------------------------------------------------------
| SUBVARIANT DRAG & DROP
|--------------------------------------------------------------------------
*/

const startOptionDrag = (variantKey, optionKey, event) => {
    draggedOption.value = {
        variantKey,
        optionKey,
    }

    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData(
        'text/plain',
        `${variantKey}:${optionKey}`
    )
}

const moveOptionTo = (variantKey, targetOptionKey, event) => {
    const source = draggedOption.value

    if (
        !source ||
        source.variantKey !== variantKey ||
        source.optionKey === targetOptionKey
    ) {
        return
    }

    const variant = form.variants.find(
        (item) => item._dragKey === variantKey
    )

    if (!variant) return

    const sourceIndex = variant._optionDragKeys.indexOf(source.optionKey)
    const targetIndex = variant._optionDragKeys.indexOf(targetOptionKey)

    if (sourceIndex < 0 || targetIndex < 0) return

    const targetElement = event.currentTarget
    const rect = targetElement.getBoundingClientRect()
    const afterTarget = event.clientY > rect.top + rect.height / 2

    const [movedOption] = variant.options.splice(sourceIndex, 1)
    const [movedKey] = variant._optionDragKeys.splice(sourceIndex, 1)

    const currentTargetIndex = variant._optionDragKeys.indexOf(targetOptionKey)

    if (currentTargetIndex < 0) return

    const insertIndex = currentTargetIndex + (afterTarget ? 1 : 0)

    if (insertIndex === sourceIndex) {
        variant.options.splice(sourceIndex, 0, movedOption)
        variant._optionDragKeys.splice(sourceIndex, 0, movedKey)
        return
    }

    variant.options.splice(insertIndex, 0, movedOption)
    variant._optionDragKeys.splice(insertIndex, 0, movedKey)

    syncCombinations()
}

const resetOptionDrag = () => {
    draggedOption.value = null
}

/*
|--------------------------------------------------------------------------
| BULK PRICE EDITOR
|--------------------------------------------------------------------------
*/

const applyBulkPrices = () => {
    if (!filteredCombinations.value.length) return

    const fields = [
        'sku',
        'price_b2b',
        'price_dropship',
        'price_host_live',
    ]

    filteredCombinations.value.forEach(({ combination }) => {
        fields.forEach((field) => {
            if (bulkPrices.value[field] !== '') {
                combination[field] = bulkPrices.value[field]
            }
        })
    })

    bulkPrices.value = {
        sku: '',
        price_b2b: '',
        price_dropship: '',
        price_host_live: '',
    }
}

/*
|--------------------------------------------------------------------------
| IMAGE MANAGEMENT
|--------------------------------------------------------------------------
*/

const openFilePicker = () => {
    if (imageItems.value.length < 10) {
        fileInput.value?.click()
    }
}

const handleImages = (event) => {
    const selectedFiles = Array.from(event.target.files || [])
    const remainingSlots = 10 - imageItems.value.length

    if (remainingSlots <= 0) {
        showNotification(
            'Jumlah foto produk sudah mencapai batas maksimal 10 foto.',
            'Batas Foto Tercapai'
        )

        event.target.value = ''
        return
    }

    const validFiles = []
    const invalidFiles = []

    selectedFiles.forEach((file) => {
        const validType = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ].includes(file.type)

        const validSize = file.size <= 5 * 1024 * 1024

        if (validType && validSize) {
            validFiles.push(file)
        } else {
            invalidFiles.push(file.name)
        }
    })

    const filesToAdd = validFiles.slice(0, remainingSlots)

    filesToAdd.forEach((file) => {
        imageItems.value.push({
            id: createDragKey(),
            type: 'new',
            file,
            name: file.name,
            url: URL.createObjectURL(file),
        })
    })

    syncImagePayload()

    if (invalidFiles.length) {
        showNotification(
            `${invalidFiles.length} foto tidak dapat ditambahkan. Gunakan JPG, PNG, atau WEBP dengan ukuran maksimal 5 MB.`,
            'Foto Tidak Valid'
        )
    } else if (validFiles.length > remainingSlots) {
        showNotification(
            `Hanya ${remainingSlots} foto yang dapat ditambahkan karena maksimal foto produk adalah 10.`,
            'Batas Foto Tercapai'
        )
    }

    event.target.value = ''
}

const removeImage = (index) => {
    const image = imageItems.value[index]

    if (!image) return

    if (image.type === 'new' && image.url) {
        URL.revokeObjectURL(image.url)
    }

    imageItems.value.splice(index, 1)

    syncImagePayload()
}

const startImageDrag = (imageKey, event) => {
    if (imageItems.value.length <= 1) return

    draggedImageKey.value = imageKey

    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', imageKey)
}

const moveImageTo = (targetKey) => {
    const sourceKey = draggedImageKey.value

    if (!sourceKey || sourceKey === targetKey) return

    const sourceIndex = imageItems.value.findIndex(
        (image) => image.id === sourceKey
    )

    if (sourceIndex < 0) return

    const targetIndex = imageItems.value.findIndex(
        (image) => image.id === targetKey
    )

    const [movedImage] = imageItems.value.splice(sourceIndex, 1)

    if (targetIndex < 0) {
        imageItems.value.push(movedImage)
    } else {
        const insertIndex = imageItems.value.findIndex(
            (image) => image.id === targetKey
        )

        if (insertIndex < 0) {
            imageItems.value.push(movedImage)
        } else {
            imageItems.value.splice(insertIndex, 0, movedImage)
        }
    }

    syncImagePayload()
}

const resetImageDrag = () => {
    draggedImageKey.value = null
    dragOverImageKey.value = null
}

/*
|--------------------------------------------------------------------------
| FORM VALIDATION
|--------------------------------------------------------------------------
*/

const validateProduct = () => {
    const errors = []

    if (!form.name.trim()) {
        errors.push({
            message: 'Nama produk belum diisi.',
            target: 'name',
        })
    }

    if (!form.category_id) {
        errors.push({
            message: 'Kategori produk belum dipilih.',
            target: 'category',
        })
    }

    if (!form.description.trim()) {
        errors.push({
            message: 'Deskripsi produk belum diisi.',
            target: 'description',
        })
    }

    if (!imageItems.value.length) {
        errors.push({
            message: 'Minimal satu foto produk harus tersedia.',
            target: 'product-images',
        })
    }

    const variantNames = form.variants.map((variant) => {
        return variant.name.trim().toLowerCase()
    })

    form.variants.forEach((variant, variantIndex) => {
        const variantName = variant.name.trim()
        const variantLabel = variantName || `Varian ${variantIndex + 1}`

        if (!variantName) {
            errors.push({
                message: `Nama varian ke-${variantIndex + 1} belum diisi.`,
                target: `variant-name-${variantIndex}`,
            })
        } else if (
            variantNames.filter((name) => name === variantName.toLowerCase()).length > 1 &&
            variantNames.indexOf(variantName.toLowerCase()) === variantIndex
        ) {
            errors.push({
                message: `Nama varian "${variantName}" tidak boleh duplikat.`,
                target: `variant-name-${variantIndex}`,
            })
        }

        if (!variant.options.length) {
            errors.push({
                message: `Varian "${variantLabel}" belum memiliki pilihan subvarian.`,
                target: `variant-option-${variantIndex}-0`,
            })

            return
        }

        const optionNames = variant.options.map((option) => {
            return option.trim().toLowerCase()
        })

        variant.options.forEach((option, optionIndex) => {
            const optionName = option.trim()

            if (!optionName) {
                errors.push({
                    message: `Pilihan subvarian ke-${optionIndex + 1} pada "${variantLabel}" belum diisi.`,
                    target: `variant-option-${variantIndex}-${optionIndex}`,
                })
            } else if (
                optionNames.filter((name) => name === optionName.toLowerCase()).length > 1 &&
                optionNames.indexOf(optionName.toLowerCase()) === optionIndex
            ) {
                errors.push({
                    message: `Pilihan "${optionName}" pada varian "${variantLabel}" tidak boleh duplikat.`,
                    target: `variant-option-${variantIndex}-${optionIndex}`,
                })
            }
        })
    })

    if (form.variants.length && !form.combinations.length) {
        errors.push({
            message: 'Kombinasi produk belum terbentuk. Periksa nama varian dan pilihan subvarian.',
            target: 'variant-section',
        })
    }

    form.combinations.forEach((combination, index) => {
        const combinationName = Object.entries(combination.values || {})
            .map(([key, value]) => `${key} ${value}`)
            .join(' / ')

        const label = combinationName || `Kombinasi ${index + 1}`

        if (!String(combination.sku ?? '').trim()) {
            errors.push({
                message: `SKU untuk ${label} belum diisi.`,
                target: `combination-sku-${index}`,
            })
        }

        const priceFields = [
            {
                key: 'price_b2b',
                label: 'Harga B2B',
            },
            {
                key: 'price_dropship',
                label: 'Harga Dropship',
            },
            {
                key: 'price_host_live',
                label: 'Harga Host Live',
            },
        ]

        priceFields.forEach((field) => {
            const value = combination[field.key]

            const isEmpty =
                value === null ||
                value === undefined ||
                String(value).trim() === ''

            if (isEmpty) {
                errors.push({
                    message: `${field.label} untuk ${label} belum diisi.`,
                    target: `combination-${field.key}-${index}`,
                })

                return
            }

            if (!Number.isFinite(Number(value)) || Number(value) < 0) {
                errors.push({
                    message: `${field.label} untuk ${label} harus berupa angka minimal 0.`,
                    target: `combination-${field.key}-${index}`,
                })
            }
        })
    })

    return errors
}

/*
|--------------------------------------------------------------------------
| FORM SUBMISSION
|--------------------------------------------------------------------------
*/

const submit = async () => {
    const errors = validateProduct()

    if (errors.length) {
        const firstError = errors[0]

        showNotification(firstError.message)

        await focusInvalidField(firstError.target)

        return
    }

    syncImagePayload()

    form.transform((data) => ({
        ...data,

        _method: 'put',

        variants: data.variants.map((variant) => {
            const {
                _dragKey,
                _optionDragKeys,
                ...cleanVariant
            } = variant

            return cleanVariant
        }),
    }))

    form.post(
        route('owner.products.update', props.product.unique_id),
        {
            forceFormData: true,
            preserveScroll: true,

            onError: (serverErrors) => {
                const firstError = Object.values(serverErrors)[0]

                if (firstError) {
                    showNotification(
                        Array.isArray(firstError) ? firstError[0] : firstError,
                        'Gagal Memperbarui Produk'
                    )
                }
            },
        }
    )
}

onBeforeUnmount(() => {
    imageItems.value.forEach((image) => {
        if (image.type === 'new' && image.url) {
            URL.revokeObjectURL(image.url)
        }
    })

    if (notificationTimer) {
        clearTimeout(notificationTimer)
    }
})
</script>

<template>
    <div class="owner-page">
        <OwnerSidebar :owner="owner" />

        <!-- APPLE STYLE NOTIFICATION -->
        <Transition name="notification">
            <div
                v-if="notification.show"
                class="notification-toast"
                role="alert"
                aria-live="assertive"
            >
                <div class="notification-icon">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M12 8V12"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                        />
                        <path
                            d="M12 16H12.01"
                            stroke="currentColor"
                            stroke-width="2.5"
                            stroke-linecap="round"
                        />
                        <path
                            d="M10.29 3.86L1.82 18.14C1.05 19.44 1.99 21 3.53 21H20.47C22.01 21 22.95 19.44 22.18 18.14L13.71 3.86C12.94 2.56 11.06 2.56 10.29 3.86Z"
                            stroke="currentColor"
                            stroke-width="1.6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>

                <div class="notification-content">
                    <strong>{{ notification.title }}</strong>
                    <p>{{ notification.message }}</p>
                </div>

                <button
                    type="button"
                    class="notification-close"
                    aria-label="Tutup notifikasi"
                    @click="closeNotification"
                >
                    ×
                </button>
            </div>
        </Transition>

        <main class="main-content">
            <header class="page-header">
                <Link
                    :href="route('owner.products.index')"
                    class="back-link"
                >
                    <span>←</span>
                    Kembali ke Produk
                </Link>

                <div class="header-content">
                    <div>
                        <span class="eyebrow">PRODUCT MANAGEMENT</span>
                        <h1>Edit Produk</h1>
                        <p>
                            Perbarui informasi dan kombinasi produk
                            pada katalog MOXXER.
                        </p>
                    </div>
                </div>
            </header>

            <form @submit.prevent="submit" class="product-form">
                <!-- FOTO PRODUK -->
                <section class="form-section image-section">
                    <div class="section-heading">
                        <div>
                            <span class="section-number">01 — MEDIA</span>
                            <h2>Foto Produk</h2>
                            <p>
                                Atur foto produk yang sudah ada atau tambahkan
                                foto baru. Maksimal 10 foto.
                            </p>
                        </div>

                        <span class="count-label">
                            {{ imageItems.length }} / 10
                        </span>
                    </div>

                    <div id="product-images" class="image-upload-wrapper">
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="hidden-input"
                            @change="handleImages"
                        />

                        <TransitionGroup
                            name="image-reorder"
                            tag="div"
                            class="image-grid"
                        >
                            <div
                                v-for="slot in imageSlots"
                                :key="slot.key"
                                class="image-slot"
                                :style="{
                                    gridRow: `${Math.floor(slot.index / 5) + 1}`,
                                    gridColumn: `${(slot.index % 5) + 1}`,
                                }"
                                :class="{
                                    'has-image': slot.image,
                                    'is-dragging':
                                        draggedImageKey === slot.key,
                                    'is-drag-over':
                                        dragOverImageKey === slot.key,
                                }"
                                :draggable="!!slot.image && imageItems.length > 1"
                                @dragstart="
                                    slot.image &&
                                    startImageDrag(slot.key, $event)
                                "
                                @dragover.prevent="moveImageTo(slot.key)"
                                @dragenter.prevent="dragOverImageKey = slot.key"
                                @drop.prevent="resetImageDrag"
                                @dragend="resetImageDrag"
                                @click="!slot.image && openFilePicker()"
                            >
                                <template v-if="slot.image">
                                    <img
                                        :src="slot.image.url"
                                        :alt="slot.image.name"
                                        class="product-image"
                                    />

                                    <div class="image-overlay">
                                        <span class="image-position">
                                            {{ String(slot.index + 1).padStart(2, '0') }}
                                        </span>

                                        <button
                                            type="button"
                                            class="remove-image"
                                            aria-label="Hapus gambar"
                                            @click.stop="removeImage(slot.index)"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="empty-slot">
                                        <span class="slot-plus">＋</span>
                                        <span class="slot-number">
                                            {{ String(slot.index + 1).padStart(2, '0') }}
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </TransitionGroup>

                        <div class="image-footer">
                            <div class="image-guidance">
                                <span class="guidance-dot"></span>
                                <span>
                                    JPG, PNG, atau WEBP · Maksimal 5 MB per foto
                                </span>
                            </div>

                            <button
                                v-if="imageItems.length < 10"
                                type="button"
                                class="add-image-button"
                                @click="openFilePicker"
                            >
                                <span>＋</span>
                                Tambah Foto
                            </button>
                        </div>

                        <p
                            v-if="imageItems.length > 1"
                            class="drag-hint"
                        >
                            <span>↔</span>
                            Klik dan tahan foto, lalu tarik untuk mengubah urutan.
                        </p>

                        <span v-if="form.errors.images" class="error">
                            {{ form.errors.images }}
                        </span>

                        <span v-if="form.errors.existing_images" class="error">
                            {{ form.errors.existing_images }}
                        </span>
                    </div>
                </section>

                <!-- INFORMASI PRODUK -->
                <section class="form-section">
                    <div class="section-heading">
                        <div>
                            <span class="section-number">02 — GENERAL</span>
                            <h2>Informasi Produk</h2>
                            <p>
                                Informasi utama yang akan digunakan
                                pada katalog produk.
                            </p>
                        </div>
                    </div>

                    <div class="field-grid">
                        <div class="field full">
                            <label for="name">Nama Produk</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Contoh: Oxford Classic Black"
                            />
                            <span v-if="form.errors.name" class="error">
                                {{ form.errors.name }}
                            </span>
                        </div>

                        <div class="field full">
                            <label for="category">Kategori</label>
                            <select
                                id="category"
                                v-model="form.category_id"
                            >
                                <option value="">Pilih kategori produk</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>

                            <span
                                v-if="form.errors.category_id"
                                class="error"
                            >
                                {{ form.errors.category_id }}
                            </span>
                        </div>

                        <div class="field full">
                            <label for="description">Deskripsi Produk</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="5"
                                placeholder="Ceritakan detail dan karakteristik produk..."
                            ></textarea>

                            <span
                                v-if="form.errors.description"
                                class="error"
                            >
                                {{ form.errors.description }}
                            </span>
                        </div>
                    </div>
                </section>

                <!-- VARIAN PRODUK -->
                <section id="variant-section" class="form-section">
                    <div class="section-heading">
                        <div>
                            <span class="section-number">03 — VARIATIONS</span>
                            <h2>Varian Produk</h2>
                            <p>
                                Tentukan jenis varian dan pilihan subvarian,
                                seperti warna atau ukuran. Kombinasi harga
                                akan dibuat otomatis.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="secondary-button"
                            @click="addVariant"
                        >
                            <span>＋</span>
                            Tambah Varian
                        </button>
                    </div>

                    <div
                        v-if="!form.variants.length"
                        class="empty-state"
                    >
                        <span class="empty-icon">◇</span>
                        <strong>Belum ada varian</strong>
                        <p>
                            Tambahkan varian jika produk memiliki pilihan
                            seperti warna atau ukuran.
                        </p>
                    </div>

                    <TransitionGroup
                        name="reorder"
                        tag="div"
                        class="variant-list"
                    >
                        <div
                            v-for="(variant, variantIndex) in form.variants"
                            :key="variant._dragKey"
                            class="variant-card"
                            :class="{
                                'variant-dragging':
                                    draggedVariantKey === variant._dragKey,
                            }"
                            @dragover.prevent.stop="
                                moveVariantTo(variant._dragKey, $event)
                            "
                        >
                            <div class="variant-top">
                                <div class="variant-heading-left">
                                    <button
                                        type="button"
                                        class="drag-handle"
                                        :class="{
                                            'handle-active':
                                                draggedVariantKey === variant._dragKey,
                                        }"
                                        draggable="true"
                                        aria-label="Pindahkan varian"
                                        title="Tarik untuk memindahkan varian"
                                        @dragstart.stop="
                                            startVariantDrag(variant._dragKey, $event)
                                        "
                                        @dragend.stop="resetVariantDrag"
                                    >
                                        <span v-for="dot in 6" :key="dot"></span>
                                    </button>

                                    <span class="variant-index">
                                        VARIAN {{ String(variantIndex + 1).padStart(2, '0') }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="text-danger"
                                    @click="removeVariant(variantIndex)"
                                >
                                    Hapus
                                </button>
                            </div>

                            <div class="field">
                                <label :for="`variant-name-${variantIndex}`">
                                    Nama Varian
                                </label>
                                <input
                                    :id="`variant-name-${variantIndex}`"
                                    v-model="variant.name"
                                    type="text"
                                    placeholder="Contoh: Warna"
                                    @change="syncCombinations"
                                />
                            </div>

                            <div class="options-heading">
                                <label>Pilihan Subvarian</label>

                                <button
                                    type="button"
                                    class="text-button"
                                    @click="addOption(variantIndex)"
                                >
                                    ＋ Tambah Pilihan
                                </button>
                            </div>

                            <TransitionGroup
                                name="reorder"
                                tag="div"
                                class="option-list"
                            >
                                <div
                                    v-for="(option, optionIndex) in variant.options"
                                    :key="variant._optionDragKeys[optionIndex]"
                                    class="option-row"
                                    :class="{
                                        'option-dragging':
                                            draggedOption?.optionKey ===
                                            variant._optionDragKeys[optionIndex],
                                    }"
                                    @dragover.prevent.stop="
                                        moveOptionTo(
                                            variant._dragKey,
                                            variant._optionDragKeys[optionIndex],
                                            $event
                                        )
                                    "
                                >
                                    <button
                                        type="button"
                                        class="drag-handle option-drag-handle"
                                        :class="{
                                            'handle-active':
                                                draggedOption?.optionKey ===
                                                variant._optionDragKeys[optionIndex],
                                        }"
                                        draggable="true"
                                        aria-label="Pindahkan subvarian"
                                        title="Tarik untuk memindahkan subvarian"
                                        @dragstart.stop="
                                            startOptionDrag(
                                                variant._dragKey,
                                                variant._optionDragKeys[optionIndex],
                                                $event
                                            )
                                        "
                                        @dragend.stop="resetOptionDrag"
                                    >
                                        <span v-for="dot in 6" :key="dot"></span>
                                    </button>

                                    <input
                                        :id="`variant-option-${variantIndex}-${optionIndex}`"
                                        v-model="variant.options[optionIndex]"
                                        type="text"
                                        :placeholder="`Pilihan ${optionIndex + 1}`"
                                        @change="syncCombinations"
                                    />

                                    <button
                                        v-if="variant.options.length > 1"
                                        type="button"
                                        class="remove-option"
                                        @click="removeOption(variantIndex, optionIndex)"
                                    >
                                        ×
                                    </button>
                                </div>
                            </TransitionGroup>

                            <span
                                v-if="form.errors[`variants.${variantIndex}.name`]"
                                class="error"
                            >
                                {{ form.errors[`variants.${variantIndex}.name`] }}
                            </span>
                        </div>
                    </TransitionGroup>

                    <span v-if="form.errors.variants" class="error">
                        {{ form.errors.variants }}
                    </span>
                </section>

                <!-- KOMBINASI DAN HARGA -->
                <section class="form-section">
                    <div class="section-heading">
                        <div>
                            <span class="section-number">04 — PRICING</span>
                            <h2>Kombinasi & Harga</h2>
                            <p>
                                Seluruh kombinasi subvarian dibuat otomatis.
                                Atur SKU dan harga langsung melalui tabel.
                            </p>
                        </div>

                        <span class="combination-count">
                            {{ form.combinations.length }} Kombinasi
                        </span>
                    </div>

                    <div
                        v-if="form.combinations.length"
                        class="pricing-workspace"
                    >
                        <div class="bulk-editor">
                            <div class="bulk-heading">
                                <div>
                                    <strong>Edit Harga Massal</strong>
                                    <span>
                                        Filter kombinasi, isi SKU atau harga,
                                        lalu terapkan sekaligus.
                                    </span>
                                </div>

                                <span class="selected-count">
                                    {{ filteredCombinations.length }} dari
                                    {{ form.combinations.length }} kombinasi
                                </span>
                            </div>

                            <div class="bulk-fields">
                                <div class="bulk-field filter-field">
                                    <label>Filter Varian</label>

                                    <div class="filter-container">
                                        <button
                                            type="button"
                                            class="filter-trigger"
                                            :class="{
                                                'filter-active': variantFilterCount > 0,
                                                'filter-open': filterOpen,
                                            }"
                                            :aria-expanded="filterOpen"
                                            @click="filterOpen = !filterOpen"
                                        >
                                            <span class="filter-trigger-text">
                                                {{
                                                    variantFilterCount
                                                        ? `${variantFilterCount} pilihan`
                                                        : 'Semua varian'
                                                }}
                                            </span>

                                            <span
                                                class="filter-icon"
                                                :class="{
                                                    'icon-open': filterOpen,
                                                }"
                                                aria-hidden="true"
                                            >
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </span>
                                        </button>

                                        <Transition name="filter-pop">
                                            <div
                                                v-if="filterOpen"
                                                class="filter-dropdown"
                                            >
                                                <div class="filter-dropdown-header">
                                                    <strong>Pilih Varian</strong>

                                                    <button
                                                        type="button"
                                                        @click="clearVariantFilters"
                                                    >
                                                        Reset
                                                    </button>
                                                </div>

                                                <div
                                                    v-if="variantFilterOptions.length"
                                                    class="filter-dropdown-content"
                                                >
                                                    <div
                                                        v-for="variant in variantFilterOptions"
                                                        :key="variant.name"
                                                        class="filter-group"
                                                    >
                                                        <span class="filter-group-title">
                                                            {{ variant.name }}
                                                        </span>

                                                        <button
                                                            v-for="option in variant.options"
                                                            :key="option"
                                                            type="button"
                                                            class="filter-option"
                                                            :class="{
                                                                'option-selected':
                                                                    isVariantFilterSelected(
                                                                        variant.name,
                                                                        option
                                                                    ),
                                                            }"
                                                            @click="
                                                                toggleVariantFilter(
                                                                    variant.name,
                                                                    option
                                                                )
                                                            "
                                                        >
                                                            <span class="filter-check">
                                                                {{
                                                                    isVariantFilterSelected(
                                                                        variant.name,
                                                                        option
                                                                    )
                                                                        ? '✓'
                                                                        : ''
                                                                }}
                                                            </span>

                                                            <span>{{ option }}</span>
                                                        </button>
                                                    </div>
                                                </div>

                                                <div
                                                    v-else
                                                    class="filter-empty"
                                                >
                                                    Belum ada pilihan varian.
                                                </div>

                                                <div class="filter-dropdown-footer">
                                                    <span>
                                                        {{ filteredCombinations.length }}
                                                        kombinasi cocok
                                                    </span>

                                                    <button
                                                        type="button"
                                                        @click="filterOpen = false"
                                                    >
                                                        Selesai
                                                    </button>
                                                </div>
                                            </div>
                                        </Transition>
                                    </div>
                                </div>

                                <div class="bulk-field">
                                    <label>SKU</label>
                                    <input
                                        v-model="bulkPrices.sku"
                                        type="text"
                                        placeholder="SKU"
                                    />
                                </div>

                                <div class="bulk-field">
                                    <label>Harga B2B</label>
                                    <input
                                        v-model="bulkPrices.price_b2b"
                                        type="number"
                                        min="0"
                                        placeholder="Harga B2B"
                                    />
                                </div>

                                <div class="bulk-field">
                                    <label>Harga Dropship</label>
                                    <input
                                        v-model="bulkPrices.price_dropship"
                                        type="number"
                                        min="0"
                                        placeholder="Harga Dropship"
                                    />
                                </div>

                                <div class="bulk-field">
                                    <label>Harga Host Live</label>
                                    <input
                                        v-model="bulkPrices.price_host_live"
                                        type="number"
                                        min="0"
                                        placeholder="Harga Host Live"
                                    />
                                </div>

                                <button
                                    type="button"
                                    class="apply-bulk-button"
                                    :disabled="
                                        !filteredCombinations.length ||
                                        Object.values(bulkPrices).every(
                                            (value) => value === ''
                                        )
                                    "
                                    @click="applyBulkPrices"
                                >
                                    Terapkan
                                </button>
                            </div>

                            <p class="bulk-hint">
                                Perubahan diterapkan ke
                                {{ filteredCombinations.length }} kombinasi
                                yang sesuai filter. Jika filter tidak dipilih,
                                perubahan berlaku untuk seluruh kombinasi.
                                Kosongkan kolom yang tidak ingin diubah.
                            </p>
                        </div>

                        <div class="pricing-table-wrapper">
                            <table class="pricing-table">
                                <thead>
                                    <tr>
                                        <th class="variant-column">
                                            Kombinasi Varian
                                        </th>
                                        <th class="sku-column">SKU</th>
                                        <th>Harga B2B (Rp)</th>
                                        <th>Harga Dropship (Rp)</th>
                                        <th>Harga Host Live (Rp)</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="(combination, index) in form.combinations"
                                        :key="getCombinationKey(combination.values)"
                                    >
                                        <td class="variant-column">
                                            <div class="variant-tags">
                                                <span
                                                    v-for="(value, key) in combination.values"
                                                    :key="key"
                                                    class="variant-tag"
                                                >
                                                    <small>{{ key }}</small>
                                                    {{ value }}
                                                </span>
                                            </div>
                                        </td>

                                        <td class="sku-column">
                                            <input
                                                :id="`combination-sku-${index}`"
                                                v-model="combination.sku"
                                                type="text"
                                                class="table-input sku-input"
                                                placeholder="Masukkan SKU"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                :id="`combination-price_b2b-${index}`"
                                                v-model="combination.price_b2b"
                                                type="number"
                                                min="0"
                                                class="table-input"
                                                placeholder="0"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                :id="`combination-price_dropship-${index}`"
                                                v-model="combination.price_dropship"
                                                type="number"
                                                min="0"
                                                class="table-input"
                                                placeholder="0"
                                            />
                                        </td>

                                        <td>
                                            <input
                                                :id="`combination-price_host_live-${index}`"
                                                v-model="combination.price_host_live"
                                                type="number"
                                                min="0"
                                                class="table-input"
                                                placeholder="0"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-footer">
                            <span>
                                {{ form.combinations.length }} total kombinasi
                            </span>

                            <span>
                                Filter aktif: {{ variantFilterCount }} pilihan
                            </span>
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <span class="empty-icon">◇</span>
                        <strong>
                            {{
                                form.variants.length
                                    ? 'Lengkapi Varian Produk'
                                    : 'Belum Ada Kombinasi'
                            }}
                        </strong>
                        <p>
                            {{
                                form.variants.length
                                    ? 'Isi nama setiap varian dan minimal satu pilihan subvarian. Tabel kombinasi akan dibuat otomatis.'
                                    : 'Tambahkan varian dan pilihan subvarian untuk menghasilkan tabel kombinasi produk.'
                            }}
                        </p>
                    </div>

                    <div class="price-note">
                        Setiap kombinasi memiliki SKU, harga B2B,
                        harga Dropship, dan harga Host Live masing-masing.
                        Kombinasi dibuat otomatis berdasarkan seluruh
                        pilihan subvarian. Tidak ada pengaturan stok.
                    </div>
                </section>

                <!-- ACTION -->
                <div class="form-footer">
                    <div class="footer-note">
                        Pastikan perubahan informasi produk sudah benar sebelum disimpan.
                    </div>

                    <div class="footer-actions">
                        <Link
                            :href="route('owner.products.index')"
                            class="cancel-button"
                        >
                            Batal
                        </Link>

                        <button
                            type="submit"
                            class="submit-button"
                            :disabled="form.processing"
                        >
                            {{
                                form.processing
                                    ? 'Menyimpan...'
                                    : 'Simpan Perubahan'
                            }}
                            <span v-if="!form.processing">→</span>
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</template>

<style scoped>
/* =========================
   BASE LAYOUT
========================= */

* {
    box-sizing: border-box;
}

.owner-page {
    min-height: 100vh;
    display: flex;
    background: #f5f5f7;
    color: #1d1d1f;
    font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    -webkit-font-smoothing: antialiased;
}

.main-content {
    flex: 1;
    min-width: 0;
    padding: 48px 64px 70px;
}

/* =========================
   APPLE STYLE NOTIFICATION
========================= */

.notification-toast {
    position: fixed;
    top: 25px;
    right: 28px;
    z-index: 9999;
    display: flex;
    width: min(390px, calc(100vw - 36px));
    align-items: flex-start;
    gap: 13px;
    padding: 17px 17px 17px 16px;
    border: 1px solid rgb(255 255 255 / 75%);
    border-radius: 15px;
    background: rgb(255 255 255 / 94%);
    box-shadow:
        0 12px 40px rgb(0 0 0 / 10%),
        0 2px 8px rgb(0 0 0 / 4%);
    backdrop-filter: blur(22px);
    -webkit-backdrop-filter: blur(22px);
}

.notification-icon {
    display: flex;
    width: 35px;
    height: 35px;
    flex: 0 0 35px;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: #f2f2f4;
    color: #424245;
}

.notification-icon svg {
    width: 19px;
    height: 19px;
}

.notification-content {
    min-width: 0;
    flex: 1;
    padding-top: 1px;
}

.notification-content strong {
    display: block;
    color: #1d1d1f;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: -.15px;
}

.notification-content p {
    margin: 5px 0 0;
    color: #6e6e73;
    font-size: 11px;
    line-height: 1.6;
    overflow-wrap: anywhere;
}

.notification-close {
    display: flex;
    width: 23px;
    height: 23px;
    flex: 0 0 23px;
    align-items: center;
    justify-content: center;
    padding: 0 0 2px;
    border: 0;
    border-radius: 50%;
    background: #f2f2f4;
    color: #86868b;
    font-family: Arial, sans-serif;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    transition:
        background .2s ease,
        color .2s ease;
}

.notification-close:hover {
    background: #e5e5ea;
    color: #1d1d1f;
}

.notification-enter-active,
.notification-leave-active {
    transition:
        opacity .28s ease,
        transform .32s cubic-bezier(.2, .8, .2, 1);
}

.notification-enter-from,
.notification-leave-to {
    opacity: 0;
    transform: translateY(-12px) scale(.97);
}

/* =========================
   PAGE HEADER
========================= */

.page-header {
    max-width: 1040px;
    margin: 0 auto 34px;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 28px;
    color: #6e6e73;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: color .2s ease;
}

.back-link:hover {
    color: #1d1d1f;
}

.back-link span {
    font-size: 17px;
}

.header-content {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
}

.eyebrow {
    display: block;
    margin-bottom: 10px;
    color: #86868b;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.8px;
}

.page-header h1 {
    margin: 0;
    color: #1d1d1f;
    font-size: 34px;
    font-weight: 650;
    letter-spacing: -1.5px;
}

.page-header p {
    margin: 10px 0 0;
    color: #86868b;
    font-size: 13px;
    line-height: 1.7;
}

.product-form {
    width: 100%;
    max-width: 1040px;
    margin: 0 auto;
}

/* =========================
   FORM SECTIONS
========================= */

.form-section {
    padding: 34px;
    margin-bottom: 18px;
    border: 1px solid #e8e8ed;
    border-radius: 15px;
    background: #fff;
    box-shadow: 0 2px 8px rgb(0 0 0 / 2%);
}

.section-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 28px;
}

.section-number {
    display: block;
    margin-bottom: 10px;
    color: #86868b;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
}

.section-heading h2 {
    margin: 0;
    color: #1d1d1f;
    font-size: 19px;
    font-weight: 650;
    letter-spacing: -.55px;
}

.section-heading p {
    max-width: 540px;
    margin: 8px 0 0;
    color: #86868b;
    font-size: 12px;
    line-height: 1.7;
}

.count-label,
.combination-count {
    padding: 7px 11px;
    border: 1px solid #e8e8ed;
    border-radius: 20px;
    background: #f5f5f7;
    color: #6e6e73;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

/* =========================
   PRODUCT IMAGE GRID
========================= */

.hidden-input {
    display: none;
}

.image-grid {
    position: relative;
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    grid-template-rows: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.image-reorder-leave-active {
    display: none !important;
}

.image-reorder-enter-active {
    transition: opacity .22s ease, transform .22s ease;
}

.image-reorder-enter-from {
    opacity: 0;
    transform: translateY(8px) scale(.98);
}

.image-reorder-move {
    transition: transform .32s cubic-bezier(.2, .8, .2, 1);
}

.image-slot {
    position: relative;
    min-width: 0;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border: 1px dashed #d2d2d7;
    border-radius: 10px;
    background: #f8f8fa;
    transition:
        border-color .2s ease,
        background .2s ease,
        transform .2s ease,
        opacity .2s ease;
}

.empty-slot {
    position: relative;
    display: flex;
    width: 100%;
    height: 100%;
    align-items: center;
    justify-content: center;
}

.image-slot:not(.has-image) {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.image-slot:not(.has-image):hover {
    border-color: #86868b;
    background: #f1f1f4;
}

.slot-plus {
    color: #b5b5ba;
    font-size: 25px;
    font-weight: 300;
    transition: color .2s ease;
}

.image-slot:not(.has-image):hover .slot-plus {
    color: #555;
}

.slot-number {
    position: absolute;
    right: 10px;
    bottom: 9px;
    color: #c1c1c6;
    font-size: 9px;
    font-weight: 600;
    letter-spacing: .5px;
}

.image-slot.has-image {
    border-style: solid;
    border-color: #e5e5ea;
    background: #f5f5f7;
    cursor: grab;
}

.image-slot.has-image:active {
    cursor: grabbing;
}

.image-slot.is-dragging {
    opacity: .35;
    transform: scale(.96);
}

.image-slot.is-drag-over {
    border: 2px solid #0071e3;
    background: #eef6ff;
    transform: scale(.98);
}

.product-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
    pointer-events: none;
    user-select: none;
}

.image-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 8px;
    background: linear-gradient(
        180deg,
        rgb(0 0 0 / 24%) 0%,
        transparent 35%
    );
    opacity: 0;
    transition: opacity .2s ease;
    pointer-events: none;
}

.image-slot:hover .image-overlay {
    opacity: 1;
}

.image-position {
    color: white;
    font-size: 10px;
    font-weight: 600;
    text-shadow: 0 1px 3px rgb(0 0 0 / 30%);
}

.remove-image {
    display: flex;
    width: 26px;
    height: 26px;
    flex: 0 0 26px;
    align-items: center;
    justify-content: center;
    padding: 0 0 2px;
    border: 0;
    border-radius: 50%;
    background: rgb(0 0 0 / 68%);
    color: white;
    font-family: Arial, sans-serif;
    font-size: 19px;
    font-weight: 400;
    line-height: 1;
    cursor: pointer;
    pointer-events: auto;
    transition:
        background .2s ease,
        transform .2s ease;
}

.remove-image:hover {
    background: #d70015;
    transform: scale(1.08);
}

.image-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-top: 20px;
}

.image-guidance {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #86868b;
    font-size: 11px;
}

.guidance-dot {
    width: 6px;
    height: 6px;
    flex: 0 0 6px;
    border-radius: 50%;
    background: #30a46c;
}

.add-image-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 13px;
    border: 1px solid #dedee3;
    border-radius: 8px;
    background: #fff;
    color: #1d1d1f;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: background .2s ease, border-color .2s ease;
}

.add-image-button:hover {
    border-color: #aaaab0;
    background: #f5f5f7;
}

.add-image-button span {
    font-size: 15px;
}

.drag-hint {
    display: flex;
    align-items: center;
    gap: 7px;
    margin: 13px 0 0;
    color: #86868b;
    font-size: 11px;
}

.drag-hint span {
    font-size: 15px;
}

/* =========================
   FORM FIELDS
========================= */

.field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 9px;
}

.field.full {
    grid-column: 1 / -1;
}

.field label,
.options-heading label {
    color: #424245;
    font-size: 12px;
    font-weight: 600;
}

.field input,
.field select,
.field textarea,
.option-row input {
    width: 100%;
    min-width: 0;
    min-height: 44px;
    padding: 12px 14px;
    border: 1px solid #dcdce1;
    border-radius: 9px;
    outline: none;
    background: #fff;
    color: #1d1d1f;
    font-family: inherit;
    font-size: 12px;
    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.field input:hover,
.field select:hover,
.field textarea:hover,
.option-row input:hover {
    border-color: #b5b5ba;
}

.field input:focus,
.field select:focus,
.field textarea:focus,
.option-row input:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgb(0 113 227 / 12%);
}

.field input::placeholder,
.field textarea::placeholder,
.option-row input::placeholder {
    color: #a1a1a6;
}

.field textarea {
    min-height: 125px;
    resize: vertical;
    line-height: 1.7;
}

.field select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2386868b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 35px;
}

.error {
    display: block;
    margin-top: 5px;
    color: #d70015;
    font-size: 11px;
    line-height: 1.5;
}

/* =========================
   BUTTONS
========================= */

.secondary-button {
    display: inline-flex;
    min-height: 37px;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px;
    border: 1px solid #dedee3;
    border-radius: 8px;
    background: white;
    color: #1d1d1f;
    font: inherit;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s ease, border-color .2s ease;
}

.secondary-button:hover {
    border-color: #aaaab0;
    background: #f5f5f7;
}

.secondary-button span {
    font-size: 15px;
}

/* =========================
   EMPTY STATE
========================= */

.empty-state {
    display: flex;
    min-height: 150px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 25px;
    border: 1px dashed #dedee3;
    border-radius: 10px;
    background: #fafafc;
    text-align: center;
}

.empty-icon {
    margin-bottom: 10px;
    color: #a1a1a6;
    font-size: 24px;
}

.empty-state strong {
    color: #424245;
    font-size: 12px;
    font-weight: 600;
}

.empty-state p {
    max-width: 350px;
    margin: 7px 0 0;
    color: #86868b;
    font-size: 11px;
    line-height: 1.6;
}

/* =========================
   VARIANTS
========================= */

.variant-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.variant-card {
    position: relative;
    padding: 23px;
    border: 1px solid #e8e8ed;
    border-radius: 11px;
    background: #fafafc;
    transition:
        border-color .22s ease,
        background .22s ease,
        box-shadow .22s ease,
        opacity .22s ease,
        transform .22s ease;
}

.variant-card.variant-dragging {
    opacity: .5;
    border-color: #0071e3;
    background: #f0f6ff;
    box-shadow: 0 8px 24px rgb(0 0 0 / 7%);
}

.variant-top,
.options-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}

.variant-top {
    margin-bottom: 22px;
}

.variant-heading-left {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.variant-index {
    color: #86868b;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
}

/* =========================
   DRAG HANDLE
========================= */

.drag-handle {
    display: grid;
    width: 29px;
    height: 31px;
    flex: 0 0 29px;
    grid-template-columns: repeat(2, 3px);
    grid-template-rows: repeat(3, 3px);
    align-content: center;
    justify-content: center;
    gap: 3px;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 7px;
    background: transparent;
    cursor: grab;
    touch-action: none;
    transition:
        background .2s ease,
        border-color .2s ease,
        transform .2s ease,
        box-shadow .2s ease;
}

.drag-handle:hover {
    border-color: #e5e5ea;
    background: #f0f0f3;
}

.drag-handle:active,
.drag-handle.handle-active {
    border-color: #d5e6fa;
    background: #eaf3ff;
    cursor: grabbing;
    transform: scale(.92);
    box-shadow: 0 2px 7px rgb(0 113 227 / 10%);
}

.drag-handle span {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: #a1a1a6;
    transition:
        background .2s ease,
        transform .2s ease;
}

.drag-handle:hover span,
.drag-handle.handle-active span {
    background: #0071e3;
}

.drag-handle.handle-active span {
    transform: scale(1.15);
}

.text-danger,
.text-button {
    padding: 0;
    border: 0;
    background: transparent;
    font: inherit;
    font-size: 11px;
    cursor: pointer;
}

.text-danger {
    color: #d70015;
}

.text-danger:hover {
    text-decoration: underline;
}

.text-button {
    color: #0071e3;
    font-weight: 600;
}

.text-button:hover {
    color: #005bb5;
}

.options-heading {
    margin: 23px 0 11px;
}

.option-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.option-row {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 8px;
    border-radius: 8px;
    transition:
        opacity .22s ease,
        background .22s ease,
        box-shadow .22s ease;
}

.option-row.option-dragging {
    opacity: .45;
    background: #eaf3ff;
    box-shadow: 0 3px 12px rgb(0 0 0 / 5%);
}

.option-drag-handle {
    width: 25px;
    height: 29px;
    flex-basis: 25px;
    flex-shrink: 0;
}

.option-row input {
    flex: 1;
    width: 0;
}

.remove-option {
    width: 34px;
    height: 42px;
    flex: 0 0 34px;
    border: 1px solid #dedee3;
    border-radius: 8px;
    background: white;
    color: #86868b;
    font-size: 19px;
    cursor: pointer;
    transition: color .2s ease, border-color .2s ease;
}

.remove-option:hover {
    border-color: #d70015;
    color: #d70015;
}

/* =========================
   DRAG ANIMATION
========================= */

.reorder-move {
    transition: transform .32s cubic-bezier(.2, .8, .2, 1);
}

.reorder-enter-active,
.reorder-leave-active {
    transition:
        opacity .22s ease,
        transform .22s cubic-bezier(.2, .8, .2, 1);
}

.reorder-enter-from {
    opacity: 0;
    transform: translateY(10px) scale(.98);
}

.reorder-leave-to {
    opacity: 0;
    transform: translateY(-7px) scale(.98);
}

/* =========================
   PRICING BULK EDITOR
========================= */

.pricing-workspace {
    width: 100%;
}

.bulk-editor {
    position: relative;
    z-index: 5;
    padding: 20px;
    margin-bottom: 18px;
    border: 1px solid #e8e8ed;
    border-radius: 11px;
    background: #fafafc;
}

.bulk-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 18px;
}

.bulk-heading > div {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.bulk-heading strong {
    color: #1d1d1f;
    font-size: 13px;
    font-weight: 650;
}

.bulk-heading span {
    color: #86868b;
    font-size: 11px;
}

.selected-count {
    padding: 7px 11px;
    border: 1px solid #dedee3;
    border-radius: 20px;
    background: #fff;
    color: #424245 !important;
    font-size: 10px !important;
    font-weight: 600;
    white-space: nowrap;
}

.bulk-fields {
    display: grid;
    grid-template-columns:
        minmax(125px, 1.15fr)
        minmax(100px, .9fr)
        minmax(115px, 1fr)
        minmax(115px, 1fr)
        minmax(115px, 1fr)
        auto;
    align-items: end;
    gap: 10px;
}

.bulk-field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 8px;
}

.bulk-field label {
    color: #424245;
    font-size: 11px;
    font-weight: 600;
}

.bulk-field input {
    width: 100%;
    min-width: 0;
    height: 40px;
    padding: 0 11px;
    border: 1px solid #dcdce1;
    border-radius: 8px;
    outline: none;
    background: #fff;
    color: #1d1d1f;
    font-family: inherit;
    font-size: 11px;
}

.bulk-field input:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgb(0 113 227 / 10%);
}

/* =========================
   VARIANT FILTER DROPDOWN
========================= */

.filter-container {
    position: relative;
    width: 100%;
}

.filter-trigger {
    display: flex;
    width: 100%;
    height: 40px;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 0 11px;
    border: 1px solid #dcdce1;
    border-radius: 8px;
    background: #fff;
    color: #424245;
    font-family: inherit;
    font-size: 11px;
    cursor: pointer;
    transition:
        border-color .25s cubic-bezier(.2, .8, .2, 1),
        background .25s cubic-bezier(.2, .8, .2, 1),
        box-shadow .25s cubic-bezier(.2, .8, .2, 1),
        transform .25s cubic-bezier(.2, .8, .2, 1);
}

.filter-trigger:hover {
    border-color: #a1a1a6;
    background: #fafafc;
}

.filter-trigger:active {
    transform: scale(.985);
}

.filter-trigger:focus-visible {
    outline: none;
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgb(0 113 227 / 12%);
}

.filter-trigger.filter-active,
.filter-trigger.filter-open {
    border-color: #a1a1a6;
    background: #fff;
}

.filter-trigger.filter-active {
    color: #1d1d1f;
    font-weight: 600;
}

.filter-trigger.filter-open {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgb(0 113 227 / 9%);
}

.filter-trigger-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.filter-icon {
    display: flex;
    width: 17px;
    height: 17px;
    flex: 0 0 17px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 3px;
    transition: transform .25s cubic-bezier(.2, .8, .2, 1);
}

.filter-icon span {
    position: relative;
    display: block;
    width: 15px;
    height: 1.5px;
    border-radius: 2px;
    background: #86868b;
    transition:
        background .2s ease,
        width .2s ease;
}

.filter-icon span:nth-child(1)::after,
.filter-icon span:nth-child(2)::after,
.filter-icon span:nth-child(3)::after {
    position: absolute;
    top: 50%;
    width: 4px;
    height: 4px;
    border: 1.3px solid #86868b;
    border-radius: 50%;
    background: #fff;
    content: "";
    transform: translateY(-50%);
    transition:
        border-color .2s ease,
        transform .2s ease;
}

.filter-icon span:nth-child(1)::after {
    left: 3px;
}

.filter-icon span:nth-child(2)::after {
    right: 3px;
}

.filter-icon span:nth-child(3)::after {
    left: 7px;
}

.filter-trigger:hover .filter-icon span,
.filter-trigger.filter-open .filter-icon span {
    background: #424245;
}

.filter-trigger:hover .filter-icon span::after,
.filter-trigger.filter-open .filter-icon span::after {
    border-color: #424245;
}

.filter-icon.icon-open {
    transform: rotate(90deg);
}

.filter-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    z-index: 30;
    width: 260px;
    max-height: 360px;
    overflow: hidden;
    border: 1px solid #e5e5ea;
    border-radius: 11px;
    background: #fff;
    box-shadow:
        0 16px 40px rgb(0 0 0 / 10%),
        0 2px 8px rgb(0 0 0 / 4%);
    transform-origin: top center;
}

.filter-pop-enter-active,
.filter-pop-leave-active {
    transition:
        opacity .2s cubic-bezier(.2, .8, .2, 1),
        transform .25s cubic-bezier(.2, .8, .2, 1);
}

.filter-pop-enter-from,
.filter-pop-leave-to {
    opacity: 0;
    transform: translateY(-7px) scale(.98);
}

.filter-dropdown-header,
.filter-dropdown-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
}

.filter-dropdown-header {
    border-bottom: 1px solid #eeeeF0;
}

.filter-dropdown-header strong {
    color: #1d1d1f;
    font-size: 11px;
    font-weight: 650;
}

.filter-dropdown-header button,
.filter-dropdown-footer button {
    padding: 5px 7px;
    border: 0;
    border-radius: 5px;
    background: transparent;
    color: #0071e3;
    font-family: inherit;
    font-size: 10px;
    font-weight: 600;
    cursor: pointer;
    transition:
        background .18s ease,
        color .18s ease;
}

.filter-dropdown-header button:hover,
.filter-dropdown-footer button:hover {
    background: #f0f6ff;
    color: #005bb5;
}

.filter-dropdown-content {
    max-height: 245px;
    overflow-y: auto;
    padding: 5px 0;
}

.filter-group {
    padding: 9px 13px;
}

.filter-group + .filter-group {
    border-top: 1px solid #f0f0f2;
}

.filter-group-title {
    display: block;
    margin-bottom: 7px;
    color: #86868b;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
}

.filter-option {
    display: flex;
    width: 100%;
    min-height: 31px;
    align-items: center;
    gap: 9px;
    padding: 5px 3px;
    border: 0;
    border-radius: 5px;
    background: transparent;
    color: #424245;
    font-family: inherit;
    font-size: 11px;
    text-align: left;
    cursor: pointer;
    transition:
        background .18s ease,
        color .18s ease;
}

.filter-option:hover {
    background: #f5f5f7;
}

.filter-option.option-selected {
    background: #f8f8fa;
    color: #1d1d1f;
    font-weight: 600;
}

.filter-check {
    display: flex;
    width: 15px;
    height: 15px;
    flex: 0 0 15px;
    align-items: center;
    justify-content: center;
    border: 1px solid #d2d2d7;
    border-radius: 4px;
    background: #fff;
    color: #fff;
    font-size: 10px;
    line-height: 1;
    transition:
        background .18s ease,
        border-color .18s ease,
        transform .18s ease;
}

.option-selected .filter-check {
    border-color: #1d1d1f;
    background: #1d1d1f;
    transform: scale(1.04);
}

.filter-empty {
    padding: 22px 14px;
    color: #86868b;
    font-size: 11px;
    text-align: center;
}

.filter-dropdown-footer {
    border-top: 1px solid #eeeeF0;
    background: #fafafc;
}

.filter-dropdown-footer span {
    color: #86868b;
    font-size: 10px;
}

.apply-bulk-button {
    height: 40px;
    padding: 0 14px;
    border: 0;
    border-radius: 8px;
    background: #1d1d1f;
    color: #fff;
    font-family: inherit;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
    cursor: pointer;
    transition:
        background .22s ease,
        opacity .22s ease,
        transform .22s cubic-bezier(.2, .8, .2, 1),
        box-shadow .22s ease;
}

.apply-bulk-button:hover:not(:disabled) {
    background: #424245;
    box-shadow: 0 3px 10px rgb(0 0 0 / 12%);
    transform: translateY(-1px);
}

.apply-bulk-button:active:not(:disabled) {
    transform: scale(.97);
}

.apply-bulk-button:disabled {
    opacity: .4;
    cursor: not-allowed;
}

.bulk-hint {
    margin: 12px 0 0;
    color: #86868b;
    font-size: 10px;
    line-height: 1.6;
}

/* =========================
   PRICING TABLE
========================= */

.pricing-table-wrapper {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e8e8ed;
    border-radius: 10px;
    background: #fff;
}

.pricing-table {
    width: 100%;
    min-width: 900px;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
}

.pricing-table th {
    height: 47px;
    padding: 0 12px;
    border-bottom: 1px solid #e8e8ed;
    background: #f8f8fa;
    color: #86868b;
    font-size: 10px;
    font-weight: 650;
    white-space: nowrap;
}

.pricing-table td {
    height: 66px;
    padding: 9px 12px;
    border-bottom: 1px solid #eeeeF0;
    background: #fff;
}

.pricing-table tbody tr:last-child td {
    border-bottom: 0;
}

.pricing-table tbody tr {
    transition: background .15s ease;
}

.pricing-table tbody tr:hover td {
    background: #fafafc;
}

.variant-column {
    min-width: 170px;
}

.sku-column {
    min-width: 140px;
}

.variant-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.variant-tag {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 7px;
    border: 1px solid #e8e8ed;
    border-radius: 5px;
    background: #fafafc;
    color: #424245;
    font-size: 10px;
    white-space: nowrap;
}

.variant-tag small {
    color: #86868b;
    font-size: 9px;
}

.table-input {
    width: 100%;
    min-width: 100px;
    height: 35px;
    padding: 0 9px;
    border: 1px solid #e2e2e7;
    border-radius: 6px;
    outline: none;
    background: #fff;
    color: #1d1d1f;
    font-family: inherit;
    font-size: 11px;
    transition: border-color .2s ease, box-shadow .2s ease;
}

.table-input:hover {
    border-color: #b5b5ba;
}

.table-input:focus {
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgb(0 113 227 / 10%);
}

.sku-input {
    min-width: 125px;
}

.table-footer {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 2px 0;
    color: #86868b;
    font-size: 10px;
}

/* =========================
   PRICE NOTE
========================= */

.price-note {
    margin-top: 17px;
    padding: 14px 16px;
    border-radius: 8px;
    background: #f5f5f7;
    color: #6e6e73;
    font-size: 11px;
    line-height: 1.7;
}

/* =========================
   FORM FOOTER
========================= */

.form-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 2px 10px;
}

.footer-note {
    color: #86868b;
    font-size: 11px;
    line-height: 1.6;
}

.footer-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cancel-button,
.submit-button {
    display: inline-flex;
    min-height: 43px;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 0 20px;
    border: 1px solid #dedee3;
    border-radius: 9px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition:
        background .2s ease,
        border-color .2s ease,
        transform .2s ease;
}

.cancel-button {
    background: #fff;
    color: #424245;
}

.cancel-button:hover {
    border-color: #aaaab0;
    background: #f5f5f7;
}

.submit-button {
    border-color: #1d1d1f;
    background: #1d1d1f;
    color: #fff;
}

.submit-button:hover {
    border-color: #424245;
    background: #424245;
}

.submit-button span {
    font-size: 16px;
    font-weight: 400;
}

.submit-button:disabled {
    opacity: .55;
    cursor: not-allowed;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 1100px) {
    .main-content {
        padding: 38px 30px 55px;
    }

    .form-section {
        padding: 27px;
    }

    .bulk-fields {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .apply-bulk-button {
        width: 100%;
    }
}

@media (max-width: 900px) {
    .bulk-fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .apply-bulk-button {
        width: 100%;
    }
}

@media (max-width: 760px) {
    .main-content {
        flex: 1;
        min-width: 0;
        margin-left: 0;
        padding: 28px 18px 40px;
    }

    .page-header h1 {
        font-size: 28px;
    }

    .form-section {
        padding: 22px 18px;
        border-radius: 12px;
    }

    .section-heading {
        flex-direction: column;
        gap: 13px;
    }

    .field-grid {
        grid-template-columns: 1fr;
    }

    .image-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 6px;
    }

    .image-slot {
        border-radius: 7px;
    }

    .slot-plus {
        font-size: 19px;
    }

    .slot-number {
        right: 5px;
        bottom: 5px;
        font-size: 8px;
    }

    .image-footer {
        align-items: flex-start;
        flex-direction: column;
    }

    .image-overlay {
        opacity: 1;
        padding: 5px;
    }

    .remove-image {
        width: 23px;
        height: 23px;
        flex-basis: 23px;
        font-size: 17px;
    }

    .option-list {
        grid-template-columns: 1fr;
    }

    .variant-card {
        padding: 17px;
    }

    .form-footer {
        align-items: stretch;
        flex-direction: column;
    }

    .footer-actions {
        width: 100%;
    }

    .cancel-button,
    .submit-button {
        flex: 1;
    }

    .notification-toast {
        top: 15px;
        right: 12px;
        width: calc(100vw - 24px);
    }
}

@media (max-width: 520px) {
    .bulk-editor {
        padding: 15px;
    }

    .bulk-fields {
        grid-template-columns: 1fr;
    }

    .bulk-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .filter-dropdown {
        width: min(260px, calc(100vw - 75px));
    }
}

@media (max-width: 420px) {
    .main-content {
        padding-right: 12px;
        padding-left: 12px;
    }

    .form-section {
        padding: 18px 13px;
    }

    .image-grid {
        gap: 5px;
    }

    .image-slot {
        border-radius: 5px;
    }

    .count-label {
        align-self: flex-start;
    }

    .footer-actions {
        flex-direction: column-reverse;
    }

    .cancel-button,
    .submit-button {
        width: 100%;
    }
}
</style>