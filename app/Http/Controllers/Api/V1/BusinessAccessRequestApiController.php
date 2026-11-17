<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessAccessRequest;
use App\Services\PermissionService;
use App\Services\TechnicalAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessAccessRequestApiController extends Controller
{
    public function index(): JsonResponse
    {
        PermissionService::expireElapsedGrants();

        $requests = BusinessAccessRequest::with(['systemAdmin:id,name,username,email', 'reviewer:id,name,username'])
            ->latest()
            ->take(50)
            ->get();

        return response()->json(['access_requests' => $requests]);
    }

    public function approve(Request $request, BusinessAccessRequest $accessRequest): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'review_notes' => 'nullable|string|max:500',
        ]);

        if ($accessRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending access requests can be approved.'], 422);
        }

        DB::transaction(function () use ($accessRequest, $validated, $request): void {
            $now = now();
            $expiresAt = $now->copy()->addMinutes((int) $accessRequest->duration_minutes);

            $accessRequest->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_at' => $now,
                'expires_at' => $expiresAt,
                'revoked_at' => null,
            ]);

            $detail = "Owner approved exceptional [{$accessRequest->scope}] access for request #{$accessRequest->id} until {$expiresAt->toDateTimeString()} via API.";
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'exceptional_access_approved',
                'model_type' => BusinessAccessRequest::class,
                'model_id' => $accessRequest->id,
                'details' => $detail,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            TechnicalAuditLogger::record(
                $request,
                $request->user()->id,
                'access_request',
                'exceptional_access_approved',
                $detail,
                BusinessAccessRequest::class,
                $accessRequest->id
            );
        });

        return response()->json([
            'message' => 'Exceptional business access approved.',
            'access_request' => $accessRequest->fresh(['systemAdmin:id,name,username', 'reviewer:id,name,username']),
        ]);
    }

    public function reject(Request $request, BusinessAccessRequest $accessRequest): JsonResponse
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        if ($accessRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending access requests can be rejected.'], 422);
        }

        $accessRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $detail = "Owner rejected exceptional [{$accessRequest->scope}] request #{$accessRequest->id} via API.";
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'exceptional_access_rejected',
            'model_type' => BusinessAccessRequest::class,
            'model_id' => $accessRequest->id,
            'details' => $detail,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'access_request',
            'exceptional_access_rejected',
            $detail,
            BusinessAccessRequest::class,
            $accessRequest->id
        );

        return response()->json([
            'message' => 'Exceptional business access rejected.',
            'access_request' => $accessRequest->fresh(),
        ]);
    }

    public function revoke(Request $request, BusinessAccessRequest $accessRequest): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
        ]);

        if ($accessRequest->status !== 'approved' || $accessRequest->revoked_at !== null) {
            return response()->json(['message' => 'Only active approved grants can be revoked.'], 422);
        }

        $accessRequest->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        $detail = "Owner revoked exceptional [{$accessRequest->scope}] access grant #{$accessRequest->id} via API.";
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'exceptional_access_revoked',
            'model_type' => BusinessAccessRequest::class,
            'model_id' => $accessRequest->id,
            'details' => $detail,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        TechnicalAuditLogger::record(
            $request,
            $request->user()->id,
            'access_request',
            'exceptional_access_revoked',
            $detail,
            BusinessAccessRequest::class,
            $accessRequest->id
        );

        return response()->json([
            'message' => 'Exceptional access grant revoked.',
            'access_request' => $accessRequest->fresh(),
        ]);
    }
}

