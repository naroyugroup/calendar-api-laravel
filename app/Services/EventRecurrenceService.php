<?php

namespace App\Services;

use App\Models\CalendarEvent;
use DateTime;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;
use Recurr\Transformer\Constraint\BetweenConstraint;

class EventRecurrenceService
{
	private function getEventsWithoutRecurrenceBetweenDates(string $calendarId, string $startDate, string $endDate)
	{
		return CalendarEvent::with('period')->where('calendar_id', $calendarId)->where('recurrence_rule', null)->whereHas('period', function ($query) use ($startDate, $endDate) {
			$query
				->whereBetween('start_date', [$startDate, $endDate])
				->orWhereBetween('end_date', [$startDate, $endDate])
				->orWhereBetween('start_date_time', [$startDate, $endDate])
				->orWhereBetween('end_date_time', [$startDate, $endDate]);
		})->get();
	}

	private function parseRecurrenceEvent(CalendarEvent $event, string $startDate, string $endDate)
	{
		$isFullDayEvent = boolval($event->period->start_date);
		$eventStartDate = $event->period->start_date_time ?? $event->period->start_date;
		$eventEndDate = $event->period->end_date_time ?? $event->period->end_date;

		$rrule = new Rule($event->recurrence_rule, $eventStartDate, $eventEndDate);

		$transformer = new ArrayTransformer();
		$constraint = new BetweenConstraint(new DateTime($startDate), new DateTime($endDate));

		return array_map(function ($recurrence) use ($event, $isFullDayEvent) {
			$newEvent = new CalendarEvent($event->toArray());

			if ($isFullDayEvent) {
				$newEvent->period->start_date = $recurrence->getStart()->format('Y-m-d');
				$newEvent->period->end_date = $recurrence->getEnd()->format('Y-m-d');
			} else {
				$newEvent->period->start_date_time = $recurrence->getStart()->format(DateTime::ATOM);
				$newEvent->period->end_date_time = $recurrence->getEnd()->format(DateTime::ATOM);
			}

			return $newEvent;
		}, $transformer->transform($rrule, $constraint)->toArray());
	}

	public function getByDateRange(string $calendarId, string $startDate, string $endDate)
	{
		$events = array();

		$eventsWithoutRecurrence = $this->getEventsWithoutRecurrenceBetweenDates($calendarId, $startDate, $endDate);
		array_push($events, ...$eventsWithoutRecurrence);

		$eventsWithRecurrence = CalendarEvent::with('period')->where('calendar_id', $calendarId)->whereNotNull('recurrence_rule')->get();

		$eventsWithRecurrence->map(function ($event) use (&$events, $startDate, $endDate) {
			$parsedEvents = $this->parseRecurrenceEvent($event, $startDate, $endDate);
			array_push($events, ...$parsedEvents);
		});

		return $events;
	}
}
