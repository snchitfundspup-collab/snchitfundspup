<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Add / edit an expense: when, what for, how much, which partner paid, to
 * whom, how, and the bill number.
 */
class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Accept "2,000" or "₹2,000".
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');

        $this->merge([
            'amount' => is_string($amount) ? str_replace([',', ' ', '₹'], '', $amount) : $amount,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'spent_on' => ['required', 'date', 'before_or_equal:'.today(config('app.business_timezone'))->toDateString()],
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'integer', 'min:1', 'max:99999999'],
            'paid_by' => ['required', 'integer', Rule::exists('users', 'id')->where('is_partner', true)],
            'paid_to' => ['nullable', 'string', 'max:120'],
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
        return [
            'spent_on.before_or_equal' => 'The date cannot be in the future.',
            'amount.min' => 'Enter an amount of at least ₹1.',
            'paid_by.exists' => 'Choose the partner who paid.',
        ];
    }
}
