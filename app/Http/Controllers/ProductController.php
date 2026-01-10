<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('variants')->get();
        return response()->json($products, 200);
    }

    public function show($id)
    {
        $product = Product::with('variants')->find($id);
        if (!$product) return response()->json(['message' => 'Không tìm thấy'], 404);
        return response()->json($product, 200);
    }

    public function store(Request $request)
    {
        $request->headers->set('Accept', 'application/json');

        $request->validate([
            'IdCategory' => 'required|string|size:2|exists:categories,IdCategory',
            'NameProduct' => 'required|string|max:100',
            'Decription' => 'required|string|max:255',

            'variants' => 'required|array|min:1',
            'variants.*.Color' => 'required|string|max:50',
            'variants.*.Price' => 'required|integer|min:0',
            'variants.*.ImgPath' => 'nullable|string|max:255',
            'variants.*.Stock' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();

        try {
            /* =============================
             * 1️⃣ TỰ SINH IdProduct
             * ============================= */
            $lastProductId = Product::max('IdProduct');
            $nextProductNumber = $lastProductId ? intval($lastProductId) : 0;
            $nextProductNumber++;

            $newProductId = str_pad($nextProductNumber, 3, '0', STR_PAD_LEFT);

            /* =============================
             * 2️⃣ TẠO PRODUCT
             * ============================= */
            $product = Product::create([
                'IdProduct' => $newProductId,
                'IdCategory' => $request->IdCategory,
                'NameProduct' => $request->NameProduct,
                'Decription' => $request->Decription,
            ]);

            /* =============================
             * 3️⃣ TỰ SINH IdProductVar
             * ============================= */
            $lastVarId = ProductVariant::max('IdProductVar');
            $nextVarNumber = $lastVarId ? intval($lastVarId) : 0;

            foreach ($request->variants as $variant) {
                $nextVarNumber++;

                ProductVariant::create([
                    'IdProductVar' => str_pad($nextVarNumber, 3, '0', STR_PAD_LEFT),
                    'IdProduct' => $product->IdProduct,
                    'Color' => $variant['Color'],
                    'Price' => $variant['Price'],
                    'ImgPath' => $variant['ImgPath'] ?? null,
                    'Stock' => $variant['Stock'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Thêm sản phẩm thành công',
                'data' => $product->load('variants')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Lỗi khi thêm sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->headers->set('Accept', 'application/json');

        $request->validate([
            'IdProduct' => 'required|string|size:3|exists:products,IdProduct',
            'IdCategory' => 'required|string|size:2|exists:categories,IdCategory',
            'NameProduct' => 'required|string|max:100',
            'Decription' => 'required|string|max:255',

            'variants' => 'required|array|min:1',
            'variants.*.IdProductVar' => 'nullable|string|size:3|exists:product_variants,IdProductVar',
            'variants.*.Color' => 'required|string|max:50',
            'variants.*.Price' => 'required|integer|min:0',
            'variants.*.Stock' => 'required|integer|min:0',
            'variants.*.ImgPath' => 'nullable|string|max:255',
        ]);

        /* =============================
        * 1️⃣ CHECK ID PRODUCT KHỚP URL
        * ============================= */
        if ($request->IdProduct !== $id) {
            return response()->json([
                'message' => 'IdProduct không khớp với URL'
            ], 422);
        }

        DB::beginTransaction();

        try {
            /* =============================
            * 2️⃣ UPDATE PRODUCT
            * ============================= */
            $product = Product::with('variants')->findOrFail($id);

            $product->update([
                'IdCategory' => $request->IdCategory,
                'NameProduct' => $request->NameProduct,
                'Decription' => $request->Decription,
            ]);

            /* =============================
            * 3️⃣ VARIANT HIỆN CÓ
            * ============================= */
            $currentVariantIds = $product->variants->pluck('IdProductVar')->toArray();
            $requestVariantIds = collect($request->variants)
                ->pluck('IdProductVar')
                ->filter()
                ->toArray();

            /* =============================
            * 4️⃣ XOÁ VARIANT BỊ REMOVE TRÊN FE
            * ============================= */
            ProductVariant::where('IdProduct', $id)
                ->whereNotIn('IdProductVar', $requestVariantIds)
                ->delete();

            /* =============================
            * 5️⃣ UPDATE / CREATE VARIANT
            * ============================= */
            $lastVarId = ProductVariant::max('IdProductVar');
            $nextVarNumber = $lastVarId ? intval($lastVarId) : 0;

            foreach ($request->variants as $variant) {
                if (!empty($variant['IdProductVar'])) {
                    // UPDATE
                    ProductVariant::where('IdProductVar', $variant['IdProductVar'])
                        ->update([
                            'Color' => $variant['Color'],
                            'Price' => $variant['Price'],
                            'Stock' => $variant['Stock'],
                            'ImgPath' => $variant['ImgPath'] ?? null,
                        ]);
                } else {
                    // CREATE NEW
                    $nextVarNumber++;

                    ProductVariant::create([
                        'IdProductVar' => str_pad($nextVarNumber, 3, '0', STR_PAD_LEFT),
                        'IdProduct' => $id,
                        'Color' => $variant['Color'],
                        'Price' => $variant['Price'],
                        'Stock' => $variant['Stock'],
                        'ImgPath' => $variant['ImgPath'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật sản phẩm thành công',
                'data' => $product->fresh('variants')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Lỗi khi cập nhật sản phẩm',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $product->delete(); // cascade xoá variant

        return response()->json([
            'message' => 'Xoá sản phẩm thành công'
        ]);
    }
}