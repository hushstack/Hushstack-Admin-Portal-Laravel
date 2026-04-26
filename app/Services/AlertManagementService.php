<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AlertManagementService
{
    public function list(array $filters): LengthAwarePaginator
    {
        return Alert::query()
            ->with(['acknowledgedBy', 'resolvedBy'])
            ->when(isset($filters['severity']), fn ($query) => $query->where('severity', $filters['severity']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['type']), fn ($query) => $query->where('type', $filters['type']))
            ->when(isset($filters['server_name']), fn ($query) => $query->where('server_name', $filters['server_name']))
            ->when(isset($filters['source_ip']), fn ($query) => $query->where('source_ip', $filters['source_ip']))
            ->when(isset($filters['date_from']), fn ($query) => $query->whereDate('last_seen_at', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn ($query) => $query->whereDate('last_seen_at', '<=', $filters['date_to']))
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];

                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%")
                        ->orWhere('server_name', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('fingerprint', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_seen_at')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function acknowledge(Alert $alert, User $user): Alert
    {
        $alert->forceFill([
            'status' => Alert::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ])->save();

        return $this->fresh($alert);
    }

    public function resolve(Alert $alert, User $user): Alert
    {
        $alert->forceFill([
            'status' => Alert::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $user->id,
        ])->save();

        return $this->fresh($alert);
    }

    public function reopen(Alert $alert): Alert
    {
        $alert->forceFill([
            'status' => Alert::STATUS_OPEN,
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'resolved_at' => null,
            'resolved_by' => null,
        ])->save();

        return $this->fresh($alert);
    }

    public function delete(Alert $alert): void
    {
        $alert->delete();
    }

    public function fresh(Alert $alert): Alert
    {
        return $alert->fresh(['acknowledgedBy', 'resolvedBy']);
    }
}
