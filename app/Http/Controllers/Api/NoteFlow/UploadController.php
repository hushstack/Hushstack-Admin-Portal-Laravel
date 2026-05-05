<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFlow\StoreUploadRequest;
use App\Http\Resources\NoteFlow\UploadResource;
use App\Models\Upload;
use App\Services\NoteFlow\UploadMetadataService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly UploadMetadataService $uploads) {}

    public function index(Request $request)
    {
        $uploads = Upload::query()
            ->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 50));

        return $this->successResponse(UploadResource::collection($uploads), 'Uploads loaded.');
    }

    public function store(StoreUploadRequest $request)
    {
        $upload = $this->uploads->store($request->user(), $request->file('file'), $request->validated());

        return $this->successResponse(new UploadResource($upload), 'Upload stored.', 201);
    }

    public function show(Request $request, int $id)
    {
        $upload = $this->findUserUpload($request, $id);
        if (! $upload) {
            return $this->notFoundResponse('Upload');
        }

        return $this->successResponse(new UploadResource($upload), 'Upload detail loaded.');
    }

    public function destroy(Request $request, int $id)
    {
        $upload = $this->findUserUpload($request, $id);
        if (! $upload) {
            return $this->notFoundResponse('Upload');
        }

        $this->uploads->deleteFile($upload);
        $upload->delete();

        return $this->successResponse(null, 'Upload deleted.');
    }

    public function reprocess(Request $request, int $id)
    {
        $upload = $this->findUserUpload($request, $id);
        if (! $upload) {
            return $this->notFoundResponse('Upload');
        }

        return $this->successResponse(new UploadResource($this->uploads->reprocess($upload)), 'Upload reprocessed.');
    }

    private function findUserUpload(Request $request, int $id): ?Upload
    {
        return Upload::query()
            ->where('user_id', $request->user()->id)
            ->find($id);
    }
}
