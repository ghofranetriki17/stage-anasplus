<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchAvailabilityController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\ChargeController;
use App\Http\Controllers\Api\CategoryController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Branch routes
Route::apiResource('branches', BranchController::class);

// Branch availability routes
Route::prefix('branches/{branchId}/availabilities')->group(function () {
    Route::get('/', [BranchAvailabilityController::class, 'index']);
    Route::post('/', [BranchAvailabilityController::class, 'store']);
    Route::get('/{id}', [BranchAvailabilityController::class, 'show']);
    Route::put('/{id}', [BranchAvailabilityController::class, 'update']);
    Route::delete('/{id}', [BranchAvailabilityController::class, 'destroy']);
});

// Route to get availability for a specific day
Route::get('branches/{branchId}/availability/{dayOfWeek}', [BranchAvailabilityController::class, 'getAvailabilityForDay']);

// Machine routes
Route::apiResource('machines', MachineController::class);

// Route to get machines by branch
Route::get('branches/{branchId}/machines', [MachineController::class, 'getByBranch']);

// Charge routes
Route::apiResource('charges', ChargeController::class);

// Category routes
Route::apiResource('categories', CategoryController::class);