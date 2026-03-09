<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Role;
use Illuminate\Http\Request;

trait InteractsWithCatalogOwnership
{
    private function isPartner(Request $request): bool
    {
        return $request->user()?->role?->slug === Role::PARTNER_SLUG;
    }

    private function isUser(Request $request): bool
    {
        return $request->user()?->role?->slug === Role::USER_SLUG;
    }

    private function enforcesOwnership(Request $request): bool
    {
        return in_array(
            $request->user()?->role?->slug,
            [Role::PARTNER_SLUG, Role::USER_SLUG],
            true
        );
    }

    private function violatesOwnership(Request $request, ?int $ownerId): bool
    {
        return $this->enforcesOwnership($request)
            && (int) $ownerId !== (int) $request->user()?->id;
    }

    private function ownsTooMany(Request $request, string $modelClass): bool
    {
        return $modelClass::where('user_id', $request->user()->id)->count() >= 5;
    }
}
