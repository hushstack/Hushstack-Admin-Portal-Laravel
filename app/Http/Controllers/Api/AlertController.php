<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Alert\StoreAlertRequest;
use App\Http\Resources\AlertResource;
use App\Services\AlertIngestionService;
use App\Traits\ApiResponseTrait;

class AlertController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private readonly AlertIngestionService $alerts) {}

    public function store(StoreAlertRequest $request)
    {
        $result = $this->alerts->ingest($request->validated());
        $statusCode = $result['created'] ? 201 : 200;
        $message = $result['created'] ? 'Alert created.' : 'Alert occurrence updated.';

        return $this->successResponse(
            new AlertResource($result['alert']),
            $message,
            $statusCode
        );
    }
}
