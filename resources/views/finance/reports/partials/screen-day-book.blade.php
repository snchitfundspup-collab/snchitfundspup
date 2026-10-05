@if ($entries->isEmpty())
    <div class="groups-empty glass">
        <span class="groups-empty-icon icon-3d icon-3d-blue"><x-icon name="calendar" /></span>
        <strong data-i18n="no_entries_period">Nothing lent or collected in this period</strong>
    </div>
@else
    <section class="group-panel glass payment-step">
        <div class="group-panel-header">
            <h2 data-i18n="day_totals">Day totals</h2>
        </div>
        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="date">Date</th>
                        <th class="ledger-col-total" data-i18n="lent_in_hand">Lent (in hand)</th>
                        <th class="ledger-col-total" data-i18n="fee_and_gst">Fee + GST</th>
                        <th class="ledger-col-total" data-i18n="collected_word">Collected</th>
                        <th class="ledger-col-total" data-i18n="net_cash">Net cash</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($days as $day)
                        <tr>
                            <td class="nowrap" data-label="Date">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('D, d M Y') }}</td>
                            <td class="ledger-col-total" data-label="Lent">@if ($day['out'] > 0)<x-rupees :amount="$day['out']" /> <small>({{ $day['loans'] }})</small>@else — @endif</td>
                            <td class="ledger-col-total" data-label="Cut">@if ($day['cut'] > 0)<x-rupees :amount="$day['cut']" />@else — @endif</td>
                            <td class="ledger-col-total ledger-total-paid" data-label="Collected">@if ($day['in'] > 0)<x-rupees :amount="$day['in']" /> <small>({{ $day['collections'] }})</small>@else — @endif</td>
                            <td @class(['ledger-col-total', 'ledger-total-due' => $day['in'] - $day['out'] < 0]) data-label="Net"><strong><x-rupees :amount="$day['in'] - $day['out']" /></strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th data-i18n="total">Total</th>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['out']" /></td>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['cut']" /></td>
                        <td class="ledger-col-total"><x-rupees :amount="$summary['in']" /></td>
                        <td class="ledger-col-total"><strong><x-rupees :amount="$summary['net']" /></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="group-panel glass payment-step">
        <div class="group-panel-header">
            <h2 data-i18n="all_entries">All entries</h2>
        </div>
        <div class="ledger-table-wrapper">
            <table class="ledger-table statement-table portal-table">
                <thead>
                    <tr>
                        <th data-i18n="date">Date</th>
                        <th data-i18n="entry_word">Entry</th>
                        <th data-i18n="customer_word">Customer</th>
                        <th data-i18n="details">Details</th>
                        <th class="ledger-col-total" data-i18n="money_out">Out</th>
                        <th class="ledger-col-total" data-i18n="money_in">In</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td class="nowrap" data-label="Date">{{ \Illuminate\Support\Carbon::parse($entry['date'])->format('d M Y') }}</td>
                            <td class="nowrap" data-label="Entry">{{ $entry['type'] === 'loan' ? 'Loan' : 'Receipt' }} {{ $entry['number'] }}</td>
                            <td data-label="Customer"><x-customer-name :customer="$entry['customer']" /></td>
                            <td data-label="Details">{{ $entry['details'] }}</td>
                            <td class="ledger-col-total" data-label="Out">@if ($entry['out'] > 0)<x-rupees :amount="$entry['out']" />@endif</td>
                            <td class="ledger-col-total ledger-total-paid" data-label="In">@if ($entry['in'] > 0)<x-rupees :amount="$entry['in']" />@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
