<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OwnerProductController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        // Ambil seluruh produk tanpa pagination.
        $products = Product::with('category')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('unique_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return Inertia::render('Owner/Products/Index', [
            'owner' => Auth::guard('owner')->user(),

            // Pertahankan struktur data agar kompatibel dengan Index.vue.
            'products' => [
                'data' => $products,
                'total' => $products->count(),
            ],

            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Tampilkan halaman tambah produk.
     * Jika parameter clone tersedia, isi halaman dengan data produk sumber.
     */
    public function create(Request $request): Response
    {
        $cloneProduct = null;

        if ($request->filled('clone')) {
            $sourceProduct = Product::where(
                'unique_id',
                $request->query('clone')
            )->firstOrFail();

            $cloneProduct = [
                'unique_id' => $sourceProduct->unique_id,
                'name' => $sourceProduct->name,
                'category_id' => $sourceProduct->category_id,
                'description' => $sourceProduct->description,
                'images' => $sourceProduct->images ?? [],
                'image_urls' => collect($sourceProduct->images ?? [])
                    ->map(fn ($path) => Storage::disk('public')->url($path))
                    ->values()
                    ->all(),
                'variants' => $sourceProduct->variants ?? [],
                'combinations' => $sourceProduct->combinations ?? [],
            ];
        }

        return Inertia::render('Owner/Products/Create', [
            'owner' => Auth::guard('owner')->user(),
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'cloneProduct' => $cloneProduct,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'clone_source_unique_id' => [
                'nullable',
                'string',
                'exists:products,unique_id',
            ],

            'clone_images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'clone_images.*' => [
                'required',
                'string',
            ],

            'variants' => [
                'nullable',
                'array',
            ],

            'variants.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'variants.*.options' => [
                'required',
                'array',
                'min:1',
            ],

            'variants.*.options.*' => [
                'required',
                'string',
                'max:100',
            ],

            'combinations' => [
                'required',
                'array',
                'min:1',
            ],

            'combinations.*.sku' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'combinations.*.values' => [
                'nullable',
                'array',
            ],

            'combinations.*.values.*' => [
                'nullable',
                'string',
                'max:100',
            ],

            'combinations.*.price_b2b' => [
                'required',
                'integer',
                'min:0',
            ],

            'combinations.*.price_dropship' => [
                'required',
                'integer',
                'min:0',
            ],

            'combinations.*.price_host_live' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | VALIDASI FOTO CLONE
        |--------------------------------------------------------------------------
        */

        $cloneImages = $validated['clone_images'] ?? [];
        $sourceProduct = null;

        if (!empty($validated['clone_source_unique_id'])) {
            $sourceProduct = Product::where(
                'unique_id',
                $validated['clone_source_unique_id']
            )->firstOrFail();

            $sourceImages = $sourceProduct->images ?? [];

            foreach ($cloneImages as $path) {
                if (!in_array($path, $sourceImages, true)) {
                    abort(422, 'Foto clone tidak valid.');
                }
            }
        } elseif (!empty($cloneImages)) {
            abort(422, 'Produk sumber untuk foto clone tidak ditemukan.');
        }

        $newUploadedImages = $validated['images'] ?? [];

        if (count($cloneImages) + count($newUploadedImages) < 1) {
            return back()
                ->withErrors([
                    'images' => 'Produk harus memiliki minimal satu foto.',
                ])
                ->withInput();
        }

        if (count($cloneImages) + count($newUploadedImages) > 10) {
            return back()
                ->withErrors([
                    'images' => 'Maksimal 10 foto untuk setiap produk.',
                ])
                ->withInput();
        }

        /*
        |--------------------------------------------------------------------------
        | BUAT FILE FOTO BARU UNTUK HASIL CLONE
        |--------------------------------------------------------------------------
        */

        $imagePaths = [];

        foreach ($cloneImages as $originalPath) {
            if (!Storage::disk('public')->exists($originalPath)) {
                return back()
                    ->withErrors([
                        'images' => 'Salah satu foto produk sumber tidak ditemukan.',
                    ])
                    ->withInput();
            }

            $extension = pathinfo($originalPath, PATHINFO_EXTENSION);

            $newPath = 'products/' . uniqid('clone_', true);

            if ($extension) {
                $newPath .= '.' . $extension;
            }

            $copied = Storage::disk('public')->copy(
                $originalPath,
                $newPath
            );

            if (!$copied) {
                foreach ($imagePaths as $createdPath) {
                    Storage::disk('public')->delete($createdPath);
                }

                return back()
                    ->withErrors([
                        'images' => 'Gagal menyalin foto produk.',
                    ])
                    ->withInput();
            }

            $imagePaths[] = $newPath;
        }

        foreach ($newUploadedImages as $image) {
            $imagePaths[] = $image->store('products', 'public');
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN VARIAN DAN KOMBINASI
        |--------------------------------------------------------------------------
        */

        $variants = $validated['variants'] ?? [];

        $combinations = collect($validated['combinations'])
            ->map(function ($combination) {
                return [
                    'sku' => $combination['sku'],
                    'values' => $combination['values'] ?? [],
                    'price_b2b' => (int) $combination['price_b2b'],
                    'price_dropship' => (int) $combination['price_dropship'],
                    'price_host_live' => (int) $combination['price_host_live'],
                ];
            })
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | BUAT PRODUK BARU
        |--------------------------------------------------------------------------
        */

        $product = new Product();

        $product->unique_id = $this->generateUniqueId();
        $product->name = $validated['name'];
        $product->category_id = $validated['category_id'] ?? null;
        $product->description = $validated['description'] ?? null;
        $product->images = $imagePaths;
        $product->variants = $variants;
        $product->combinations = $combinations;

        $product->save();

        return redirect()
            ->route('owner.products.index')
            ->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Owner/Products/Edit', [
            'owner' => Auth::guard('owner')->user(),

            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name']),

            'product' => [
                'id' => $product->id,
                'unique_id' => $product->unique_id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'description' => $product->description,
                'images' => $product->images ?? [],
                'image_urls' => collect($product->images ?? [])
                    ->map(fn ($path) => Storage::disk('public')->url($path))
                    ->values()
                    ->all(),
                'variants' => $product->variants ?? [],
                'combinations' => $product->combinations ?? [],
            ],
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $currentImages = $product->images ?? [];

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'existing_images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'existing_images.*' => [
                'required',
                'string',
                Rule::in($currentImages),
            ],

            'images' => [
                'nullable',
                'array',
                'max:10',
            ],

            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'variants' => [
                'nullable',
                'array',
            ],

            'variants.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'variants.*.options' => [
                'required',
                'array',
                'min:1',
            ],

            'variants.*.options.*' => [
                'required',
                'string',
                'max:100',
            ],

            'combinations' => [
                'required',
                'array',
                'min:1',
            ],

            'combinations.*.sku' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],

            'combinations.*.values' => [
                'nullable',
                'array',
            ],

            'combinations.*.values.*' => [
                'nullable',
                'string',
                'max:100',
            ],

            'combinations.*.price_b2b' => [
                'required',
                'integer',
                'min:0',
            ],

            'combinations.*.price_dropship' => [
                'required',
                'integer',
                'min:0',
            ],

            'combinations.*.price_host_live' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);

        $existingImages = array_values(array_unique(
            $validated['existing_images'] ?? []
        ));

        $newImages = $validated['images'] ?? [];

        if (count($existingImages) + count($newImages) < 1) {
            return back()
                ->withErrors([
                    'images' => 'Produk harus memiliki minimal satu foto.',
                ])
                ->withInput();
        }

        if (count($existingImages) + count($newImages) > 10) {
            return back()
                ->withErrors([
                    'images' => 'Maksimal 10 foto untuk setiap produk.',
                ])
                ->withInput();
        }

        $newImagePaths = [];

        foreach ($newImages as $image) {
            $newImagePaths[] = $image->store('products', 'public');
        }

        $imagePaths = array_merge($existingImages, $newImagePaths);

        $combinations = collect($validated['combinations'])
            ->map(function ($combination) {
                return [
                    'sku' => $combination['sku'],
                    'values' => $combination['values'] ?? [],
                    'price_b2b' => (int) $combination['price_b2b'],
                    'price_dropship' => (int) $combination['price_dropship'],
                    'price_host_live' => (int) $combination['price_host_live'],
                ];
            })
            ->values()
            ->all();

        $product->name = $validated['name'];
        $product->category_id = $validated['category_id'] ?? null;
        $product->description = $validated['description'] ?? null;
        $product->images = $imagePaths;
        $product->variants = $validated['variants'] ?? [];
        $product->combinations = $combinations;

        $product->save();

        $removedImages = array_diff($currentImages, $existingImages);

        foreach ($removedImages as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('owner.products.index')
            ->with('success', 'Produk berhasil diperbarui.');
    }

    private function generateUniqueId(): string
    {
        do {
            $uniqueId = str_pad(
                (string) random_int(0, 999999999999),
                12,
                '0',
                STR_PAD_LEFT
            );
        } while (Product::where('unique_id', $uniqueId)->exists());

        return $uniqueId;
    }
}
