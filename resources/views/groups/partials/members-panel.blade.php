{{-- Members panel of View Group: serial number, ID, name, identification
     and a Details button per member (popup filled by group-show.js). --}}

<section class="group-panel glass">

    <div class="group-panel-header">

        <h2 data-i18n="group_members">
            Members
        </h2>

        <a
            href="{{ route('groups.members.edit', $group) }}"
            class="group-panel-link"
        >
            <x-icon name="edit" />
            <span data-i18n="edit_members">Edit Members</span>
        </a>

    </div>


    <x-member-count
        :total="$memberTotal"
        :planned="$group->member_count"
        :show-start-hint="$group->isForming()"
    />


    @if ($group->members->isEmpty())

        <p
            class="members-empty"
            data-i18n="members_empty"
        >
            No members yet. Use Edit Members to add customers to this group.
        </p>

    @else

        <div class="member-cards">

            @foreach ($group->members as $member)

                <div class="member-card">

                    <span
                        class="member-list-number"
                        aria-label="Serial number {{ $loop->iteration }}"
                    >{{ $loop->iteration }}</span>

                    <x-customer-avatar :customer="$member->customer" />

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

                        @if ($group->isRunning())

                            @php $balanceDue = $member->balanceDue(); @endphp

                            <a
                                href="{{ route('payments.create', ['customer' => $member->customer_id, 'member' => $member->id]) }}#collect"
                                class="member-due-link"
                                title="Collect payment"
                            >
                                @if ($balanceDue > 0)
                                    <span class="due-badge due-badge-due">
                                        <x-rupees :amount="$balanceDue" />
                                        <span data-i18n="due">due</span>
                                    </span>
                                @else
                                    <span
                                        class="due-badge due-badge-ok"
                                        data-i18n="paid_up"
                                    >Paid up</span>
                                @endif
                            </a>

                        @endif

                        @if ($member->wonDraw)
                            <a
                                href="{{ route('draws.show', $member->wonDraw) }}"
                                class="won-badge"
                                title="Won the draw in month {{ $member->wonDraw->month_number }}"
                            >
                                <span data-i18n="won_word">Won</span>
                                <x-rupees :amount="$member->wonDraw->prizeAmount()" />
                                · M{{ $member->wonDraw->month_number }}
                            </a>
                        @endif

                    </div>

                    <div class="member-card-actions">

                        @if ($member->customer->phone)
                            <a
                                href="tel:{{ preg_replace('/[^\d+]/', '', $member->customer->phone) }}"
                                class="member-call-button"
                                aria-label="Call {{ $member->customer->name }} on {{ $member->customer->phone }}"
                                title="Call {{ $member->customer->phone }}"
                            >
                                <x-icon name="phone" />
                                <span data-i18n="call">Call</span>
                            </a>
                        @endif

                        <button
                            type="button"
                            class="member-details-button"
                            data-member-id="{{ $member->id }}"
                            aria-label="Show details for {{ $member->customer->name }}"
                            title="Details"
                        >
                            <x-icon name="info" />
                            <span data-i18n="details">Details</span>
                        </button>

                    </div>

                </div>

            @endforeach

        </div>

    @endif


    @if ($group->remarks)

        <div class="group-remarks">
            <strong data-i18n="remarksLabel">Remarks</strong>
            <p>{{ $group->remarks }}</p>
        </div>

    @endif

</section>
