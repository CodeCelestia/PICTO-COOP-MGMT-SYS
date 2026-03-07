<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforce the account-first → complete PDS onboarding rule for members.
 *
 * Any authenticated user with the 'member' role who has not yet completed
 * their PDS (users.pds_id IS NULL) will be redirected to the complete-pds
 * page after every request — except for:
 *   • The complete-pds page itself
 *   • Logout / auth routes
 *   • API requests (non-HTML)
 */
class EnsurePdsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce for authenticated members
        if (!$user || !$user->hasRole('member')) {
            return $next($request);
        }

        // Never redirect on the completion page itself or Fortify auth routes
        if ($request->routeIs('member.complete-pds', 'member.complete-pds.store')
            || $request->is('logout', 'login', 'email/*', 'verify-email*')
        ) {
            return $next($request);
        }

        // Force PDS completion if not yet linked
        if (is_null($user->pds_id)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'PDS completion required.'], 403);
            }

            return redirect()->route('member.complete-pds')
                ->with('info', 'Please complete your Personal Data Sheet to continue.');
        }

        // If member record exists but is not yet active (pending admin approval)
        $member = $user->member;
        if ($member && $member->status === 'pending') {
            // Allow the member to view/edit their own PDS while pending
            if (!$request->routeIs('member.my-pds*', 'member.complete-pds*')) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Your membership is pending approval.'], 403);
                }

                return redirect()->route('member.complete-pds')
                    ->with('info', 'Your membership is pending approval from your office administrator.');
            }
        }

        return $next($request);
    }
}
