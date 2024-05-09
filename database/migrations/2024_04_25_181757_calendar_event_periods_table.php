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
		Schema::create('calendar_event_periods', function (Blueprint $table) {
			$table->id();
			$table->foreignUuid('calendar_event_id')->references('id')->on('calendar_events')->onDelete('cascade');
			$table->date('start_date')->nullable();
			$table->timestampTz('start_date_time')->nullable();
			$table->text('start_timezone')->nullable();
			$table->date('end_date')->nullable();
			$table->timestampTz('end_date_time')->nullable();
			$table->text('end_timezone')->nullable();
			$table->timestampsTz();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('calendar_event_periods');
	}
};
