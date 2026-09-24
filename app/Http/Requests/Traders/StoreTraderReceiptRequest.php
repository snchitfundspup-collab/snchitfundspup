<?php

namespace App\Http\Requests\Traders;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Money received from a customer against their SN Traders credit.
 */
class StoreTraderReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Accept "1,250" / "₹1,250" and the date-time box's "2026-09-24T14:30".
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $receivedAt = $this->input('received_at');

        $this->merge([
            'amount' => is_string($amount) ? str_replace([',', ' ', '₹'], '', $amount) : $amount,
            'received_at' => is_string($receivedAt) ? str_replace('T', ' ', $receivedAt) : $receivedAt,
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
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'amount' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'received_at' => ['required', 'date', 'before_or_equal:'.now(config('app.business_timezone'))->format('Y-m-d H:i:59')],
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
            'customer_id.required' => 'Choose the customer.',
            'amount.min' => 'Enter an amount of at least ₹1.',
            'received_at.before_or_equal' => 'The date and time cannot be in the future.',
        ];
    }
}
