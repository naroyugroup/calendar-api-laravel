<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CalendarEvent extends Model
{
	use HasFactory;

	protected $keyType = 'string';

	public $incrementing = false;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'id',
		'google_id',
		'calendar_id',
		'summary',
		'description',
		'location',
		'recurrence_rule',
		'creator',
		'organizer',
		'attendee',
		'created_at',
		'updated_at'
	];

	/**
	 * The attributes that should be hidden for serialization.
	 *
	 * @var array<int, string>
	 */
	protected $hidden = [];

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return ['attendee' => 'array'];
	}

	public function calendar(): BelongsTo
	{
		return $this->belongsTo(Calendar::class);
	}

	public function period(): HasOne
	{
		return $this->hasOne(CalendarEventPeriod::class);
	}
}
