<?php

namespace App\Http\Requests\Finance;

use App\Models\FinanceLoan;
use Illuminate\Validation\Validator;

/**
 * Edit a saved loan (Sathiya only): the same fields and checks as a new
 * loan, and the new total can never be less than what is already
 * collected.
 */
class UpdateLoanRequest extends StoreLoanRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit-loans') === true;
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var FinanceLoan $loan */
                $loan = $this->route('loan');
                $total = (int) $this->input('installment_amount') * (int) $this->input('installments');
                $collected = $loan->collected();

                if ($total < $collected) {
                    $validator->errors()->add(
                        'installment_amount',
                        'The new total of ₹'.number_format($total).' is less than the ₹'.number_format($collected).' already collected on this loan.'
                    );
                }
            },
        ];
    }
}
