<?php

namespace App\Http\Controllers;

use App\Enums\CalendarParticipantRole;
use App\Http\Requests\CalendarParticipants\AddParticipantsByEmailRequest;
use App\Http\Requests\CalendarParticipants\DeleteParticipantByEmailRequest;
use App\Http\Requests\CalendarParticipants\UpdateParticipantRoleByEmailRequest;
use App\Models\Calendar;
use App\Models\CalendarParticipant;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

use OpenApi\Attributes as OA;

class CalendarParticipantController extends Controller
{
	#[OA\Post(path: '/api/{calendarId}/participants/list', summary: 'Add calendar participants by email list', description: 'Set each participant as member. Admin role is required', tags: ['Calendar participant'])]
	#[OA\Response(response: 201, description: '')]
	public function addParticipantsByEmail(AddParticipantsByEmailRequest $request, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:admin', [$calendar]), 403);

		$invitedUsers = array_map(function (string $email) {
			$user = User::where('email', $email)->first();
			abort_if(is_null($user), 404, "User not found");

			return $user;
		}, $request->input('participants'));

		$invitedUsers = array_filter($invitedUsers, function ($user) use ($calendar) {
			return $user->id !== $calendar->owner_id;
		});

		foreach ($invitedUsers as $user) {
			if (!CalendarParticipant::where('user_id', $user->id)->where('calendar_id', $calendar->id)->exists()) {
				CalendarParticipant::create(array('user_id' => $user->id, 'calendar_id' => $calendar->id));
			}
		}
	}

	#[OA\Get(path: '/api/{calendarId}/participants/list', summary: 'Get all calendar participants', description: 'Member role is required', tags: ['Calendar participant'])]
	#[OA\Response(response: 200, description: 'Calendar participants')]
	public function getAllCalendarParticipants(string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:member', [$calendar]), 403);

		$records = CalendarParticipant::where('calendar_id', $calendarId)->get()->toArray();

		$calendarOwner = User::findOrFail($calendar->owner_id);
		$calendarOwner->role = CalendarParticipantRole::OWNER;

		$participants = array_map(function ($record) {
			$user = User::findOrFail($record['user_id']);
			$user->role = $record['role'];

			return $user;
		}, $records);

		array_push($participants, $calendarOwner);
		return $participants;
	}

	#[OA\Put(path: '/api/{calendarId}/participants', summary: 'Update calendar participant role by email', description: 'Owner role is required', tags: ['Calendar participant'])]
	#[OA\Response(response: 200, description: '')]
	public function updateParticipantRoleByEmail(UpdateParticipantRoleByEmailRequest $request, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:owner', [$calendar]), 403);

		$user = User::where('email', $request->input('email'))->first();
		abort_if(is_null($user), 404, "User not found");

		$participant = CalendarParticipant::where('calendar_id', $calendarId)->where('user_id', $user->id)->first();
		abort_if(is_null($participant), 404, "Participant not found");

		$participant->role = $request->input('role');
		$participant->save();
	}

	#[OA\Delete(path: '/api/{calendarId}/participants', summary: 'Remove calendar participant by email', description: 'Owner role is required', tags: ['Calendar participant'])]
	#[OA\Response(response: 200, description: '')]
	public function removeParticipantByEmail(DeleteParticipantByEmailRequest $request, string $calendarId)
	{
		$calendar = Calendar::whereId($calendarId)->first();
		abort_if(is_null($calendar), 404, "Calendar not found");

		abort_if(!Gate::allows('participant:owner', [$calendar]), 403);

		$user = User::where('email', $request->input('email'))->first();
		abort_if(is_null($user), 404, "User not found");

		CalendarParticipant::where('calendar_id', $calendarId)->where('user_id', $user->id)->delete();
	}
}
