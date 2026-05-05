<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFlow\StoreFolderRequest;
use App\Http\Resources\NoteFlow\FolderResource;
use App\Models\Folder;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $folders = Folder::query()
            ->where('user_id', $request->user()->id)
            ->withCount('notes')
            ->orderBy('name')
            ->get();

        return $this->successResponse(FolderResource::collection($folders), 'Folders loaded.');
    }

    public function store(StoreFolderRequest $request)
    {
        $folder = Folder::create([
            'user_id' => $request->user()->id,
            'name' => $request->validated('name'),
            'color' => $request->validated('color'),
        ]);

        return $this->successResponse(new FolderResource($folder), 'Folder created.', 201);
    }
}
