<?php

namespace App\Http\Requests\Finance;

use App\Models\FinanceLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A new Sri Lakshmi Micro Finance loan: the loan amount, the processing fee
 * and GST cut when giving it, weekly or daily with the number of weeks /
 * days, and the instalment the office types — the interest and its yearly
 * rate are worked out from it.
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
     * Accept "10,000" / "₹10,000" and "1.5%".
     */
    protected function prepareForValidation(): void
    {
        $clean = fn ($value) => is_string($value) ? str_replace([',', ' ', '₹', '%'], '', $value) : $value;

        $this->merge([
            'principal' => $clean($this->input('principal')),
            'processing_fee_rate' => $clean($this->input('processing_fee_rate')),
            'gst_rate' => $clean($this->input('gst_rate')),
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
            'frequency' => ['required', Rule::in(array_keys(FinanceLoan::FREQUENCIES))],
            'installments' => ['required', 'integer', 'min:1', 'max:3650'],
            'installment_amount' => ['required', 'integer', 'min:1'],
            'first_due_on' => ['required', 'date', 'after_or_equal:loaned_on'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'agent_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }

    /**
     * The instalments must repay at least the loan amount (interest cannot
     * be below nothing).
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

                $principal = (int) $this->input('principal');
                $installments = (int) $this->input('installments');
                $amount = (int) $this->input('installment_amount');

                if ($amount * $installments < $principal) {
                    $validator->errors()->add(
                        'installment_amount',
                        '₹'.number_format($amount).' × '.$installments.' = ₹'.number_format($amount * $installments)
                        .' is less than the loan of ₹'.number_format($principal).'. Use at least ₹'.number_format((int) ceil($principal / max(1, $installments))).'.'
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
            'installment_amount.required' => 'Enter the instalment amount.',
            'installment_amount.integer' => 'Enter the instalment in whole rupees.',
            'first_due_on.after_or_equal' => 'The first instalment cannot be before the loan date.',
        ];
    }
}
