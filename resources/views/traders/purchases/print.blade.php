{{-- Printable purchases list (SN Traders). --}}
@extends('layouts.print')

@section('title', 'Purchases')

@section('doc_title', 'Purchases')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-bills', ['kind' => 'purchase', 'bills' => $purchases])

@endsection
