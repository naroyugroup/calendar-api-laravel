<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('calendar_events', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->text('google_id')->nullable();
			$table->bigInteger('calendar_id');
			$table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
			$table->text('summary');
			$table->text('description')->nullable();
			$table->text('location')->nullable();
			$table->text('recurrence_rule')->nullable();
			$table->text('creator');
			$table->text('organizer');
			$table->json('attendee')->nullable();
			$table->timestampsTz();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('calendar_events');
	}
};
