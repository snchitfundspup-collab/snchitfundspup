@if ($rows->isEmpty())
    <div class="groups-empty glass">
        <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="check" /></span>
        <strong data-i18n="nothing_overdue">Nothing is overdue.</strong>
    </div>
@else
    <section class="group-panel glass payment-step">
        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="customer_word">Customer</th>
                        <th data-i18n="loan_no">Loan No.</th>
                        <th data-i18n="installment_word">Instalment</th>
                        <th data-i18n="overdue_since">Overdue since</th>
                        <th class="ledger-col-total" data-i18n="days_late">days late</th>
                        <th data-i18n="last_paid">Last paid</th>
                        <th class="ledger-col-total" data-i18n="overdue_word">Overdue</th>
                        <th class="ledger-col-total" data-i18n="balance_word">Balance</th>
                        <th></th>
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
                            <td class="nowrap" data-label="Instalment">{{ $loan->frequencyLabel() }} <x-rupees :amount="$loan->installment_amount" /></td>
                            <td class="nowrap" data-label="Overdue since">{{ $standing['overdue_since']->format('d M Y') }}</td>
                            <td class="ledger-col-total ledger-total-due" data-label="Days late">{{ $standing['days_overdue'] }}</td>
                            <td class="nowrap" data-label="Last paid">{{ $row['last_paid'] ? \Illuminate\Support\Carbon::parse($row['last_paid'])->format('d M Y') : '—' }}</td>
                            <td class="ledger-col-total ledger-total-due" data-label="Overdue"><strong><x-rupees :amount="$standing['overdue']" /></strong></td>
                            <td class="ledger-col-total" data-label="Balance"><x-rupees :amount="$loan->balance()" /></td>
                            <td>
                                @if ($loan->customer->phone)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $loan->customer->phone) }}" class="member-call-button" aria-label="Call {{ $loan->customer->name }}">
                                        <x-icon name="phone" />
                                        <span data-i18n="call">Call</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
