@extends('layouts.app')

@section('title', 'Edit Members – '.$group->name.' | SN Chit Funds')

@push('styles')
    @vite('resources/css/groups.css')
@endpush

@section('content')

<div class="group-show-page">


    {{-- =====================================================
         FLASH MESSAGES
    ====================================================== --}}

    @if (session('success'))
        <div class="group-flash group-flash-success" role="status">
            <x-icon name="check" />
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="group-flash group-flash-error" role="alert">
            <x-icon name="info" />
            {{ $errors->first() }}
        </div>
    @endif


    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <section class="group-hero glass">

        <a
            href="{{ route('groups.show', $group) }}"
            class="group-back-link"
        >
            <x-icon name="arrow-left" />
            <span data-i18n="back_to_group">Back to Group</span>
        </a>


        <div class="group-hero-main">

            <span class="group-hero-icon icon-3d icon-3d-blue">
                <x-icon name="users" />
            </span>

            <div class="group-hero-text">

                <h1 class="group-hero-title">
                    <span data-i18n="edit_members">Edit Members</span>
                </h1>

                <p class="group-hero-meta">
                    {{ $group->name }}
                </p>

            </div>

            <x-member-count
                :total="$members->count()"
                :planned="$group->member_count"
                :show-start-hint="$group->isForming()"
                class="member-count-large"
            />

        </div>

    </section>


    <div class="members-edit-grid">


        {{-- =================================================
             ADD MEMBERS (customer search)
        ================================================== --}}

        <section class="group-panel glass">

            <div class="group-panel-header">
                <h2 data-i18n="add_members">Add members</h2>
            </div>


            <form
                method="GET"
                action="{{ route('groups.members.edit', $group) }}"
                class="member-search"
                id="memberSearchForm"
                role="search"
            >

                <span class="member-search-icon">
                    <x-icon name="search" />
                </span>

                <input
                    type="search"
                    name="q"
                    id="memberSearchInput"
                    class="member-search-input"
                    value="{{ $search }}"
                    placeholder="Search by name, ID, phone or identification"
                    data-i18n-placeholder="member_search_placeholder"
                    autocomplete="off"
                    autofocus
                >

            </form>


            <div
                class="member-search-results"
                id="memberSearchResults"
            >

                @if ($search === '')

                    <p
                        class="members-empty"
                        data-i18n="member_search_hint"
                    >
                        Start typing to find customers. Customers already in this group are not shown.
                    </p>

                @elseif ($results->isEmpty())

                    <p
                        class="members-empty"
                        data-i18n="member_search_none"
                    >
                        No active customers match your search (members already in this group are hidden).
                    </p>

                @else

                    @foreach ($results as $customer)

                        <div class="member-result">

                            <x-customer-avatar :customer="$customer" />

                            <div class="member-card-info">

                                <span class="member-card-code">
                                    {{ $customer->customer_code }}
                                </span>

                                <strong class="member-card-name">
                                    {{ $customer->name }}
                                </strong>

                                <span class="member-card-identification">
                                    {{ $customer->remarks ?: '—' }}
                                    · {{ $customer->phone }}
                                </span>

                            </div>

                            <form
                                method="POST"
                                action="{{ route('groups.members.store', $group) }}"
                            >
                                @csrf

                                <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                                <input type="hidden" name="q" value="{{ $search }}">

                                <button
                                    type="submit"
                                    class="member-add-button"
                                >
                                    <x-icon name="plus" />
                                    <span data-i18n="add">Add</span>
                                </button>
                            </form>

                        </div>

                    @endforeach

                @endif

            </div>

        </section>


        {{-- =================================================
             CURRENT MEMBERS
        ================================================== --}}

        <section class="group-panel glass">

            <div class="group-panel-header">

                <h2 data-i18n="current_members">Current members</h2>

                <span
                    class="reorder-status"
                    id="reorderStatus"
                    role="status"
                    aria-live="polite"
                ></span>

            </div>


            @if ($members->count() > 1)
                <p
                    class="reorder-hint"
                    data-i18n="reorder_hint"
                >
                    Drag a card by its handle to change the order. It saves automatically.
                </p>
            @endif


            @if ($members->isEmpty())

                <p
                    class="members-empty"
                    data-i18n="members_none_yet"
                >
                    No members yet. Search on the left and click Add.
                </p>

            @else

                <ol
                    class="member-list"
                    id="memberList"
                    data-reorder-url="{{ route('groups.members.reorder', $group) }}"
                >

                    @foreach ($members as $member)

                        <li
                            class="member-result member-sortable"
                            data-member-id="{{ $member->id }}"
                        >

                            <button
                                type="button"
                                class="drag-handle"
                                aria-label="Move {{ $member->customer->name }} ({{ $member->member_code }}). Drag, or use the up and down arrow keys."
                            >
                                <x-icon name="grip" />
                            </button>

                            <span class="member-list-number">{{ $loop->iteration }}</span>

                            <div class="member-card-info">

                                <span class="member-card-code">
                                    {{ $member->member_code }}
                                </span>

                                <strong class="member-card-name">
                                    {{ $member->customer->name }}
                                </strong>

                                <span class="member-card-identification">
                                    {{ $member->customer->remarks ?: '—' }}
                                </span>

                            </div>

                            <form
                                method="POST"
                                action="{{ route('groups.members.destroy', [$group, $member]) }}"
                                onsubmit="return confirm('Remove {{ addslashes($member->customer->name) }} ({{ $member->member_code }}) from this group?')"
                            >
                                @csrf
                                @method('DELETE')

                                <input type="hidden" name="q" value="{{ $search }}">

                                <button
                                    type="submit"
                                    class="member-remove-button"
                                    aria-label="Remove {{ $member->customer->name }} ({{ $member->member_code }})"
                                >
                                    <x-icon name="x" />
                                    <span data-i18n="remove">Remove</span>
                                </button>
                            </form>

                        </li>

                    @endforeach

                </ol>

            @endif

        </section>

    </div>

</div>

@endsection


@push('scripts')
    @vite('resources/js/group-members.js')
@endpush
