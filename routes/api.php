<?php

use App\Http\Controllers\Api\V1\AttendeeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('events', [AttendeeApiController::class, 'events']);
    Route::get('organizers/{organizer}/events/{event}', [AttendeeApiController::class, 'event']);
    Route::post('login', [AttendeeApiController::class, 'login']);
    Route::post('logout', [AttendeeApiController::class, 'logout']);
    Route::post('organizers/{organizer}/events/{event}/registration', [AttendeeApiController::class, 'register']);
    Route::get('registrations', [AttendeeApiController::class, 'registrations']);
    Route::get('ratings', [AttendeeApiController::class, 'ratings']);
    Route::post('events/{event}/rating', [AttendeeApiController::class, 'rateEvent'])->whereNumber('event');
    Route::post('sessions/{session}/rating', [AttendeeApiController::class, 'rateSession'])->whereNumber('session');
});
