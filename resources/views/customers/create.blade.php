@extends('layouts.app')

@section('title', 'Add Customer')

@push('styles')
    @vite('resources/css/create.css')
@endpush

@section('content')

<div class="page-wrapper">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="hero glass">

        <div class="hero-glow"></div>

        <div class="hero-glow-right"></div>


        <!-- CUSTOMER -->

        <div class="customer-illustration">

            <div class="customer-circle"></div>

            <div class="person-body"></div>

            <div class="person-neck"></div>

            <div class="person-head">

                <div class="person-hair"></div>

                <div class="eye eye-left"></div>

                <div class="eye eye-right"></div>

                <div class="smile"></div>

            </div>

            <div class="tablet"></div>

        </div>


        <!-- HERO CONTENT -->

        <div class="hero-content">

            <div class="badge">

                <span class="badge-icon icon-3d icon-3d-orange">
                    <x-icon name="user-plus" />
                </span>

                <span data-i18n="heroBadge">
                    Customer Registration
                </span>

            </div>


            <h1
                class="hero-title"
                data-i18n="heroTitle"
            >
                Add Customer
            </h1>


            <p
                class="hero-description"
                data-i18n="heroDescription"
            >
                Create a new customer profile and keep all
                their chit fund information organized.
            </p>

        </div>


        <!-- ID CARD -->

        <div class="id-preview">

            <div class="id-person"></div>

            <div class="id-lines">

                <span></span>
                <span></span>
                <span style="width:40px;"></span>

            </div>


            <div class="id-plus icon-3d icon-3d-orange">
                <x-icon name="plus" />
            </div>

        </div>

    </section>


    <!-- =====================================================
         FORM CARD
    ====================================================== -->

    <section class="form-card glass">

        <div class="form-inner">


            <!-- BACK -->

            <a
                href="{{ route('customers.index') }}"
                class="back-button"
            >

                <span class="back-icon icon-3d icon-3d-blue">
                    <x-icon name="arrow-left" />
                </span>

                <span data-i18n="backText">
                    Back to Customers
                </span>

            </a>


            <!-- SUCCESS -->

            @if (session('success'))

                <div class="alert-success">

                    {{ session('success') }}

                </div>

            @endif


            <!-- ERRORS -->

            @if ($errors->any())

                <div class="alert-error">

                    <strong data-i18n="errorTitle">
                        Please correct the following:
                    </strong>

                    <ul>

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <!-- CUSTOMER ID -->

            <div class="info-box">

                <div class="info-icon icon-3d icon-3d-blue">
                    <x-icon name="id-card" />
                </div>


                <div>

                    <div
                        class="info-title"
                        data-i18n="customerIdTitle"
                    >
                        Customer ID
                    </div>


                    <div
                        class="info-text"
                        data-i18n="customerIdText"
                    >
                        A unique customer ID will be generated
                        automatically when you save the customer.
                    </div>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="{{ route('customers.store') }}"
                class="customer-form"
            >

                @csrf


                <!-- NAME -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-orange">
                        <x-icon name="user" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="name"
                            data-i18n="nameLabel"
                        >
                            Name
                        </label>


                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="input"
                            placeholder="Enter customer name"
                            data-i18n-placeholder="namePlaceholder"
                            required
                        >

                    </div>

                </div>


                <!-- PHONE -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-blue">
                        <x-icon name="phone" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="phone"
                            data-i18n="phoneLabel"
                        >
                            Phone Number
                        </label>


                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            class="input"
                            placeholder="Enter phone number"
                            data-i18n-placeholder="phonePlaceholder"
                            required
                        >

                    </div>

                </div>


                <!-- EMAIL -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-purple">
                        <x-icon name="mail" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="email"
                            data-i18n="emailLabel"
                        >
                            Email
                        </label>


                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="input"
                            placeholder="Enter email address"
                            data-i18n-placeholder="emailPlaceholder"
                        >

                    </div>

                </div>


                <!-- ADDRESS -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-orange">
                        <x-icon name="map-pin" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="address"
                            data-i18n="addressLabel"
                        >
                            Address
                        </label>


                        <textarea
                            id="address"
                            name="address"
                            rows="3"
                            class="input"
                            placeholder="Enter customer address"
                            data-i18n-placeholder="addressPlaceholder"
                        >{{ old('address') }}</textarea>

                    </div>

                </div>


                <!-- REMARKS -->

                <div class="field">

                    <div class="field-icon icon-3d icon-3d-blue">
                        <x-icon name="id-card" />
                    </div>


                    <div class="field-content">

                        <label
                            class="field-label"
                            for="remarks"
                            data-i18n="remarksLabel"
                        >
                            Remarks / Identification
                        </label>


                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="3"
                            class="input"
                            placeholder="Optional notes or identification"
                            data-i18n-placeholder="remarksPlaceholder"
                        >{{ old('remarks') }}</textarea>

                    </div>

                </div>


                <!-- FORM FOOTER -->

                <div class="form-footer">


                    <!-- CANCEL -->

                    <a
                        href="{{ route('customers.index') }}"
                        class="cancel-button"
                    >

                        <x-icon name="x" />

                        <span data-i18n="cancelText">
                            Cancel
                        </span>

                    </a>


                    <!-- SAVE -->

                    <button
                        type="submit"
                        class="save-button"
                    >

                        <x-icon name="save" />

                        <span data-i18n="saveText">
                            Save Customer
                        </span>

                    </button>

                </div>

            </form>

        </div>

    </section>

</div>


<!-- =========================================================
     CUSTOMER SUCCESS POPUP
========================================================== -->

<div
    id="customerSuccessPopup"
    class="customer-success-overlay"
    aria-hidden="true"
>
    <div
        class="customer-success-popup"
        role="alert"
        aria-live="polite"
    >

        <button
            type="button"
            class="customer-success-close"
            onclick="closeCustomerSuccessPopup()"
            aria-label="Close"
        >
            <x-icon name="x" />
        </button>


        <div class="customer-success-icon icon-3d icon-3d-green">
            <x-icon name="check" />
        </div>


        <div class="customer-success-content">

            <h3 id="customerSuccessTitle" data-i18n="customerAddedTitle">
                Customer Added Successfully
            </h3>

            <p id="customerSuccessMessage" data-i18n="customerAddedMessage">
                The customer has been added successfully.
            </p>

        </div>


        <div class="customer-success-progress"></div>

    </div>
</div>

@endsection

@push('scripts')
    @vite('resources/js/create.js')

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                showCustomerSuccessPopup();
            });
        </script>
    @endif
@endpush