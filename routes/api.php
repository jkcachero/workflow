<?php

use App\Http\Controllers\TodoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/todos', [TodoController::class, 'store'])->name('api.todos.store');
    Route::get('/todos', [TodoController::class, 'index'])->name('api.todos.index');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('api.todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('api.todos.destroy');
});

