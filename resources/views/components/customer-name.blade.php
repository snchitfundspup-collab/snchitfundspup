@props(['customer'])

{{-- A customer's name, always followed by their identification (remarks)
     when they have one: "Kumaran Shop (Textile shop)". --}}

<span {{ $attributes->merge(['class' => 'customer-name']) }}>{{ $customer->name }}@if (filled($customer->remarks))<span class="customer-ident"> ({{ $customer->remarks }})</span>@endif</span>
