<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFlow\FavoriteNoteRequest;
use App\Http\Requests\NoteFlow\ShareNoteRequest;
use App\Http\Requests\NoteFlow\StoreNoteRequest;
use App\Http\Requests\NoteFlow\UpdateNoteRequest;
use App\Http\Resources\NoteFlow\NoteResource;
use App\Http\Resources\NoteFlow\NoteVersionResource;
use App\Models\Note;
use App\Services\NoteFlow\NoteService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NoteController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly NoteService $notes) {}

    public function index(Request $request)
    {
        $notes = $this->notes->list($request->user(), $request->only([
            'q',
            'folder_id',
            'favorite',
            'sort',
            'order',
            'per_page',
        ]));

        return $this->successResponse(NoteResource::collection($notes), 'Notes loaded.');
    }

    public function store(StoreNoteRequest $request)
    {
        $note = $this->notes->create($request->user(), $request->validated());

        return $this->successResponse(new NoteResource($note), 'Note created.', 201);
    }

    public function show(Request $request, int $id)
    {
        $note = $this->findUserNote($request, $id, ['folder', 'blocks']);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        return $this->successResponse(new NoteResource($note), 'Note detail loaded.');
    }

    public function update(UpdateNoteRequest $request, int $id)
    {
        $note = $this->findUserNote($request, $id, ['blocks']);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $updated = $this->notes->update($note, $request->validated());

        return $this->successResponse(new NoteResource($updated), 'Note updated.');
    }

    public function destroy(Request $request, int $id)
    {
        $note = $this->findUserNote($request, $id);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $note->delete();

        return $this->successResponse(null, 'Note deleted.');
    }

    public function duplicate(Request $request, int $id)
    {
        $note = $this->findUserNote($request, $id, ['blocks']);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $copy = $this->notes->duplicate($note);

        return $this->successResponse(new NoteResource($copy), 'Note duplicated.', 201);
    }

    public function favorite(FavoriteNoteRequest $request, int $id)
    {
        $note = $this->findUserNote($request, $id);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $updated = $this->notes->setFavorite($note, (bool) $request->validated('is_favorite'));

        return $this->successResponse(new NoteResource($updated), 'Favorite status updated.');
    }

    public function share(ShareNoteRequest $request, int $id)
    {
        $note = $this->findUserNote($request, $id);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $share = $note->shares()->create([
            'user_id' => $request->user()->id,
            'token' => Str::random(48),
            'permission' => $request->validated('permission') ?? 'view',
            'expires_at' => $request->validated('expires_at'),
        ]);

        return $this->successResponse([
            'share_url' => url('/shared/notes/'.$share->token),
            'token' => $share->token,
        ], 'Share link created.', 201);
    }

    public function versions(Request $request, int $id)
    {
        $note = $this->findUserNote($request, $id);
        if (! $note) {
            return $this->notFoundResponse('Note');
        }

        $versions = $note->versions()->paginate(min($request->integer('per_page', 15), 50));

        return $this->successResponse(NoteVersionResource::collection($versions), 'Note versions loaded.');
    }

    private function findUserNote(Request $request, int $id, array $with = []): ?Note
    {
        return Note::query()
            ->where('user_id', $request->user()->id)
            ->with($with)
            ->find($id);
    }
}
