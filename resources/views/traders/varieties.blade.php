@extends('layouts.app')

@section('title', 'Rice Varieties | SN Traders')

@push('styles')
    @vite([
        'resources/css/create.css',
        'resources/css/groups.css',
        'resources/css/payments.css',
        'resources/css/traders.css'
    ])
@endpush

@php
    $kg = fn ($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
@endphp

@section('content')

<div class="groups-list-page payments-page traders-page">

    @include('traders.partials.flash')


    <div class="groups-list-header">
        <div>
            <h1 class="groups-title" data-i18n="rice_varieties">Rice Varieties</h1>
            <p class="groups-subtitle" data-i18n="varieties_subtitle">The rice you buy and sell, with the usual bag weight (it can be changed on each bill).</p>
        </div>
    </div>


    <section class="group-panel glass">

        <div class="group-panel-header">
            <h2 data-i18n="add_variety">Add a variety</h2>
        </div>

        <form method="POST" action="{{ route('traders.varieties.store') }}" class="master-add">
            @csrf
            <label class="group-field">
                <span class="field-label" data-i18n="variety_name">Name</span>
                <input type="text" name="name" class="input" maxlength="80" required placeholder="e.g. Ponni" value="{{ old('name') }}">
            </label>
            <label class="group-field">
                <span class="field-label" data-i18n="bag_kg">Kg / bag</span>
                <input type="number" name="bag_kg" class="input" min="1" max="200" step="0.01" required value="{{ old('bag_kg', 26) }}">
            </label>
            <button type="submit" class="save-button">
                <x-icon name="plus" />
                <span data-i18n="add_word">Add</span>
            </button>
        </form>

    </section>


    <section class="group-panel glass payment-step">

        @if ($varieties->isEmpty())

            <p class="members-empty" data-i18n="no_varieties">No rice varieties yet</p>

        @else

            <div class="ledger-table-wrapper">
                <table class="ledger-table statement-table">
                    <thead>
                        <tr>
                            <th data-i18n="variety_name">Name</th>
                            <th class="ledger-col-total" data-i18n="in_stock_bags">In stock (bags)</th>
                            <th data-i18n="edit">Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($varieties as $variety)
                            <tr>
                                <td>
                                    <strong>{{ $variety->name }}</strong>
                                    <small class="dues-part-paid">{{ $kg($variety->bag_kg) }} <span data-i18n="kg_bag">kg bag</span>@unless ($variety->is_active) · <span data-i18n="inactive_word">inactive</span>@endunless</small>
                                </td>
                                <td class="ledger-col-total">{{ (int) $variety->purchased_bags - (int) $variety->sold_bags }}</td>
                                <td>
                                    <form method="POST" action="{{ route('traders.varieties.update', $variety) }}" class="master-row-form">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="name" class="input" maxlength="80" required value="{{ $variety->name }}" aria-label="Name">
                                        <input type="number" name="bag_kg" class="input" min="1" max="200" step="0.01" required value="{{ (float) $variety->bag_kg }}" aria-label="Kg per bag">
                                        <label class="method-option">
                                            <input type="checkbox" name="is_active" value="1" @checked($variety->is_active)>
                                            <span data-i18n="active_word">Active</span>
                                        </label>
                                        <div class="master-actions">
                                            <button type="submit" class="group-action" title="Save"><x-icon name="save" /></button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('traders.varieties.destroy', $variety) }}" onsubmit="return confirm('Delete {{ $variety->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="balance-delete" title="Delete" aria-label="Delete {{ $variety->name }}"><x-icon name="trash" /></button>
                                    </form>
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
