<?php

namespace App\Http\Requests\Finance;

use App\Models\FinanceLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new Sri Lakshmi Micro Finance loan: the loan amount, the processing fee
 * and GST cut when giving it, the interest per annum, daily or weekly with
 * the number of days / weeks, and the instalment (worked out unless the
 * office types its own).
 */
class StoreLoanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Accept "10,000" / "₹10,000" and "1.5%"; an empty instalment means
     * "work it out".
     */
    protected function prepareForValidation(): void
    {
        $clean = fn ($value) => is_string($value) ? str_replace([',', ' ', '₹', '%'], '', $value) : $value;

        $this->merge([
            'principal' => $clean($this->input('principal')),
            'processing_fee_rate' => $clean($this->input('processing_fee_rate')),
            'gst_rate' => $clean($this->input('gst_rate')),
            'interest_rate' => $clean($this->input('interest_rate')),
            'installments' => $clean($this->input('installments')),
            'installment_amount' => filled($this->input('installment_amount')) ? $clean($this->input('installment_amount')) : null,
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

        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'loaned_on' => ['required', 'date', 'before_or_equal:'.$today],
            'principal' => ['required', 'integer', 'min:100', 'max:100000000'],
            'processing_fee_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'gst_rate' => ['required', 'numeric', 'min:0', 'max:50'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:200'],
            'frequency' => ['required', Rule::in(array_keys(FinanceLoan::FREQUENCIES))],
            'installments' => ['required', 'integer', 'min:1', 'max:3650'],
            'installment_amount' => ['nullable', 'integer', 'min:1'],
            'first_due_on' => ['required', 'date', 'after_or_equal:loaned_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The instalment (the office's own, or the worked-out one) must pay the
     * total in that many instalments.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $terms = FinanceLoan::terms(
                    (int) $this->input('principal'),
                    (float) $this->input('processing_fee_rate'),
                    (float) $this->input('gst_rate'),
                    (float) $this->input('interest_rate'),
                    (string) $this->input('frequency'),
                    (int) $this->input('installments'),
                );

                $amount = (int) ($this->input('installment_amount') ?? $terms['installment_amount']);

                if (! FinanceLoan::installmentFits($terms['loan_amount'], $terms['installments'], $amount)) {
                    $validator->errors()->add(
                        'installment_amount',
                        '₹'.number_format($amount).' × '.$terms['installments'].' does not repay ₹'.number_format($terms['loan_amount'])
                        .' exactly. Use about ₹'.number_format($terms['installment_amount']).', or change the number of '.($this->input('frequency') === FinanceLoan::WEEKLY ? 'weeks' : 'days').'.'
                    );
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
            'customer_id.required' => 'Choose the customer.',
            'loaned_on.before_or_equal' => 'The loan date cannot be in the future.',
            'principal.integer' => 'Enter the loan amount in whole rupees.',
            'principal.min' => 'Enter a loan of at least ₹100.',
            'installments.integer' => 'Enter the number of days / weeks as a whole number.',
            'installment_amount.integer' => 'Enter the instalment in whole rupees.',
            'first_due_on.after_or_equal' => 'The first instalment cannot be before the loan date.',
        ];
    }
}
