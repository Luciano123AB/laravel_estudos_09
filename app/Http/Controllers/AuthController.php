<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View {
        return view("auth.login");
    }

    public function authenticate(Request $request): RedirectResponse {
        
        //Validação do form:
        $credentials = $request->validate(
            [
                "username" => "required|min:3|max:30",
                "password" => "required|min:8|max:32|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/"
            ],

            [
                "username.required" => "O usuário é obrigatório.",
                "username.min" => "O usuário deve conter no mínimo :min caracteres.",
                "username.max" => "O usuário deve conter no mínimo :max caracteres.",
                "password.required" => "A senha é obrigatório.",
                "password.min" => "A senha deve conter no mínimo :min caracteres.",
                "password.max" => "A senha deve conter no mínimo :max caracteres.",
                "password.regex" => "A senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número."
            ]
        );

        //Login tradicional do Laravel:
        // if (Auth::attempt($credentials)) {
        //     $request->session()->regenerate();

        //     return redirect()->route("home");
        // } //Só usar se tem email e password.

        //Verificar se o user existe:
        $user = User::where("username", $credentials["username"])
                    ->where("active", true)
                    ->where(function($query) {
                        $query->whereNull("blocked_until")
                            ->orWhere("blocked_until", "<=", now());
                    })
                    ->whereNotNull("email_verified_at")
                    ->whereNull("deleted_at")
                    ->first();
        
        //Verifica se o user existe:
        if (!$user) {
            return back()->withInput()->with([
                "invalid_login" => "Login inválido!"
            ]);
        }

        //Verificar se a password é válida:
        if (!password_verify($credentials["password"], $user->password)) {
            return back()->withInput()->with([
                "invalid_login" => "Login inválido!"
            ]);
        }

        //Atualizar o último login (last_login_at):
        $user->last_login_at = now();
        $user->blocked_until = null;
        $user->save();

        //Login propriamente dito:
        $request->session()->regenerate();
        Auth::login($user);

        //Renderizar:
        return redirect()->intended(route("home"));
    }

    public function logout(): RedirectResponse {
        //Logout:
        Auth::logout();
        
        return redirect()->route("login");
    }

    public function register(): View {
        return view("auth.register");
    }

    public function storeUser(Request $request): void {
        //Form validation:
        $request->validate(
            [
                "username" => "required|min:3|max:30|unique:users,username",
                "email" => "required|email|unique:users,email"
            ]
        );

        echo "FIM!";
    }
}