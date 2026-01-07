<?php

namespace App\Http\Controllers\Api;

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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'IdProduct'    => 'required|string|max:3|unique:products',
            'IdCategory'   => 'required|string|max:2',
            'NameProduct'  => 'required|string|max:100',
            'IdProductVar' => 'required|string|max:3|unique:product_variants',
            'Color'        => 'required|string',
            'Price'        => 'required|numeric',
            'Stock'        => 'required|integer',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        DB::beginTransaction();
        try {
            $product = Product::create($request->only(['IdProduct', 'IdCategory', 'NameProduct', 'Decription']));
            
            ProductVariant::create([
                'IdProductVar' => $request->IdProductVar,
                'IdProduct'    => $request->IdProduct,
                'Color'        => $request->Color,
                'Price'        => $request->Price,
                'ImgPath'      => $request->ImgPath,
                'Stock'        => $request->Stock,
            ]);

            DB::commit();
            return response()->json(['message' => 'Thêm thành công', 'data' => $product->load('variants')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Không tìm thấy'], 404);

        DB::beginTransaction();
        try {
            $product->update($request->only(['IdCategory', 'NameProduct', 'Decription']));

            if ($request->has('IdProductVar')) {
                $variant = ProductVariant::where('IdProduct', $id)->first();
                if ($variant) {
                    $variant->update($request->only(['Color', 'Price', 'ImgPath', 'Stock']));
                }
            }

            DB::commit();
            return response()->json(['message' => 'Cập nhật thành công', 'data' => $product->load('variants')]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['message' => 'Không tìm thấy'], 404);

        ProductVariant::where('IdProduct', $id)->delete();
        $product->delete();

        return response()->json(['message' => 'Đã xóa sản phẩm và các biến thể liên quan']);
    }
}