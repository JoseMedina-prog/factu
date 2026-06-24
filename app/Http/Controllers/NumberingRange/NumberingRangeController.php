<?php

namespace App\Http\Controllers\NumberingRange;

use App\Http\Controllers\Controller;
use App\Http\Requests\NumberingRange\StoreNumberingRangeRequest;
use App\Http\Requests\NumberingRange\UpdateNumberingRangeRequest;
use App\Models\NumberingRange;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NumberingRangeController extends Controller
{
    public function __construct(
        protected NumberingService $numberingService
    ) {}

    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $ranges = NumberingRange::query()
            ->when($request->document_type, fn ($q) => $q->where('document_type', $request->document_type))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->is_active))
            ->orderBy('document_type')
            ->orderBy('from_number')
            ->paginate(15);

        $alerts = $this->numberingService->getExhaustionAlertsQuietly($tenant);

        return view('numbering-range.index', compact('ranges', 'alerts'));
    }

    public function create(): View
    {
        $tenant = auth()->user()->tenant;
        $defaultPrefix = $tenant->invoice_prefix ?: 'INV';
        return view('numbering-range.create', compact('defaultPrefix'));
    }

    public function store(StoreNumberingRangeRequest $request): RedirectResponse
    {
        $tenant = auth()->user()->tenant;
        $data = $request->validated();
        $data['tenant_id'] = $tenant->id;
        $data['current_number'] = ($data['from_number'] ?? 1) - 1;

        $range = NumberingRange::create($data);

        try {
            $this->numberingService->validateNoOverlap($range);
        } catch (\App\Exceptions\NumberingRangeException $e) {
            $range->delete();
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.numbering.index')
            ->with('success', 'Rango de numeración creado correctamente');
    }

    public function edit(NumberingRange $numberingRange): View
    {
        return view('numbering-range.edit', ['range' => $numberingRange]);
    }

    public function update(UpdateNumberingRangeRequest $request, NumberingRange $numberingRange): RedirectResponse
    {
        $data = $request->validated();

        if (($data['from_number'] ?? 0) <= ($numberingRange->current_number ?? 0)
            && ($data['from_number'] ?? 0) !== $numberingRange->from_number) {
            return back()
                ->withInput()
                ->with('error', 'El nuevo rango "desde" no puede ser menor o igual al número ya usado.');
        }

        $numberingRange->update($data);

        try {
            $this->numberingService->validateNoOverlap($numberingRange);
        } catch (\App\Exceptions\NumberingRangeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('settings.numbering.index')
            ->with('success', 'Rango actualizado correctamente');
    }

    public function destroy(NumberingRange $numberingRange): RedirectResponse
    {
        if ($numberingRange->current_number > ($numberingRange->from_number - 1)) {
            return back()->with('error', 'No se puede eliminar un rango que ya tiene números asignados. Desactívelo en su lugar.');
        }

        $numberingRange->delete();

        return back()->with('success', 'Rango eliminado correctamente');
    }
}