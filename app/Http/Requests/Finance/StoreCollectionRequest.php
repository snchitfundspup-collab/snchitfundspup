<?php

namespace App\Http\Requests\Finance;

use App\Models\FinanceLoan;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Money collected against a loan: never more than the loan's balance.
 */
class StoreCollectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Accept "1,250" / "₹1,250" and the date-time box's "2026-10-05T14:30".
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $collectedAt = $this->input('collected_at');

        $this->merge([
            'amount' => is_string($amount) ? str_replace([',', ' ', '₹'], '', $amount) : $amount,
            'collected_at' => is_string($collectedAt) ? str_replace('T', ' ', $collectedAt) : $collectedAt,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var FinanceLoan $loan */
        $loan = $this->route('loan');

        return [
            'amount' => ['required', 'integer', 'min:1', 'max:'.max(0, $loan->balance())],
            'collected_at' => [
                'required',
                'date',
                'after_or_equal:'.$loan->loaned_on->toDateString(),
                'before_or_equal:'.now(config('app.business_timezone'))->format('Y-m-d H:i:59'),
            ],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        /** @var FinanceLoan $loan */
        $loan = $this->route('loan');

        return [
            'amount.integer' => 'Enter the amount in whole rupees.',
            'amount.min' => 'Enter an amount of at least ₹1.',
            'amount.max' => $loan->balance() > 0
                ? 'The amount cannot be more than the balance of ₹'.number_format($loan->balance()).'.'
                : 'This loan is fully paid.',
            'collected_at.after_or_equal' => 'The date cannot be before the loan was given.',
            'collected_at.before_or_equal' => 'The date and time cannot be in the future.',
        ];
    }
}
