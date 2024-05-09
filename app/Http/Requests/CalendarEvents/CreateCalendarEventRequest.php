<?php

namespace App\Http\Requests\CalendarEvents;

use App\Rules\EventPeriodRule;
use Illuminate\Foundation\Http\FormRequest;


class CreateCalendarEventRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(): bool
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'summary' => ['required', 'string'],
			'creator' => ['required', 'email'],
			'organizer' => ['required', 'email'],
			'period' => ['required', new EventPeriodRule],
			'description' => ['string'],
			'location' => ['string'],
			'recurrence_rule' => ['string'],
			'attendee' => ['array'],
			'attendee.*' => ['email']
		];
	}
}
