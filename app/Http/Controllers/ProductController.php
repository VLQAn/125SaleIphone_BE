<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function edit($id)
    {
        $product = Product::with('variants')->findOrFail($id);
        return response()->json($product);
    }

    // Cập nhật sản phẩm
    public function updateVariant(Request $request, $idProduct, $idVariant)
    {
        // Validate dữ liệu
        $request->validate([
            'Decription' => 'nullable|string|max:255',
            'Price' => 'required|integer|min:0',
            'Stock' => 'required|integer|min:0',
            'ImgPath' => 'nullable|url|max:255', 
        ]);

        DB::transaction(function() use ($request, $idProduct, $idVariant) {

            // Kiểm tra sản phẩm có tồn tại
            $product = Product::findOrFail($idProduct);

            // Lấy variant
            $variant = ProductVariant::where('IdProduct', $idProduct)
                ->where('IdProductVar', $idVariant)
                ->firstOrFail();

            // Cập nhật giá, số lượng, mô tả
            $variant->Price = $request->Price;
            $variant->Stock = $request->Stock;

            // Cập nhật mô tả sản phẩm (nếu muốn lưu chung)
            $product->Decription = $request->Decription ?? $product->Decription;
            $product->save();

            // Nếu upload ảnh mới
            if ($request->hasFile('ImgPath')) {
                // Xóa ảnh cũ nếu có
                if ($variant->ImgPath && Storage::exists($variant->ImgPath)) {
                    Storage::delete($variant->ImgPath);
                }

                $path = $request->file('ImgPath')->store('product_images', 'public');
                $variant->ImgPath = $path;
            }

            $variant->save();
        });

        return response()->json(['message' => 'Cập nhật variant thành công']);
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