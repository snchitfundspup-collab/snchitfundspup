<div class="report-columns">

    <section class="group-panel glass">
        <div class="group-panel-header">
            <h2 data-i18n="profit_statement">Profit statement</h2>
        </div>
        <table class="ledger-table statement-table profit-statement">
            <tbody>
                <tr>
                    <th>
                        <span data-i18n="processing_fees">Processing fees</span>
                        <small class="dues-part-paid">{{ $summary['loans'] }} <span data-i18n="loans_given">loans given</span></small>
                    </th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['fees']" /></td>
                </tr>
                <tr>
                    <th>
                        <span data-i18n="interest_collected">Interest collected</span>
                        <small class="dues-part-paid" data-i18n="interest_collected_note">The interest part of the money collected</small>
                    </th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['interest']" /></td>
                </tr>
                <tr class="profit-subtotal">
                    <th data-i18n="income_word">Income</th>
                    <td class="ledger-col-total"><strong><x-rupees :amount="$summary['income']" /></strong></td>
                </tr>
                <tr>
                    <th>
                        <a href="{{ route('finance.expenses.index', ['from' => $filters['from'], 'to' => $filters['to']]) }}" class="dues-member-link">
                            <span data-i18n="less_expenses">Less: expenses</span>
                        </a>
                        <small class="dues-part-paid">{{ $summary['expense_count'] }} <span data-i18n="entries_word">entries</span></small>
                    </th>
                    <td class="ledger-col-total">− <x-rupees :amount="$summary['expenses']" /></td>
                </tr>
                <tr class="profit-total">
                    <th data-i18n="net_profit">Net profit</th>
                    <td @class(['ledger-col-total', 'ledger-total-paid' => $summary['net'] >= 0, 'ledger-total-due' => $summary['net'] < 0])><strong><x-rupees :amount="$summary['net']" /></strong></td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="group-panel glass">
        <div class="group-panel-header">
            <h2 data-i18n="money_view">For reference</h2>
        </div>
        <table class="ledger-table statement-table profit-statement">
            <tbody>
                <tr>
                    <th>
                        <span data-i18n="gst_collected">GST collected (to pay the government)</span>
                    </th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['gst']" /></td>
                </tr>
                <tr>
                    <th data-i18n="lent_in_hand">Lent (in hand)</th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['lent']" /></td>
                </tr>
                <tr>
                    <th data-i18n="collected_in_period">Collected in this period</th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['collected']" /></td>
                </tr>
                <tr>
                    <th data-i18n="money_outstanding">Money with customers</th>
                    <td class="ledger-col-total"><x-rupees :amount="$summary['outstanding']" /></td>
                </tr>
                <tr>
                    <th data-i18n="overdue_word">Overdue</th>
                    <td class="ledger-col-total ledger-total-due"><x-rupees :amount="$summary['overdue']" /></td>
                </tr>
            </tbody>
        </table>
    </section>

</div>
