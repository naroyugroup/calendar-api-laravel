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
		Schema::create('calendars', function (Blueprint $table) {
			$table->id();
			$table->text('google_id')->nullable();
			$table->bigInteger('owner_id');
			$table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
			$table->text('summary');
			$table->boolean('favorite')->default(false);
			$table->timestampsTz();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('calendars');
	}
};
