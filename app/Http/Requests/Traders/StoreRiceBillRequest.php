<?php

namespace App\Http\Requests\Traders;

use App\Models\Payment;
use App\Models\RiceVariety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A rice purchase bill (from a supplier) or sale invoice (to a customer):
 * the header plus one or more variety lines — bags and the rate per bag
 * (SN Traders deals in whole bags). A sale may also take money now.
 */
class StoreRiceBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function isSale(): bool
    {
        return $this->routeIs('traders.sales.*');
    }

    /**
     * Drop empty lines, and accept "1,250" / "₹1,250" amounts.
     */
    protected function prepareForValidation(): void
    {
        $clean = fn ($value) => is_string($value) ? str_replace([',', ' ', '₹'], '', $value) : $value;

        $lines = collect($this->input('lines', []))
            ->filter(fn ($line) => is_array($line) && filled($line['variety_id'] ?? null))
            ->map(fn (array $line) => [
                'variety_id' => $line['variety_id'],
                'bags' => $clean($line['bags'] ?? null),
                'rate' => $clean($line['rate'] ?? null),
            ])
            ->values()
            ->all();

        $this->merge([
            'lines' => $lines,
            'received_amount' => $clean($this->input('received_amount')) ?: 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $today = today(config('app.business_timezone'))->toDateString();

        $lines = [
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.variety_id' => ['required', 'integer', Rule::exists('trader_varieties', 'id')],
            'lines.*.bags' => ['required', 'integer', 'min:1', 'max:100000'],
            'lines.*.rate' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->isSale()) {
            return $lines + [
                'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
                'sold_on' => ['required', 'date', 'before_or_equal:'.$today],
                'received_amount' => ['nullable', 'numeric', 'min:0'],
                'received_method' => ['required', Rule::in(array_keys(Payment::METHODS))],
                'received_reference' => ['nullable', 'string', 'max:100'],
            ];
        }

        return $lines + [
            'supplier_id' => ['required', 'integer', Rule::exists('trader_suppliers', 'id')],
            'purchased_on' => ['required', 'date', 'before_or_equal:'.$today],
            'supplier_bill_no' => ['nullable', 'string', 'max:60'],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
        ];
    }

    /**
     * A sale cannot take more money now than the invoice total.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $total = 0;

                foreach ($this->input('lines') as $line) {
                    $total += round((int) $line['bags'] * (float) $line['rate'], 2);
                }

                if ($this->isSale() && (float) $this->input('received_amount') > round($total, 2) + 0.001) {
                    $validator->errors()->add('received_amount', 'The amount received now cannot be more than the invoice total (₹'.number_format($total, 2).').');
                }
            },
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => 'Add at least one rice line.',
            'lines.*.rate.min' => 'Enter the rate for every line.',
            'lines.*.rate.required' => 'Enter the rate for every line.',
            'lines.*.bags.required' => 'Enter the bags for every line.',
            'lines.*.bags.min' => 'Enter the bags for every line.',
            'lines.*.bags.integer' => 'Bags must be a whole number.',
            'sold_on.before_or_equal' => 'The date cannot be in the future.',
            'purchased_on.before_or_equal' => 'The date cannot be in the future.',
            'customer_id.required' => 'Choose the customer.',
            'supplier_id.required' => 'Choose the supplier.',
        ];
    }

    /**
     * The lines, cast, ready for Purchase::record / Sale::record: whole
     * bags priced per bag, weighed at the variety's bag size.
     *
     * @return list<array{variety_id: int, bags: int, bag_kg: float, loose_kg: float, rate: float, rate_per: string}>
     */
    public function lines(): array
    {
        $lines = collect($this->validated('lines'));
        $bagKg = RiceVariety::query()->whereKey($lines->pluck('variety_id'))->pluck('bag_kg', 'id');

        return $lines
            ->map(fn (array $line) => [
                'variety_id' => (int) $line['variety_id'],
                'bags' => (int) $line['bags'],
                'bag_kg' => (float) ($bagKg[$line['variety_id']] ?? 0),
                'loose_kg' => 0.0,
                'rate' => (float) $line['rate'],
                'rate_per' => 'bag',
            ])
            ->all();
    }
}
