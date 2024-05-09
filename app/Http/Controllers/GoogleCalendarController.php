<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class GoogleCalendarController extends Controller
{
	#[OA\Post(path: '/api/resources/google/sync', summary: 'Synchronize google calendar events and calendars', tags: ['Google calendar'])]
	#[OA\Response(response: 201, description: '')]
	public function sync(Request $request, GoogleCalendarService $GCservice)
	{
		return $GCservice->synchronizeAllUserResources($request->user());
	}
}
