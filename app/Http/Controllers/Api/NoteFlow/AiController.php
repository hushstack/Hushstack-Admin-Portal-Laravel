<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFlow\AiGenerateRequest;
use App\Http\Requests\NoteFlow\UpdateAiSettingsRequest;
use App\Http\Resources\NoteFlow\AiGenerationResource;
use App\Models\AiGeneration;
use App\Services\NoteFlow\AiGenerationService;
use App\Services\NoteFlow\SettingsService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AiController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly AiGenerationService $ai,
        private readonly SettingsService $settings
    ) {}

    public function tools()
    {
        return $this->successResponse($this->ai->tools(), 'AI tools loaded.');
    }

    public function generate(AiGenerateRequest $request)
    {
        $generation = $this->ai->generate($request->user(), $request->validated());

        return $this->successResponse(new AiGenerationResource($generation), 'AI generation created.', 201);
    }

    public function generations(Request $request)
    {
        $generations = AiGeneration::query()
            ->where('user_id', $request->user()->id)
            ->when($request->query('tool'), fn ($query, string $tool) => $query->where('tool', $tool))
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return $this->successResponse(AiGenerationResource::collection($generations), 'AI generations loaded.');
    }

    public function settings(Request $request)
    {
        return $this->successResponse($this->settings->getOrCreate($request->user())->ai, 'AI settings loaded.');
    }

    public function updateSettings(UpdateAiSettingsRequest $request)
    {
        $settings = $this->settings->updateAi($request->user(), $request->validated());

        return $this->successResponse($settings->ai, 'AI settings updated.');
    }
}
