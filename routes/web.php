<?php

use App\Http\Controllers\Admin\ChannelController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Auth\OrganizerAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return session()->has('organizer_id')
        ? redirect()->route('admin.events.index')
        : redirect()->route('login');
})->name('home');

Route::get('/login', [OrganizerAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [OrganizerAuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [OrganizerAuthController::class, 'logout'])->name('logout');

Route::prefix('admin')->name('admin.')->middleware('organizer')->group(function () {
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('events', [EventController::class, 'store'])->name('events.store');
    Route::get('events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');

    Route::get('events/{event}/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('events/{event}/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('events/{event}/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('events/{event}/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');

    Route::get('events/{event}/sessions/create', [SessionController::class, 'create'])->name('sessions.create');
    Route::post('events/{event}/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::get('events/{event}/sessions/{session}/edit', [SessionController::class, 'edit'])->name('sessions.edit');
    Route::put('events/{event}/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');

    Route::get('events/{event}/channels/create', [ChannelController::class, 'create'])->name('channels.create');
    Route::post('events/{event}/channels', [ChannelController::class, 'store'])->name('channels.store');
    Route::get('events/{event}/channels/{channel}/edit', [ChannelController::class, 'edit'])->name('channels.edit');
    Route::put('events/{event}/channels/{channel}', [ChannelController::class, 'update'])->name('channels.update');

    Route::get('events/{event}/rooms/create', [RoomController::class, 'create'])->name('rooms.create');
    Route::post('events/{event}/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('events/{event}/rooms/{room}/edit', [RoomController::class, 'edit'])->name('rooms.edit');
    Route::put('events/{event}/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');

    Route::get('events/{event}/reports/room-capacity', [ReportController::class, 'roomCapacity'])->name('reports.room-capacity');
});

// Attendee single-page application (phase two).
Route::view('/app', 'attendee.index')->name('attendee.app');
Route::view('/app/{any?}', 'attendee.index')->where('any', '.*');

Route::fallback(function () {
    if (request()->is('api/*')) {
        return response()->json(['message' => 'Not found'], 404);
    }

    return response()->view('errors.404', [], 404);
});
