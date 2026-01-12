<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')
            ->select('IdUser', 'UserName', 'Email', 'Role', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }
    
    public function updateRole(Request $request, $idUser)
    {
        $request->validate([
            'Role' => 'required|exists:roles,IdRole'
        ]);

        $user = User::findOrFail($idUser);

        $user->Role = $request->Role;
        $user->save();

        return response()->json([
            'message' => 'Cập nhật vai trò người dùng thành công',
            'data' => [
                'IdUser' => $user->IdUser,
                'Role' => $user->Role
            ]
        ]);
    }
}
