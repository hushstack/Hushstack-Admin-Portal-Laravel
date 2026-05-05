<?php

namespace App\Services\NoteFlow;

use App\Models\Note;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NoteService
{
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 15), 50);

        return Note::query()
            ->where('user_id', $user->id)
            ->with('folder')
            ->when($filters['q'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', '%'.$search.'%')
                        ->orWhere('content', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['folder_id'] ?? null, fn (Builder $query, int $folderId) => $query->where('folder_id', $folderId))
            ->when(array_key_exists('favorite', $filters), fn (Builder $query) => $query->where('is_favorite', filter_var($filters['favorite'], FILTER_VALIDATE_BOOLEAN)))
            ->orderBy($this->sortColumn((string) ($filters['sort'] ?? 'updated_at')), $this->sortDirection((string) ($filters['order'] ?? 'desc')))
            ->paginate($perPage);
    }

    public function create(User $user, array $data): Note
    {
        return DB::transaction(function () use ($user, $data) {
            $blocks = $data['blocks'] ?? [];
            $content = $this->contentFromBlocks($blocks);

            $note = Note::create([
                'user_id' => $user->id,
                'folder_id' => $data['folder_id'] ?? null,
                'title' => $data['title'],
                'emoji' => $data['emoji'] ?? '',
                'content' => $content,
                'word_count' => $this->wordCount($content),
            ]);

            $this->replaceBlocks($note, $blocks);

            return $note->load(['folder', 'blocks']);
        });
    }

    public function update(Note $note, array $data): Note
    {
        return DB::transaction(function () use ($note, $data) {
            $note->load('blocks');
            $this->snapshot($note);

            if (array_key_exists('blocks', $data)) {
                $content = $this->contentFromBlocks($data['blocks']);
                $data['content'] = $content;
                $data['word_count'] = $this->wordCount($content);
            }

            $note->update(collect($data)->except('blocks')->all());

            if (array_key_exists('blocks', $data)) {
                $this->replaceBlocks($note, $data['blocks']);
            }

            return $note->fresh(['folder', 'blocks']);
        });
    }

    public function duplicate(Note $note): Note
    {
        return DB::transaction(function () use ($note) {
            $note->load('blocks');

            $copy = $note->replicate(['is_favorite']);
            $copy->title = Str::limit($note->title.' Copy', 255, '');
            $copy->is_favorite = false;
            $copy->push();

            foreach ($note->blocks as $block) {
                $copy->blocks()->create($block->only(['type', 'content', 'checked', 'sort_order']));
            }

            return $copy->load(['folder', 'blocks']);
        });
    }

    public function setFavorite(Note $note, bool $isFavorite): Note
    {
        $note->update(['is_favorite' => $isFavorite]);

        return $note->fresh(['folder', 'blocks']);
    }

    private function replaceBlocks(Note $note, array $blocks): void
    {
        $note->blocks()->delete();

        foreach (array_values($blocks) as $index => $block) {
            $note->blocks()->create([
                'type' => $block['type'],
                'content' => $block['content'] ?? null,
                'checked' => $block['checked'] ?? null,
                'sort_order' => $block['sort_order'] ?? $index,
            ]);
        }
    }

    private function snapshot(Note $note): void
    {
        $note->versions()->create([
            'user_id' => $note->user_id,
            'title' => $note->title,
            'emoji' => $note->emoji,
            'content' => $note->content,
            'blocks_snapshot' => $note->blocks->map(fn ($block) => $block->only(['id', 'type', 'content', 'checked', 'sort_order']))->values()->all(),
        ]);
    }

    private function contentFromBlocks(array $blocks): string
    {
        return collect($blocks)
            ->pluck('content')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->implode("\n");
    }

    private function wordCount(?string $content): int
    {
        return str_word_count(strip_tags((string) $content));
    }

    private function sortColumn(string $sort): string
    {
        return in_array($sort, ['updated_at', 'created_at', 'title'], true) ? $sort : 'updated_at';
    }

    private function sortDirection(string $direction): string
    {
        return strtolower($direction) === 'asc' ? 'asc' : 'desc';
    }
}
