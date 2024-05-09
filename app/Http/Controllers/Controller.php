<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info('1.0.0', title: 'Calendar laravel')]
#[OA\SecurityScheme(type: 'http', securityScheme: 'bearerAuth', scheme: 'bearer', bearerFormat: 'JWT')]
abstract class Controller
{
	//
}
