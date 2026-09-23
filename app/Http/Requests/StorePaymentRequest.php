<?php

namespace App\Http\Requests;

use App\Models\ChitGroupMember;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Collect Payment form: which member seat, how much (full months or a
 * partial amount — the screen fills the amount for "full"), how and when.
 */
class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Accept "2,000" or "₹2,000", and the date-time box's "2026-03-20T14:30"
     * (office local time).
     */
    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $paidAt = $this->input('paid_at');

        $this->merge([
            'amount' => is_string($amount) ? str_replace([',', ' ', '₹'], '', $amount) : $amount,
            'paid_at' => is_string($paidAt) ? str_replace('T', ' ', $paidAt) : $paidAt,
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
            'chit_group_member_id' => ['required', 'integer', Rule::exists('chit_group_members', 'id')],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date', 'before_or_equal:'.now(config('app.business_timezone'))->format('Y-m-d H:i:59')],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Payments are only taken for groups that have started.
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

                if (! $this->member()->chitGroup->isRunning()) {
                    $validator->errors()->add('chit_group_member_id', 'Payments can only be recorded for groups that have started.');
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
            'amount.min' => 'Enter an amount of at least ₹1.',
            'paid_at.before_or_equal' => 'The payment date and time cannot be in the future.',
        ];
    }

    public function member(): ChitGroupMember
    {
        return ChitGroupMember::with('chitGroup', 'allocations')->findOrFail($this->input('chit_group_member_id'));
    }
}
