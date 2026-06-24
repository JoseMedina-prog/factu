<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Services\InvoiceService;
use App\Services\NumberingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request, InvoiceService $invoiceService, NumberingService $numberingService, PaymentService $paymentService, \App\Services\InventoryService $inventoryService)
    {
        $tenant = auth()->user()->tenant;
        $stats = $invoiceService->getInvoiceStats();
        $charts = $this->getChartData();
        $activity = $this->getRecentActivity();
        $alerts = $this->getAlerts();
        $numberingAlerts = $tenant ? $numberingService->getExhaustionAlertsQuietly($tenant) : [];
        $paymentStats = $paymentService->getStats($tenant?->id);
        $accountsReceivable = $tenant ? $paymentService->getAccountsReceivable($tenant->id) : null;
        $inventoryAlerts = $tenant ? $inventoryService->getInventoryValuation($tenant) : null;

        return view('dashboard', compact('stats', 'charts', 'activity', 'alerts', 'numberingAlerts', 'paymentStats', 'accountsReceivable', 'inventoryAlerts'));
    }

    private function getChartData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        $months = 6;

        $monthlyInvoices = Invoice::selectRaw('MONTH(issue_date) as month, YEAR(issue_date) as year, COUNT(*) as count, SUM(total) as amount')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('issue_date')
            ->where('issue_date', '>=', now()->subMonths($months))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $labels = [];
        $invoiceCounts = [];
        $invoiceAmounts = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->month;
            $yearKey = $date->year;

            $labels[] = $date->format('M');

            $data = $monthlyInvoices->firstWhere('month', $monthKey);
            $invoiceCounts[] = $data ? (int) $data->count : 0;
            $invoiceAmounts[] = $data ? (float) $data->amount : 0;
        }

        $statusDistribution = Invoice::where('tenant_id', $tenantId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $topClients = Invoice::where('tenant_id', $tenantId)
            ->selectRaw('client_id, COUNT(*) as invoice_count, SUM(total) as total_amount')
            ->with('client:id,name')
            ->groupBy('client_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get();

        return [
            'labels' => $labels,
            'invoice_counts' => $invoiceCounts,
            'invoice_amounts' => $invoiceAmounts,
            'status_distribution' => $statusDistribution,
            'top_clients' => $topClients,
        ];
    }

    private function getRecentActivity(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $recentInvoices = Invoice::where('tenant_id', $tenantId)
            ->with('client:id,name')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'number', 'total', 'status', 'created_at', 'client_id']);

        $recentClients = Client::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'name', 'created_at']);

        $recentProducts = Product::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'name', 'price', 'created_at']);

        return [
            'invoices' => $recentInvoices,
            'clients' => $recentClients,
            'products' => $recentProducts,
        ];
    }

    private function getAlerts(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'sent'])
            ->where('due_date', '<', now()->toDateString())
            ->count();

        $rejectedInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'rejected')
            ->where('updated_at', '>=', now()->subDays(7))
            ->count();

        $draftInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'draft')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        return [
            'overdue' => $overdueInvoices,
            'rejected' => $rejectedInvoices,
            'old_drafts' => $draftInvoices,
        ];
    }
}
