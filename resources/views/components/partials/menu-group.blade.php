{{-- A side-menu group with a submenu (same markup as the Chit Funds groups).
     $group: id, icon, colour, i18n, label, items [[route, icon, i18n, label, params?]], active (route pattern[s]). --}}

@php
    $isOpen = request()->routeIs(...(array) $group['active']);
@endphp

<div @class(['menu-group', 'open' => $isOpen])>

    <button
        type="button"
        @class(['menu-item', 'menu-group-toggle', 'active' => $isOpen])
        onclick="toggleMenuGroup(this)"
        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
        aria-controls="{{ $group['id'] }}"
    >
        <span class="menu-item-icon icon-3d icon-3d-{{ $group['colour'] }}">
            <x-icon :name="$group['icon']" />
        </span>

        <span class="menu-item-text" data-i18n="{{ $group['i18n'] }}">
            {{ $group['label'] }}
        </span>

        <x-icon name="chevron-down" class="menu-group-caret" />
    </button>

    <div
        class="menu-submenu"
        id="{{ $group['id'] }}"
    >
        @foreach ($group['items'] as $item)
            @php
                [$itemRoute, $itemIcon, $itemI18n, $itemLabel] = $item;
                $itemParams = $item[4] ?? [];
                $itemActive = request()->routeIs($itemRoute)
                    && collect($itemParams)->every(fn ($value, $key) => (string) request()->route($key) === (string) $value);
            @endphp
            <a
                href="{{ route($itemRoute, $itemParams) }}"
                @class(['menu-subitem', 'active' => $itemActive])
            >
                <x-icon :name="$itemIcon" />
                <span data-i18n="{{ $itemI18n }}">{{ $itemLabel }}</span>
            </a>
        @endforeach
    </div>

</div>
