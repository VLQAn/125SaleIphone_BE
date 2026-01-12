<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return response()->json(['success' => true, 'data' => $user], 200);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'UserName' => 'string|max:255',
            'Email'    => 'email|max:255|unique:users,Email,' . $user->IdUser . ',IdUser',
            'Phone'    => 'nullable|string|max:20',
            'Address'  => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        try {
            $user->update($request->only(['UserName', 'Email', 'Phone', 'Address']));
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công', 'data' => $user]);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Lỗi hệ thống: ' . $ex->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'currentPassword' => 'required',
            'newPassword'     => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 400);
        }

        if (!Hash::check($request->currentPassword, $user->Password)) {
            return response()->json(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng'], 400);
        }

        try {
            $user->Password = Hash::make($request->newPassword);
            $user->save();
            
            return response()->json(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
        } catch (\Exception $ex) {
            return response()->json(['success' => false, 'message' => 'Có lỗi xảy ra'], 500);
        }
    }
}