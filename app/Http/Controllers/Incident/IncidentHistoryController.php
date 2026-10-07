<?php

namespace App\Http\Controllers\Incident;

use App\Http\Controllers\Controller;
use App\Models\Incident\Incident;
use App\Models\Master\Agency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentHistoryController extends Controller
{
    /**
     * Halaman Riwayat Kejadian / Arsip Insiden Terpadu
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');
        $severity = $request->query('severity');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = Incident::with(['assignments.unit', 'media', 'aiAnalysis', 'statusLogs'])
            ->orderBy('reported_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('incident_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('address_text', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category', $category);
        }

        if ($severity) {
            $query->where('severity', $severity);
        }

        if ($startDate) {
            $query->whereDate('reported_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('reported_at', '<=', $endDate);
        }

        $incidents = $query->paginate(15)->withQueryString();

        // Statistik Keseluruhan Arsip
        $stats = [
            'total'     => Incident::count(),
            'resolved'  => Incident::where('status', 'RESOLVED')->count(),
            'closed'    => Incident::where('status', 'CLOSED')->count(),
            'cancelled' => Incident::whereIn('status', ['CANCELLED', 'DUPLICATE', 'FALSE_REPORT'])->count(),
        ];

        return view('command_center.history', compact('incidents', 'stats'));
    }
}
