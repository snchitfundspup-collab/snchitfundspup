@extends('layouts.app')

@section('title', 'Customers')

@push('styles')
    @vite('resources/css/customers.css')
@endpush

@section('content')

<div class="customers-page">

    <div class="customers-container">

        {{-- =====================================================
             PAGE HEADER
        ====================================================== --}}

        <div class="customers-header">

            <div class="customers-header-content">

                <div class="customers-title-row">

                    <h1
                        class="customers-title"
                        data-i18n="customers_title"
                    >
                        Customers
                    </h1>

                    <span class="customers-count">
                        {{ $customers->total() }}
                    </span>

                </div>

                <p
                    class="customers-subtitle"
                    data-i18n="customers_subtitle"
                >
                    Manage your customers
                </p>

            </div>


            <a
                href="{{ route('customers.create') }}"
                class="add-customer-btn"
            >
                <span class="add-icon">+</span>

                <span data-i18n="add_customer">
                    Add Customer
                </span>
            </a>

        </div>


        {{-- =====================================================
             SEARCH
        ====================================================== --}}

        <div class="customers-controls">

            <div class="customer-search-wrapper">

                <span class="customer-search-icon">
                    ⌕
                </span>

                <input
                    type="text"
                    id="customerSearch"
                    class="customer-search"
                    placeholder="Search customers..."
                    value="{{ $search ?? '' }}"
                    autocomplete="off"
                    data-i18n-placeholder="search_customers"
                >

                <button
                    type="button"
                    id="clearCustomerSearch"
                    class="clear-search"
                    aria-label="Clear search"
                    style="{{ !empty($search) ? '' : 'display:none;' }}"
                >
                    ×
                </button>

            </div>


            <div
                class="customer-results-info"
                id="customerResultsInfo"
            >
                @if ($customers->total() > 0)
                    {{ $customers->firstItem() }}
                    -
                    {{ $customers->lastItem() }}
                    /
                    {{ $customers->total() }}
                @else
                    0 / 0
                @endif
            </div>

        </div>


        {{-- =====================================================
             CUSTOMER CARD
        ====================================================== --}}

        <div class="customers-card">


            {{-- =================================================
                 DESKTOP TABLE
            ================================================== --}}

            <div class="customers-table-wrapper">

                <table class="customers-table">

                    <thead>

                        <tr>

                            <th data-i18n="customer_id">
                                Customer ID
                            </th>

                            <th data-i18n="name">
                                Name
                            </th>

                            <th data-i18n="identification">
                                Identification
                            </th>

                            <th data-i18n="phone">
                                Phone
                            </th>

                            <th data-i18n="groups">
                                Groups
                            </th>

                            <th data-i18n="status">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody id="customersTableBody">

                        @forelse ($customers as $customer)

                            <tr
                                class="customer-row"
                                data-customer-id="{{ $customer->id }}"

                                data-customer-code="{{ $customer->customer_code }}"
                                data-customer-name="{{ $customer->name }}"
                                data-customer-phone="{{ $customer->phone }}"
                                data-customer-email="{{ $customer->email }}"
                                data-customer-remarks="{{ $customer->remarks }}"
                                data-customer-address="{{ $customer->address }}"
                                data-customer-active="{{ $customer->is_active ? '1' : '0' }}"
                            >

                                {{-- CUSTOMER ID --}}

                                <td>

                                    <span class="customer-code">
                                        {{ $customer->customer_code }}
                                    </span>

                                </td>


                                {{-- NAME --}}

                                <td>

                                    <button
                                        type="button"
                                        class="customer-name-button"
                                        data-customer-id="{{ $customer->id }}"
                                    >
                                        {{ $customer->name }}
                                    </button>

                                </td>


                                {{-- IDENTIFICATION --}}

                                <td>

                                    <span class="customer-identification">
                                        {{ $customer->remarks ?: '-' }}
                                    </span>

                                </td>


                                {{-- PHONE --}}

                                <td>

                                    <a
                                        href="tel:{{ $customer->phone }}"
                                        class="customer-phone"
                                    >
                                        {{ $customer->phone }}
                                    </a>

                                </td>


                                {{-- GROUPS --}}

                                <td>

                                    <button
                                        type="button"
                                        class="view-groups-button"
                                        data-customer-id="{{ $customer->id }}"
                                    >
                                        View Groups
                                    </button>

                                </td>


                                {{-- STATUS --}}

                                <td>

                                    @if ($customer->is_active)

                                        <span class="customer-status active">
                                            <span></span>
                                            Active
                                        </span>

                                    @else

                                        <span class="customer-status inactive">
                                            <span></span>
                                            Inactive
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="customers-empty"
                                >

                                    <div class="empty-icon">
                                        ♙
                                    </div>

                                    <strong>
                                        No customers found
                                    </strong>

                                    <p>
                                        Add your first customer to get started.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =================================================
                 MOBILE CARDS
            ================================================== --}}

            <div
                class="customers-mobile-list"
                id="customersMobileList"
            >

                @forelse ($customers as $customer)

                    <div
                        class="customer-mobile-card"
                        data-customer-id="{{ $customer->id }}"

                        data-customer-code="{{ $customer->customer_code }}"
                        data-customer-name="{{ $customer->name }}"
                        data-customer-phone="{{ $customer->phone }}"
                        data-customer-email="{{ $customer->email }}"
                        data-customer-remarks="{{ $customer->remarks }}"
                        data-customer-address="{{ $customer->address }}"
                        data-customer-active="{{ $customer->is_active ? '1' : '0' }}"
                    >

                        <div class="mobile-card-top">

                            <div>

                                <span class="mobile-customer-code">
                                    {{ $customer->customer_code }}
                                </span>

                                <button
                                    type="button"
                                    class="customer-name-button mobile-customer-name"
                                    data-customer-id="{{ $customer->id }}"
                                >
                                    {{ $customer->name }}
                                </button>

                            </div>


                            @if ($customer->is_active)

                                <span class="customer-status active">
                                    <span></span>
                                    Active
                                </span>

                            @else

                                <span class="customer-status inactive">
                                    <span></span>
                                    Inactive
                                </span>

                            @endif

                        </div>


                        <div class="mobile-card-details">

                            <div class="mobile-detail">

                                <span class="mobile-detail-label">
                                    Identification
                                </span>

                                <span class="mobile-detail-value">
                                    {{ $customer->remarks ?: '-' }}
                                </span>

                            </div>


                            <div class="mobile-detail">

                                <span class="mobile-detail-label">
                                    Phone
                                </span>

                                <a
                                    href="tel:{{ $customer->phone }}"
                                    class="mobile-detail-value mobile-phone"
                                >
                                    {{ $customer->phone }}
                                </a>

                            </div>


                            <div class="mobile-detail">

                                <span class="mobile-detail-label">
                                    Email
                                </span>

                                <span class="mobile-detail-value">
                                    {{ $customer->email ?: '-' }}
                                </span>

                            </div>


                            <div class="mobile-detail">

                                <span class="mobile-detail-label">
                                    Address
                                </span>

                                <span class="mobile-detail-value">
                                    {{ $customer->address ?: '-' }}
                                </span>

                            </div>

                        </div>


                        <div class="mobile-card-actions">

                            <button
                                type="button"
                                class="mobile-view-button customer-name-button"
                                data-customer-id="{{ $customer->id }}"
                            >
                                View / Edit
                            </button>

                            <button
                                type="button"
                                class="mobile-groups-button view-groups-button"
                                data-customer-id="{{ $customer->id }}"
                            >
                                View Groups
                            </button>

                        </div>

                    </div>

                @empty

                    <div class="mobile-empty-message">
                        No customers found.
                    </div>

                @endforelse

            </div>


            {{-- =================================================
                 PAGINATION
            ================================================== --}}

            @if ($customers->hasPages())

                <div class="customers-pagination">

                    <div class="pagination-info">

                        Showing
                        <strong>
                            {{ $customers->firstItem() }}
                        </strong>

                        -
                        <strong>
                            {{ $customers->lastItem() }}
                        </strong>

                        of
                        <strong>
                            {{ $customers->total() }}
                        </strong>

                    </div>


                    <div class="pagination-links">

                        {{-- PREVIOUS --}}

                        @if ($customers->onFirstPage())

                            <span class="pagination-button disabled">
                                ‹
                            </span>

                        @else

                            <a
                                href="{{ $customers->previousPageUrl() }}"
                                class="pagination-button"
                            >
                                ‹
                            </a>

                        @endif


                        {{-- PAGE NUMBERS --}}

                        @foreach ($customers->getUrlRange(
                            max(1, $customers->currentPage() - 2),
                            min($customers->lastPage(), $customers->currentPage() + 2)
                        ) as $page => $url)

                            @if ($page == $customers->currentPage())

                                <span class="pagination-button active">
                                    {{ $page }}
                                </span>

                            @else

                                <a
                                    href="{{ $url }}"
                                    class="pagination-button"
                                >
                                    {{ $page }}
                                </a>

                            @endif

                        @endforeach


                        {{-- NEXT --}}

                        @if ($customers->hasMorePages())

                            <a
                                href="{{ $customers->nextPageUrl() }}"
                                class="pagination-button"
                            >
                                ›
                            </a>

                        @else

                            <span class="pagination-button disabled">
                                ›
                            </span>

                        @endif

                    </div>

                </div>

            @endif

        </div>

    </div>

</div>


{{-- =========================================================
     CUSTOMER EDIT MODAL
========================================================= --}}

<div
    id="customerModal"
    class="customer-modal"
    aria-hidden="true"
>

    <div
        class="customer-modal-overlay"
        id="customerModalOverlay"
    ></div>


    <div class="customer-modal-dialog">


        {{-- MODAL HEADER --}}

        <div class="customer-modal-header">

            <div>

                <div
                    class="modal-customer-code"
                    id="editCustomerCodeDisplay"
                >
                    CUSTOMER
                </div>

                <h2>
                    Customer Details
                </h2>

            </div>


            <button
                type="button"
                id="customerModalClose"
                class="modal-close"
            >
                ×
            </button>

        </div>


        {{-- FORM --}}

        <form id="customerEditForm">

            @csrf

            <div class="customer-modal-body">

                <input
                    type="hidden"
                    id="editCustomerId"
                >


                <div class="modal-form-grid">


                    {{-- NAME --}}

                    <div class="modal-field">

                        <label for="editCustomerName">
                            Name
                        </label>

                        <input
                            type="text"
                            id="editCustomerName"
                            required
                        >

                    </div>


                    {{-- PHONE --}}

                    <div class="modal-field">

                        <label for="editCustomerPhone">
                            Phone
                        </label>

                        <input
                            type="text"
                            id="editCustomerPhone"
                            required
                        >

                    </div>


                    {{-- EMAIL --}}

                    <div class="modal-field">

                        <label for="editCustomerEmail">
                            Email
                        </label>

                        <input
                            type="email"
                            id="editCustomerEmail"
                        >

                    </div>


                    {{-- IDENTIFICATION --}}

                    <div class="modal-field">

                        <label for="editCustomerRemarks">
                            Identification
                        </label>

                        <input
                            type="text"
                            id="editCustomerRemarks"
                        >

                    </div>


                    {{-- ADDRESS --}}

                    <div class="modal-field modal-field-full">

                        <label for="editCustomerAddress">
                            Address
                        </label>

                        <textarea
                            id="editCustomerAddress"
                            rows="3"
                        ></textarea>

                    </div>


                    {{-- ACTIVE --}}

                    <label class="modal-field modal-checkbox">

                        <input
                            type="checkbox"
                            id="editCustomerActive"
                        >

                        <span>
                            Active Customer
                        </span>

                    </label>


                    {{-- ERROR --}}

                    <div
                        id="customerFormError"
                        class="customer-form-error modal-field-full"
                        style="display:none;"
                    ></div>

                </div>

            </div>


            {{-- MODAL FOOTER --}}

            <div class="customer-modal-footer">

                <button
                    type="button"
                    id="customerModalCancel"
                    class="modal-cancel-button"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    id="customerSaveButton"
                    class="modal-save-button"
                >

                    <span id="customerSaveText">
                        Save Changes
                    </span>

                    <span
                        id="customerSaveLoading"
                        style="display:none;"
                    >
                        Saving...
                    </span>

                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     GROUPS MODAL
========================================================= --}}

<div
    id="groupsModal"
    class="customer-modal"
    aria-hidden="true"
>

    <div
        class="customer-modal-overlay"
        id="groupsModalOverlay"
    ></div>


    <div class="customer-modal-dialog">

        <div class="customer-modal-header">

            <div>

                <div class="modal-customer-code">
                    GROUPS
                </div>

                <h2>
                    Customer Groups
                </h2>

            </div>


            <button
                type="button"
                id="groupsModalClose"
                class="modal-close"
            >
                ×
            </button>

        </div>


        <div class="customer-modal-body">

            <div class="groups-placeholder">

                <div class="groups-placeholder-icon">
                    ◈
                </div>

                <strong>
                    Groups
                </strong>

                <p>
                    Group membership details will be shown here.
                </p>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SUCCESS POPUP
========================================================= --}}

<div
    id="customerSuccessPopup"
    class="customer-success-popup"
    aria-hidden="true"
>

    <div class="success-popup-icon">
        ✓
    </div>


    <div class="success-popup-content">

        <strong>
            Customer Updated
        </strong>

        <span>
            Customer details updated successfully.
        </span>

    </div>

</div>


@endsection


@push('scripts')

    @vite('resources/js/customers.js')

@endpush