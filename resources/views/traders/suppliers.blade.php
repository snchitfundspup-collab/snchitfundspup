@extends('layouts.app')

@section('title', 'Suppliers | SN Traders')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="suppliers">Suppliers</h1>
            <p class="groups-subtitle" data-i18n="suppliers_subtitle">Mills and wholesalers you buy rice from.</p>
        </div>
    </div>


    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="add_supplier">Add a supplier</h2>
        </div>

        <form method="POST" action="{{ route('traders.suppliers.store') }}" class="master-add">
            @csrf
            <label class="group-field">
                <span class="field-label" data-i18n="supplier_name">Name</span>
                <input type="text" name="name" class="input" maxlength="120" required placeholder="e.g. Sri Murugan Rice Mill" value="{{ old('name') }}">
            </label>
            <label class="group-field">
                <span class="field-label" data-i18n="phone">Phone</span>
                <input type="text" name="phone" class="input" maxlength="20" value="{{ old('phone') }}">
            </label>
            <label class="group-field">
                <span class="field-label" data-i18n="place_word">Place</span>
                <input type="text" name="place" class="input" maxlength="120" value="{{ old('place') }}">
            </label>
            <button type="submit" class="save-button">
                <x-icon name="plus" />
                <span data-i18n="add_word">Add</span>
            </button>
        </form>

    </section>


    <section class="group-panel glass payment-step">

        <form method="GET" action="{{ route('traders.suppliers.index') }}" class="member-search" role="search">
            <span class="member-search-icon"><x-icon name="search" /></span>
            <input type="search" name="q" class="member-search-input" value="{{ $search }}" placeholder="Name, phone or place" autocomplete="off">
        </form>

        @if ($suppliers->isEmpty())

            <p class="members-empty" data-i18n="no_suppliers">No suppliers yet</p>

        @else

            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="supplier_word">Supplier</th>
                            <th class="ledger-col-total" data-i18n="bought_word">Bought</th>
                            <th data-i18n="edit">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td>
                                    <a href="{{ route('traders.purchases.index', ['supplier' => $supplier->id, 'range' => 'all']) }}" class="dues-member-link">
                                        <strong>{{ $supplier->name }}</strong>
                                    </a>
                                    <small class="dues-part-paid">{{ collect([$supplier->place, $supplier->phone])->filter()->implode(' · ') ?: '—' }}</small>
                                </td>
                                <td class="ledger-col-total">
                                    <x-rupees :amount="$supplier->purchased_amount ?? 0" />
                                    <small class="dues-part-paid">{{ $supplier->purchases_count }} <span data-i18n="purchases_word">purchases</span></small>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('traders.suppliers.update', $supplier) }}" class="master-row-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" class="input" maxlength="120" required value="{{ $supplier->name }}" aria-label="Name">
                                        <input type="text" name="phone" class="input" maxlength="20" value="{{ $supplier->phone }}" aria-label="Phone" placeholder="Phone">
                                        <input type="text" name="place" class="input" maxlength="120" value="{{ $supplier->place }}" aria-label="Place" placeholder="Place">
                                        <div class="master-actions">
                                            <button type="submit" class="group-action" title="Save"><x-icon name="save" /></button>
                                            @if ($supplier->phone)
                                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $supplier->phone) }}" class="member-call-button collect-call-button" aria-label="Call {{ $supplier->name }}"><x-icon name="phone" /><span>Call</span></a>
                                            @endif
                                        </div>
                                    </form>
                                    @if ($supplier->purchases_count === 0)
                                        <form method="POST" action="{{ route('traders.suppliers.destroy', $supplier) }}" onsubmit="return confirm('Delete {{ $supplier->name }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="balance-delete" title="Delete" aria-label="Delete {{ $supplier->name }}"><x-icon name="trash" /></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @endif

    </section>

</div>

@endsection
