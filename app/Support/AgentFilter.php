<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The "Agent" filter on lists and reports (?agent=user id): loans and chit
 * seats by the agent who brought the customer in, receipts by who
 * collected them. Staff are the agents for now.
 */
class AgentFilter
{
    /**
     * Staff to choose from, id => name.
     *
     * @return Collection<int, string>
     */
    public static function staff(): Collection
    {
        return User::query()->active()->orderBy('name')->pluck('name', 'id');
    }

    /**
     * The chosen agent's id, or null for all agents (also when the id is not
     * a staff member).
     */
    public static function selected(Request $request): ?int
    {
        $agentId = $request->integer('agent');

        return $agentId > 0 && User::query()->whereKey($agentId)->exists() ? $agentId : null;
    }
}
