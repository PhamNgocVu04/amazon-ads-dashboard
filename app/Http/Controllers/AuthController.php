<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate(["email"=>"required|email","password"=>"required"]);
        if (Auth::attempt($request->only("email","password"), $request->boolean("remember"))) {
            $request->session()->regenerate();
            return response()->json(["status"=>"success","user"=>["name"=>Auth::user()->name,"email"=>Auth::user()->email,"role"=>Auth::user()->role??"viewer"]]);
        }
        return response()->json(["status"=>"error","message"=>"Email or password incorrect"], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(["status"=>"success"]);
    }

    public function check(): JsonResponse
    {
        if (Auth::check()) {
            return response()->json(["authenticated"=>true,"user"=>["name"=>Auth::user()->name,"email"=>Auth::user()->email,"role"=>Auth::user()->role??"viewer"]]);
        }
        return response()->json(["authenticated"=>false], 401);
    }

    public function register(Request $request): JsonResponse
    {
        $request->validate(["name"=>"required|string|max:255","email"=>"required|email|unique:users","password"=>"required|min:6|confirmed"]);
        $user = User::create(["name"=>$request->name,"email"=>$request->email,"password"=>Hash::make($request->password)]);
        Auth::login($user);
        return response()->json(["status"=>"success","user"=>["name"=>$user->name,"email"=>$user->email,"role"=>$user->role??"viewer"]]);
    }
}
