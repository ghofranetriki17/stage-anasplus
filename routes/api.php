<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchAvailabilityController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\ChargeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\ProgrammeController;
use App\Http\Controllers\Api\UserProgressController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // User Progress routes
    Route::apiResource('user-progresses', UserProgressController::class);
    Route::get('user-progresses/history', [UserProgressController::class, 'history']);
    
    // Workout routes - DÉPLACÉES DANS LE GROUPE PROTÉGÉ
    Route::apiResource('workouts', WorkoutController::class);
    Route::get('workouts/{workout}/exercises', [WorkoutController::class, 'getExercises']);
    Route::post('workouts/{workout}/exercises', [WorkoutController::class, 'addExercise']);
    Route::delete('workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'removeExercise']);
    Route::patch('workouts/{workout}/exercises/{exercise}/progress', [WorkoutController::class, 'updateExerciseProgress']);
});

// Branch routes (peuvent rester publiques si nécessaire)
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

// Programme routes
Route::apiResource('programmes', ProgrammeController::class);
Route::patch('programmes/{programme}/activate', [ProgrammeController::class, 'activate']);

// Movement routes
Route::apiResource('movements', MovementController::class);

// Exercise routes
Route::apiResource('exercises', ExerciseController::class);
Route::get('machines/{machine}/charges', [ExerciseController::class, 'getChargesForMachine']);