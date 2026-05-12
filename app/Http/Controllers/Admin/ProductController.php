<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ProductsExport;
use App\Exports\ProductsImportTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\CreateRequest;
use App\Http\Requests\Admin\Products\UpdateRequest;
use App\Imports\ProductsImport;
use App\Models\Country;
use App\Models\Product;
use App\Services\BranchService;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\VariantService;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    protected ProductService $service;

    protected CategoryService $categoryService;

    protected VendorService $vendorService;

    protected VariantService $variantService;

    protected BranchService $branchService;

    public function __construct(
        ProductService $service,
        CategoryService $categoryService,
        VendorService $vendorService,
        VariantService $variantService,
        BranchService $branchService
    ) {
        $this->service = $service;
        $this->categoryService = $categoryService;
        $this->vendorService = $vendorService;
        $this->variantService = $variantService;
        $this->branchService = $branchService;
    }

    /**
     * Display a listing of the products.
     */
    public function index(): View|JsonResponse
    {
        $filters = [
            'search' => request()->get('search', ''),
            'status' => request()->get('status', ''),
            'featured' => request()->get('featured', ''),
            'approved' => request()->get('approved', ''),
            'type' => request()->get('type', ''),
            'vendor_id' => request()->get('vendor_id', ''),
            'category_id' => request()->get('category_id', ''),
            'stock' => request()->get('stock', ''),
            'min_price' => request()->get('min_price', ''),
            'max_price' => request()->get('max_price', ''),
            'is_new' => request()->get('is_new', ''),
            'is_bookable' => request()->get('is_bookable', ''),
            'sort' => request()->get('sort', ''),
        ];

        $products = $this->service->getPaginatedProducts(15, $filters);

        // If AJAX request, return JSON
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'html' => view('admin.products.partials.table', compact('products'))->render(),
                'pagination' => view('admin.products.partials.pagination', compact('products'))->render(),
            ]);
        }

        $categories = $this->categoryService->getCategoryTree();
        $vendors = $this->vendorService->getActiveVendors();

        return view('admin.products.index', compact('products', 'filters', 'categories', 'vendors'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $categories = $this->categoryService->getCategoryTree();
        $vendors = $this->vendorService->getActiveVendors();
        $variants = $this->variantService->getActiveVariants()->load('options'); // Load options with variants
        $allProducts = $this->service->getAllProducts(); // For related products selection
        $countries = Country::query()->active()->ordered()->get();

        return view('admin.products.create', compact('categories', 'vendors', 'variants', 'allProducts', 'countries'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(CreateRequest $request): RedirectResponse
    {
        try {
            $this->service->createProduct($request);

            return redirect()->route('admin.products.index')
                ->with('success', __('Product created successfully.'));
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first('variations')
                ?: $e->validator->errors()->first()
                ?: __('Unable to save product. Please review variation SKUs and try again.');

            return redirect()->route('admin.products.create')
                ->with('error', $message);
        } catch (\Exception $e) {
            return redirect()->route('admin.products.create')
                ->with('error', __('Unable to save product. Please review variation SKUs and try again.'));
        }
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        $product = $this->service->getProductById($product->id);

        // Get vendor branches to show branch stock breakdown
        $vendorBranches = null;
        if ($product->vendor_id) {
            $vendorBranches = $this->branchService->getBranchesByVendor($product->vendor_id);
        }

        return view('admin.products.show', compact('product', 'vendorBranches'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $product = $this->service->getProductById($product->id);
        $categories = $this->categoryService->getCategoryTree();
        $vendors = $this->vendorService->getActiveVendors();
        $variants = $this->variantService->getActiveVariants()->load('options'); // Load options with variants
        $allProducts = $this->service->getAllProducts(); // For related products selection
        $countries = Country::query()->active()->ordered()->get();

        // Load product relationships for editing
        $product->load([
            'variants.values.variantOption',
            'images',
            'categories',
            'countries',
            'relations',
            'branchProductStocks',
            'variants.branchVariantStocks',
        ]);

        // Prepare existing product data for JavaScript
        $existingVariations = [];
        $selectedVariantOptions = [];
        if ($product->type === 'variable' && $product->variants && $product->variants->count() > 0) {
            foreach ($product->variants as $variant) {
                $optionIds = [];
                $variantId = null;

                // Get variant option IDs from variant values
                if ($variant->values && $variant->values->count() > 0) {
                    foreach ($variant->values as $value) {
                        // Ensure variantOption is loaded
                        if (! $value->relationLoaded('variantOption') && $value->variant_option_id) {
                            $value->load('variantOption');
                        }

                        if ($value->variantOption) {
                            $variantId = $value->variantOption->variant_id;
                            $optionIds[] = $value->variant_option_id;
                        }
                    }
                }

                if (! empty($optionIds) && $variantId) {
                    if (! isset($selectedVariantOptions[$variantId])) {
                        $selectedVariantOptions[$variantId] = [];
                    }
                    $selectedVariantOptions[$variantId] = array_unique(array_merge($selectedVariantOptions[$variantId], $optionIds));
                }

                // Prepare variation data
                $branchStocks = [];
                if ($variant->branchVariantStocks && $variant->branchVariantStocks->count() > 0) {
                    foreach ($variant->branchVariantStocks as $stock) {
                        $branchStocks[$stock->branch_id] = $stock->quantity;
                    }
                }

                $values = [];
                if ($variant->values && $variant->values->count() > 0) {
                    foreach ($variant->values as $value) {
                        // Ensure variantOption is loaded
                        if (! $value->relationLoaded('variantOption') && $value->variant_option_id) {
                            $value->load('variantOption');
                        }

                        if ($value->variantOption) {
                            $values[] = [
                                'variant_id' => $value->variantOption->variant_id,
                                'option_id' => $value->variant_option_id,
                            ];
                        }
                    }
                }

                // Always add variant to existingVariations, even if it has no values
                $existingVariations[] = [
                    'name' => $variant->getTranslation('name', app()->getLocale()),
                    'name_en' => $variant->getTranslation('name', 'en'),
                    'name_ar' => $variant->getTranslation('name', 'ar'),
                    'sku' => $variant->sku ?? '',
                    'price' => $variant->price ?? 0,
                    'thumbnailPreview' => $variant->thumbnail ? $variant->thumbnail : null,
                    'thumbnailFile' => null,
                    'values' => $values,
                    'branchStocks' => $branchStocks,
                ];
            }
        }

        // Get vendor branches if product has a vendor
        $vendorBranches = collect();
        if ($product->vendor_id) {
            $vendorBranches = $this->branchService->getBranchesByVendor($product->vendor_id);
        }

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'vendors',
            'variants',
            'allProducts',
            'existingVariations',
            'selectedVariantOptions',
            'vendorBranches',
            'countries'
        ));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->service->updateProduct($request, $product);

            return redirect()->route('admin.products.index')
                ->with('success', __('Product updated successfully.'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', __('Unable to update product. Please review variation SKUs and try again.'));
        }
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse|JsonResponse
    {
        try {
            $this->service->deleteProduct($product);

            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Product deleted successfully.'),
                ]);
            }

            return redirect()->route('admin.products.index')
                ->with('success', __('Product deleted successfully.'));
        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to delete product: :error', ['error' => $e->getMessage()]),
                ], 422);
            }

            return redirect()->back()
                ->with('error', __('Failed to delete product: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Toggle product active status.
     */
    public function toggleActive(Product $product): JsonResponse
    {
        try {
            $product = $this->service->toggleActive($product);

            return response()->json([
                'success' => true,
                'message' => __('Product status updated successfully.'),
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update product status: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Toggle product featured status.
     */
    public function toggleFeatured(Product $product): JsonResponse
    {
        try {
            $product = $this->service->toggleFeatured($product);

            return response()->json([
                'success' => true,
                'message' => __('Product featured status updated successfully.'),
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update product featured status: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Toggle product approved status.
     */
    public function toggleApproved(Product $product): JsonResponse
    {
        try {
            $product = $this->service->toggleApproved($product);

            return response()->json([
                'success' => true,
                'message' => __('Product approval status updated successfully.'),
                'product' => $product,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update product approval status: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }

    /**
     * Export products
     */
    public function export(Request $request): BinaryFileResponse
    {
        $filters = [
            'search' => $request->get('search', ''),
            'status' => $request->get('status', ''),
            'featured' => $request->get('featured', ''),
            'approved' => $request->get('approved', ''),
            'type' => $request->get('type', ''),
            'vendor_id' => $request->get('vendor_id', ''),
            'category_id' => $request->get('category_id', ''),
        ];

        $filename = 'products_export_'.date('Y-m-d_His').'.xlsx';

        return Excel::download(new ProductsExport($filters), $filename);
    }

    /**
     * Show import form
     */
    public function showImport(): View
    {
        return view('admin.products.import');
    }

    /**
     * Handle products import (queued)
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // 10MB max
        ]);

        try {
            // Store file temporarily
            $file = $request->file('file');
            $filePath = $file->store('imports', 'local');

            // Queue the import
            $userId = Auth::check() ? Auth::id() : null;
            $import = new ProductsImport($userId);
            Excel::queueImport($import, $filePath, 'local');

            return redirect()->route('admin.products.index')
                ->with('success', __('Products import has been queued and will be processed in the background. You will be notified when it completes.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to queue products import: '.$e->getMessage());
        }
    }

    /**
     * Download import template
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $filename = 'products_import_template_'.date('Y-m-d').'.xlsx';

        return Excel::download(new ProductsImportTemplate, $filename);
    }

    /**
     * Sync product
     */
    public function syncProduct(Product $product): JsonResponse
    {
        try {
            $this->service->syncProduct($product);

            return response()->json([
                'success' => true,
                'message' => __('Product synced successfully.'),
            ]);
        } catch (\Exception $e) {

            Log::error('Sync failed', [
                'sku' => $product->sku,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to sync product: :error', [
                    'error' => $e->getMessage(),
                ]),
            ], 422);
        }
    }

    /**
     * Queue sync for all products stock/quantities from Daftra.
     */
    public function syncAllProductsStock(): JsonResponse
    {
        try {
            Artisan::queue('products:sync-daftra-stock', [
                '--only-active' => true,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Sync job has been queued successfully.'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Queue sync all products stock failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('Failed to queue sync job: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
