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
            <p class="groups-subtitle" data-i18n="varieties_subtitle">The rice you buy and sell, with the bag size and the purchase and selling price per bag. A new purchase bill updates the purchase price.</p>
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
            <label class="group-field">
                <span class="field-label" data-i18n="purchase_price_bag">Purchase price / bag (₹)</span>
                <input type="number" name="purchase_price" class="input" min="0" step="0.01" inputmode="decimal" placeholder="0.00" value="{{ old('purchase_price') }}">
            </label>
            <label class="group-field">
                <span class="field-label" data-i18n="selling_price_bag">Selling price / bag (₹)</span>
                <input type="number" name="selling_price" class="input" min="0" step="0.01" inputmode="decimal" placeholder="0.00" value="{{ old('selling_price') }}">
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

            <div class="variety-list">
                @foreach ($varieties as $variety)
                    @php $profitPerBag = $variety->profitPerBag(); @endphp

                    <div @class(['variety-card', 'is-inactive' => ! $variety->is_active])>

                        <div class="variety-card-head">
                            <span class="variety-card-icon icon-3d icon-3d-green"><x-icon name="package" /></span>
                            <div class="variety-card-title">
                                <strong>{{ $variety->name }}</strong>
                                <small>
                                    {{ $kg($variety->bag_kg) }} <span data-i18n="kg_bag">kg bag</span>
                                    @unless ($variety->is_active) · <span data-i18n="inactive_word">inactive</span>@endunless
                                </small>
                            </div>
                            <div class="variety-card-facts">
                                <span>
                                    <small data-i18n="profit_per_bag">Profit / bag</small>
                                    <strong @class(['ledger-total-paid' => $profitPerBag > 0, 'ledger-total-due' => $profitPerBag !== null && $profitPerBag < 0])>
                                        @if ($profitPerBag !== null)<x-rupees :amount="$profitPerBag" />@else — @endif
                                    </strong>
                                </span>
                                <span>
                                    <small data-i18n="in_stock_bags">In stock (bags)</small>
                                    <strong>{{ (int) $variety->purchased_bags - (int) $variety->sold_bags }}</strong>
                                </span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('traders.varieties.update', $variety) }}" class="variety-edit-form">
                            @csrf
                            @method('PUT')
                            <label class="master-field master-field-name">
                                <span class="bill-line-label" data-i18n="variety_name">Name</span>
                                <input type="text" name="name" class="input" maxlength="80" required value="{{ $variety->name }}">
                            </label>
                            <label class="master-field">
                                <span class="bill-line-label" data-i18n="bag_kg">Kg / bag</span>
                                <input type="number" name="bag_kg" class="input" min="1" max="200" step="0.01" required value="{{ (float) $variety->bag_kg }}">
                            </label>
                            <label class="master-field">
                                <span class="bill-line-label" data-i18n="purchase_price">Purchase price</span>
                                <input type="number" name="purchase_price" class="input" min="0" step="0.01" value="{{ $variety->purchase_price }}" placeholder="₹">
                            </label>
                            <label class="master-field">
                                <span class="bill-line-label" data-i18n="selling_price">Selling price</span>
                                <input type="number" name="selling_price" class="input" min="0" step="0.01" value="{{ $variety->selling_price }}" placeholder="₹">
                            </label>
                            <div class="variety-edit-actions">
                                <label class="method-option">
                                    <input type="checkbox" name="is_active" value="1" @checked($variety->is_active)>
                                    <span data-i18n="active_word">Active</span>
                                </label>
                                <button type="submit" class="group-action">
                                    <x-icon name="save" />
                                    <span data-i18n="save_word">Save</span>
                                </button>
                                <button
                                    type="submit"
                                    class="balance-delete"
                                    form="delete-variety-{{ $variety->id }}"
                                    title="Delete"
                                    aria-label="Delete {{ $variety->name }}"
                                ><x-icon name="trash" /></button>
                            </div>
                        </form>

                        <form
                            id="delete-variety-{{ $variety->id }}"
                            method="POST"
                            action="{{ route('traders.varieties.destroy', $variety) }}"
                            onsubmit="return confirm('Delete {{ $variety->name }}?')"
                            hidden
                        >
                            @csrf
                            @method('DELETE')
                        </form>

                    </div>
                @endforeach
            </div>

        @endif

    </section>

</div>

@endsection
