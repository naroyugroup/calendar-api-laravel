<?php

namespace App\Http\Controllers;

use App\Enums\TokenAbility;
use App\Http\Requests\Authentication\LoginUserRequest;
use App\Http\Requests\Authentication\RegisterUserRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

use OpenApi\Attributes as OA;

class AuthenticationController extends Controller
{

	#[OA\Post(path: '/api/auth/register', summary: 'Local register and return tokens', tags: ['Auth'])]
	#[OA\Response(response: 201, description: 'Access and refresh tokens')]
	public function register(RegisterUserRequest $request)
	{
		$request['remember_token'] = Str::random(10);
		$user = User::create($request->all());

		$accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
		$refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

		return [
			'access_token' => $accessToken->plainTextToken,
			'refresh_token' => $refreshToken->plainTextToken,
		];
	}

	#[OA\Post(path: '/api/auth/login', summary: 'Local login and return tokens', tags: ['Auth'])]
	#[OA\Response(response: 201, description: 'Access and refresh tokens')]
	#[OA\Response(response: 401, description: 'Invalid credentials')]
	public function login(LoginUserRequest $request)
	{
		$user = User::where('email', $request->email)->first();

		abort_if(!$user, 401, 'Invalid credentials');
		abort_if(!Hash::check($request->password, $user->password), 401, 'Invalid credentials');

		$accessToken = $user->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
		$refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

		return [
			'access_token' => $accessToken->plainTextToken,
			'refresh_token' => $refreshToken->plainTextToken,
		];
	}

	#[OA\Post(path: '/api/auth/refresh', summary: 'Refresh and return tokens', tags: ['Auth'])]
	#[OA\Response(response: 201, description: 'Access and refresh tokens')]
	public function refresh(Request $request)
	{
		$user = $request->user();

		$user->currentAccessToken()->delete();

		$accessToken = $request->user()->createToken('access_token', [TokenAbility::ACCESS_API->value], Carbon::now()->addMinutes(config('sanctum.ac_expiration')));
		$refreshToken = $user->createToken('refresh_token', [TokenAbility::ISSUE_ACCESS_TOKEN->value], Carbon::now()->addMinutes(config('sanctum.rt_expiration')));

		return [
			'access_token' => $accessToken->plainTextToken,
			'refresh_token' => $refreshToken->plainTextToken,
		];
	}

	#[OA\Post(path: '/api/auth/logout', summary: 'Logout and delete tokens', tags: ['Auth'])]
	#[OA\Response(response: 201, description: 'Access and refresh tokens')]
	public function logout(Request $request)
	{
		$request->user()->tokens()->delete();
	}
}
