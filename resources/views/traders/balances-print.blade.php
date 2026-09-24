{{-- Printable customer balances list (SN Traders). --}}
@extends('layouts.print')

@section('title', 'Customer Balances')

@section('doc_title', 'Customer Balances')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-balances')

@endsection
