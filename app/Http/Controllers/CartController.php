<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function index()
    {
        $userId = Auth::id(); 
        $cartItems = CartItem::where('IdCart', $userId)
            ->with('product')
            ->get();
        $goiYSanPhams = Product::inRandomOrder()->take(4)->get();

        return response()->json([
            'ChiTietGioHangList' => $cartItems,
            'GoiYSanPhams' => $goiYSanPhams
        ]);
    }
    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idSanPham' => 'required|string',
            'soLuong'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) return response()->json($validator->errors(), 400);

        $userId = Auth::id();
        $idProduct = $request->idSanPham;
        $quantity = $request->soLuong;
        $item = CartItem::where('IdCart', $userId)
            ->where('IdProduct', $idProduct)
            ->first();

        if ($item) {
            $item->update([
                'Quantity' => $item->Quantity + $quantity
            ]);
        } else {
            CartItem::create([
                'IdCartItem' => Str::upper(Str::random(5)),
                'IdCart' => $userId,
                'IdProduct' => $idProduct,
                'Quantity' => $quantity
            ]);
        }

        return response()->json(['message' => 'Thành công'], 200);
    }

    public function updateCart(Request $request)
    {
        $updates = $request->SoLuongCapNhat;

        foreach ($updates as $id => $quantity) {
            CartItem::where('IdCartItem', $id)->update(['Quantity' => $quantity]);
        }

        return response()->json(['message' => 'Đã cập nhật']);
    }

    public function removeFromCart($id)
    {
        CartItem::where('IdCartItem', $id)->delete();
        return response()->json(['message' => 'Đã xóa']);
    }

    public function clearCart()
    {
        $userId = Auth::id();
        CartItem::where('IdCart', $userId)->delete();
        return response()->json(['message' => 'Giỏ hàng đã trống']);
    }
}