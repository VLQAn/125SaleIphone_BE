<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Models\Cart;
use App\Models\CartItem;    
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartController extends Controller
{
    // xem gio hang (tao moi) gio hang 
    private function getCurrentCart(): Cart
    {
        $user = Auth::user();
        $cart = Cart::where('IdUser', $user->IdUser)->first();
        // tao id gio hang
        if (!$cart) {
            $lastCart = Cart::orderBy('IdCart', 'desc')->first();
            $number = $lastCart ? intval(substr($lastCart->IdCart, 1)) + 1 : 1;
            $newId = 'C' . str_pad($number, 4, '0', STR_PAD_LEFT);
            $cart = Cart::create([
                'IdCart' => $newId,
                'IdUser' => $user->IdUser,
            ]);
        }

        return $cart;
    }
// lay san pham yrong gio hang
    public function index()
    {
        $cart = $this->getCurrentCart();
        $cart->load('items.product');

        return response()->json([
            'success' => true,
            'cart_id' => $cart->IdCart,
            'data'    => $cart->items,
        ], 200);
    }
// them san pham vao gio hang
    public function addToCart(AddToCartRequest $request)
    {
        $data = $request->validated();
        $cart = $this->getCurrentCart();
// kiem tra san pham da co trong gio hang chua
        $item = $cart->items()
            ->where('IdProduct', $data['IdProduct'])
            ->first();
        // cap nhat so luong neu san pham da co
        if ($item) {
            $item->increment('Quantity', $data['Quantity']);
        } else {
            $lastItem = CartItem::orderBy('IdCartItem', 'desc')->first();
            $number = $lastItem ? intval(substr($lastItem->IdCartItem, 1)) + 1 : 1;
            $newIdItem = 'I' . str_pad($number, 4, '0', STR_PAD_LEFT);

            $cart->items()->create([
                'IdCartItem' => $newIdItem,
                'IdProduct'  => $data['IdProduct'],
                'Quantity'   => $data['Quantity'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng',
        ], 201);
    }

    public function updateCart(UpdateCartRequest $request)
    {
        $data = $request->validated();
        $cart = $this->getCurrentCart();
        $item = $cart->items()
            ->where('IdCartItem', $data['IdCartItem'])
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Sản phẩm không tồn tại trong giỏ hàng của bạn',
            ], 404);
        }

        $item->update(['Quantity' => $data['Quantity']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật số lượng thành công',
        ], 200);
    }
// xoa san pham khoi gio hang
    public function removeFromCart(string $id)
    {
        $cart = $this->getCurrentCart();
        $item = $cart->items()->where('IdCartItem', $id)->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền xóa sản phẩm này',
            ], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
        ], 200);
    }
// xoa het gio hang
    public function clearCart()
    {
        $cart = $this->getCurrentCart();
        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Giỏ hàng của bạn đã được làm trống',
        ], 200);
    }
}