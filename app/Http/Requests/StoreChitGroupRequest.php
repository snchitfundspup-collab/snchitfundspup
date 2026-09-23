<?php

namespace App\Http\Requests;

use App\Models\ChitGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validates the Add Group and Edit Group forms, including the
 * month-by-month withdrawal schedule (payouts[1..months]).
 */
class StoreChitGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Strip the thousands separators people type ("2,00,000") and give an
     * empty commission a value of 0.
     */
    protected function prepareForValidation(): void
    {
        $toNumber = fn (mixed $value): mixed => is_string($value) ? str_replace([',', ' ', '₹'], '', $value) : $value;

        $this->merge([
            'amount' => $toNumber($this->input('amount')),
            'installment_amount' => $toNumber($this->input('installment_amount')),
            'commission_amount' => $toNumber($this->input('commission_amount')) ?: 0,
            'payouts' => array_map($toNumber, (array) $this->input('payouts', [])),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var ChitGroup|null $group */
        $group = $this->route('group');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('chit_groups', 'name')->ignore($group?->id)],
            'type' => ['required', Rule::in(ChitGroup::TYPES)],
            'amount' => ['required', 'integer', 'min:1000', 'max:1000000000'],
            'months' => ['required', 'integer', 'min:1', 'max:120'],
            'installment_amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'member_count' => ['required', 'integer', 'min:1', 'max:200'],
            'commission_amount' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'payouts' => ['required', 'array'],
            'payouts.*' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * The schedule must have exactly one amount per month. Amounts may be
     * above the chit amount (later months can pay out more).
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

                $months = (int) $this->input('months');
                $payouts = $this->input('payouts', []);

                foreach (range(1, $months) as $monthNumber) {
                    if (! array_key_exists($monthNumber, $payouts) && ! array_key_exists((string) $monthNumber, $payouts)) {
                        $validator->errors()->add('payouts', "Enter the withdrawal amount for every month (month {$monthNumber} is missing).");

                        return;
                    }
                }

                if (count($payouts) !== $months) {
                    $validator->errors()->add('payouts', 'The withdrawal schedule must have exactly one amount per month.');
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
            'name.unique' => 'A group with this name already exists.',
            'installment_amount.required' => 'Enter the monthly installment.',
            'payouts.*.required' => 'Enter the withdrawal amount for every month.',
            'payouts.*.integer' => 'Withdrawal amounts must be whole rupees.',
        ];
    }

    /**
     * The validated schedule as [month_number => amount], ordered by month.
     *
     * @return array<int, int>
     */
    public function payoutSchedule(): array
    {
        $schedule = [];

        foreach ($this->validated('payouts') as $monthNumber => $withdrawalAmount) {
            $schedule[(int) $monthNumber] = (int) $withdrawalAmount;
        }

        ksort($schedule);

        return $schedule;
    }
}
