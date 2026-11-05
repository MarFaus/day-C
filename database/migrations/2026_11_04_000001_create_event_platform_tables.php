<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizers', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('name', 45);
            $table->string('slug', 45)->unique();
            $table->string('email', 255)->nullable()->unique();
            $table->string('password_hash', 255);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('organizer_id');
            $table->string('name', 45)->nullable();
            $table->string('slug', 45)->nullable();
            $table->date('date')->nullable();
            $table->unique(['organizer_id', 'slug']);
            $table->index('organizer_id');
        });

        Schema::create('channels', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('event_id');
            $table->string('name', 45)->nullable();
            $table->index('event_id');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('channel_id');
            $table->string('name', 45)->nullable();
            $table->integer('capacity')->nullable();
            $table->index('channel_id');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('room_id');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('speaker', 45)->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('type', 10);
            $table->decimal('cost', 9, 2)->nullable();
            $table->index('room_id');
            $table->index(['room_id', 'start', 'end']);
        });

        Schema::create('event_tickets', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('event_id');
            $table->string('name', 45)->nullable();
            $table->decimal('cost', 9, 2)->nullable();
            $table->text('special_validity')->nullable();
            $table->index('event_id');
        });

        Schema::create('attendees', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('firstname', 45);
            $table->string('lastname', 45);
            $table->string('username', 45)->unique();
            $table->string('email', 255);
            $table->string('registration_code', 6);
            $table->string('login_token', 255)->nullable();
            $table->index('registration_code');
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('attendee_id');
            $table->integer('ticket_id');
            $table->dateTime('registration_time')->nullable();
            $table->index('attendee_id');
            $table->index('ticket_id');
        });

        Schema::create('session_registrations', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('registration_id');
            $table->integer('session_id');
            $table->index('registration_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        foreach (['session_registrations', 'registrations', 'attendees', 'event_tickets', 'sessions', 'rooms', 'channels', 'events', 'organizers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
