<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rice Varieties (SN Traders → Masters): the rice kinds bought and sold,
 * each with its usual bag weight.
 */
class VarietyController extends Controller
{
    public function index(): View
    {
        return view('traders.varieties', [
            'varieties' => RiceVariety::query()
                ->withSum('purchaseItems as purchased_bags', 'bags')
                ->withSum('saleItems as sold_bags', 'bags')
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $variety = RiceVariety::create($this->validated($request));

        return redirect()->route('traders.varieties.index')->with('success', "{$variety->name} added.");
    }

    public function update(Request $request, RiceVariety $variety): RedirectResponse
    {
        $variety->update($this->validated($request, $variety) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('traders.varieties.index')->with('success', "{$variety->name} updated.");
    }

    public function destroy(RiceVariety $variety): RedirectResponse
    {
        if ($variety->purchaseItems()->exists() || $variety->saleItems()->exists()) {
            return redirect()
                ->route('traders.varieties.index')
                ->with('error', "{$variety->name} is on purchase or sale bills, so it cannot be deleted. Untick Active to hide it instead.");
        }

        $variety->delete();

        return redirect()->route('traders.varieties.index')->with('success', "{$variety->name} deleted.");
    }

    /**
     * @return array{name: string, bag_kg: float}
     */
    private function validated(Request $request, ?RiceVariety $variety = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('trader_varieties', 'name')->ignore($variety)],
            'bag_kg' => ['required', 'numeric', 'min:1', 'max:200'],
        ], [
            'name.unique' => 'That variety already exists.',
        ]);
    }
}
