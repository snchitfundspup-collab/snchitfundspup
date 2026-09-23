@extends('layouts.app')

@section('title', 'Groups | SN Chit Funds')

@push('styles')
    @vite('resources/css/groups.css')
@endpush

@section('content')

<div class="groups-list-page">


    {{-- =====================================================
         PAGE HEADER
    ====================================================== --}}

    <div class="groups-list-header">

        <div>

            <div class="groups-title-row">

                <h1
                    class="groups-title"
                    data-i18n="menu_groups"
                >
                    Groups
                </h1>

                <span class="groups-count">
                    {{ $statusCounts->sum() }}
                </span>

            </div>

            <p
                class="groups-subtitle"
                data-i18n="groups_subtitle"
            >
                All chit groups, their status and schedule.
            </p>

        </div>


        <a
            href="{{ route('groups.create') }}"
            class="add-group-button"
        >
            <x-icon name="plus" />
            <span data-i18n="add_group">Add Group</span>
        </a>

    </div>


    {{-- =====================================================
         STATUS TABS
    ====================================================== --}}

    <nav
        class="status-tabs"
        aria-label="Filter groups by status"
    >

        @foreach ([
            '' => ['status_all', 'All', $statusCounts->sum()],
            'forming' => ['status_forming', 'Forming', $statusCounts->get('forming', 0)],
            'running' => ['status_running', 'Running', $statusCounts->get('running', 0)],
            'completed' => ['status_completed', 'Completed', $statusCounts->get('completed', 0)],
        ] as $tabStatus => [$tabKey, $tabLabel, $tabCount])

            <a
                href="{{ route('groups.index', array_filter(['status' => $tabStatus])) }}"
                @class(['status-tab', 'active' => $status === $tabStatus])
                @if ($status === $tabStatus) aria-current="page" @endif
            >
                <span data-i18n="{{ $tabKey }}">{{ $tabLabel }}</span>
                <span class="status-tab-count">{{ $tabCount }}</span>
            </a>

        @endforeach

    </nav>


    {{-- =====================================================
         GROUP CARDS
    ====================================================== --}}

    @if ($groups->isEmpty())

        <div class="groups-empty glass">

            <span class="groups-empty-icon icon-3d icon-3d-purple">
                <x-icon name="layers" />
            </span>

            <strong data-i18n="groups_empty_title">
                No groups yet
            </strong>

            <p data-i18n="groups_empty_text">
                Create your first chit group to start adding members.
            </p>

            <a
                href="{{ route('groups.create') }}"
                class="add-group-button"
            >
                <x-icon name="plus" />
                <span data-i18n="add_group">Add Group</span>
            </a>

        </div>

    @else

        <div class="group-cards">

            @foreach ($groups as $group)

                <a
                    href="{{ route('groups.show', $group) }}"
                    class="group-card glass"
                >

                    <div class="group-card-top">

                        <span @class([
                            'group-card-icon',
                            'icon-3d',
                            'icon-3d-orange' => $group->type === 'draw',
                            'icon-3d-purple' => $group->type === 'auction',
                        ])>
                            <x-icon :name="$group->type === 'auction' ? 'rupee' : 'trophy'" />
                        </span>

                        <div class="group-card-heading">

                            <strong>{{ $group->name }}</strong>

                            <span
                                class="group-type-label"
                                data-i18n="type_{{ $group->type }}"
                            >
                                {{ ucfirst($group->type) }}
                            </span>

                        </div>

                        <x-group-status :status="$group->status" />

                    </div>


                    <div class="group-card-amount">
                        <x-rupees :amount="$group->amount" />
                    </div>


                    <dl class="group-card-facts">

                        <div>
                            <dt data-i18n="monthly_installment">Monthly Installment</dt>
                            <dd><x-rupees :amount="$group->monthlyInstallment()" /></dd>
                        </div>

                        <div>
                            <dt data-i18n="group_months">Months</dt>
                            <dd>{{ $group->months }}</dd>
                        </div>

                        <div>
                            <dt data-i18n="group_members">Members</dt>
                            <dd>{{ $group->members_count }} / {{ $group->member_count }}</dd>
                        </div>

                        <div>
                            <dt data-i18n="start_date">Start Date</dt>
                            <dd>{{ $group->start_date->format('d M Y') }}</dd>
                        </div>

                    </dl>

                </a>

            @endforeach

        </div>


        <x-pagination :paginator="$groups" label="Group pages" />

    @endif

</div>

@endsection
