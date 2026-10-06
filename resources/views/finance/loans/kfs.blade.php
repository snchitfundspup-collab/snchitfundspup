{{-- Key Fact Statement (page 1) and Collection Passbook (page 2) for a
     loan, on A4. Opens as a print page ($layout = layouts.print) or a PDF.
     $loan, $customer, $schedule, $kfs (config app.finance_kfs) --}}
@extends($layout ?? 'pdf.layout')

@section('title', 'Key Fact Statement '.$loan->loan_number)

@section('page_margin', '8mm')

@section('font_size', '8.5px')

@section('doc_title', 'Key Fact Statement')

@section('doc_subtitle', $loan->loan_number)

@section('styles')
    .kfs-meta td { padding: 2px 10px 6px 0; vertical-align: top; }
    .kfs-meta .label, .kfs-part .label { display: block; font-size: 7px; font-weight: bold; letter-spacing: 0.4px; text-transform: uppercase; color: #4b5563; }
    .kfs-part { margin: 8px 0 4px; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
    .kfs-table { width: 100%; border-collapse: collapse; }
    .kfs-table th, .kfs-table td { border: 1px solid #9ca3af; padding: 4px 6px; text-align: left; vertical-align: top; }
    .kfs-table th { width: 4%; background: #f1f3f6; text-align: center; }
    .kfs-table td.amount { text-align: right; white-space: nowrap; font-weight: bold; width: 26%; }
    .kfs-table tr.total td { background: #f1f3f6; }
    .kfs-notes { margin: 4px 0 0 14px; padding: 0; }
    .kfs-notes li { margin: 2px 0; line-height: 1.45; }
    .kfs-sign { margin-top: 26px; width: 100%; }
    .kfs-sign td { width: 50%; padding-top: 18px; border-top: 1px solid #111827; font-size: 8px; vertical-align: bottom; }
    .passbook-page { page-break-before: always; }
    .passbook-head td { padding: 0 10px 4px 0; font-size: 8px; }
    .passbook { width: 100%; border-collapse: collapse; font-size: 7px; }
    .passbook th, .passbook td { border: 1px solid #9ca3af; padding: 1.6px 3px; }
    .passbook th { background: #f1f3f6; font-size: 6.5px; }
    .passbook td.num { text-align: right; white-space: nowrap; }
    .passbook td.sign { width: 8%; }
    .passbook .gap { width: 1.5%; border-top: 0; border-bottom: 0; background: #ffffff; }
    .passbook .is-paid { color: #15803d; }
    .passbook .is-overdue { color: #b91c1c; }
    .passbook-weekly { font-size: 9px; }
    .passbook-weekly th, .passbook-weekly td { padding: 5px 6px; }
@endsection

@section('content')

    @php
        $weekly = $loan->frequency === \App\Models\FinanceLoan::WEEKLY;
        $unit = $weekly ? __('Week') : __('Day');
        $grievancePhone = $kfs['grievance_phone'] ?: config('app.office_phone');
    @endphp

    {{-- ===================== PART A: KEY FACT STATEMENT ===================== --}}

    <table class="kfs-meta">
        <tr>
            <td>
                <span class="label">{{ __('Lender') }}</span>
                <strong>{{ $kfs['legal_name'] }}</strong>
                @if (filled($kfs['registration']))<br>{{ $kfs['registration'] }}@endif
            </td>
            <td>
                <span class="label">{{ __('Branch') }}</span>
                <strong>{{ $kfs['branch'] ?: '—' }}</strong>
                @if (filled($kfs['address']))<br>{{ $kfs['address'] }}@endif
            </td>
            <td>
                <span class="label">{{ __('Date') }}</span>
                <strong>{{ $loan->loaned_on->format('d M Y') }}</strong>
            </td>
            <td>
                <span class="label">{{ __('Loan No.') }}</span>
                <strong>{{ $loan->loan_number }}</strong>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">{{ __('Borrower') }}</span>
                <strong>{{ $customer->name }}@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif</strong>
                · {{ $customer->customer_code }} · {{ $customer->phone ?: '—' }}
                @if (filled($customer->address))<br>{{ $customer->address }}@endif
            </td>
            <td colspan="2">
                <span class="label">{{ __('Loan type') }}</span>
                <strong>{{ $weekly ? __('Weekly loan') : __('Daily loan') }}</strong> · {{ __('No collateral') }}
            </td>
        </tr>
    </table>

    <div class="kfs-part">{{ __('Part A — Key Fact Statement') }}</div>

    <table class="kfs-table">
        <tr><th>1</th><td>{{ __('Sanctioned loan amount') }}</td><td class="amount"><x-rupees :amount="$loan->principal" /></td></tr>
        <tr><th>2</th><td>{{ __('Processing fee') }} ({{ \App\Models\FinanceLoan::rate($loan->processing_fee_rate) }} {{ __('of the loan') }})</td><td class="amount"><x-rupees :amount="$loan->processing_fee" /></td></tr>
        <tr><th>3</th><td>{{ __('GST on the processing fee') }} ({{ \App\Models\FinanceLoan::rate($loan->gst_rate) }})</td><td class="amount"><x-rupees :amount="$loan->gst" /></td></tr>
        <tr class="total"><th>4</th><td><strong>{{ __('Net amount disbursed to the borrower') }}</strong> (1 − 2 − 3)</td><td class="amount"><x-rupees :amount="$loan->amountGiven()" /></td></tr>
        <tr><th>5</th><td>{{ __('Total interest charge') }} ({{ \App\Models\FinanceLoan::rate($loan->interest_rate) }} {{ __('a year') }}, {{ __('flat on the loan amount') }}, {{ $loan->term_days }} {{ __('days') }})</td><td class="amount"><x-rupees :amount="$loan->interest" /></td></tr>
        <tr class="total"><th>6</th><td><strong>{{ __('Total amount to be repaid') }}</strong> (1 + 5)</td><td class="amount"><x-rupees :amount="$loan->loan_amount" /></td></tr>
        <tr><th>7</th><td>{{ __('Repayment frequency and number of instalments') }}</td><td class="amount">{{ $loan->frequencyLabel() }} · {{ $loan->installments }}</td></tr>
        <tr>
            <th>8</th>
            <td>{{ $weekly ? __('Fixed instalment per week') : __('Fixed instalment per day') }}@if ($loan->lastInstallment() !== $loan->installment_amount) ({{ __('last one') }} <x-rupees :amount="$loan->lastInstallment()" />)@endif</td>
            <td class="amount"><x-rupees :amount="$loan->installment_amount" /></td>
        </tr>
        <tr><th>9</th><td>{{ __('First and last instalment dates') }}</td><td class="amount">{{ $loan->first_due_on->format('d M Y') }} – {{ $loan->lastDueDate()->format('d M Y') }}</td></tr>
        <tr><th>10</th><td>{{ __('Penal charges for late payment') }}</td><td class="amount">{{ __('None') }}</td></tr>
    </table>

    <div class="kfs-part">{{ __('Disclosures') }}</div>

    <ul class="kfs-notes">
        <li>{{ __('No collateral or security is taken for this loan.') }}</li>
        <li>{{ __('Late or missed instalments are shown as overdue; no penalty or extra interest is charged. The loan can be repaid early at any time.') }}</li>
        @if (filled($kfs['credit_bureaus']))
            <li>{{ __('Your repayment record will be reported to credit information companies: :bureaus.', ['bureaus' => $kfs['credit_bureaus']]) }}</li>
        @endif
        <li>
            {{ __('Complaints and grievances') }}:
            @if (filled($kfs['grievance_officer'])){{ $kfs['grievance_officer'] }} · @endif
            {{ $grievancePhone }}
            @if (filled($kfs['grievance_email'])) · {{ $kfs['grievance_email'] }}@endif
        </li>
        <li>{{ __('Keep this statement and the passbook with you. Every payment gets a receipt.') }}</li>
    </ul>

    <table class="kfs-sign">
        <tr>
            <td>{{ __('I have read and understood this statement.') }}<br><br>{{ __('Signature of the borrower') }}</td>
            <td style="text-align: right;">
                <x-signature :user="$loan->recorder" :height="30" /><br>
                {{ __('Authorised signature') }} — {{ $kfs['legal_name'] }}
            </td>
        </tr>
    </table>

    {{-- ===================== PART B: COLLECTION PASSBOOK ===================== --}}

    <div class="passbook-page">

        <div class="kfs-part">{{ __('Part B — Collection Passbook') }}</div>

        <table class="passbook-head">
            <tr>
                <td><strong>{{ $customer->name }}</strong>@if (filled($customer->remarks)) ({{ $customer->remarks }})@endif · {{ $customer->customer_code }}</td>
                <td>{{ __('Loan No.') }} <strong>{{ $loan->loan_number }}</strong></td>
                <td>{{ __('Total to repay') }} <strong><x-rupees :amount="$loan->loan_amount" /></strong></td>
                <td>{{ $loan->frequencyLabel() }} · <x-rupees :amount="$loan->installment_amount" /></td>
            </tr>
        </table>

        @php
            /* one flat table (the PDF tool runs out of memory on tables inside tables):
               daily — instalment n on the left, n + half on the right; weekly — one list */
            $halves = $weekly ? 1 : 2;
            $rowsPerHalf = (int) ceil($schedule->count() / $halves);
        @endphp

        <table @class(['passbook', 'passbook-weekly' => $weekly])>
            <thead>
                <tr>
                    @for ($half = 0; $half < $halves; $half++)
                        @if ($half > 0)<th class="gap"></th>@endif
                        <th>{{ $unit }}</th>
                        <th>{{ __('Due date') }}</th>
                        <th>{{ __('Instalment') }} (₹)</th>
                        <th>{{ __('Collected') }} (₹)</th>
                        <th>{{ __('Paid on') }}</th>
                        <th>{{ __('Balance') }} (₹)</th>
                        <th>{{ __('Agent sign') }}</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @for ($line = 0; $line < $rowsPerHalf; $line++)
                    <tr>
                        @for ($half = 0; $half < $halves; $half++)
                            @php $row = $schedule[$line + $half * $rowsPerHalf] ?? null; @endphp
                            @if ($half > 0)<td class="gap"></td>@endif
                            @if ($row)
                                <td class="num {{ $row['state'] === 'paid' ? 'is-paid' : '' }}">{{ $row['number'] }}</td>
                                <td class="{{ $row['state'] === 'paid' ? 'is-paid' : '' }}">{{ $row['due_on']->format('d M y') }}</td>
                                <td class="num">{{ number_format($row['amount']) }}</td>
                                <td class="num {{ $row['state'] === 'paid' ? 'is-paid' : ($row['state'] === 'overdue' ? 'is-overdue' : '') }}">{{ $row['paid'] > 0 ? number_format($row['paid']) : '' }}</td>
                                <td>{{ collect($row['paid_on'])->map(fn ($date) => $date->format('d M'))->implode(', ') }}</td>
                                <td class="num">{{ number_format($row['balance_after']) }}</td>
                                <td class="sign"></td>
                            @else
                                <td colspan="7"></td>
                            @endif
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>

    </div>

@endsection
