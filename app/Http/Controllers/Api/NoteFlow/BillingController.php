<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class BillingController extends Controller
{
    use ApiResponseTrait;

    public function subscription()
    {
        return $this->successResponse([
            'plan_name' => 'Free Plan',
            'price' => 0,
            'currency' => 'USD',
            'interval' => 'month',
            'renews_at' => null,
        ], 'Subscription loaded.');
    }

    public function invoices()
    {
        return $this->successResponse([], 'Invoices loaded.');
    }
}
