<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\StockNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = $user?->roleName() ?: 'staff';

        return response()->json(
            StockNotificationService::forUser($user, $roleName, 20)
        );
    }
}

