<?php

namespace App\Services\NoteFlow;

use App\Models\AiGeneration;
use App\Models\User;

class AiGenerationService
{
    public function tools(): array
    {
        return [
            ['id' => 'summarize', 'name' => 'Summarize', 'description' => 'Generate concise summaries of long texts'],
            ['id' => 'rewrite', 'name' => 'Rewrite & Improve', 'description' => 'Enhance writing style and grammar'],
            ['id' => 'translate', 'name' => 'Translate', 'description' => 'Translate text to multiple languages'],
            ['id' => 'code', 'name' => 'Code Assistant', 'description' => 'Explain, debug, or generate code'],
            ['id' => 'ideas', 'name' => 'Generate Ideas', 'description' => 'Brainstorm ideas and creative suggestions'],
            ['id' => 'chat', 'name' => 'AI Chat', 'description' => 'Have a conversation with an AI assistant'],
        ];
    }

    public function generate(User $user, array $data): AiGeneration
    {
        $output = $this->placeholderOutput($data['tool'], $data['input_text']);

        return AiGeneration::create([
            'user_id' => $user->id,
            'source_note_id' => $data['source_note_id'] ?? null,
            'source_upload_id' => $data['source_upload_id'] ?? null,
            'tool' => $data['tool'],
            'title' => $this->titleFor($data['tool']),
            'input_text' => $data['input_text'],
            'output_text' => $output,
            'language' => $data['language'] ?? null,
            'tone' => $data['tone'] ?? null,
            'output_length' => $data['output_length'] ?? null,
        ]);
    }

    private function titleFor(string $tool): string
    {
        return match ($tool) {
            'summarize' => 'Generated Summary',
            'rewrite' => 'Rewritten Text',
            'translate' => 'Translation',
            'code' => 'Code Assistant Result',
            'ideas' => 'Generated Ideas',
            default => 'AI Chat Response',
        };
    }

    private function placeholderOutput(string $tool, string $input): string
    {
        return 'AI provider is not configured yet. Tool: '.$tool.'. Input preview: '.mb_substr($input, 0, 240);
    }
}
