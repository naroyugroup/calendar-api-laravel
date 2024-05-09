<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Enums\CalendarParticipantRole;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('calendar_participants', function (Blueprint $table) {
			$table->id();
			$table->bigInteger('calendar_id');
			$table->bigInteger('user_id');
			$table->foreign('calendar_id')->references('id')->on('calendars')->onDelete('cascade');
			$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
			$table->enum('role', CalendarParticipantRole::toArray())->default(CalendarParticipantRole::MEMBER);
			$table->boolean('favorite')->default(false);
			$table->timestampsTz();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('calendar_participants');
	}
};
