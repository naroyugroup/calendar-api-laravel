<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Requests\Users\UpdateUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class UserController extends Controller
{
	#[OA\Get(path: '/api/users/me', summary: 'Get current user', tags: ['User'])]
	#[OA\Response(response: 200, description: 'User')]
	public function getUser(Request $request)
	{
		return $request->user();
	}

	#[OA\Put(path: '/api/users/me', summary: 'Update current user', tags: ['User'])]
	#[OA\Response(response: 200, description: 'User')]
	public function updateUser(UpdateUserRequest $request)
	{
		$user = $request->user();

		$user->name = $request->input('name');
		$user->save();

		return $user;
	}


	#[OA\Put(path: '/api/users/me/password', summary: 'Update current user password', tags: ['User'])]
	#[OA\Response(response: 200, description: 'User')]
	#[OA\Response(response: 403, description: 'Account is registered with google, cannot change password')]
	public function updateUserPassword(UpdateUserPasswordRequest $request)
	{
		$user = $request->user();

		if ($user->google_id) {
			abort(403, 'Account is registered with google, cannot change password');
		}

		$user->password = $request->input('password');
		$user->save();

		return $user;
	}
}
