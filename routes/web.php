<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Route;

//Usuários não autenticados.
Route::middleware("guest")->group(function() {
    //Login routes:
    Route::get("/login", [AuthController::class, "login"])->name("login");
    Route::post("/login", [AuthController::class, "authenticate"])->name("authenticate");

    //Registration routes:
    Route::get("/register", [AuthController::class, "register"])->name("register");
    Route::post("/register", [AuthController::class, "storeUser"])->name("store_user");

    //New user confirmation:
    Route::get("/new_user_confirmation/{token}", [AuthController::class, "newUserConfirmation"])->name("new_user_confirmation");

    //Forgot password:
    Route::get("/forgot_password", [AuthController::class, "forgotPassword"])->name("forgot_password");
    Route::post("/forgot_password", [AuthController::class, "sendResetPasswordLink"])->name("send_reset_password_link");
});

Route::middleware("auth")->group(function() {
    Route::get("/", [MainController::class, "home"])->name("home");
    
    //Profile - Change Password:
    Route::get("/profile", [AuthController::class, "profile"])->name("profile");
    Route::post("/profile", [AuthController::class, "changePassword"])->name("change_password");
    
    //Logout:
    Route::get("/logout", [AuthController::class, "logout"])->name("logout");
});