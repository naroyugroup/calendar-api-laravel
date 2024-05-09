<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use OpenApi\Attributes as OA;

class ImageController extends Controller
{
	#[OA\Post(path: '/api/users/me/avatar', summary: 'Upload user image to S3 and set as picture field in user entity', tags: ['User'])]
	#[OA\Response(response: 201, description: '')]
	public function setUserAvatar(Request $request)
	{
		if ($request->hasFile('image')) {
			$user = $request->user();

			if (preg_match('/https:\/\/\S*\.amazonaws.com\/\S*/', $user->picture)) {
				Storage::disk('s3')->delete($user->picture);
			}

			$extension = request()->file('image')->getClientOriginalExtension();
			$image_name = time() . '_' . $request->title . '.' . $extension;
			$path = $request->file('image')->storeAs(
				'images',
				$image_name,
				's3'
			);

			$user->picture = $path;
			$user->save();
		}
	}
}
