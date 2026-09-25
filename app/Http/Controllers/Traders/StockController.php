<?php

namespace App\Http\Controllers\Traders;

use App\Http\Controllers\Controller;
use App\Models\RiceVariety;
use App\Support\ReportPdf as Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Stock (SN Traders): rice on hand per variety — everything purchased minus
 * everything sold, in bags.
 */
class StockController extends Controller
{
    public function index(): View
    {
        return view('traders.stock', $this->report());
    }

    public function printStock(): View
    {
        return view('traders.stock-print', $this->report());
    }

    public function pdf(): Response
    {
        return Pdf::loadView('pdf.traders.stock', $this->report())
            ->setPaper('a4', 'portrait')
            ->download('Stock-'.today(config('app.business_timezone'))->toDateString().'.pdf');
    }

    /**
     * Stock per variety (active ones, plus any inactive with stock left).
     *
     * @return Collection<int, array{id: int, name: string, bag_kg: float, is_active: bool, purchased_bags: int, sold_bags: int, stock_bags: int, purchased_amount: float, sold_amount: float}>
     */
    public static function stockByVariety(): Collection
    {
        return RiceVariety::query()
            ->withSum('purchaseItems as purchased_bags', 'bags')
            ->withSum('saleItems as sold_bags', 'bags')
            ->withSum('purchaseItems as purchased_amount', 'amount')
            ->withSum('saleItems as sold_amount', 'amount')
            ->orderBy('name')
            ->get()
            ->map(function (RiceVariety $variety) {
                return [
                    'id' => $variety->id,
                    'name' => $variety->name,
                    'bag_kg' => (float) $variety->bag_kg,
                    'is_active' => $variety->is_active,
                    'purchased_bags' => (int) $variety->purchased_bags,
                    'sold_bags' => (int) $variety->sold_bags,
                    'stock_bags' => (int) $variety->purchased_bags - (int) $variety->sold_bags,
                    'purchased_amount' => (float) $variety->purchased_amount,
                    'sold_amount' => (float) $variety->sold_amount,
                ];
            })
            ->filter(fn (array $row) => $row['is_active'] || $row['stock_bags'] !== 0)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        $rows = self::stockByVariety();

        return [
            'companyName' => 'Traders',
            'rows' => $rows,
            'totalBags' => (int) $rows->sum('stock_bags'),
            'today' => today(config('app.business_timezone')),
        ];
    }
}
