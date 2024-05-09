<?php

namespace App\Services;

use App\Models\Calendar as ModelsCalendar;
use App\Models\CalendarEvent;
use App\Models\CalendarEventPeriod;
use App\Models\User;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Calendar as CalendarCalendar;
use Google\Service\Calendar\Event;
use Illuminate\Support\Str;

class GoogleCalendarService
{
	public function getCalendarServiceInstance(User $user)
	{
		$client = new Client();
		$client->setClientId(env('GOOGLE_CLIENT_ID'));
		$client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

		$client->refreshToken($user->google_api_refresh_token);

		return new Calendar($client);
	}

	public function transformLocalEventToGoogleRequestEvent(CalendarEvent $event)
	{
		return array(
			'start' => array(
				'date' => $event->period->start_date,
				'dateTime' => date('c', strtotime($event->period->start_date_time)),
				'timeZone' => $event->period->start_timezone,
			),
			'end' => array(
				'date' => $event->period->end_date,
				'dateTime' => date('c', strtotime($event->period->end_date_time)),
				'timeZone' => $event->period->end_timezone,
			),
			'summary' => $event->summary,
			'description' => $event->description,
			'location' => $event->location,
			'recurrence' => array($event->recurrence_rule),
			'creator' => array('email' => $event->creator),
			'organizer' => array('email' => $event->organizer),
			'attendees' => $event->attendee ? array_map(function ($attendee) {
				return array('email' => $attendee);
			}, $event->attendee) : null,
		);
	}

	public function transformGoogleResponseEventToLocalEvent($event)
	{
		return array(
			'google_id' => $event->id,
			'period' => array(
				'start_date' => $event->start->date,
				'start_date_time' => $event->start->dateTime,
				'start_timezone' => $event->start->timeZone,
				'end_date' => $event->end->date,
				'end_date_time' => $event->end->dateTime,
				'end_timezone' => $event->end->timeZone,
			),
			'summary' => $event->summary,
			'description' => $event->description,
			'creator' => $event->creator->email,
			'organizer' => $event->organizer->email,
			'location' => $event->location,
			'recurrence_rule' => $event->recurrence ? $event->recurrence[0] : null,
			'attendee' => $event->attendees ? array_map(function ($attendee) {
				return $attendee->email;
			}, $event->attendees) : null,
			'created_at' => $event->created,
			'updated_at' => $event->updated
		);
	}

	public function createCalendar(
		Calendar $service,
		ModelsCalendar $calendar
	) {
		$createdCalendar = $service->calendars->insert(new CalendarCalendar(array('summary' => $calendar->summary)));

		$calendar->update(['google_id', $createdCalendar->id]);

		return $createdCalendar->id;
	}

	public function updateCalendar(
		Calendar $service,
		ModelsCalendar $calendar
	) {
		$service->calendars->update($calendar->google_id, new CalendarCalendar(array('summary' => $calendar->summary)));
	}

	public function upsertCalendar(ModelsCalendar $calendar)
	{
		$owner = User::findOrFail($calendar->owner_id);
		$service = $this->getCalendarServiceInstance($owner);

		if ($calendar->google_id) $this->updateCalendar($service, $calendar);
		else $this->createCalendar($service, $calendar);
	}

	public function deleteCalendar(ModelsCalendar $calendar)
	{
		$owner = User::findOrFail($calendar->owner_id);
		$service = $this->getCalendarServiceInstance($owner);

		$service->calendars->delete($calendar->google_id);
	}

	public function createEvent(
		Calendar $service,
		ModelsCalendar $calendar,
		CalendarEvent $event
	) {
		$createdEvent = $service->events->insert($calendar->google_id, new Event($this->transformLocalEventToGoogleRequestEvent($event)));

		CalendarEvent::whereKey($event->getKey())->update(['google_id' => $createdEvent->id]);
	}

	public function updateEvent(
		Calendar $service,
		ModelsCalendar $calendar,
		CalendarEvent $event
	) {
		$service->events->update($calendar->google_id, $event->google_id, new Event($this->transformLocalEventToGoogleRequestEvent($event)));
	}

	public function upsertEvent(CalendarEvent $event)
	{
		$calendar = ModelsCalendar::findOrFail($event->calendar_id);
		$owner = User::findOrFail($calendar->owner_id);
		$service = $this->getCalendarServiceInstance($owner);

		if (!$calendar->google_id) {
			$this->createCalendar($service, $calendar);
		}

		if ($event->googleId) $this->updateEvent($service, $calendar, $event);
		else $this->createEvent($service, $calendar, $event);
	}

	public function deleteEvent(Event $event)
	{
		$calendar = ModelsCalendar::findOrFail($event->calendar_id);
		$owner = User::findOrFail($calendar->owner_id);
		$service = $this->getCalendarServiceInstance($owner);

		// there is no point to delete the event which does not exist or from the calendar which does not exist
		if ($calendar->google_id && $event->google_id) {
			$service->events->delete($calendar->google_id, $event->google_id);
		}
	}

	public function synchronizeUserCalendar(
		Calendar $service,
		ModelsCalendar $calendar
	) {
		$result = $service->calendars->get($calendar->google_id);

		if ($result->status === 404)
			return $calendar->delete();

		$calendar->summary = $result->data->summary;
		$calendar->save();
	}

	public function synchronizeUserCalendars(
		Calendar $service,
		User $owner
	) {
		$calendars = $service->calendarList->listCalendarList()->getItems();

		foreach ($calendars as $calendar) {
			if ($calendar->deleted) {
				ModelsCalendar::where('google_id', $calendar->id)->delete();
			} else {
				$localCalendar = ModelsCalendar::where('google_id', $calendar->id)->first();

				if (!$localCalendar) {
					$localCalendar = new ModelsCalendar(array('owner_id' => $owner->id, 'google_id' => $calendar->id, 'summary' => $calendar->summary));
				} else {
					$localCalendar->summary = $calendar->summary;
				}

				$localCalendar->save();
			}
		}
	}

	public function synchronizeCalendarEvents(
		Calendar $service,
		ModelsCalendar $calendar
	) {
		$events = $service->events->listEvents($calendar->google_id)->getItems();

		foreach ($events as $event) {
			if ($event->status === "cancelled") {
				CalendarEvent::where('google_id', $event->id)->delete();
			} else {
				$localEvent = CalendarEvent::with('period')->where('google_id', $event->id)->first();

				$dto = $this->transformGoogleResponseEventToLocalEvent($event);

				if ($localEvent) {
					$localEvent->fill($dto);
					$localEvent->push();
				} else {
					$dto['id'] = Str::uuid();
					$dto['calendar_id'] = $calendar->id;

					$eventTemp = new CalendarEvent($dto);
					$eventPeriodTemp = new CalendarEventPeriod($dto['period']);

					$eventTemp->save();
					$eventTemp->period()->save($eventPeriodTemp);
				}
			}
		}
	}

	public function synchronizeAllUserResources(User $user)
	{
		$service = $this->getCalendarServiceInstance($user);


		$this->synchronizeUserCalendars(
			$service,
			$user
		);

		$calendars = ModelsCalendar::where('owner_id', $user->id)->get();

		foreach ($calendars as $calendar) {
			if (!$calendar->google_id) {
				$googleId = $this->createCalendar($service, $calendar);

				$calendar->google_id = $googleId;
			}

			$this->synchronizeCalendarEvents($service, $calendar);
		}
	}

	public function init(User $user)
	{
		$service = $this->getCalendarServiceInstance($user);

		$calendarsList = $service->calendarList->listCalendarList()->getItems();

		foreach ($calendarsList as $calendar) {
			$createdCalendar = new ModelsCalendar(['owner_id' => $user->id, 'summary' => $calendar->summary, 'google_id' => $calendar->id]);

			$eventList = $service->events->listEvents($calendar->id)->getItems();

			foreach ($eventList as $event) {
				$dto = $this->transformGoogleResponseEventToLocalEvent($event);

				$dto['id'] = Str::uuid();
				$dto['calendar_id'] = $createdCalendar->id;

				$eventTemp = new CalendarEvent($dto);
				$eventPeriodTemp = new CalendarEventPeriod($dto['period']);

				$eventTemp->save();
				$eventTemp->period()->save($eventPeriodTemp);
			}
		}
	}
}
