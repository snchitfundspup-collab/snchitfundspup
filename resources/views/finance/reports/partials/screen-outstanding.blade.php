@if ($rows->isEmpty())
    <div class="groups-empty glass">
        <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="wallet" /></span>
        <strong data-i18n="no_running_loans">No running loans.</strong>
    </div>
@else
    <section class="group-panel glass payment-step">
        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="customer_word">Customer</th>
                        <th data-i18n="loan_no">Loan No.</th>
                        <th data-i18n="loan_date">Loan date</th>
                        <th data-i18n="installment_word">Instalment</th>
                        <th class="ledger-col-total" data-i18n="total_to_repay">Total to repay</th>
                        <th class="ledger-col-total" data-i18n="collected_word">Collected</th>
                        <th class="ledger-col-total" data-i18n="balance_word">Balance</th>
                        <th class="ledger-col-total" data-i18n="overdue_word">Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @php [$loan, $standing] = [$row['loan'], $row['standing']]; @endphp
                        <tr>
                            <td data-label="Customer">
                                <a href="{{ route('finance.loans.show', $loan) }}" class="dues-member-link"><x-customer-name :customer="$loan->customer" /></a>
                                <small class="dues-part-paid">{{ $loan->customer->customer_code }} · {{ $loan->customer->phone ?: '—' }}</small>
                            </td>
                            <td class="nowrap" data-label="Loan">{{ $loan->loan_number }}</td>
                            <td class="nowrap" data-label="Loan date">{{ $loan->loaned_on->format('d M Y') }}</td>
                            <td class="nowrap" data-label="Instalment">{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></td>
                            <td class="ledger-col-total" data-label="Total to repay"><x-rupees :amount="$loan->loan_amount" /></td>
                            <td class="ledger-col-total ledger-total-paid" data-label="Collected"><x-rupees :amount="$loan->collected()" /></td>
                            <td class="ledger-col-total" data-label="Balance"><strong><x-rupees :amount="$loan->balance()" /></strong></td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $standing['overdue'] > 0]) data-label="Overdue">@if ($standing['overdue'] > 0)<x-rupees :amount="$standing['overdue']" />@else — @endif</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" data-i18n="total">Total</th>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['repayable']" /></td>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['collected']" /></td>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$summary['balance']" /></strong></td>
                        <td class="ledger-col-total ledger-total-due"><x-rupees :amount="$summary['overdue']" /></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
@endif
