<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Suppliers (SN Traders → Masters): mills and wholesalers rice is bought from.
 */
class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        return view('traders.suppliers', [
            'search' => $search,
            'suppliers' => Supplier::query()
                ->withCount('purchases')
                ->withSum('purchases as purchased_amount', 'total_amount')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('place', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()
            ->to($request->input('return_to') === 'purchase' ? route('traders.purchases.create', ['supplier' => $supplier->id]) : route('traders.suppliers.index'))
            ->with('success', "{$supplier->name} added.");
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('traders.suppliers.index')->with('success', "{$supplier->name} updated.");
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchases()->exists()) {
            return redirect()
                ->route('traders.suppliers.index')
                ->with('error', "{$supplier->name} has purchases, so it cannot be deleted.");
        }

        $supplier->delete();

        return redirect()->route('traders.suppliers.index')->with('success', "{$supplier->name} deleted.");
    }

    /**
     * @return array{name: string, phone: ?string, place: ?string, notes: ?string}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'place' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
