<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register()
    {
        $validator = Validator::make(request()->all(),[
            'name'      => 'required',
            'email'     => 'required|email|unique:users',
            'password'  => 'required',
            'role'      => 'required|in:admin,member',
        ]);

        if($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $user = User::create([
            'name'      => request('name'),
            'email'     => request('email'),
            'password'  => Hash::make(request('password')),
            'role'      => request('role'),
        ]);

        if($user) {
            return response()->json(['message' => 'Account registration successfully']);
        } else {
            return response()->json(['message' => 'Account registration failed']);
        }
    }

    public function login()
    {
        $validator = Validator::make(request()->all(),[
            'email'     => 'required|email',
            'password'  => 'required',
        ]);

        if($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $credentials = request(['email', 'password']);

        $user = User::where('email', request('email'))->first();

        if(! $user){
            return response()->json(['status' => 'The credentials you entered did not match our records.'], 422);
        }

        $claims = [
            'name'  => $user->name,
            'email' => $user->email,
        ];

        if (! $token = auth()->claims($claims)->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        return response()->json(auth()->user(), 200);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
