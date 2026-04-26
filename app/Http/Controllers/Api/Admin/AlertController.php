<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Alert\IndexAlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Services\AlertManagementService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly AlertManagementService $alerts) {}

    public function index(IndexAlertRequest $request)
    {
        $alerts = $this->alerts->list($request->validated());

        return $this->successResponse([
            'data' => AlertResource::collection($alerts->items()),
            'page' => $alerts->currentPage(),
            'per_page' => $alerts->perPage(),
            'page_total' => $alerts->count(),
            'total' => $alerts->total(),
            'last_page' => $alerts->lastPage(),
        ], 'Alerts loaded.');
    }

    public function show(Alert $alert)
    {
        return $this->successResponse(new AlertResource($this->alerts->fresh($alert)), 'Alert loaded.');
    }

    public function acknowledge(Request $request, Alert $alert)
    {
        return $this->successResponse(
            new AlertResource($this->alerts->acknowledge($alert, $request->user())),
            'Alert acknowledged.'
        );
    }

    public function resolve(Request $request, Alert $alert)
    {
        return $this->successResponse(
            new AlertResource($this->alerts->resolve($alert, $request->user())),
            'Alert resolved.'
        );
    }

    public function reopen(Alert $alert)
    {
        return $this->successResponse(
            new AlertResource($this->alerts->reopen($alert)),
            'Alert reopened.'
        );
    }

    public function destroy(Alert $alert)
    {
        $this->alerts->delete($alert);

        return $this->successResponse(null, 'Alert deleted.');
    }
}
