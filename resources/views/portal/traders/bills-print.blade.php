{{-- A customer's SN Traders statement as a printable page (same body as
     the PDF). --}}
@extends('layouts.print', ['companyName' => 'Traders'])

@section('title', 'Customer Account')

@section('doc_title', 'Customer Account')

@section('styles')
    @include('payments.partials.list-styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-account')

@endsection
