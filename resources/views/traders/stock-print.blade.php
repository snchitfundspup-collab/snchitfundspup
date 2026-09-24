{{-- Printable stock list (SN Traders). --}}
@extends('layouts.print')

@section('title', 'Stock')

@section('doc_title', 'Stock')

@section('styles')
    @include('reports.partials.dues-print-styles')
@endsection

@section('content')

    @include('traders.partials.print-stock')

@endsection
