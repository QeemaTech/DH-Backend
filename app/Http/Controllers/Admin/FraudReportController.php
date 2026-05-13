<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FraudReports\UpdateRequest;
use App\Models\FraudReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FraudReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = FraudReport::query()->with('assignee');

        if ($request->filled('assigned_to')) {
            if ($request->input('assigned_to') === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', (int) $request->input('assigned_to'));
            }
        }

        $reports = $query
            ->latest()
            ->paginate(20);

        $admins = User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.reports.fraud_reports.index', compact('reports', 'admins'));
    }

    public function show(FraudReport $fraudReport): View
    {
        $fraudReport->load(['assignee', 'events.actor']);

        $admins = User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.reports.fraud_reports.show', compact('fraudReport', 'admins'));
    }

    public function update(UpdateRequest $request, FraudReport $fraudReport): RedirectResponse
    {
        $data = $request->validated();

        $beforeStatus = $fraudReport->status;
        $beforeAssignedTo = $fraudReport->assigned_to;

        $fraudReport->status = $data['status'];
        $fraudReport->assigned_to = $data['assigned_to'] ?? null;
        $fraudReport->resolved_at = $data['status'] === 'resolved' ? now() : null;
        $fraudReport->save();

        if ($beforeStatus !== $fraudReport->status) {
            $fraudReport->events()->create([
                'event_type' => 'status_changed',
                'actor_type' => 'admin',
                'actor_id' => Auth::id(),
                'meta' => [
                    'from' => $beforeStatus,
                    'to' => $fraudReport->status,
                ],
            ]);
        }

        if ((int) $beforeAssignedTo !== (int) $fraudReport->assigned_to) {
            $fromAdmin = $beforeAssignedTo ? User::query()->find($beforeAssignedTo) : null;
            $toAdmin = $fraudReport->assigned_to ? User::query()->find($fraudReport->assigned_to) : null;

            $fraudReport->events()->create([
                'event_type' => 'assigned_changed',
                'actor_type' => 'admin',
                'actor_id' => Auth::id(),
                'meta' => [
                    'from' => $beforeAssignedTo,
                    'to' => $fraudReport->assigned_to,
                    'from_name' => $fromAdmin?->name,
                    'to_name' => $toAdmin?->name,
                ],
            ]);
        }

        return redirect()->route('admin.fraud-reports.show', $fraudReport)
            ->with('success', __('Fraud report updated successfully.'));
    }
}
