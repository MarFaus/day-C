<?php

namespace App\Http\Middleware;

use App\Models\Organizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizerId = $request->session()->get('organizer_id');

        if (! $organizerId || ! Organizer::query()->whereKey($organizerId)->exists()) {
            $request->session()->forget('organizer_id');

            return redirect()->guest(route('login'));
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
