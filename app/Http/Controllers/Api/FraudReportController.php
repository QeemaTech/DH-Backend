<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreFraudReportRequest;
use App\Mail\FraudReportReceivedMail;
use App\Models\FraudReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class FraudReportController extends Controller
{
    public function store(StoreFraudReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $report = FraudReport::query()->create([
            ...$data,
            'status' => 'new',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $report->events()->create([
            'event_type' => 'submitted',
            'actor_type' => 'guest',
            'actor_id' => null,
            'meta' => [
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ],
        ]);
        
        Mail::to($report->email)->send(new FraudReportReceivedMail($report));

        return response()->json([
            'success' => true,
            'message' => __('Fraud report submitted successfully.'),
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
            ],
        ], 201);
    }
}
