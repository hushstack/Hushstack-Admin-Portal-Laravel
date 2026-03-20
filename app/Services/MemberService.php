<?php

namespace App\Services;

use App\Models\Member;

class MemberService
{
    public function getAll(int $perPage = 15)
    {
        return Member::query()
            ->with(['user', 'position'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Member::create($data);
    }

    public function update(Member $member, array $data)
    {
        $member->update($data);

        return $member;
    }

    public function delete(Member $member)
    {
        return $member->delete();
    }
}
