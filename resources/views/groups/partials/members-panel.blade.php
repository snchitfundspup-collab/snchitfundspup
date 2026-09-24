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

                            @php
                                $pendingAmount = $member->collectionStatus()['pending'];
                                $nextUnpaid = $member->nextUnpaidMonth();
                                $nextPaid = $nextUnpaid ? ($member->paidByMonth()[$nextUnpaid] ?? 0) : 0;
                            @endphp

                            {{-- how far the member has paid --}}
                            <span class="member-progress">
                                @if ($nextUnpaid === null)
                                    <span data-i18n="all_months_paid_short">All months paid</span>
                                @elseif ($nextUnpaid === 1 && $nextPaid === 0)
                                    <span data-i18n="nothing_paid_yet">Nothing paid yet</span>
                                @else
                                    @if ($nextUnpaid > 1)
                                        <span data-i18n="paid_up_to">Paid up to</span> <span data-i18n="month_number">Month</span> {{ $nextUnpaid - 1 }}
                                    @endif
                                    @if ($nextPaid > 0)
                                        @if ($nextUnpaid > 1) · @endif
                                        <span data-i18n="month_number">Month</span> {{ $nextUnpaid }} <span data-i18n="part_paid_lower">part paid</span>
                                        (<x-rupees :amount="$nextPaid" />)
                                    @endif
                                @endif
                            </span>

                            <a
                                href="{{ route('payments.create', ['customer' => $member->customer_id, 'member' => $member->id]) }}#collect"
                                class="member-due-link"
                                title="Collect payment"
                            >
                                @if ($pendingAmount > 0)
                                    <span class="due-badge due-badge-due">
                                        <x-rupees :amount="$pendingAmount" />
                                        <span data-i18n="pending_word">pending</span>
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
