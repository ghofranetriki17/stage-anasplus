<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\BranchAvailabilityController;
use App\Http\Controllers\Api\MachineController;
use App\Http\Controllers\Api\ChargeController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\ProgrammeController;
use App\Http\Controllers\Api\UserProgressController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\CoachController;
use App\Http\Controllers\Api\SpecialityController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CoachAvailabilityController;
use App\Http\Controllers\Api\GroupTrainingSessionController;
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
    Route::delete('workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'removeExercise']);
    Route::patch('workouts/{workout}/exercises/{exercise}/progress', [WorkoutController::class, 'updateExerciseProgress']);

});

// Branch routes (peuvent rester publiques si nécessaire)
Route::apiResource('branches', BranchController::class);
Route::post('/workouts/{workout}/exercises', [WorkoutController::class, 'addExercise']);

// Branch availability routes
Route::prefix('branches/{branchId}/availabilities')->group(function () {
    Route::get('/', [BranchAvailabilityController::class, 'index']);
    Route::post('/', [BranchAvailabilityController::class, 'store']);
    Route::get('/{id}', [BranchAvailabilityController::class, 'show']);
    Route::put('/{id}', [BranchAvailabilityController::class, 'update']);
    Route::delete('/{id}', [BranchAvailabilityController::class, 'destroy']);
});
Route::delete('/exercises/{exercise}', [ExerciseController::class, 'destroy']);
Route::patch('/workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'updateExercisePivot']);

Route::delete('/workouts/{workout}/exercises/{exercise}', [WorkoutController::class, 'removeExercise']);

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
// Coach routes
Route::apiResource('coaches', CoachController::class);

// Speciality routes
Route::apiResource('specialities', SpecialityController::class);

// Course routes
Route::apiResource('courses', CourseController::class);

// Coach Availability routes
Route::apiResource('coach-availabilities', CoachAvailabilityController::class);
Route::get('coaches/{coachId}/availabilities', [CoachAvailabilityController::class, 'getByCoach']);

// Group Training Session routes
Route::apiResource('group-training-sessions', GroupTrainingSessionController::class);
Route::get('branches/{branchId}/sessions', [GroupTrainingSessionController::class, 'getByBranch']);
Route::get('coaches/{coachId}/sessions', [GroupTrainingSessionController::class, 'getByCoach']);
Route::get('sessions/upcoming', [GroupTrainingSessionController::class, 'getUpcoming']);
Route::get('sessions/filter', [GroupTrainingSessionController::class, 'getSessionsByFilters']);
Route::post('sessions/{id}/join', [GroupTrainingSessionController::class, 'joinSession']);
Route::post('sessions/{id}/leave', [GroupTrainingSessionController::class, 'leaveSession']);

// Additional useful routes
Route::get('coaches/{coachId}/specialities', function($coachId) {
    $coach = \App\Models\Coach::with('specialities')->find($coachId);
    if (!$coach) {
        return response()->json(['success' => false, 'message' => 'Coach not found'], 404);
    }
    return response()->json(['success' => true, 'data' => $coach->specialities]);
});

Route::post('coaches/{coachId}/specialities', function(Request $request, $coachId) {
    $coach = \App\Models\Coach::find($coachId);
    if (!$coach) {
        return response()->json(['success' => false, 'message' => 'Coach not found'], 404);
    }
    
    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'speciality_ids' => 'required|array',
        'speciality_ids.*' => 'exists:specialities,id'
    ]);
    
    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }
    
    $coach->specialities()->sync($request->speciality_ids);
    return response()->json(['success' => true, 'message' => 'Specialities updated successfully']);
});
Route::get('/branches/{branch}/coaches', [BranchController::class, 'getCoaches']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sessions/{id}/book', [GroupTrainingSessionController::class, 'bookSession']);
    Route::post('/sessions/{id}/cancel', [GroupTrainingSessionController::class, 'cancelBooking']);
    Route::get('/user/bookings', [GroupTrainingSessionController::class, 'getUserBookings']);
});
use App\Http\Controllers\Api\VideoController;

Route::get('/coaches/{coachId}/videos', [VideoController::class, 'index']);
Route::post('/videos', [VideoController::class, 'store']);
Route::delete('/videos/{id}', [VideoController::class, 'destroy']);
// Add these routes to your api.php routes file

Route::middleware('auth:sanctum')->group(function () {
    // Group Training Sessions
    Route::get('/group-sessions', [GroupTrainingSessionController::class, 'index']);
    Route::post('/group-sessions', [GroupTrainingSessionController::class, 'store']);
    Route::get('/group-sessions/{id}', [GroupTrainingSessionController::class, 'show']);
    Route::get('/group-sessions/upcoming', [GroupTrainingSessionController::class, 'getUpcoming']);
    Route::get('/group-sessions/available', [GroupTrainingSessionController::class, 'getAvailable']);
    
    // Branch-specific sessions
    Route::get('/branches/{branchId}/sessions', [GroupTrainingSessionController::class, 'getByBranch']);
    
    // Booking endpoints
    Route::post('/group-sessions/{sessionId}/book', [GroupTrainingSessionController::class, 'bookSession']);
    Route::delete('/group-sessions/{sessionId}/book', [GroupTrainingSessionController::class, 'cancelBooking']);
    Route::get('/group-sessions/{sessionId}/booking-status', [GroupTrainingSessionController::class, 'checkBookingStatus']);
    Route::get('/group-sessions/{sessionId}/bookings', [GroupTrainingSessionController::class, 'getSessionBookings']);
    
    // User bookings
    Route::get('/user/bookings', [GroupTrainingSessionController::class, 'getUserBookings']);
});