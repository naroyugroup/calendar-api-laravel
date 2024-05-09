<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TokenAbility;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Exception;
use Laravel\Socialite\Facades\Socialite;

use OpenApi\Attributes as OA;

class GoogleSocialiteController extends Controller
{

	#[OA\Get(path: '/api/auth/google', summary: 'Google OAuth2 endpoint', tags: ['Auth'])]
	#[OA\Response(response: 200, description: '')]
	public function redirectToGoogle()
	{
		return Socialite::driver('google')->scopes([
			"https://www.googleapis.com/auth/userinfo.email",
			"https://www.googleapis.com/auth/userinfo.profile",
			"https://www.googleapis.com/auth/calendar",
		])->with([
			"access_type" => "offline",
			"prompt" => "consent select_account"
		])->redirect();
	}

	#[OA\Get(path: '/api/auth/google/callback', summary: 'Google OAuth2 callback endpoint', tags: ['Auth'])]
	#[OA\Response(response: 200, description: '')]
	public function handleCallback(GoogleCalendarService $GCservice)
	{
		$user = Socialite::driver('google')->user();
		$foundUser = User::where('google_id', $user->id)->first();

		if ($foundUser) {
			Auth::login($foundUser);

			$accessToken = $foundUser->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
			$refreshToken = $foundUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

			$GCservice->init($foundUser);

			return [
				'access_token' => $accessToken->plainTextToken,
				'refresh_token' => $refreshToken->plainTextToken,
			];
		} else {
			$newUser = User::create([
				'google_id' => $user->id,
				'google_api_refresh_token' => $user->refreshToken,
				'name' => $user->name,
				'email' => $user->email,
				'picture' => $user->avatar
			]);

			Auth::login($newUser);

			$accessToken = $newUser->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
			$refreshToken = $newUser->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

			$GCservice->init($newUser);

			return [
				'access_token' => $accessToken->plainTextToken,
				'refresh_token' => $refreshToken->plainTextToken,
			];
		}
	}
}
