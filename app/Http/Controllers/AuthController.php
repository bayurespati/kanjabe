<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $fields = $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('nik', $fields['nik'])->with(['regional', 'witel', 'mitra'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json([
                'message' => 'NIK or password is incorect'
            ], 401);
        }

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Success login',
            'user' => $user,
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Success logout',
        ], 200);
    }
}
