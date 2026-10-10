<?php

namespace App\Http\Controllers;

use App\Models\DailySummary;
use App\Models\Status;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function Dashboard(Request $request)
    {
        $validation = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $startDate = $validation['start_date'] ?? null;
        $endDate = $validation['end_date'] ?? null;

        $successStatusId = Status::where('code', 'success')->value('id');

        $totalTransactions = DailySummary::filterDate($startDate, $endDate)->sum('total_count');

        $successCount = DailySummary::filterDate($startDate, $endDate)
            ->where('status_id', $successStatusId)
            ->sum('total_count');

        $totalAmount = DailySummary::filterDate($startDate, $endDate)
            ->where('status_id', $successStatusId)
            ->sum('total_amount');

        $averageAmount = $successCount > 0 ? $totalAmount / $successCount : 0;

        $successRate = $totalTransactions > 0 ? $successCount / $totalTransactions * 100 : 0;

        $daily = DailySummary::filterDate($startDate, $endDate)
            ->selectRaw('date, SUM(total_count) as total_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $monthly = DailySummary::filterDate($startDate, $endDate)
            ->where('status_id', $successStatusId)
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(total_amount) as total_amount")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $perCategory = DailySummary::filterDate($startDate, $endDate)
            ->with('category')
            ->where('status_id', $successStatusId)
            ->selectRaw('category_id, SUM(total_count) as total_count, SUM(total_amount) as total_amount')
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->get();

        $perStatus = DailySummary::filterDate($startDate, $endDate)
            ->with('status')
            ->selectRaw('status_id, SUM(total_count) as total_count')
            ->groupBy('status_id')
            ->orderBy('status_id')
            ->get();

        return view('Dashboard.Dashboard', [
            'judul' => 'Dashboard',
            'totalTransactions' => $totalTransactions,
            'totalAmount' => $totalAmount,
            'averageAmount' => $averageAmount,
            'successRate' => $successRate,
            'daily' => $daily,
            'monthly' => $monthly,
            'perCategory' => $perCategory,
            'perStatus' => $perStatus,
        ]);
    }
}
