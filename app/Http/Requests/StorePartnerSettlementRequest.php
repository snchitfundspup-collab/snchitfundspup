<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Record money one partner handed another to settle the shared spending.
 */
class StorePartnerSettlementRequest extends FormRequest
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
        $partner = Rule::exists('users', 'id')->where('is_partner', true);

        return [
            'settled_on' => ['required', 'date', 'before_or_equal:'.today(config('app.business_timezone'))->toDateString()],
            'from_user_id' => ['required', 'integer', $partner],
            'to_user_id' => ['required', 'integer', $partner, 'different:from_user_id'],
            'amount' => ['required', 'integer', 'min:1', 'max:99999999'],
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
            'settled_on.before_or_equal' => 'The date cannot be in the future.',
            'to_user_id.different' => 'Choose two different partners.',
            'amount.min' => 'Enter an amount of at least ₹1.',
        ];
    }
}
