{{-- Profit & Loss for the print page and PDF. --}}

<table class="info">
    <tr>
        <td><span class="label">{{ __('Period') }}</span><strong>@include('traders.reports.partials.period-text')</strong></td>
        <td><span class="label">{{ __('Income') }}</span><strong><x-rupees :amount="$summary['income']" /></strong></td>
        <td><span class="label">{{ __('Net profit') }}</span><strong class="{{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></strong></td>
    </tr>
</table>

<h3 class="list-title">{{ __('Profit statement') }}</h3>

<table class="grid">
    <tbody>
        <tr>
            <td>{{ __('Processing fees') }} ({{ $summary['loans'] }} {{ __('loans given') }})</td>
            <td class="amount"><x-rupees :amount="$summary['fees']" /></td>
        </tr>
        <tr>
            <td>{{ __('Interest collected') }}</td>
            <td class="amount"><x-rupees :amount="$summary['interest']" /></td>
        </tr>
        <tr>
            <th>{{ __('Income') }}</th>
            <th class="amount"><x-rupees :amount="$summary['income']" /></th>
        </tr>
        <tr>
            <td>{{ __('Less: expenses') }} ({{ $summary['expense_count'] }} {{ __('entries') }})</td>
            <td class="amount">− <x-rupees :amount="$summary['expenses']" /></td>
        </tr>
        <tr>
            <th>{{ __('Net profit') }}</th>
            <th class="amount {{ $summary['net'] < 0 ? 'pending' : '' }}"><x-rupees :amount="$summary['net']" /></th>
        </tr>
        <tr>
            <td class="muted">{{ __('GST collected (to pay the government)') }}</td>
            <td class="amount muted"><x-rupees :amount="$summary['gst']" /></td>
        </tr>
        <tr>
            <td class="muted">{{ __('Lent (in hand)') }} ({{ __('for reference') }})</td>
            <td class="amount muted"><x-rupees :amount="$summary['lent']" /></td>
        </tr>
        <tr>
            <td class="muted">{{ __('Collected in this period') }} ({{ __('for reference') }})</td>
            <td class="amount muted"><x-rupees :amount="$summary['collected']" /></td>
        </tr>
        <tr>
            <td class="muted">{{ __('Money with customers') }} ({{ __('for reference') }})</td>
            <td class="amount muted"><x-rupees :amount="$summary['outstanding']" /></td>
        </tr>
    </tbody>
</table>
