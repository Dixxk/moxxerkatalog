<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;

class CatalogController extends Controller
{
    /**
     * Katalog utama.
     */
    public function index()
    {
        $products = Product::query()
            ->latest()
            ->get([
                'unique_id',
                'name',
                'category_id',
                'images',
                'combinations',
            ]);

        $categories = Category::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Catalog/Index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    /**
     * Katalog khusus Host Live.
     */
    public function hostLive()
    {
        $products = Product::query()
            ->latest()
            ->get([
                'unique_id',
                'name',
                'category_id',
                'images',
                'combinations',
            ])
            ->filter(function ($product) {
                $combinations = $product->combinations;

                if (!is_array($combinations)) {
                    return false;
                }

                foreach ($combinations as $combination) {
                    $price = $combination['price_host_live'] ?? null;

                    if (
                        $price !== null &&
                        $price !== '' &&
                        is_numeric($price) &&
                        (float) $price >= 0
                    ) {
                        return true;
                    }
                }

                return false;
            })
            ->values();

        $categories = Category::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        return Inertia::render('Catalog/HostLive', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
