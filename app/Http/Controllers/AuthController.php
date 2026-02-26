<?php

namespace App\Http\Controllers;

use App\Mail\NewUserConfirmation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    public function storeUser(Request $request): RedirectResponse | View {
        //Form validation:
        $request->validate(
            [
                "username" => "required|min:3|max:30|unique:users,username",
                "email" => "required|email|unique:users,email",
                "password" => "required|min:8|max:32|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/",
                "password_confirmation" => "required|same:password"
            ],

            [
                "username.required" => "O usuário é obrigatório.",
                "username.min" => "O usuário deve ter no mínimo :min caracteres.",
                "username.max" => "O usuário deve ter no máximo :max caracteres.",
                "username.unique" => "Esse nome não pode ser usado.",
                "email.required" => "O email é obrigatório.",
                "email.email" => "O email deve ser um endereço de email válido.",
                "email.unique" => "Esse email não pode ser usado.",
                "password.required" => "A senha é obrigatório.",
                "password.min" => "A senha deve ter no mínimo :min caracteres.",
                "password.max" => "A senha deve ter no mínimo :min caracteres.",
                "password.regex" => "A senha deve conter pelo menos uma letra maiúscula, uma letra mnúscula e um número.",
                "password_confirmation.required" => "A confirmação de senha é obrigatório.",
                "password_confirmation.same" => "A confirmção de senha deve ser igual à senha."
            ]
        );

        //Vamos criar um novo usuário definindo um token de verificação de email:
        $user = new User();
        $user->username = $request->username;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->token = Str::random(64);

        //Gerar link:
        $confirmation_link = route("new_user_confirmation", ["token" => $user->token]);

        //Enviar email:
        $result = Mail::to($user->email)->send(new NewUserConfirmation($user->username, $confirmation_link));

        //Verificar se o email foi verificado com sucesso:
        if (!$result) {
            return back()->withInput()->with(["server_error" => "Ocorreu um erro ao enviar o email de confirmação."]);
        }

        //Criar o usuário na base de dados:
        $user->save();

        //Apresentar view de sucesso:
        return view("auth.email_sent", ["email" => $user->email]);
    }

    public function newUserConfirmation($token) {
        //Verificar se o token é válido:
        $user = User::where("token", $token)->first();

        if (!$user) {
            return redirect()->route("login");
        }

        //Confirmar o registro do usuário:
        $user->email_verified_at = Carbon::now();
        $user->token = null;
        $user->active = 1;
        $user->save();

        //Autenticação automática do usuário confirmado:
        Auth::login($user);

        //Apresenta uma mensagem de sucesso:
        return view("auth.new_user_confirmation");
    }

    public function profile(): View {
        return view("auth.profile");
    }

    public function changePassword(Request $request) {
        $request->validate(
            [
                "current_password" => "required|min:8|max:32|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/",
                "new_password" => "required|min:8|max:32|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|different:current_password",
                "new_password_confirmation" => "required|same:new_password"
            ],

            [
                "current_password.required" => "A senha atual é obrigatória.",
                "current_password.min" => "A senha atual deve conter no mínimo :min caracteres.",
                "current_password.max" => "A senha atual deve conter no máximo :max caracteres.",
                "current_password.regex" => "A senha atual deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número.",
                "new_password.required" => "A nova senha é obrigatória.",
                "new_password.min" => "A nova senha deve conter no mínimo :min caracteres.",
                "new_password.max" => "A nova senha deve conter no máximo :max caracteres.",
                "new_password.regex" => "A nova senha deve conter pelo menos uma letra maiúscula, uma letra minúscula e um número.",
                "new_password.different" => "A nova senha deve ser diferente da senha atual.",
                "new_password_confirmation.required" => "A confirmação da nova senha é obrigatória.",
                "new_password_confirmation.same" => "A confirmação da nova senha deve ser igual à nova senha."
            ]
        );

        //Verificar se a password atual (current_password) está correta:
        if (!password_verify($request->current_password, Auth::user()->password)) {
            return back()->withInput()->with(
                [
                    "server_error" => "A senha atual não está correta."
                ]
            );
        }

        //Atualizar a senha na base de dados:
        $user = Auth::user();
        $user->password = bcrypt($request->new_password);
        $user->save();

        //Atualizar a passsword na sessão:
        Auth::user()->password = $request->new_password;

        //Apresentar uma mensagem de sucesso:
        return redirect()->route("profile")->with(
            [
                "success" => "A senha foi atualizada com sucesso!"
            ]
        );
    }

    public function forgotPassword() {
        return view("auth.forgot_password");
    }

    public function sendResetPasswordLink(Request $request) {
        //Form validation:
        $request->validate(
            [
                "email" => "required|email"
            ],

            [
                "email.required" => "O email é obrigatório.",
                "email.email" => "O email deve ser um endereço de email válido."
            ]
        );

        $generic_message = "Verifique a sua caixa de correio para prosseguir com a recuperação de senha.";

        //Verificar se email existe:
        $user = User::where("email", $request->email)->first();

        if (!$user) {
            return back()->with(
                [
                    "server_message" => $generic_message
                ]
            );
        }
        
        /*
            Resto da lógica.
        */

        dd("aqui");

        return back()->with(
            [
                "server_message" => $generic_message
            ]
        );        
    }
}