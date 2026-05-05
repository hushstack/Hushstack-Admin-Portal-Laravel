<?php

namespace App\Http\Controllers\Api\NoteFlow;

use App\Http\Controllers\Controller;
use App\Http\Requests\NoteFlow\StoreNotificationRequest;
use App\Http\Requests\NoteFlow\UpdateNotificationRequest;
use App\Http\Resources\NoteFlow\NotificationDeliveryResource;
use App\Http\Resources\NoteFlow\NotificationResource;
use App\Models\NotificationDelivery;
use App\Models\UserNotification;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $notifications = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('scheduled_for')
            ->get();

        return $this->successResponse(NotificationResource::collection($notifications), 'Notifications loaded.');
    }

    public function store(StoreNotificationRequest $request)
    {
        $notification = UserNotification::create(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id, 'status' => 'pending']
        ));

        return $this->successResponse(new NotificationResource($notification), 'Notification created.', 201);
    }

    public function update(UpdateNotificationRequest $request, int $id)
    {
        $notification = $this->findUserNotification($request, $id);
        if (! $notification) {
            return $this->notFoundResponse('Notification');
        }

        $notification->update($request->validated());

        return $this->successResponse(new NotificationResource($notification), 'Notification updated.');
    }

    public function destroy(Request $request, int $id)
    {
        $notification = $this->findUserNotification($request, $id);
        if (! $notification) {
            return $this->notFoundResponse('Notification');
        }

        $notification->delete();

        return $this->successResponse(null, 'Notification deleted.');
    }

    public function history(Request $request)
    {
        $deliveries = NotificationDelivery::query()
            ->where('user_id', $request->user()->id)
            ->latest('sent_at')
            ->paginate(min($request->integer('per_page', 15), 50));

        return $this->successResponse(NotificationDeliveryResource::collection($deliveries), 'Notification history loaded.');
    }

    private function findUserNotification(Request $request, int $id): ?UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->find($id);
    }
}
