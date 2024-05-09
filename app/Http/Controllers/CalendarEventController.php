<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarEvents\CreateCalendarEventRequest;
use App\Http\Requests\CalendarEvents\GetEventsByDateRangeRequest;
use App\Http\Requests\CalendarEvents\UpdateCalendarEventRequest;
use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\CalendarEventPeriod;
use App\Services\EventRecurrenceService;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Throwable;

use OpenApi\Attributes as OA;

class CalendarEventController extends Controller
{
	public function snake_keys($array)
	{
		$result = [];
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$value = $this->snake_keys($value);
			}
			$result[Str::snake($key)] = $value;
		}
		return $result;
	}

	#[OA\Post(path: '/api/calendars/{calendarId}/events', summary: 'Create event', description: 'Admin role is required', tags: ['Event'])]
	#[OA\Response(response: 201, description: 'Event')]
	public function createEvent(CreateCalendarEventRequest $request, GoogleCalendarService $GCservice, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:admin', [$calendar]), 403);

		$createEventDto = $request->all();
		$createEventDto['id'] = Str::uuid();
		$createEventDto['calendar_id'] = $calendarId;
		$event = new CalendarEvent($createEventDto);

		$createEventPeriodDto = $this->snake_keys($request->input('period'));
		$eventPeriod = new CalendarEventPeriod($createEventPeriodDto);

		$event->save();
		$event->period()->save($eventPeriod);

		$event->period = $eventPeriod;

		try {
			$GCservice->upsertEvent($event);
		} catch (Throwable $ignore) {
		}

		return $event;
	}

	#[OA\Get(path: '/api/calendars/{calendarId}/events/{eventId}', summary: 'Get event by id', description: 'Member role is required', tags: ['Event'])]
	#[OA\Response(response: 200, description: 'Event')]
	public function getEventById(string $calendarId, string $eventId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:member', [$calendar]), 403);

		return CalendarEvent::with('period')->findOrFail($eventId);
	}

	#[OA\Get(path: '/api/calendars/{calendarId}/events/range', summary: 'Get event by id', description: 'Member role is required', tags: ['Event'])]
	#[OA\Response(response: 200, description: 'Events')]
	public function getEventsByDateRange(GetEventsByDateRangeRequest $request, EventRecurrenceService $eventService, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:member', [$calendar]), 403);

		$startDate = $request->query('startDate');
		$endDate = $request->query('endDate');

		return $eventService->getByDateRange($calendarId, $startDate, $endDate);
	}

	#[OA\Put(path: '/api/calendars/{calendarId}/events/{eventId}', summary: 'Update event by id', description: 'Admin role is required', tags: ['Event'])]
	#[OA\Response(response: 200, description: 'Event')]
	public function updateEventById(UpdateCalendarEventRequest $request, GoogleCalendarService $GCservice, string $calendarId, string $eventId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:admin', [$calendar]), 403);

		$event = CalendarEvent::with('period')->findOrFail($eventId);

		$event->fill($this->snake_keys($request->all()));
		if ($request->input('period')) $event->period->fill($this->snake_keys($request->input('period')));

		$event->push();

		try {
			$GCservice->upsertEvent($event);
		} catch (Throwable $ignore) {
		}

		return $event;
	}

	#[OA\Delete(path: '/api/calendars/{calendarId}/events/{eventId}', summary: 'Delete event by id', description: 'Admin role is required', tags: ['Event'])]
	#[OA\Response(response: 200, description: '')]
	public function deleteEventById(GoogleCalendarService $GCservice, string $calendarId, string $eventId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		$event = Calendar::whereId($eventId)->first();
		abort_if(is_null($event), 404, "Event not found");

		abort_if(!Gate::allows('participant:admin', [$calendar]), 403);

		try {
			$GCservice->deleteEvent($event);
		} catch (Throwable $ignore) {
		}

		CalendarEvent::whereId($eventId)->delete();
	}
}
