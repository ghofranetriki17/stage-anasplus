<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // ← ADD THIS LINE!

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->query('q');
        $per = (int)($request->query('per_page', 12));

        $query = Movement::query()->withCount('exercises');
        if ($q) {
            $query->where(fn($qq) => $qq
                ->where('name', 'like', "%$q%")
                ->orWhere('description', 'like', "%$q%"));
        }
        return response()->json($query->orderByDesc('id')->paginate($per));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'video_url'   => 'nullable|url|max:2048',
            'media'       => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:51200',
        ]);

        $mediaUrl = null;
        $mediaType = null;

        if ($request->hasFile('media')) {
            $path = $request->file('media')->store('movements', 'public');
            $mediaUrl = Storage::url($path);
            $mime = $request->file('media')->getMimeType();
            $mediaType = str_starts_with($mime, 'image') ? 'image' : 'video';
        } elseif (!empty($validated['video_url'])) {
            $mediaUrl = $validated['video_url'];
            $mediaType = 'video';
        }

        $movement = Movement::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'video_url'   => $validated['video_url'] ?? null,
            'media_url'   => $mediaUrl,
            'media_type'  => $mediaType,
        ]);

        return response()->json(['success' => true, 'data' => $movement], 201);
    }

    public function show(Movement $movement)
    {
        return response()->json(['success' => true, 'data' => $movement->load('exercises')]);
    }

    public function update(Request $request, Movement $movement)
    {
        $validated = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'video_url'    => 'nullable|url|max:2048',
            'media'        => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm|max:51200',
            'remove_media' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_media')) {
            if ($movement->media_url && str_starts_with($movement->media_url, '/storage/')) {
                $rel = str_replace('/storage/', '', $movement->media_url);
                Storage::disk('public')->delete($rel);
            }
            $movement->media_url = null;
            $movement->media_type = null;
        }

        if ($request->hasFile('media')) {
            if ($movement->media_url && str_starts_with($movement->media_url, '/storage/')) {
                $rel = str_replace('/storage/', '', $movement->media_url);
                Storage::disk('public')->delete($rel);
            }
            $path = $request->file('media')->store('movements', 'public');
            $movement->media_url = Storage::url($path);
            $movement->media_type = str_starts_with($request->file('media')->getMimeType(), 'image') ? 'image' : 'video';
        }

        if (array_key_exists('video_url', $validated)) {
            $movement->video_url = $validated['video_url'];
            if ($validated['video_url']) {
                $movement->media_url = $validated['video_url'];
                $movement->media_type = 'video';
            }
        }

        if (array_key_exists('name', $validated)) $movement->name = $validated['name'];
        if (array_key_exists('description', $validated)) $movement->description = $validated['description'];

        $movement->save();

        return response()->json(['success' => true, 'data' => $movement]);
    }

    public function destroy(Movement $movement)
    {
        Log::info('Attempting to delete movement', ['movement_id' => $movement->id, 'name' => $movement->name]);
        
        try {
            DB::beginTransaction();

            // Check related exercises and FORCE DELETE them
            $relatedExercises = DB::table('exercises')
                ->where('movement_id', $movement->id)
                ->get();
            
            $exerciseCount = $relatedExercises->count();
            
            Log::info('Found related exercises', [
                'movement_id' => $movement->id, 
                'exercise_count' => $exerciseCount,
                'exercise_ids' => $relatedExercises->pluck('id')->toArray()
            ]);
            
            if ($exerciseCount > 0) {
                // FORCE DELETE: Remove related exercises first
                Log::info('Force deleting related exercises before movement deletion', [
                    'movement_id' => $movement->id,
                    'exercises_to_delete' => $relatedExercises->pluck('id')->toArray()
                ]);
                
                // Delete from pivot tables first (exercise_workout, etc.)
                DB::table('exercise_workout')
                    ->whereIn('exercise_id', $relatedExercises->pluck('id'))
                    ->delete();
                
                // Delete the exercises themselves
                $deletedExercises = DB::table('exercises')
                    ->where('movement_id', $movement->id)
                    ->delete();
                
                Log::info('Deleted related exercises', [
                    'movement_id' => $movement->id,
                    'deleted_count' => $deletedExercises
                ]);
            }

            // Clean up media files BEFORE deletion
            if ($movement->media_url && str_starts_with($movement->media_url, '/storage/')) {
                $rel = str_replace('/storage/', '', $movement->media_url);
                if (Storage::disk('public')->exists($rel)) {
                    Storage::disk('public')->delete($rel);
                    Log::info('Deleted media file', ['file' => $rel]);
                }
            }

            // Now delete the movement itself
            $movementId = $movement->id;
            $movementName = $movement->name;
            
            $deleted = $movement->delete();
            
            if (!$deleted) {
                DB::rollBack();
                Log::error('Movement delete() returned false', ['movement_id' => $movementId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete movement from database'
                ], 500);
            }
            
            // Verify it's actually gone
            $stillExists = Movement::find($movementId);
            if ($stillExists) {
                DB::rollBack();
                Log::error('Movement still exists after delete', ['movement_id' => $movementId]);
                return response()->json([
                    'success' => false,
                    'message' => 'Movement deletion failed - record still exists'
                ], 500);
            }
            
            DB::commit();
            
            $successMessage = $exerciseCount > 0 
                ? "Movement '{$movementName}' and {$exerciseCount} related exercise(s) deleted successfully"
                : "Movement '{$movementName}' deleted successfully";
            
            Log::info('Movement force deletion completed', [
                'movement_id' => $movementId, 
                'name' => $movementName,
                'exercises_deleted' => $exerciseCount
            ]);

            return response()->json([
                'success' => true,
                'message' => $successMessage
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            
            Log::error('Database error during force deletion', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Database error occurred while deleting movement: ' . $e->getMessage()
            ], 500);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Unexpected error during force deletion', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}