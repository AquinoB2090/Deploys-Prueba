<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\MisionController;
use App\Http\Controllers\RegistroController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', DashboardController::class);

Route::post('/registro', [RegistroController::class, 'store']);

Route::get('/estudiantes', [EstudianteController::class, 'index']);
Route::get('/estudiantes/{carnet}', [EstudianteController::class, 'show']);
Route::post('/estudiantes', [EstudianteController::class, 'store']);
Route::match(['put', 'patch'], '/estudiantes/{carnet}', [EstudianteController::class, 'update']);
Route::delete('/estudiantes/{carnet}', [EstudianteController::class, 'destroy']);

Route::get('/misiones', [MisionController::class, 'index']);
Route::post('/misiones', [MisionController::class, 'store']);
Route::match(['put', 'patch'], '/misiones/{id}', [MisionController::class, 'update']);
Route::delete('/misiones/{id}', [MisionController::class, 'destroy']);

Route::options('/{any}', fn () => response()->noContent())->where('any', '.*');
