<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Auth\GoogleSocialiteController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CalendarEventController;
use App\Http\Controllers\CalendarParticipantController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\ImageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => ['web']], function () {
	Route::controller(GoogleSocialiteController::class)
		->prefix('auth/google')
		->group(
			function () {
				Route::get('/', 'redirectToGoogle');
				Route::get('/callback', 'handleCallback');
			}
		);
});

Route::controller(AuthenticationController::class)
	->prefix('auth')
	->group(
		function () {
			Route::post('/register', 'register');
			Route::post('/login', 'login');
			Route::post('/refresh', 'refresh')->middleware([
				'auth:sanctum',
				'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value,
			]);
			Route::post('/logout', 'logout');
		}
	);

Route::group(['middleware' => ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]], function () {
	Route::controller(UserController::class)
		->prefix('users')
		->group(
			function () {
				Route::get('/me', 'getUser');
				Route::put('/me', 'updateUser');
				Route::put('/me/password', 'updateUserPassword');

				Route::controller(ImageController::class)
					->group(
						function () {
							Route::post('/me/avatar', 'setUserAvatar');
						}
					);
			}
		);

	Route::controller(GoogleCalendarController::class)
		->prefix('resources/google')
		->group(
			function () {
				Route::Post('/sync', 'sync');
			}
		);

	Route::controller(CalendarController::class)
		->prefix('calendars')
		->group(
			function () {
				Route::post('/', 'createCalendar');
				Route::get('/all', 'getAllCalendars');
				Route::get('/{calendarId}', 'getCalendarById');
				Route::put('/{calendarId}', 'updateCalendarById');
				Route::put('/{calendarId}/favorite', 'updateCalendarFavoriteStatusById');
				Route::delete('/{calendarId}', 'deleteCalendarById');

				Route::controller(CalendarParticipantController::class)
					->prefix('{calendarId}/participants')
					->group(
						function () {
							Route::post('/list', 'addParticipantsByEmail');
							Route::get('/list', 'getAllCalendarParticipants');
							Route::put('/', 'updateParticipantRoleByEmail');
							Route::delete('/', 'removeParticipantByEmail');
						}
					);

				Route::controller(CalendarEventController::class)
					->prefix('{calendarId}/events')
					->group(
						function () {
							Route::post('/', 'createEvent');
							Route::get('/range', 'getEventsByDateRange');
							Route::get('/{eventId}', 'getEventById');
							Route::put('/{eventId}', 'updateEventById');
							Route::delete('/{eventId}', 'deleteEventById');
						}
					);
			}
		);
});
