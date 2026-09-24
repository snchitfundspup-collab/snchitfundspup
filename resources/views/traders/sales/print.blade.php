{{-- Printable sales list (SN Traders). --}}
@extends('layouts.print')

@section('title', 'Sales')

@section('doc_title', 'Sales')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-bills', ['kind' => 'sale', 'bills' => $sales])

@endsection
