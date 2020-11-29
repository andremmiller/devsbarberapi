<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['create', 'login', 'unauthorized']]);
    }

    public function create(Request $request) {
        $array = ['error' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if(!$validator->fails()) {
            $emailExists = User::where('email', $request->input('email'))->count();

            if($emailExists === 0) {
                $newUser = new User();
                $newUser->name = $request->input('name');
                $newUser->email = $request->input('email');
                $newUser->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
                $newUser->save();

                $token = auth()->attempt([
                    'email' => $request->input('email'),
                    'password' => $request->input('password')
                ]);

                if(!$token) {
                    $array['error'] = 'Erro ao realizar o login.';
                } else {
                    $info = auth()->user();
                    $info['avatar'] = url('media/avatars/'.$info['avatar']);
                    $array['data'] = $info;
                    $array['token'] = $token;
                }

            } else {
                $array['error'] = 'Email já cadastrado';
            }
        } else {
            $array['error'] = 'Dados incorretos';
        }

        return $array;
    }

    public function login(Request $request) {
        $array = ['error' => ''];

        $token = auth()->attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password')
        ]);

        if(!$token) {
            $array['error'] = 'Usuário e/ou senha incorretos.';
        } else {
            $info = auth()->user();
            $info['avatar'] = url('media/avatars/'.$info['avatar']);
            $array['data'] = $info;
            $array['token'] = $token;
        }

        return $array;
    }

    public function logout() {
        auth()->logout();
        return ['error' => ''];
    }

    public function refresh() {
        $array = ['error' => ''];

        $token = auth()->refresh();
        $info = auth()->user();
        $info['avatar'] = url('media/avatars/'.$info['avatar']);
        $array['data'] = $info;
        $array['token'] = $token;

        return $array;
    }

    public function unauthorized() {
        return response()->json(['error' => 'Não autorizado'], 401);
    }
}
