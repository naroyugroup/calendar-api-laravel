<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calendars\CreateCalendarRequest;
use App\Http\Requests\Calendars\UpdateCalendarFavoriteStatusRequest;
use App\Http\Requests\Calendars\UpdateCalendarRequest;
use App\Models\Calendar;
use App\Models\CalendarParticipant;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Throwable;

use OpenApi\Attributes as OA;

class CalendarController extends Controller
{
	#[OA\Post(path: '/api/calendars', summary: 'Create calendar', tags: ['Calendar'])]
	#[OA\Response(response: 201, description: 'Calendar')]
	public function createCalendar(CreateCalendarRequest $request, GoogleCalendarService $GCservice)
	{
		$user = $request->user();

		$body = $request->all();
		$body['owner_id'] = $user->id;

		$calendar = Calendar::create($body);

		try {
			$GCservice->upsertCalendar($calendar);
		} catch (Throwable $ignore) {
		}

		return $calendar;
	}

	#[OA\Get(path: '/api/calendars/{calendarId}', summary: 'Get calendar by id', description: 'Member role is required', tags: ['Calendar'])]
	#[OA\Response(response: 200, description: 'Calendar')]
	public function getCalendarById(Request $request, string $calendarId)
	{
		$user = $request->user();

		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:member', [$calendar]), 403);

		if ($calendar->owner_id === $user->id) return $calendar;

		$participantRecord = CalendarParticipant::where('calendar_id', $calendarId)->where('user_id', $user->id)->first();
		$calendar->favorite = $participantRecord->favorite;
		return $calendar;
	}

	#[OA\Get(path: '/api/calendars/all', summary: 'Get all available calendars', description: 'Available means that user owns and participates in', tags: ['Calendar'])]
	#[OA\Response(response: 200, description: 'Calendar')]
	public function getAllCalendars(Request $request)
	{
		$user = $request->user();

		$ownedCalendars = Calendar::where('owner_id', $user->id)->get()->toArray();
		$participationRecords = CalendarParticipant::where('user_id', $user->id)->get()->toArray();
		$sharedCalendars = array_map(function ($record) {
			$calendar = Calendar::findOrFail($record['calendar_id']);
			$calendar->favorite = $record['favorite'];

			return $calendar;
		}, $participationRecords);

		return array_merge($ownedCalendars, $sharedCalendars);
	}

	#[OA\Put(path: '/api/calendars/{calendarId}', summary: 'Update calendar by id', description: 'Admin role is required', tags: ['Calendar'])]
	#[OA\Response(response: 200, description: 'Calendar')]
	public function updateCalendarById(UpdateCalendarRequest $request, GoogleCalendarService $GCservice, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:admin', [$calendar]), 403);

		$calendar->update($request->all());

		try {
			$GCservice->upsertCalendar($calendar);
		} catch (Throwable $ignore) {
		}

		return $calendar;
	}

	#[OA\Put(path: '/api/calendars/{calendarId}/favorite', summary: 'Update calendar favorite status by id', description: 'Member role is required', tags: ['Calendar'])]
	#[OA\Response(response: 200, description: 'Calendar')]
	public function updateCalendarFavoriteStatusById(UpdateCalendarFavoriteStatusRequest $request, string $calendarId)
	{
		$user = $request->user();

		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:member', [$calendar]), 403);

		if ($calendar->owner_id === $user->id) {
			$calendar->favorite = $request->input('favorite');
			$calendar->save();

			return $calendar;
		}

		$participantRecord = CalendarParticipant::where('calendar_id', $calendarId)->where('user_id', $user->id)->first();

		$participantRecord->favorite = $request->input('favorite');
		$participantRecord->save();

		$calendar->favorite = $participantRecord->favorite;

		return $calendar;
	}

	#[OA\Delete(path: '/api/calendars/{calendarId}', summary: 'Delete calendar by id', description: 'Owner role is required', tags: ['Calendar'])]
	#[OA\Response(response: 200, description: 'Calendar')]
	public function deleteCalendarById(GoogleCalendarService $GCservice, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:owner', [$calendar]), 403);

		try {
			$GCservice->deleteCalendar($calendar);
		} catch (Throwable $ignore) {
		}

		Calendar::whereId($calendarId)->delete();
	}
}
