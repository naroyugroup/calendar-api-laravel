<?php

namespace App\Http\Requests\CalendarParticipants;

use App\Enums\CalendarParticipantRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateParticipantRoleByEmailRequest extends FormRequest
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
			'email' => ['required', 'email'],
			'role' => ['required', new Enum(CalendarParticipantRole::class)]
		];
	}
}
