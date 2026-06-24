<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\CreditNote;
use App\Exports\InvoicesExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function sales(Request $request): View
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $invoices = Invoice::with(['client', 'items.product'])
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'sent'])
            ->orderByDesc('issue_date')
            ->get();

        $creditNotes = CreditNote::with('client')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->get();

        $totalInvoiced = $invoices->sum('total');
        $totalCredits = $creditNotes->sum('total');
        $netSales = $totalInvoiced - $totalCredits;

        $byClient = $invoices->groupBy('client_id')->map(function ($items) {
            return [
                'name' => $items->first()->client->name,
                'count' => $items->count(),
                'total' => $items->sum('total'),
            ];
        })->sortByDesc('total')->take(10);

        $byProduct = [];
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                $key = $item->product_id ?? $item->description;
                if (!isset($byProduct[$key])) {
                    $byProduct[$key] = [
                        'description' => $item->description,
                        'quantity' => 0,
                        'total' => 0,
                    ];
                }
                $byProduct[$key]['quantity'] += $item->quantity;
                $byProduct[$key]['total'] += $item->subtotal;
            }
        }

        usort($byProduct, fn($a, $b) => $b['total'] <=> $a['total']);
        $byProduct = array_slice($byProduct, 0, 10);

        return view('reports.sales', compact(
            'invoices',
            'startDate',
            'endDate',
            'totalInvoiced',
            'totalCredits',
            'netSales',
            'byClient',
            'byProduct'
        ));
    }

    public function clientStatement(Request $request, Client $client): View
    {
        $invoices = Invoice::where('client_id', $client->id)
            ->whereIn('status', ['approved', 'sent'])
            ->orderByDesc('issue_date')
            ->get();

        $creditNotes = CreditNote::where('client_id', $client->id)
            ->where('status', 'approved')
            ->orderByDesc('issue_date')
            ->get();

        $totalInvoiced = $invoices->sum('total');
        $totalCredits = $creditNotes->sum('total');
        $balance = $totalInvoiced - $totalCredits;

        $client->load('tenant');

        return view('reports.client-statement', compact(
            'client',
            'invoices',
            'creditNotes',
            'totalInvoiced',
            'totalCredits',
            'balance'
        ));
    }

    public function exportInvoices(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

        $invoices = Invoice::with('client')
            ->whereBetween('issue_date', [$startDate, $endDate])
            ->whereIn('status', ['approved', 'sent'])
            ->orderByDesc('issue_date')
            ->get();

        $export = new InvoicesExport($invoices);

        return $export->download('facturas_' . date('Y-m-d') . '.csv');
    }
}
