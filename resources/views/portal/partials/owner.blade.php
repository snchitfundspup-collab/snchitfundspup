{{-- Whose seat / order / bill it is, when family members share a phone:
     name (identification) · customer ID. $owner --}}

<span class="portal-owner">
    <x-icon name="user" />
    <x-customer-name :customer="$owner" />
    <small>{{ $owner->customer_code }}</small>
</span>
