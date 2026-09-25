@extends('layouts.portal')

@section('title', 'Rice & Order')

@push('scripts')
    @vite('resources/js/portal.js')
@endpush

@section('content')

<div class="groups-list-page portal-page">

    <div class="groups-list-header">
        <div>
            @include('portal.partials.back')
            <h1 class="groups-title" data-i18n="rice_and_order">Rice &amp; Order</h1>
            <p class="groups-subtitle" data-i18n="rice_subtitle">Choose how many bags you want and send the order. The office will call you to confirm and deliver.</p>
        </div>
    </div>

    @if ($varieties->isEmpty())

        <div class="groups-empty glass">
            <span class="groups-empty-icon icon-3d icon-3d-green"><x-icon name="package" /></span>
            <strong data-i18n="no_rice_listed">No rice is listed right now.</strong>
        </div>

    @else

        <form method="POST" action="{{ route('portal.orders.store') }}" id="riceOrderForm">

            @csrf

            @if ($errors->any())
                <p class="portal-error glass" role="alert">{{ $errors->first() }}</p>
            @endif

            <div class="portal-grid">
                @foreach ($varieties as $card)
                    @include('portal.partials.rice-card', ['orderable' => true])
                @endforeach
            </div>

            <section class="group-panel glass payment-step portal-order-box">

                <label class="group-field">
                    <span class="field-label" data-i18n="order_note">Note for the office (optional)</span>
                    <input type="text" name="notes" class="input" maxlength="500" value="{{ old('notes') }}" placeholder="e.g. deliver on Saturday morning">
                </label>

                <div class="portal-order-total">
                    <span>
                        <small data-i18n="estimated_total">Estimated total</small>
                        <strong id="riceOrderTotal">₹0</strong>
                        <small id="riceOrderBags">0 bags</small>
                    </span>
                    <button type="submit" class="add-group-button" id="riceOrderSubmit" disabled>
                        <x-icon name="check" />
                        <span data-i18n="send_order">Send order</span>
                    </button>
                </div>

                <p class="portal-card-note" data-i18n="order_price_note">The final bill is made by the office at the day's price.</p>

            </section>

        </form>

    @endif

</div>

@endsection
