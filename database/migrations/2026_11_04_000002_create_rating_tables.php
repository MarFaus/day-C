<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_ratings', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('attendee_id');
            $table->integer('event_id');
            $table->unsignedTinyInteger('rating');
            $table->dateTime('created_at');
            $table->unique(['attendee_id', 'event_id']);
            $table->index('event_id');
        });

        Schema::create('session_ratings', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->integer('attendee_id');
            $table->integer('session_id');
            $table->unsignedTinyInteger('rating');
            $table->dateTime('created_at');
            $table->unique(['attendee_id', 'session_id']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_ratings');
        Schema::dropIfExists('event_ratings');
    }
};
