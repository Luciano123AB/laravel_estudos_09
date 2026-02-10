<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function() {
    DB::connection()->getPdo();
    echo "Home";
});

Route::view("/teste", "teste")->middleware("auth");

Route::get("/login", function() {
    echo "Formulário de login!";
})->name("login");

Route::middleware("guest")->group(function() {
    Route::get("/register", function() {
        echo "Formulário de registro!";
    })->name("register");
});

Route::get("/register", function() {
    echo "Formulário de registro!";
})->name("register")->middleware("guest");