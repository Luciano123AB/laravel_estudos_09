<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

//Usuários não autenticados.
Route::middleware("guest")->group(function() {
    Route::get("/login", [AuthController::class, "login"])->name("login");
    Route::post("/login", [AuthController::class, "authenticate"])->name("authenticate");
});

Route::middleware("auth")->group(function() {
    Route::get("/", function() {
        echo "Olá Mundo!";
    })->name("home");
});