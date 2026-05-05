<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Resources\NoteFlow\AiGenerationResource;
use App\Http\Resources\NoteFlow\NoteResource;
use App\Models\AiGeneration;
use App\Models\Note;
use App\Models\Upload;
use App\Models\UserNotification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function __invoke(Request $request)
    {
        $user = $request->user();

        $recentNotes = Note::query()
            ->where('user_id', $user->id)
            ->with('folder')
            ->latest('updated_at')
            ->limit(5)
            ->get();

        $recentAi = AiGeneration::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return $this->successResponse([
            'welcome_name' => $user->first_name,
            'stats' => [
                ['key' => 'total_notes', 'label' => 'Total Notes', 'value' => Note::where('user_id', $user->id)->count()],
                ['key' => 'ai_generations', 'label' => 'AI Generations', 'value' => AiGeneration::where('user_id', $user->id)->count()],
                ['key' => 'files_processed', 'label' => 'Files Processed', 'value' => Upload::where('user_id', $user->id)->count()],
                ['key' => 'active_reminders', 'label' => 'Active Reminders', 'value' => UserNotification::where('user_id', $user->id)->where('status', 'pending')->count()],
            ],
            'recent_notes' => NoteResource::collection($recentNotes),
            'recent_ai_generations' => AiGenerationResource::collection($recentAi),
            'storage' => [
                'used_bytes' => Upload::where('user_id', $user->id)->sum('size_bytes'),
                'limit_bytes' => 10 * 1024 * 1024 * 1024,
            ],
        ], 'Dashboard loaded.');
    }
}
