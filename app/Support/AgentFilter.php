<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The "Agent" filter on lists and reports (?agent=user id): loans and chit
 * seats by the agent who brought the customer in, receipts by who
 * collected them. Staff are the agents for now.
 *
 * In Sri Lakshmi Micro Finance only the owner (users.is_owner, Sathiya)
 * sees everyone; any other staff member is always held to their own
 * records, whatever the address asks for.
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
        if (self::heldToOwn($request)) {
            return $request->user()->id;
        }

        $agentId = $request->integer('agent');

        return $agentId > 0 && User::query()->whereKey($agentId)->exists() ? $agentId : null;
    }

    /**
     * Whether this person sees every agent (the owner, or outside Micro
     * Finance).
     */
    public static function seesAll(Request $request): bool
    {
        return ! self::heldToOwn($request);
    }

    /**
     * A signed-in staff member who is not the owner, on a Micro Finance page
     * (or the shared Customers list while Micro Finance is open).
     */
    public static function heldToOwn(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User || $user->is_owner === true) {
            return false;
        }

        return $request->routeIs('finance.*') || (view()->shared('business')['key'] ?? null) === 'finance';
    }
}
