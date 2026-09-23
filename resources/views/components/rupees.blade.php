@props(['amount'])

@php
    /**
     * Indian-style rupee amount: 200000 → ₹2,00,000, 10000.5 → ₹10,000.50.
     */
    $value = (float) $amount;
    $isNegative = $value < 0;
    [$whole, $fraction] = array_pad(explode('.', number_format(abs($value), 2, '.', '')), 2, '00');

    $lastThree = substr($whole, -3);
    $rest = substr($whole, 0, -3);
    $grouped = $rest !== ''
        ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest).','.$lastThree
        : $lastThree;

    $formatted = ($isNegative ? '-' : '').'₹'.$grouped.($fraction !== '00' ? '.'.$fraction : '');
@endphp

<span {{ $attributes->merge(['class' => 'rupees']) }}>{{ $formatted }}</span>
