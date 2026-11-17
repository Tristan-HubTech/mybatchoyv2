<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BusinessAccessRequest;
use App\Services\PermissionService;
use App\Services\TechnicalAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessAccessRequestController extends Controller
{
    public function index()
    {
        PermissionService::expireElapsedGrants();

        $requests = BusinessAccessRequest::with(['systemAdmin:id,name,username,email', 'reviewer:id,name,username'])
            ->latest()
            ->paginate(15);

        return view('owner.access-requests', [
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, BusinessAccessRequest $accessRequest)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'review_notes' => 'nullable|string|max:500',
        ]);

        if ($accessRequest->status !== 'pending') {
            return redirect()->route('owner.access.index')
                ->withErrors(['access_request' => 'Only pending access requests can be approved.']);
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

            $detail = "Owner approved exceptional [{$accessRequest->scope}] access for request #{$accessRequest->id} until {$expiresAt->toDateTimeString()}.";
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

        return redirect()->route('owner.access.index')
            ->with('success', "Approved scoped access for {$accessRequest->scopeLabel()} ({$accessRequest->duration_minutes} mins).");
    }

    public function reject(Request $request, BusinessAccessRequest $accessRequest)
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        if ($accessRequest->status !== 'pending') {
            return redirect()->route('owner.access.index')
                ->withErrors(['access_request' => 'Only pending access requests can be rejected.']);
        }

        DB::transaction(function () use ($accessRequest, $validated, $request): void {
            $accessRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_at' => now(),
            ]);

            $detail = "Owner rejected exceptional [{$accessRequest->scope}] access request #{$accessRequest->id}.";
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
        });

        return redirect()->route('owner.access.index')->with('success', 'Access request rejected.');
    }

    public function revoke(Request $request, BusinessAccessRequest $accessRequest)
    {
        $request->validate([
            'current_password' => 'required|current_password',
        ]);

        if ($accessRequest->status !== 'approved' || $accessRequest->revoked_at !== null) {
            return redirect()->route('owner.access.index')
                ->withErrors(['access_request' => 'Only active approved grants can be revoked.']);
        }

        DB::transaction(function () use ($accessRequest, $request): void {
            $accessRequest->update([
                'status' => 'revoked',
                'revoked_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            $detail = "Owner revoked exceptional [{$accessRequest->scope}] access grant #{$accessRequest->id}.";
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
        });

        return redirect()->route('owner.access.index')->with('success', 'Active System Admin access grant revoked.');
    }
}

