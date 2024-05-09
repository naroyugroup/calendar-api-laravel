<?php

namespace App\Providers;

use App\Enums\CalendarParticipantRole;
use App\Models\Calendar;
use App\Models\CalendarParticipant;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
	/**
	 * Register any application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		Gate::define('participant:member', function (User $user, Calendar $calendar) {
			if ($user->id === $calendar->owner_id) return true;

			$participantRecord = CalendarParticipant::where('user_id', $user->id)->where('calendar_id', $calendar->id)->first();
			abort_if(!$participantRecord, 403);

			return
				$participantRecord->role === CalendarParticipantRole::OWNER->value ||
				$participantRecord->role === CalendarParticipantRole::ADMIN->value ||
				$participantRecord->role === CalendarParticipantRole::MEMBER->value;
		});

		Gate::define('participant:admin', function (User $user, Calendar $calendar) {
			if ($user->id === $calendar->owner_id) return true;

			$participantRecord = CalendarParticipant::where('user_id', $user->id)->where('calendar_id', $calendar->id)->first();
			abort_if(!$participantRecord, 403);

			return
				$participantRecord->role === CalendarParticipantRole::OWNER->value ||
				$participantRecord->role === CalendarParticipantRole::ADMIN->value;
		});

		Gate::define('participant:owner', function (User $user, Calendar $calendar) {
			if ($user->id === $calendar->owner_id) return true;

			$participantRecord = CalendarParticipant::where('user_id', $user->id)->where('calendar_id', $calendar->id)->first();
			abort_if(!$participantRecord, 403);

			return
				$participantRecord->role === CalendarParticipantRole::OWNER->value;
		});
	}
}
