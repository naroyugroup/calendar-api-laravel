<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EventPeriodRule implements ValidationRule
{
	/**
	 * Run the validation rule.
	 *
	 * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
	 */
	public function validate(string $attribute, mixed $value, Closure $fail): void
	{
		// if full day, must exist only (start/end)Date field
		$isStartFullDay = isset($value['startDate']) && !isset($value['startDateTime']) && !isset($value['startTimezone']);
		$isEndFullDay = isset($value['endDate']) && !isset($value['endDateTime']) && !isset($value['endTimezone']);

		// if regular, must exist only (start/end)DateTime and (start/end)Timezone fields
		$isStartRegular = isset($value['startDateTime']) && isset($value['startTimezone']) && !isset($value['startDate']);
		$isEndRegular = isset($value['endDateTime']) && isset($value['endTimezone']) && !isset($value['endDate']);

		$isFullDayEvent = $isStartFullDay && $isEndFullDay;
		$isRegularEvent = $isStartRegular && $isEndRegular;

		$passed = $isFullDayEvent || $isRegularEvent;
		if (!$passed) {
			$fail("event period is not valid, if full day, must exist only (start/end)Date field, if regular, must exist only (start/end)DateTime and (start/end)Timezone fields");
		}
	}
}
