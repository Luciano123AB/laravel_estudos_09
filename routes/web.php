<?php

use App\Http\Controllers\AuthController;
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
});

Route::middleware("auth")->group(function() {
    Route::get("/", function() {
        echo "Olá Mundo!";
    })->name("home");

    Route::get("/logout", [AuthController::class, "logout"])->name("logout");
});